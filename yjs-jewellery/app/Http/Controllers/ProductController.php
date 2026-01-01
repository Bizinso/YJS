<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ProductType;
use App\Models\Tag;
use App\Models\MetalType;
use App\Models\MetalName;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\Tax;
use App\Models\ProductVariant;
use App\Models\ProductCharges;
use App\Models\ProductTax;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Increase GROUP_CONCAT length to avoid truncated JSON
            DB::statement("SET SESSION group_concat_max_len = 1000000");

            $sortBy = $request->get('sortBy');
            $direction = $request->get('sortDesc') === 'true' ? 'DESC' : 'ASC';
            $userId = auth('customer')->id() ?? 0;

            $product = Product::query()
                ->leftJoin('product_types', 'product_types.id', '=', 'products.product_type_id')
                ->leftJoin('categories as cat', 'products.category_id', '=', 'cat.id')
                ->leftJoin('categories as sub_cat', 'products.sub_category_id', '=', 'sub_cat.id')
                ->leftJoin('order_products', 'products.id', '=', 'order_products.product_id')
                ->leftJoin('products as children', 'children.parent_id', '=', 'products.id')
                ->leftJoin('orders', function ($join) {
                    $join->on('orders.id', '=', 'order_products.order_id')
                        ->whereNotIn('orders.order_status', ['cancelled', 'returned']);
                })
                ->select(
                    'products.*',
                    'product_types.name as product_type_name',
                    'cat.name as category_name',
                    'sub_cat.name as sub_category_name',
                    'products.available_stock as available_stock_data',
                    DB::raw('COUNT(children.id) as variant_count'),
                    DB::raw("
                        COALESCE(
                            CONCAT(
                                '[', 
                                GROUP_CONCAT(
                                    DISTINCT JSON_OBJECT(
                                        'id', children.id,
                                        'variant_name', JSON_QUOTE(children.name),
                                        'sku', JSON_QUOTE(children.sku),
                                        'base_price', children.base_price,
                                        'product_price', children.final_price,
                                        'status', JSON_QUOTE(children.status),
                                        'metal_weight', children.metal_weight,
                                        'additional_charges', children.additional_charges,
                                        'taxes', children.taxes,
                                        'main_image', JSON_QUOTE(children.main_image)
                                    )
                                    SEPARATOR ','
                                ), 
                                ']'
                            ), '[]'
                        ) as variants
                    "),
                    DB::raw("CASE WHEN EXISTS (
                    SELECT 1 
                    FROM wishlists 
                    WHERE wishlists.product_id = products.id 
                    AND wishlists.user_id = {$userId}
                    AND wishlists.deleted_at IS NULL
                ) THEN TRUE ELSE FALSE END as wishlisted"),
                )
                ->where(function ($q) {
                    $q->whereNull('products.parent_id')
                    ->orWhere('products.parent_id', 0);
                })
                ->groupBy('products.id', 'product_types.name', 'cat.name', 'sub_cat.name');

            // Filters
            $product = $product
                ->when($request->has('name'), fn ($q) => $q->where('products.name', 'LIKE', $request->name . '%'))
                ->when($request->has('product_type_id'), fn ($q) => $q->where('products.product_type_id', $request->product_type_id))
                ->when($request->has('category_id'), fn ($q) => $q->where('products.category_id', $request->category_id))
                ->when($request->has('sub_category_id'), fn ($q) => $q->where('products.sub_category_id', $request->sub_category_id))
                ->when($request->filled('product_name_filter'), fn ($q) =>
                    $q->where('products.name', 'LIKE', "%{$request->product_name_filter}%")
                )
                ->when($request->has('status_filter'), fn ($q) => $q->where('products.status', $request->status_filter))
                ->when($request->has('created_date'), function ($q) use ($request) {
                    $date = substr($request->created_date, 0, 10);
                    $q->whereDate('products.created_at', $date);
                });

            // Sorting
            if ($sortBy) {
                if ($sortBy === 'name') {
                    $product->orderBy('products.name', $direction);
                } elseif ($sortBy === 'category_name') {
                    $product->orderBy('cat.name', $direction);
                } elseif ($sortBy === 'sub_category_name') {
                    $product->orderBy('sub_cat.name', $direction);
                } else {
                    $product->orderBy('products.' . $sortBy, $direction);
                }
            } else {
                $product->orderBy('products.id', 'DESC');
            }

            // Global search



            if ($request->filled('globalSearch')) {

                $term = trim($request->globalSearch);


                if (preg_match('/\d{2}\/\d{2}\/\d{4}/', $term)) {

                    $globalDate = Carbon::createFromFormat('d/m/Y', $term)->format('Y-m-d');
                    $product->whereDate('products.created_at', $globalDate);

                } else {


                    $statusMap = [
                        'active'   => 'A',
                        'inactive' => 'I',
                        'draft'    => 'D',
                    ];

                    $lowerTerm = strtolower($term);

                    if (array_key_exists($lowerTerm, $statusMap)) {

                        $product->where('products.status', $statusMap[$lowerTerm]);

                    } else {

                        $search = '%' . $term . '%';

                        $product->where(function ($q) use ($search) {
                            $q->orWhere('product_types.name', 'like', $search)
                                ->orWhere('cat.name', 'like', $search)
                                ->orWhere('sub_cat.name', 'like', $search)
                                ->orWhere('products.name', 'like', $search)
                                ->orWhere('products.sku', 'like', $search);
                        });
                    }
                }
            }

            $allproduct = $product->get();
            $total = $product->count();
            $products = $allproduct->forPage($request->page, $request->perPage);

            return response()->json([
                'data' => $products,
                'total' => $total,
                'status' => 200,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to fetch Data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getCategoryOptions()
    {
        $categories = Category::where('status', 'A')->select('id as value', 'name as label')->whereNull('parent_id')->get();
        return response()->json($categories);
    }

    public function getProductTypeOptions()
    {
        $productType = ProductType::where('status', 'A')->select('id as value', 'name as label')->get();
        return response()->json($productType);

    }

    public function getTagOptions()
    {
        $tags = Tag::where('status', 'A')
            ->where('name', '!=', '')
            ->whereNull('deleted_at')
            ->select('id as value', 'name as label')
            ->get();

        $metalTypes = DB::table('metal_names')->where('status', 'active')
            ->where('name', '!=', '')
            ->whereNull('deleted_at')
            ->whereNot('name', 'NA')
            ->select('id as value', 'name as label')
            ->get();

        $productTypes = ProductType::where('status', 'A')
            ->where('name', '!=', '')
            ->whereNull('deleted_at')
            ->select('id as value', 'name as label')
            ->get();

        $purity = DB::table('purities')
            ->where('name', '!=', '')
            ->whereNull('deleted_at')
            ->select('id as value', 'name as label')
            ->get();

        $occasion = DB::table('occasions')
            ->where('name', '!=', '')
            ->whereNull('deleted_at')
            ->select('id as value', 'name as label')
            ->get();

        $metalColor = MetalType::where('status', 'active')
            ->where('color', '!=', '')
            ->whereNull('deleted_at')
            ->select('id as value', 'color as label')
            ->get();

        $categories = Category::where('name', '!=', '')
            ->select('id as value', 'name as label')->whereNull('deleted_at')->get();

        $genders = DB::table('genders')->where('name', '!=', '')
        ->select('id as value', 'name as label')->get();

        $allOptions = collect()
            ->merge($tags)
            ->merge($metalTypes)
            ->merge($productTypes)
            ->merge($categories)
            ->merge($purity)
            ->merge($occasion)
            ->merge($metalColor)
            ->merge($genders)
            ->values();

        return response()->json($allOptions);
    }

    public function getMaterialTypeOptions()
    {
        $metalType = MetalName::where('status', 'active')->select('id as value', 'name as label')->get();
        return response()->json($metalType);

    }

    public function getVariantAttributeOption()
    {
        $getAttribute = Attribute::select('id as value', 'name as label')->get();
        return response()->json($getAttribute);
    }

    public function getTaxMasterOptions()
    {
        return Tax::select('tax_name', 'tax_rate')->get();
    }

    public function getChargeApplicationsOptions()
    {
        $applications = DB::table('additional_charges')
            ->whereNull('additional_charges.deleted_at')
            ->leftJoin('additional_charge_types', 'additional_charges.type_id', '=', 'additional_charge_types.id')
            ->select(
                'additional_charges.id',
                'additional_charges.type_id',
                'additional_charges.charges_type',
                'additional_charges.amount',
                'additional_charges.description',
                'additional_charge_types.name as application_name'
            )
            ->get();

        return response()->json($applications);
    }

    public function generateProcessId()
    {

        $lastExpense = Product::orderBy('id', 'desc')->first();
        if ($lastExpense) {
            $lastPONumber = $lastExpense->sku;
            $numberPart = (int)str_replace('SKU', '', $lastPONumber);
            $newPONumber = 'SKU' . str_pad($numberPart + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newPONumber = 'SKU001';
        }
        return response(['data' => $newPONumber, 'status' => 'success'], 200);
    }

    public function getSubCategoryOptions($categoryId)
    {
        $categories = Category::where('parent_id', $categoryId)->where('status', 'A')->select('id as value', 'name as label')->get();
        return response()->json($categories);
    }

    public function fetchSku()
    {
        // Get max numeric SKU (ignore -COPY)
        $lastSkuNumber = Product::withTrashed()
            ->where('parent_id', 0)
            ->where('sku', 'REGEXP', '^SKU[0-9]+$') // only real SKUs
            ->selectRaw("MAX(CAST(SUBSTRING(sku, 4) AS UNSIGNED)) as max_sku")
            ->value('max_sku');

        $nextNumber = $lastSkuNumber ? $lastSkuNumber + 1 : 1;

        $newSku = 'SKU' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        return response()->json([
            'status' => true,
            'data' => $newSku
        ]);
    }

    public function validateTab(Request $request, $tabIndex)
    {
        $rules = [];
        $messages = [];
        $productId = $request->id ?? null;
        $uniqueNameRule = Rule::unique('products', 'name')
            ->ignore($productId)
            ->whereNull('deleted_at');

        $uniqueSkuRule = Rule::unique('products', 'sku')
            ->ignore($productId)
            ->whereNull('deleted_at');

        switch ($tabIndex) {
            case 0:
                $rules = [
                    'product_name' => [
                        'required',
                        'string',
                        'max:255',
                        $uniqueNameRule,
                    ],
                    'sku' => [
                        'required',
                        'string',
                        'max:100',
                        $uniqueSkuRule,
                    ],
                    'category_id' => 'required|exists:categories,id',
                    'sub_category_id' => 'required|exists:categories,id',
                    'product_type_id' => 'required|exists:product_types,id',
                    'tags_id' => 'required',
                    'tags_id.*' => 'exists:tags,id',
                    'status' => 'required',
                    'videos.*' => 'nullable|mimes:mp4,webm,ogg|max:2048',
                    'main_image' => 'required',
                    'visible_to' => 'required|in:customer,partner,both',
                    'visible_partner_ids' => [
                        'required_if:visible_to,partner,both',
                        'array'
                    ],
                    'visible_partner_ids.*' => 'exists:users,id',
                ];

                $messages = [
                    'sku.required' => 'SKU is required',
                    'product_name.regex' => 'Product name must contain at least one letter and may include letters, numbers, spaces, hyphens (-), underscores (_), @, ., or #.',
                    'product_name.unique' => 'Product Name is Already Taken',
                    'category_id.required' => 'Category is required',
                    'sub_category_id.required' => 'Sub Category is required',
                    'product_type_id.required' => 'Product type is required',
                    'tags_id.required' => 'At least one tag is required.',
                    'tags_id.min' => 'At least one tag is required.',
                    'status.required' => 'Status is required',
                    'main_image.required' => 'Primary Image is required',
                    'main_image.dimensions' => 'Image resolution must be upto 3848px × 3848px',
                    'videos.*.max' => "Video must not be larger than 2MB",
                ];
                //  Only validate image rules if a new file is uploaded
                if ($request->hasFile('main_image')) {
                    $rules['main_image'] = 'nullable|image|mimes:jpeg,png,jpg,gif,webp|dimensions:max_width=3848,max_height=3848';
                }



                //Add image validation for each image
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $index => $file) {
                        $rules["images.$index"] = 'required|image|mimes:jpeg,png,jpg,gif,webp|dimensions:max_width=3848,max_height=3848';
                        $messages["images.$index.dimensions"] = "Image #$index must be upto 3848px × 3848px";
                    }
                }

                if ($request->hasFile('videos')) {
                    $rules['videos.*'] = 'nullable|mimes:mp4,webm,ogg|max:2048';
                    $messages['videos.*.max'] = "Video must not be larger than 2MB";
                }
                break;

            case 1: // Product Details tab
                $rules = [
                    'material_type_id' => 'required|exists:metal_names,id',
                    'purity_karat_id' => 'required|exists:purities,id'
                ];

                $messages = [
                    'material_type_id.required' => 'Metal type is required',
                    'purity_karat_id.required' => 'Purity is required',
                ];

                break;

            case 2: // Variant & Pricing tab
                // Validation handled in frontend
                $rules = [
                    'taxes' => 'array',
                    'taxes.*.tax_application' => 'required|string|max:255',
                    'taxes.*.type' => 'required|in:Percentage,Flat',
                    'taxes.*.value' => 'required|numeric|min:0',

                ];

                $messages = [
                    'taxes.*.tax_application.required' => 'Tax application is required',
                    'taxes.*.type.required' => 'Tax type is required',
                    'taxes.*.type.in' => 'Tax type must be Percentage or Flat',
                    'taxes.*.value.required' => 'Tax value is required',
                    'taxes.*.value.numeric' => 'Tax value must be a number',
                    'taxes.*.value.min' => 'Tax value cannot be negative',

                ];

                if ($request->variants_mode === 'yes') {
                    $rules['variants_product'] = 'required|array';

                    foreach ($request->variants_product ?? [] as $index => $variant) {
                        if ($request->hasFile("variants_product.$index.primary_image")) {
                            if ($request->isEditing == true) {
                                $rules["variants_product.$index.primary_image"] =
                                    'required|image|mimes:jpeg,png,jpg,webp|dimensions:max_width=3848,max_height=3848';
                            }

                        } else {
                            // Set rule so Validator sees it
                            $rules["variants_product.$index.primary_image"] = 'nullable';
                        }

                        $messages["variants_product.$index.primary_image.required"] =
                            "Variant primary image is required.";
                        $messages["variants_product.$index.primary_image.mimes"] =
                            "Variant primary image must be jpeg, jpg, webp or png.";
                        $messages["variants_product.$index.primary_image.dimensions"] =
                            "Variant primary image must be 3848px × 3848px.";
                    }
                }

                // Run base validator first
                $validator = Validator::make($request->all(), $rules, $messages);

                // ✅ Force 'required' check manually for string "null" or empty
                $validator->after(function ($validator) use ($request) {
                    if ($request->variants_mode === 'yes') {
                        foreach ($request->variants_product ?? [] as $index => $variant) {
                            $input = $variant['primary_image'] ?? null;

                            if (
                                !$request->hasFile("variants_product.$index.primary_image") &&
                                ($input === null || $input === '' || $input === 'null')
                            ) {
                                $validator->errors()->add(
                                    "variants_product.$index.primary_image",
                                    "Variant primary image is required."
                                );
                            }
                        }
                    }
                });

                if ($validator->fails()) {
                    return response()->json([
                        'errors' => $validator->errors(),
                        'valid' => false,
                    ], 422);
                }

                break;

                // Add cases for other tabs as needed
        }
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'valid' => false
            ], 422);
        }

        return response()->json(['valid' => true]);
    }

    public function getPurityOptions($metalTypeId)
    {
       $purity = DB::table('metal_types')
                ->join('purities', 'metal_types.purity_id', '=', 'purities.id')
                ->whereNull('metal_types.deleted_at')
                ->whereNull('purities.deleted_at')
                ->where('metal_types.metal_name_id', $metalTypeId)
                ->whereIn('metal_types.id', function ($q) {
                    $q->selectRaw('MAX(id)')
                    ->from('metal_types')
                    ->whereNull('deleted_at')
                    ->groupBy('metal_name_id', 'purity_id');
                })
                ->select(
                    'purities.id as value',
                    'purities.name as label'
                )
                ->get();


        return response()->json($purity);
    }

    public function generateBasePrice(Request $request)
    {
        $materialTypeId = $request->query('material_type_id');
        $purityKaratId = $request->query('purity_karat_id');
        $basePrice = DB::table('metal_types')
            ->where('metal_name_id', $materialTypeId)
            ->where('purity_id', $purityKaratId)
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->orderBy('updated_at', 'desc')
            ->orderBy('id', 'desc')
            ->value('price_per_gram');

        return response()->json(['data' => $basePrice]);
    }

    public function getRelatedProducts(Request $request)
    {

        $query = Product::query();

        if ($request->has('ids')) {
            $ids = explode(',', $request->ids);
            $query->whereIn('id', $ids);
        }

        if ($request->has('exclude')) {
            $query->where('id', '!=', $request->exclude);
        }

        if ($request->has('product_type_id')) {
            $query->where('product_type_id', $request->product_type_id);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('sub_category_id')) {
            $query->where('sub_category_id', $request->sub_category_id);
        }

        $products = $query->select(
            'id',
            'name',
            'sku',
            'product_type_id',
            'category_id',
            'sub_category_id',
            'main_image',
            'final_price',
            'views_count'
        )
                        ->orderBy('name', 'asc')
                        ->limit(100)
                        ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    public function show($id)
    {
        try {
            $id = (int) base64_decode($id, true) ?: $id;
            $today = now();
            $userId = auth('customer')->id() ?? 0;

            // ✅ Fetch product with joins
            $product = Product::with('childrenproducts.charges', 'childrenproducts.taxCharges')
                ->leftJoin('product_types', 'products.product_type_id', '=', 'product_types.id')
                ->leftJoin('categories as cat', 'products.category_id', '=', 'cat.id')
                ->leftJoin('categories as sub_cat', 'products.sub_category_id', '=', 'sub_cat.id')
                ->leftJoin('tags', 'products.tags_id', '=', 'tags.id')
                ->leftJoin('product_media', function ($join) {
                    $join->on('products.id', '=', 'product_media.product_id')
                        ->where('product_media.media_type', '=', 'image');
                })
                ->leftJoin('product_media as product_video', function ($join) {
                    $join->on('products.id', '=', 'product_video.product_id')
                        ->where('product_video.media_type', '=', 'video');
                })
                ->leftJoin('metal_names', 'products.material_type_id', '=', 'metal_names.id')
                ->leftJoin('purities', 'products.purity_karat_id', '=', 'purities.id')
                ->leftJoin('order_products', 'products.id', '=', 'order_products.product_id')
                ->leftJoin('orders', function ($join) {
                    $join->on('orders.id', '=', 'order_products.order_id')
                        ->whereNotIn('orders.order_status', ['cancelled', 'returned']);
                })
                ->select(
                    'products.*',
                    'product_types.name as product_type_name',
                    'cat.name as category_name',
                    'sub_cat.name as sub_category_name',
                    'tags.name as tag_name',
                    DB::raw('GROUP_CONCAT(product_media.file_url) as other_images'),
                    DB::raw('GROUP_CONCAT(product_video.file_url) as other_videos'),
                    'metal_names.name as metal_name',
                    'purities.name as purity_name',
                    DB::raw('CAST(GREATEST(products.available_stock - COALESCE(SUM(order_products.quantity), 0), 0) AS UNSIGNED) as available_stock_data'),
                    'products.available_stock as remaining_stock',
                    DB::raw("CASE WHEN EXISTS (
                    SELECT 1 
                    FROM wishlists 
                    WHERE wishlists.product_id = products.id 
                    AND wishlists.user_id = {$userId}
                    AND wishlists.deleted_at IS NULL
                ) THEN TRUE ELSE FALSE END as wishlisted"),
                )
                ->where('products.id', $id)
                ->groupBy('products.id')
                ->firstOrFail();

            $product->variant_attributes = collect();
            if ($product->parent_id != 0) {
                $parent = Product::find($product->parent_id);
                $variantOptions = $parent->variant_options;
                $variantAttrIds = collect($variantOptions)->pluck('attribute_id')->unique()->values()->toArray();
                if (count($variantAttrIds)) {
                    $attributes = DB::table('attribute_masters')
                        ->leftJoin('attribute_options', 'attribute_masters.data_type', '=', 'attribute_options.id')
                        ->whereIn('attribute_masters.id', $variantAttrIds)
                        ->select('attribute_masters.id', 'attribute_masters.name', 'attribute_options.name as data_type')
                        ->get();

                    $variantOptions = DB::table('product_variants')
                        ->join('products', 'products.id', '=', 'product_variants.product_id')
                        ->where('products.parent_id', $product->parent_id)
                        ->where('products.status', 'A')
                        ->select('product_variants.attribute_id', 'product_variants.attribute_value as label', 'product_variants.attribute_value as sku', 'products.base_price', 'products.metal_weight', 'product_variants.product_id')
                        ->get();

                    $grouped = [];
                    foreach ($attributes as $attr) {
                        $options = $variantOptions->where('attribute_id', $attr->id)
                            ->map(function ($opt) {
                                return [
                                    "sku"             => $opt->sku,
                                    "label"           => $opt->label,
                                    "base_price"      => $opt->base_price,
                                    "attribute_id"    => $opt->attribute_id,
                                    "metal_weight"    => $opt->metal_weight,
                                    "product_id"      => $opt->product_id,
                                ];
                            })
                            // 🟢 Keep only unique labels (so 4,5 or Men,Women)
                            ->unique('label')
                            ->values();

                        $grouped[] = [
                            "id"        => $attr->id,
                            "name"      => $attr->name,
                            "data_type" => $attr->data_type,
                            "options"   => $options
                        ];
                    }

                    $product->variant_attributes = $grouped;

                }
            }

            // ✅ Increment views
            $product->increment('views_count');

            // ✅ Convert CSV images
            $product->other_images = $product->other_images ? explode(',', $product->other_images) : [];

            // ✅ Charges & Taxes
            $product->charges = DB::table('product_charges')->where('product_id', $product->id)->whereNull('deleted_at')->get();
            $product->taxes   = DB::table('product_taxes')->where('product_id', $product->id)->whereNull('deleted_at')->get();


            
            $totalCharges = 0;
            foreach ($product->charges as $charge) {
                $charge->calculated_amount = 0;
                if ($charge->type === "Percentage (%)") {
                    if ($charge->primary_cost == 'Metal Weight Cost') {
                        $metalRate = DB::table('metal_types')
                            ->where('metal_name_id', $product->material_type_id)
                            ->where('purity_id', $product->purity_karat_id)
                            ->whereNull('deleted_at')
                            ->where('status', 'active')
                            ->orderBy('updated_at', 'desc')
                            ->orderBy('id', 'desc')
                            ->value('price_per_gram');
                        $ProductChargesBasePrice = $metalRate * (float) $product->metal_weight;
                    } else {
                        $ProductChargesBasePrice = $product->unit_price ?: $product->base_price;
                    }
                    $charge->calculated_amount = ($ProductChargesBasePrice * $charge->value) / 100;
                } else {
                    $charge->calculated_amount = (float)$charge->value;
                }
                $totalCharges += $charge->calculated_amount;
            }
            $product->subtotal = $product->base_price + $totalCharges;
            $relatedIds = json_decode(trim($product->related_product_ids, '"'), true) ?: [];
            $youMayLikeIds = json_decode(trim($product->you_may_like_product_ids, '"'), true) ?: [];

            $relatedProducts = collect();
            $youMayLikeProducts = collect();

            if (!empty($relatedIds)) {
                $relatedProducts = Product::whereIn('id', $relatedIds)
                    ->select('id', 'name', 'sku')
                    ->get();
            }

            if (!empty($youMayLikeIds)) {
                $youMayLikeProducts = Product::whereIn('id', $youMayLikeIds)
                    ->select('id', 'name', 'sku')
                    ->get();
            }

            // Attach to product
            $product->related_products = $relatedProducts;
            $product->you_may_like_products = $youMayLikeProducts;


            $product->visible_to = $product->visible_to ?? 'customer';
            $visiblePartnerIds = [];
            if (in_array($product->visible_to, ['partner', 'both']) && $product->visible_partner_ids) {
                try {
                    $visiblePartnerIds = is_array($product->visible_partner_ids) ? $product->visible_partner_ids : [];
                } catch (\Exception $e) {
                    $visiblePartnerIds = [];
                }
            }

            $partnerNames = [];
            if (!empty($visiblePartnerIds)) {
                $partnerNames = DB::table('partners')
                    ->join('users', 'partners.user_id', '=', 'users.id')
                    ->whereIn('users.id', $visiblePartnerIds)
                    ->whereNull('users.deleted_at')
                    ->select('users.id', 'partners.business_name as name')
                    ->get();
            }

            // Attach to product response
            $product->visible_partner_ids = $visiblePartnerIds;
            $product->partner_options = $partnerNames;
            // ✅ Active offers
            $offers = DB::table('offers')
                ->where('status', 'active')
                ->where('valid_from', '<=', $today)
                ->where('valid_to', '>=', $today)
                ->get();

            $listingOffers = collect();
            $cartOffers = collect();
            $eligibleListingOffers = collect();
            $finalPrice = (float)$product->subtotal;

            foreach ($offers as $offer) {
                // Decode apply_on & values
                $applyOnTypes = [];
                if (!empty($offer->apply_on)) {
                    $decodedApplyOn = json_decode($offer->apply_on, true);
                    $applyOnTypes = is_array($decodedApplyOn) ? $decodedApplyOn : [$offer->apply_on];
                }

                $applyValues = [];
                if (!empty($offer->apply_on_value)) {
                    $decoded = json_decode($offer->apply_on_value, true);
                    if (is_string($decoded)) {
                        $decoded = json_decode($decoded, true);
                    }
                    $applyValues = is_array($decoded) ? $decoded : [];
                }

                // ✅ Eligibility
                $eligible = true;
                $productTags = json_decode($product->tags_id, true) ?: [];

                $tagLabels = collect($applyValues)
                            ->filter(fn ($item) => $item['value']['type'] === 'tags')
                            ->pluck('label')
                            ->toArray();
                foreach ($applyOnTypes as $type) {
                    $idsForType = collect($applyValues)
                        ->filter(fn ($item) => $item['value']['type'] === $type)
                        ->pluck('value.id')
                        ->map(fn ($v) => (int) $v) // <-- cast to integer
                        ->toArray();

                    $matches = match ($type) {
                        'products' => in_array((int)$product->id, $idsForType),
                        'category_id' => in_array((int)$product->category_id, $idsForType),
                        'sub_category_id' => in_array((int)$product->sub_category_id, $idsForType),
                        'product_type_id' => in_array((int)$product->product_type_id, $idsForType),
                        'tags' => !empty(array_intersect($productTags, $tagLabels)),
                        default => false,
                    };

                    if ($matches == true && $offer->offer_type_option == 'FLASH_SALE') {
                        $now = now('Asia/Kolkata');
                        $start = \Carbon\Carbon::parse($offer->valid_from, 'Asia/Kolkata');
                        $end   = \Carbon\Carbon::parse($offer->valid_to, 'Asia/Kolkata');


                        if ($now->lt($start) || $now->gt($end)) {
                            $eligible = false;
                            break;
                        }
                    } elseif (!$matches) {
                        $eligible = false;
                        break;
                    }
                }


                // ✅ Skip ineligible offers
                if (!$eligible) {
                    continue;
                }


                // ✅ Discount calculation
                $discountAmount = 0;
                $eligibleForCartOnly = false;

                switch ($offer->offer_type_option) {
                    case 'NO_MAKING_CHARGES':
                        // ✅ NEW LOGIC
                        $gemCharge   = $product->charges->firstWhere('primary_cost', 'Gemstone Cost');
                        $metalCharge = $product->charges->firstWhere('primary_cost', 'Metal Weight Cost');
                        $baseCharge  = $product->charges->firstWhere('primary_cost', 'Base Price');

                        // Use calculated_amount if exists, fallback in order
                        $charge = $gemCharge?->calculated_amount + $metalCharge?->calculated_amount + $baseCharge?->calculated_amount;
                        // Apply discount

                        $discountAmount = self::calculateGenericDiscount($charge, $offer);

                        break;

                    case 'CATEGORY_BASED':
                    case 'SUBCATEGORY_BASED':
                    case 'GENDER_BASED':
                    case 'FESTIVAL':
                        $discountAmount = $this->calculateGenericDiscount($finalPrice, $offer);
                        break;
                    case 'FLASH_SALE':
                        $discountAmount = $this->calculateGenericDiscount($finalPrice, $offer);
                        break;

                    case 'METAL_WEIGHT':
                        $metalRate = DB::table('metal_types')
                        ->where('metal_name_id', $product->material_type_id)
                        ->where('purity_id', $product->purity_karat_id)
                        ->whereNull('deleted_at')
                        ->where('status', 'active')
                        ->orderBy('updated_at', 'desc')
                        ->orderBy('id', 'desc')
                        ->value('price_per_gram');

                        $metalCharge = $metalRate * (float)$product->metal_weight;
                        $offerDetails = json_decode($offer->details, true);
                        if (is_string($offerDetails)) {
                            $offerDetails = json_decode($offerDetails, true);
                        }
                        $minWeight = (float)($offerDetails['min_weight'] ?? 0);
                        if ((float)$product->metal_weight >= ($offer->details['min_weight'] ?? 0)) {
                            $discountAmount = $this->calculateGenericDiscount($metalCharge, $offer);
                        } else {
                            $discountAmount = 0;
                        }
                        break;

                    default:
                        $eligibleForCartOnly = true;
                        $cartOffers->push($offer);
                        break;
                }

                if ($eligibleForCartOnly) {
                    continue;
                }

                if ($discountAmount > 0) {
                    $eligibleListingOffers->push([
                        'offer' => $offer,
                        'discount' => $discountAmount,
                    ]);
                }
            }

            // ✅ Apply best discount strategy
            if ($eligibleListingOffers->isNotEmpty()) {
                $best = $eligibleListingOffers->sortByDesc('discount')->first();
                $finalPrice -= $best['discount'];
                $listingOffers->push($this->makeOfferMeta($best['offer'], $best['discount']));
            }

            $product->final_price = max($finalPrice, 0);
            $totalTax = DB::table('product_taxes')
                ->where('product_id', $product->id)
                ->sum('amount');
            $product->final_amount_with_tax = $product->final_price + $totalTax;
            $product->offers = $listingOffers->values();
            $product->cart_offers = $cartOffers->values();

            return response()->json([
                'status' => 'success',
                'data'   => $product,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function calculateGenericDiscount($price, $offer)
    {
        if ($offer->discount_type === 'flat') {
            $discount = (float)$offer->discount_amount;
        } elseif ($offer->discount_type === 'percent') {
            $discount = $price * ($offer->discount_percent / 100);
            if (!empty($offer->max_discount_amount)) {
                $discount = min($discount, $offer->max_discount_amount);
            }
        } else {
            $discount = 0;
        }

        // ✅ Cap discount to price
        if ($discount > $price) {
            $discount = 0;
        }

        return $discount;
    }


    private function makeOfferMeta($offer, $discount)
    {
        return [
            'id' => $offer->id,
            'title' => $offer->title,
            'type' => $offer->offer_type_option,
            'discount' => $discount,
            'coupon_code' => $offer->coupon_code ?? null
        ];
    }

    public function store(Request $request)
    {
        $configurableType = ProductType::where('name', 'Configurable')->first();
        $configurableTypeId = $configurableType ? $configurableType->id : null;
        $uniqueNameRule = Rule::unique('products', 'name')->whereNull('deleted_at');

        if ($request->status === 'draft') {
            $validator = Validator::make($request->all(), [
                'product_name' => [
                    'required',
                    'string',
                    'max:255',
                    $uniqueNameRule,
                ],
                'tags_id.*' => 'nullable|exists:tag_masters,id',
                'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
                'videos.*' => 'nullable|mimes:mp4,webm,ogg|max:2048',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
                'product_type_id' => 'required|exists:product_types,id',
                'category_id' => 'required|exists:categories,id',
                'sub_category_id' => 'required|exists:categories,id',
                'visible_to' => 'required|in:customer,partner,both',
                'visible_partner_ids' => [
                    'nullable',
                    'array',
                    'required_if:visible_to,partner',
                    'required_if:visible_to,both',
                ],
                'visible_partner_ids.*' => 'exists:users,id',
            ], [
                'product_name.required' => 'Product name is required.',
                'product_name.unique' => 'Product name must be unique.',

            ]);

            if ($validator->fails()) {
                return response([
                    'errors' => $validator->errors()->messages(),
                    'code' => 422
                ], 422);
            }

        } else {
            $rules = [
                'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|dimensions:max_width=3848,max_height=3848',
                'product_name' => [
                        'required',
                        'string',
                        'max:255',
                        $uniqueNameRule,
                    ],
                'category_id' => 'required|exists:categories,id',
                'sub_category_id' => 'required|exists:categories,id',
                'product_type_id' => 'required|exists:product_types,id',
                'tags_id' => 'required',
                'tags_id.*' => 'exists:tag_masters,id',
                'material_type_id' => 'required|exists:metal_names,id',
                'purity_karat_id' => 'required|exists:purities,id',
                'media.*' => 'required|file', // Base rule for all media files
                'variants_product.*.primary_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|dimensions:max_width=3848,max_height=3848',
                'status' => 'required', // A=Active, I=Inactive, D=Draft
                'videos.*' => 'nullable|mimes:mp4,webm,ogg|max:2048',

                'taxes' => 'nullable|array',
                'taxes.*.tax_application' => 'required|string|max:255',
                'taxes.*.type' => 'required|in:Percentage,Flat',
                'taxes.*.value' => 'required|numeric|min:0',
                'visible_to' => 'required|in:customer,partner,both',
                'visible_partner_ids' => [
                    'nullable',
                    'array',
                    'required_if:visible_to,partner',
                    'required_if:visible_to,both',
                ],
                'visible_partner_ids.*' => 'exists:users,id',

            ];

            $messages = [
                'main_image.dimensions' => 'Image resolution must be upto 3848px × 3848px',
                'product_name.regex' => 'Product name must contain at least one letter and can only include letters, numbers, spaces, hyphens (-), or underscores (_).',
                'category_id.required' => 'Category is required',
                'category_id.exists' => 'Category is required',
                'sub_category_id.required' => 'Sub Category is required',
                'sub_category_id.exists' => 'Sub Category is required',
                'product_type_id.required' => 'Product Type is required',
                'pvariantsroduct_type_id.exists' => 'Product Type is required',
                'tags_id.required' => 'At least one tag is required.',
                'tags_id.min' => 'At least one tag is required.',
                'material_type_id.exists' => 'Metal Type is required',
                'purity_karat_id.exists' => 'Purity Type is required',
                'variants_product.*.primary_image.dimensions' => 'Variant primary image must be exactly 3848px × 3848px',
                'status.required' => 'Status is required',
                'status.in' => 'Invalid status value',
                'videos.*.max' => "Video must not be larger than 2MB",
                'taxes.*.tax_application.required' => 'Tax Application is required.',
                'taxes.*.type.required' => 'Tax Type is required.',
                'taxes.*.type.in' => 'Tax Type must be either Percentage or Flat.',
                'taxes.*.value.required' => 'Tax Value is required.',
                'taxes.*.value.numeric' => 'Tax Value must be a number.',
                'taxes.*.primary_cost.required' => 'Primary Cost is required.',
            ];

            // Conditional stock validation
            if ($request->product_type_id != $configurableTypeId) {
                $rules['total_stock'] = 'required|numeric|min:0';
                $rules['low_stock'] = 'nullable|numeric|min:0';
            } else {
                $rules['variant_options.*.total_stock'] = 'required|numeric|min:0';
                $rules['variant_options.*.low_stock'] = 'nullable|numeric|min:0';
            }

            // Add image validation for each image
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $file) {
                    $rules["images.$index"] = 'nullable|image|mimes:jpeg,png,jpg,gif,webp|dimensions:max_width=3848,max_height=3848';
                    $messages["images.$index.dimensions"] = "Image #$index must be upto 3848px × 3848px";
                }
            }

            $validator = Validator::make($request->all(), $rules, $messages);

            // Ensure low_stock is not greater than total_stock
            $validator->after(function ($validator) use ($request, $configurableTypeId) {
                // Non-configurable: compare top-level fields
                if ((int)$request->product_type_id !== (int)$configurableTypeId) {
                    $total = is_numeric($request->total_stock) ? (float)$request->total_stock : null;
                    $low   = is_numeric($request->low_stock) ? (float)$request->low_stock : null;
                    if ($total !== null && $low !== null && $low > $total) {
                        $validator->errors()->add('low_stock', 'Low stock cannot be greater than total stock.');
                    }
                } else {
                    // Configurable: compare inside variant_options
                    $variants = $request->get('variant_options', []);
                    if (is_array($variants)) {
                        foreach ($variants as $idx => $variant) {
                            $vTotal = isset($variant['total_stock']) && is_numeric($variant['total_stock']) ? (float)$variant['total_stock'] : null;
                            $vLow   = isset($variant['low_stock']) && is_numeric($variant['low_stock']) ? (float)$variant['low_stock'] : null;
                            if ($vTotal !== null && $vLow !== null && $vLow > $vTotal) {
                                $validator->errors()->add("variant_options.$idx.low_stock", 'Low stock cannot be greater than total stock for this variant.');
                            }
                        }
                    }
                }
            });

            if ($validator->fails()) {
                return response([
                    'errors' => $validator->errors()->messages(),
                    'code' => 422
                ], 422);
            }
        }



        DB::beginTransaction();

        try {
            $product = new Product();
            $product->name = $request->product_name;
            $existingProducts = Product::withTrashed()
            ->where('sku', $request->sku)
            ->get();
            foreach ($existingProducts as $oldProduct) {
                if (!str_starts_with($oldProduct->sku, 'DELETED_')) {
                    $oldProduct->sku = 'DELETED_' . $oldProduct->sku;
                    $oldProduct->save();
                }
            }


            $product->sku = $request->sku;
            $product->description = $request->description;
            $product->category_id = $request->category_id;
            $product->sub_category_id = $request->sub_category_id;
            $product->product_type_id = $request->product_type_id;
            // Deduplicate tags before saving
            $product->tags_id = $this->deduplicateTags($request->tags_id);
            $product->material_type_id = is_numeric($request->material_type_id)
                                            ? $request->material_type_id
                                            : null;           
            $product->purity_karat_id = $request->purity_karat_id != 'null' ? $request->purity_karat_id : 0;
            $product->video_url = $request->video_url ?? null;
            $product->metal_weight = $request->metal_weight;
            $productType = ProductType::where('id', $request->product_type_id)->first();
            $product->base_price = $productType->name == 'Ready-made'
            ? round($request->unit_price, 2)
            : round($request->base_price, 2);
            $product->unit_price = $request->unit_price;
            $product->discount = $request->discount === null || $request->discount === 'null' || $request->discount === ''
            ? null
            : $request->discount;
            $product->final_price = round($request->final_price, 2);
            $product->status = $request->status;
            $product->variant_options = json_decode($request->variant_options_id, true) ?? [];
            $product->weight = $request->weight;
            $product->dimensions = $request->dimensions;
            $totalStock = $request->total_stock === 'null' ? null : $request->total_stock;
            $product->total_stock = $totalStock ?? 0;
            $product->available_stock = $totalStock ?? 0;

            $product->low_stock = $request->low_stock !== 'null' ? $request->low_stock : null;
            $product->seo_title = $request->seo_title;
            $product->meta_description = $request->meta_description;
            $product->meta_slug_url = $request->meta_slug_url;
            $product->unit = $request->unit !== 'null' ? $request->unit : null;
            $product->length = $request->length !== 'null' ? $request->length : null;
            $product->width  = $request->width  !== 'null' ? $request->width : null;
            $product->height = $request->height !== 'null' ? $request->height : null;
            $product->additional_charges = $request->additional_charges;

            if ($request->hasFile('main_image')) {
                $product->main_image = $request->file('main_image')->store('products', 'public');
            }

            // Store related products as array of IDs
            $product->related_product_ids = json_encode($request->related_product_ids ?? []);
            $product->you_may_like_product_ids = json_encode($request->you_may_like_product_ids ?? []);
            
            $product->is_featured = $request->is_featured ? 1 : 0;
            $product->variants = json_encode($request->variant_attribute_id) ;
            $product->visible_to = $request->visible_to;

            if (in_array($request->visible_to, ['partner', 'both'])) {
                if ($request->filled('visible_partner_ids')) {
                    $product->visible_partner_ids = is_array($request->visible_partner_ids)
                        ? $request->visible_partner_ids
                        : json_decode($request->visible_partner_ids, true);
                } else {
                    $product->visible_partner_ids = null;
                }

            } else {
                $product->visible_partner_ids = null;
            }
            $product->save();

            if (!empty($request->variant_options) && is_array($request->variant_options)) {
                foreach ($request->variant_options as $index => $variant) {
                    $childproduct = new Product();
                    $childproduct->name = $variant['variant_name'];

                    $childproduct->sku = $variant['sku'] ?? null;
                    $childproduct->description = $request->description;
                    $childproduct->category_id = $request->category_id;
                    $childproduct->sub_category_id = $request->sub_category_id;
                    $childproduct->product_type_id = $request->product_type_id;
                    $childproduct->tags_id = $this->deduplicateTags($request->tags_id);
                    $childproduct->material_type_id = $request->material_type_id;
                    $childproduct->purity_karat_id = $request->purity_karat_id;

                    $childproduct->metal_weight = is_numeric($variant['metal_weight'] ?? null) ? $variant['metal_weight'] : null;
                    $childproduct->base_price = is_numeric($variant['base_price'] ?? null) ? $variant['base_price'] : null;
                    $childproduct->unit_price = is_numeric($variant['base_price'] ?? null) ? $variant['base_price'] : null;
                    $childproduct->discount = $request->discount === null || $request->discount === 'null' || $request->discount === ''
            ? null
            : $request->discount;
                    $childproduct->final_price = $variant['product_price'] ?? null;
                    $childproduct->status = $variant['status'] ?? 'A';
                    $childproduct->variants = $variant['attributes'];
                    $childproduct->weight = isset($variant['weight']) ? (float)$variant['weight'] : null;
                    $length = isset($variant['length']) ? (float)$variant['length'] : null;
                    $width = isset($variant['width']) ? (float)$variant['width'] : null;
                    $height = isset($variant['height']) ? (float)$variant['height'] : null;
                    $childproduct->dimensions = $length .'*'. $width .'*'. $height;
                    $childproduct->total_stock = $variant['total_stock'] ?? 0;
                    $childproduct->available_stock = $variant['total_stock'] ?? 0;
                    $childproduct->low_stock = $variant['low_stock'] ?? 0;
                    $childproduct->seo_title =  $variant['seo_title'] ?? null;
                    $childproduct->meta_description = $variant['meta_description'] ?? null;
                    $childproduct->meta_slug_url = $variant['meta_slug_url'] ?? null;
                    $childproduct->unit = $variant['unit'] ?? 'cm';
                    $childproduct->length = $length;
                    $childproduct->width  = $width;
                    $childproduct->height = $height;
                    $childproduct->visible_to = $request->visible_to;

                    if (in_array($request->visible_to, ['partner', 'both'])) {
                        $childproduct->visible_partner_ids =$request->visible_partner_ids ?? [];
                    } else {
                        $childproduct->visible_partner_ids = null;
                    }

                    $childproduct->additional_charges = isset($variant['additional_charges']) ? $variant['additional_charges'] : null ;

                    // Store primary image in product_variants table
                    if (isset($request->variants_product[$index]['primary_image'])) {

                        $primaryImage = $request->variants_product[$index]['primary_image'];
                        $primaryImagePath = $primaryImage->store('product_variants', 'public');
                        $childproduct->main_image  = $primaryImagePath;
                    }


                    // Store related products as array of IDs
                    $childproduct->related_product_ids = json_encode($request->related_product_ids ?? []);
                    $childproduct->you_may_like_product_ids = json_encode($request->you_may_like_product_ids ?? []);
                    
                    $childproduct->is_featured = $request->is_featured ? 1 : 0;
                    $childproduct->parent_id = $product->id;

                    $childproduct->save();

                    $attributes = json_decode($variant['attributes'], true);

                    foreach ($attributes as $attribute) {
                        $productvarient = new ProductVariant();
                        $productvarient->product_id = $childproduct->id;
                        $productvarient->attribute_id = $attribute['attribute_id'];
                        $productvarient->attribute_value = isset($attribute['value']) ? $attribute['value'] : $attribute['attribute_value'];
                        $productvarient->save();
                    }


                    if ($request->has('charges')) {
                        foreach ($request->charges as $charge) {
                            ProductCharges::create([
                                'product_id' => $childproduct->id,
                                'charges' => $charge['charges'],
                                'type' => $charge['type'],
                                'value' => $charge['value'],
                                'primary_cost' => $charge['primary_cost'],
                                'description' => $charge['description'],
                            ]);
                        }
                    }

                    if ($request->has('taxes')) {

                        $variantTaxes = json_decode($variant['variant_taxes'] ?? '[]', true);
                        if (is_string($variantTaxes)) {
                            $variantTaxes = json_decode($variantTaxes, true);
                        }
                        if (!is_array($variantTaxes)) {
                            $variantTaxes = [];
                        }

                        $variantBase     = (float)($variant['base_price'] ?? 0);
                        $variantMetal    = (float)($variant['metal_base'] ?? 0);
                        $variantGemstone = (float)($variant['gemstone_base'] ?? 0);

                        foreach ($request->taxes as $taxIndex => $tax) {
                            // Find variant-level amount if available
                            $variantTaxes = $request->variantscvb[$index]['taxes'] ?? [];
                            $variantAmount = isset($variantTaxes[$taxIndex]['amount'])
                                ? round((float)$variantTaxes[$taxIndex]['amount'], 2)
                                : 0;

                            // Find matching label (if you still need this logic)
                            $matched = collect($variantTaxes)->first(function ($item) use ($tax) {
                                return strtolower(trim($item['label'] ?? '')) === strtolower(trim($tax['tax_application'] ?? ''));
                            });

                            $matchedAmount = isset($matched['amount']) ? round((float)$matched['amount'], 2) : 0;

                            // Determine base cost
                            $primaryCost = $tax['primary_cost'] ?? 'Base Price';
                            $baseAmount = 0;
                            if ($primaryCost === 'Base Price') {
                                $baseAmount = $variantBase;
                            } elseif ($primaryCost === 'Metal Weight Cost') {
                                $baseAmount = $variantMetal;
                            } elseif ($primaryCost === 'Gemstone Cost') {
                                $baseAmount = $variantGemstone;
                            }

                            // Calculate tax if percentage or fixed
                            $value = is_numeric($tax['value'] ?? null) ? (float)$tax['value'] : 0;
                            $isPercent = is_string($tax['type'] ?? '') && str_contains($tax['type'], 'Percentage');
                            $computedAmount = $isPercent ? round(($baseAmount * $value) / 100, 2) : round($value, 2);

                            // ✅ Final amount (prefer variant → matched → computed)
                            $amount = $variantAmount ?: ($matchedAmount ?: $computedAmount);

                            // ✅ Store tax record
                            ProductTax::create([
                                'product_id'      => $childproduct->id,
                                'tax_application' => $tax['tax_application'] ?? null,
                                'type'            => $tax['type'] ?? null,
                                'value'           => is_numeric($tax['value']) ? $tax['value'] : null,
                                'primary_cost'    => $primaryCost,
                                'amount'          => $amount, // ✅ correct value here
                                'description'     => $tax['description'] ?? null,
                            ]);
                        }

                    }


                    // Store additional images in product_media table
                    if (isset($request->variants_product[$index]['additional_images'])) {
                        foreach ($request->variants_product[$index]['additional_images'] as $additionalImage) {
                            $path = $additionalImage->store('product_media', 'public');
                            $childproduct->media()->create([
                                //'product_variant_id' => $childproduct->id,
                                'media_type' => 'image',
                                'file_url' => $path
                            ]);
                        }
                    }
                    if (isset($request->variants_product[$index]['videos'])) {
                        foreach ($request->variants_product[$index]['videos'] as $video) {
                            $path = $video->store('product_media', 'public');
                            $childproduct->media()->create([
                               // 'product_variant_id' => $childproduct->id,
                                'media_type' => 'video',
                                'file_url' => $path
                            ]);
                        }
                    }

                }
            }

            if ($request->has('charges')) {
                foreach ($request->charges as $charge) {
                    ProductCharges::create([
                        'product_id' => $product->id,
                        'charges' => $charge['charges'],
                        'type' => $charge['type'],
                        'value' => $charge['value'],
                        'primary_cost' => $charge['primary_cost'],
                        'description' => $charge['description'],
                    ]);
                }
            }

            if ($request->has('taxes')) {
                foreach ($request->taxes as $tax) {

                    ProductTax::create([
                        'product_id'     => $product->id,
                        'tax_application' => $tax['tax_application'],
                        'type'           => $tax['type'],
                        'value'          => is_numeric($tax['value']) ? $tax['value'] : null,
                        'primary_cost'   => $tax['primary_cost'] ?? null,
                        'amount'         => isset($tax['amount']) ? round((float)$tax['amount'], 2) : 0,
                        'description'    => $tax['description'] ?? null,
                    ]);
                }
            }

            if ($request->has('media')) {
                foreach ($request->media as $index => $file) {
                    $path = $file->store('product_media', 'public');
                    $product->media()->create([
                        'media_type' => $request->media_types[$index],
                        'file_url' => $path
                    ]);
                }
            }


            DB::commit();

            return response()->json([
                'message' => 'Product and size values saved successfully',
                'product' => $product
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    private function deduplicateTags($tags)
    {
        if (empty($tags)) {
            return json_encode([]);
        }

        if (is_string($tags)) {
            $decoded = json_decode($tags, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $tags = $decoded;
            } else {
                $tags = [$tags];
            }
        }

        if (!is_array($tags)) {
            return json_encode([]);
        }

        $uniqueTags = array_values(array_unique(
            array_filter(
                array_map(function ($tag) {
                    if (is_array($tag) && isset($tag['label'])) {
                        return trim($tag['label']);
                    }
                    return trim((string)$tag);
                }, $tags),
                function ($tag) {
                    return !empty($tag);
                }
            ),
            SORT_STRING
        ));

        return json_encode($uniqueTags);
    }

    public function edit($id)
    {

        $id = (int) base64_decode($id, true) ?: $id;

        $product = Product::with('childrenproducts.attributes')->findOrFail($id);

        $product->tags_id = json_decode($product->tags_id);
        $product->increment('views_count');
        $product->refresh();

        return response()->json([
            'status' => 'success',
            'data' => $product,
        ]);
    }

    public function getCharges($productId)
    {
        $charges = ProductCharges::where('product_id', $productId)
        ->select('charges', 'type', 'value', 'primary_cost', 'description')->get();

        return response()->json($charges);
    }

    public function getTaxes($productId)
    {
        $taxes = ProductTax::where('product_id', $productId)
            ->select('tax_application', 'type', 'value', 'primary_cost', 'description', 'amount as calculated_amount')
            ->get();

        return response()->json($taxes);
    }

    public function getMedia(Product $product)
    {
        return response()->json($product->media()->get());
    }

    public function getVariantMedia($productId)
    {
        $variants = ProductVariant::where('product_id', $productId)->get();

        $result = $variants->map(function ($variant) {
            return [
                'product_variant_id' => $variant->id,
                'product_variant_image' => $variant->product_variant_image,
                'images' => $variant->media()->where('media_type', 'image')->get()->map(function ($media) {
                    return $media->file_url;
                }),
                'videos' => $variant->media()->where('media_type', 'video')->get()->map(function ($media) {
                    return $media->file_url;
                })
            ];
        });

        return response()->json($result);
    }

    public function update(Request $request, $id)
    {
        
        $prod = Product::findOrFail($id);
        // Get the Configurable product type ID
        $configurableType = ProductType::where('name', 'Configurable')->first();
        $configurableTypeId = $configurableType ? $configurableType->id : null;


        $uniqueNameRule = Rule::unique('products', 'name')->ignore($prod->id)->whereNull('deleted_at');

        if ($request->status === 'D') {
            $validator = Validator::make($request->all(), [
                'product_name' => [
                    'required',
                    'string',
                    'max:255',
                    $uniqueNameRule,
                ],
                'tags_id.*' => 'nullable|exists:tag_masters,id',
                'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
                'videos.*' => 'nullable|mimes:mp4,webm,ogg|max:2048',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
                'product_type_id' => 'required|exists:product_types,id',
                'category_id' => 'required|exists:categories,id',
                'sub_category_id' => 'required|exists:categories,id',
                'visible_to' => 'required|in:customer,partner,both',
                    'visible_partner_ids' => [
                        'required_if:visible_to,partner,both',
                        'array'
                    ],
                    'visible_partner_ids.*' => 'exists:users,id',
            ], [
                'product_name.required' => 'Product name is required.',
                'product_name.unique' => 'Product name must be unique.',

            ]);

            if ($validator->fails()) {
                return response([
                    'errors' => $validator->errors()->messages(),
                    'code' => 422
                ], 422);
            }

        } else {
            $rules = [
                'product_name' => [
                    'required',
                    'string',
                    'max:255',
                    $uniqueNameRule,
                ],
                'category_id' => 'required|exists:categories,id',
                'sub_category_id' => 'required|exists:categories,id',
                'product_type_id' => 'required|exists:product_types,id',
                'tags_id' => 'required',
                'tags_id.*' => 'exists:tag_masters,id',
                'material_type_id' => 'required|exists:metal_names,id',
                'purity_karat_id' => 'required|exists:purities,id',
                'status' => 'required',
            ];



            $messages = [
                'main_image.dimensions' => 'Image resolution must be upto 3848px × 3848px',
                'product_name.regex' => 'Product name must contain at least one letter and may include letters, numbers, spaces, hyphens (-), underscores (_), @, ., or #.',
                'category_id.required' => 'Category is required',
                'category_id.exists' => 'Category is required',
                'sub_category_id.required' => 'Sub Category is required',
                'sub_category_id.exists' => 'Sub Category is required',
                'product_type_id.required' => 'Product Type is required',
                'product_type_id.exists' => 'Product Type is required',
                'tags_id.required' => 'At least one tag is required.',
                'tags_id.min' => 'At least one tag is required.',
                'material_type_id.exists' => 'Metal Type is required',
                'purity_karat_id.exists' => 'Purity Type is required',
                'status.required' => 'Status is required',
                'status.in' => 'Invalid status value',
            ];

            // Conditional stock validation
            if ($request->product_type_id != $configurableTypeId) {
                $rules['total_stock'] = 'required|numeric|min:0';
                $rules['low_stock'] = 'nullable|numeric|min:0';
            } else {
                $rules['variant_options.*.total_stock'] = 'required|numeric|min:0';
                $rules['variant_options.*.low_stock'] = 'nullable|numeric|min:0';
            }

            // Primary image
            if ($request->hasFile('main_image')) {
                $rules['main_image'] = 'image|mimes:jpeg,png,jpg,gif,webp|dimensions:max_width=3848,max_height=3848';
            }



            if ($request->hasFile('videos')) {
                $rules['videos.*'] = 'nullable|mimes:mp4,webm,ogg|max:2048';
                $messages['videos.*.max'] = "Video must not be larger than 2MB";
            }

            // Add image validation for each image
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $file) {
                    $rules["images.$index"] = 'required|image|mimes:jpeg,png,jpg,gif,webp|dimensions:max_width=3848,max_height=3848';
                    $messages["images.$index.dimensions"] = "Image #$index must be upto 3848px × 3848px";
                }
            }


            // Variant primary image validations
            if ($request->has('variants_product')) {
                foreach ($request->file('variants_product', []) as $index => $variant) {
                    if (isset($variant['primary_image']) && $variant['primary_image']) {
                        $rules["variants_product.$index.primary_image"] = 'nullable|image|mimes:jpeg,png,jpg,webp|dimensions:max_width=3848,max_height=3848';

                        $messages["variants_product.$index.primary_image.dimensions"] = "Variant #$index primary image must be 3848px × 3848px";
                        $messages["variants_product.$index.primary_image.mimes"] = "Variant #$index primary image must be jpeg, jpg, webp or png";
                    }
                }
            }


            //  Taxes validation (added here)
            if ($request->has('taxes')) {
                $rules['taxes'] = 'array';
                $rules['taxes.*.tax_application'] = 'required|string|max:255';
                $rules['taxes.*.type'] = 'required|in:Percentage,Flat';
                $rules['taxes.*.value'] = 'required|numeric|min:0';
                $rules['taxes.*.description'] = 'nullable|string|max:500';

                $messages['taxes.*.tax_application.required'] = 'Tax application is required';
                $messages['taxes.*.type.required'] = 'Tax type is required';
                $messages['taxes.*.type.in'] = 'Tax type must be Percentage or Flat';
                $messages['taxes.*.value.required'] = 'Tax value is required';
                $messages['taxes.*.value.numeric'] = 'Tax value must be a number';
                $messages['taxes.*.value.min'] = 'Tax value cannot be negative';
            }

            $validator = Validator::make($request->all(), $rules, $messages);

            $validator->after(function ($validator) use ($request, $configurableTypeId) {
                // Non-configurable
                if ((int)$request->product_type_id !== (int)$configurableTypeId) {
                    $total = is_numeric($request->total_stock) ? (float)$request->total_stock : null;
                    $low   = is_numeric($request->low_stock) ? (float)$request->low_stock : null;
                    if ($total !== null && $low !== null && $low > $total) {
                        $validator->errors()->add('low_stock', 'Low stock cannot be greater than total stock.');
                    }
                } else {
                    // Configurable variants
                    $variants = $request->get('variant_options', []);
                    if (is_array($variants)) {
                        foreach ($variants as $idx => $variant) {
                            $vTotal = isset($variant['total_stock']) && is_numeric($variant['total_stock']) ? (float)$variant['total_stock'] : null;
                            $vLow   = isset($variant['low_stock']) && is_numeric($variant['low_stock']) ? (float)$variant['low_stock'] : null;
                            if ($vTotal !== null && $vLow !== null && $vLow > $vTotal) {
                                $validator->errors()->add("variant_options.$idx.low_stock", 'Low stock cannot be greater than total stock for this variant.');
                            }
                        }
                    }
                }
            });

            if ($validator->fails()) {
                return response([
                    'errors' => $validator->errors()->messages(),
                    'code' => 422
                ], 422);
            }
        }


        DB::beginTransaction();

        try {
            // Step 1: Update the product
            $product = Product::findOrFail($id);

            $product->name = $request->product_name;
            $product->sku = $request->sku;
            $product->description = $request->description;
            $product->category_id = $request->category_id;
            $product->sub_category_id = $request->sub_category_id;
            $product->product_type_id = $request->product_type_id;
            // Deduplicate tags before saving
            $product->tags_id = $this->deduplicateTags($request->tags_id);
            $product->material_type_id = $request->material_type_id;
            $product->purity_karat_id = $request->purity_karat_id;
            $product->video_url = $request->video_url ?? null;
            $productType = ProductType::where('id', $request->product_type_id)->first();
            $product->metal_weight = $request->metal_weight;
            $product->base_price = $productType->name == 'Ready-made'
            ? round($request->unit_price, 2)
            : round($request->base_price, 2);
            $product->unit_price = $request->unit_price;
            $product->discount = $request->discount === null || $request->discount === 'null' || $request->discount === ''
            ? null
            : $request->discount;

            $product->final_price = round($request->final_price, 2);
            $product->status = $request->status;
            $product->variant_options = json_decode($request->variant_options_id, true) ?? [];
            $product->weight = $request->weight;
            $product->dimensions = $request->dimensions;
            $totalStock = $request->total_stock === 'null' ? null : $request->total_stock;
            $product->total_stock = $totalStock ?? 0;
            $product->available_stock = $totalStock ?? 0;
            $product->low_stock = $request->low_stock !== 'null' ? $request->low_stock : null;
            $product->seo_title = $request->seo_title;
            $product->meta_description = $request->meta_description;
            $product->meta_slug_url = $request->meta_slug_url;
            $product->unit = $request->unit !== 'null' ? $request->unit : null;
            $product->length = $request->length !== 'null' ? $request->length : null;
            $product->width  = $request->width  !== 'null' ? $request->width : null;
            $product->height = $request->height !== 'null' ? $request->height : null;

            if ($request->hasFile('main_image')) {
                $product->main_image = $request->file('main_image')->store('products', 'public');
            }

            // Store related products as array of IDs
            $product->related_product_ids = json_encode($request->related_product_ids ?? []);
            $product->you_may_like_product_ids = json_encode($request->you_may_like_product_ids ?? []);
            
            $product->is_featured = $request->is_featured ? 1 : 0;
            $product->variants = json_encode($request->variant_attribute_id) ;
            $product->visible_to = $request->visible_to;

            // Store partner ids only if visible to partner/both
            if (in_array($request->visible_to, ['partner', 'both'])) {
               
                if ($request->filled('visible_partner_ids')) {
                    $product->visible_partner_ids = is_array($request->visible_partner_ids)
                        ? $request->visible_partner_ids
                        : json_decode($request->visible_partner_ids, true);
                } else {
                    $product->visible_partner_ids = null;
                }
            } else {
                // remove any old partner data
                $product->visible_partner_ids = null;
            }
            $product->save();



            if (!empty($request->variant_options) && is_array($request->variant_options)) {
                foreach ($request->variant_options as $index => $variant) {

                    $childproduct = Product::find($variant['id']);
                    if (!$childproduct) {
                        $childproduct = new Product();
                    }

                    $childproduct->name = $variant['variant_name'];

                    $childproduct->sku = $variant['sku'] ?? null;
                    $childproduct->description = $request->description;
                    $childproduct->category_id = $request->category_id;
                    $childproduct->sub_category_id = $request->sub_category_id;
                    $childproduct->product_type_id = $request->product_type_id;
                    $childproduct->tags_id = $this->deduplicateTags($request->tags_id);
                    $childproduct->material_type_id = $request->material_type_id;
                    $childproduct->purity_karat_id = $request->purity_karat_id;

                    $childproduct->metal_weight = is_numeric($variant['metal_weight'] ?? null) ? $variant['metal_weight'] : null;
                    $childproduct->base_price = is_numeric($variant['base_price'] ?? null) ? $variant['base_price'] : null;
                    $childproduct->unit_price = is_numeric($variant['base_price'] ?? null) ? $variant['base_price'] : null;
                    $childproduct->discount =$request->discount === null || $request->discount === 'null' || $request->discount === ''
                    ? null
                    : $request->discount;
                    $childproduct->final_price = $variant['product_price'] ?? null;
                    $childproduct->status = $variant['status'] ?? 'A';
                    $childproduct->variants = $variant['attributes'];
                    $childproduct->weight = isset($variant['weight']) ? (float)$variant['weight'] : null;
                    $length = isset($variant['length']) ? (float)$variant['length'] : null;
                    $width = isset($variant['width']) ? (float)$variant['width'] : null;
                    $height = isset($variant['height']) ? (float)$variant['height'] : null;
                    $childproduct->dimensions = $length .'*'. $width .'*'. $height;

                    $childproduct->total_stock = $variant['total_stock'] ?? 0;
                    $childproduct->available_stock = $variant['total_stock'] ?? 0;
                    $childproduct->low_stock = $variant['low_stock'] ?? 0;
                    $childproduct->seo_title =  $variant['seo_title'] ?? null;
                    $childproduct->meta_description = $variant['meta_description'] ?? null;
                    $childproduct->meta_slug_url = $variant['meta_slug_url'] ?? null;
                    $childproduct->unit = $variant['unit'] ?? 'cm';
                    $childproduct->length = $length;
                    $childproduct->width  = $width;
                    $childproduct->height = $height;
                    $childproduct->visible_to = $request->visible_to;

                    // Store partner ids only if visible to partner/both
                    if (in_array($request->visible_to, ['partner', 'both'])) {
                        if ($request->filled('visible_partner_ids')) {
                            $childproduct->visible_partner_ids = is_array($request->visible_partner_ids)
                                ? $request->visible_partner_ids
                                : json_decode($request->visible_partner_ids, true);
                        } else {
                            $childproduct->visible_partner_ids = null;
                        }

                    } else {
                        // remove any old partner data
                        $childproduct->visible_partner_ids = null;
                    }
                    // Store primary image in product_variants table
                    if (isset($request->variants_product[$index]['primary_image'])) {
                        if ($childproduct->main_image != $request->variants_product[$index]['primary_image']) {
                            $primaryImage = $request->variants_product[$index]['primary_image'];
                            $primaryImagePath = $primaryImage->store('product_variants', 'public');
                            $childproduct->main_image  = $primaryImagePath;
                        }
                    }


                    // Store related products as array of IDs
                    $childproduct->related_product_ids = json_encode($request->related_product_ids ?? []);
                    $childproduct->you_may_like_product_ids = json_encode($request->you_may_like_product_ids ?? []);
                    
                    $childproduct->is_featured = $request->is_featured ? 1 : 0;
                    $childproduct->parent_id = $product->id;

                    $childproduct->save();

                    $attributes = json_decode($variant['attributes'], true);
                    ProductVariant::where('product_id', $childproduct->id)->delete();
                    foreach ($attributes as $attribute) {

                        $productvarient = new ProductVariant();
                        $productvarient->product_id = $childproduct->id;
                        $productvarient->attribute_id = $attribute['attribute_id'];
                        $productvarient->attribute_value = isset($attribute['value']) ? $attribute['value'] : $attribute['attribute_value'];
                        $productvarient->save();
                    }


                    if ($request->has('charges')) {
                        foreach ($request->charges as $chargeData) {
                            $existingCharge = ProductCharges::where('product_id', $childproduct->id)
                                ->where('charges', $chargeData['charges'])
                                ->where('type', $chargeData['type'])
                                ->first();

                            if ($existingCharge) {
                                $existingCharge->update([
                                    'value' => $chargeData['value'],
                                    'primary_cost' => $chargeData['primary_cost'],
                                    'description' => $chargeData['description'],
                                ]);
                            } else {
                                ProductCharges::create([
                                    'product_id' => $childproduct->id,
                                    'charges' => $chargeData['charges'],
                                    'type' => $chargeData['type'],
                                    'value' => $chargeData['value'],
                                    'primary_cost' => $chargeData['primary_cost'],
                                    'description' => $chargeData['description'],
                                ]);
                            }
                        }
                    }

                    if ($request->has('taxes')) {
                        // Pull variant-specific tax amounts sent from frontend
                        $variantTaxes = json_decode($variant['variant_taxes'] ?? '[]', true);
                        // Handle double-encoded JSON strings
                        if (is_string($variantTaxes)) {
                            $variantTaxes = json_decode($variantTaxes, true);
                        }
                        if (!is_array($variantTaxes)) {
                            $variantTaxes = [];
                        }
                        // dd($request->all());

                        if (!empty($request->taxes)) {

                            ProductTax::where('product_id', $childproduct->id)->delete();

                            // ✅ Get current variant’s taxes from variantscvb
                            $variantTaxes = $request->variants_tax[$index]['taxes'] ?? [];

                            foreach ($request->taxes as $taxIndex => $taxData) {

                                // ✅ Get matching amount from variant-level taxes
                                $variantAmount = isset($variantTaxes[$taxIndex]['amount'])
                                    ? (float) $variantTaxes[$taxIndex]['amount']
                                    : 0;
                                // ✅ Compute final amount (prefer variant’s actual value)
                                $amount = $variantAmount > 0
                                    ? round($variantAmount, 2)
                                    : (isset($taxData['amount'])
                                        ? round((float)$taxData['amount'], 2)
                                        : (isset($taxData['calculated_amount'])
                                            ? round((float)$taxData['calculated_amount'], 2)
                                            : 0));
                                // ✅ Save to DB
                                ProductTax::create([
                                    'product_id'      => $childproduct->id,
                                    'tax_application' => $taxData['tax_application'] ?? null,
                                    'type'            => $taxData['type'] ?? null,
                                    'value'           => is_numeric($taxData['value']) ? $taxData['value'] : null,
                                    'primary_cost'    => $taxData['primary_cost'] ?? null,
                                    'description'     => $taxData['description'] ?? null,
                                    'amount'          => $amount, // ✅ correct amount now stored
                                ]);
                            }
                        }

                    }




                    // Store additional images in product_media table
                    if (isset($request->variants_product[$index]['additional_images'])) {
                        foreach ($request->variants_product[$index]['additional_images'] as $additionalImage) {
                            $path = $additionalImage->store('product_media', 'public');
                            $childproduct->media()->create([
                                'media_type' => 'image',
                                'file_url' => $path
                            ]);
                        }
                    }
                    if (isset($request->variants_product[$index]['videos'])) {
                        foreach ($request->variants_product[$index]['videos'] as $video) {
                            $path = $video->store('product_media', 'public');
                            $childproduct->media()->create([
                                'media_type' => 'video',
                                'file_url' => $path
                            ]);
                        }
                    }

                }

            }

            if ($request->has('charges')) {
                // Delete all existing charges for this product
                ProductCharges::where('product_id', $product->id)->delete();

                // Re-create charges
                foreach ($request->charges as $chargeData) {
                    $existingCharge = ProductCharges::where('product_id', $product->id)
                                ->where('charges', $chargeData['charges'])
                                ->where('type', $chargeData['type'])
                                ->first();

                    if ($existingCharge) {
                        $existingCharge->update([
                            'value' => $chargeData['value'],
                            'primary_cost' => $chargeData['primary_cost'],
                            'description' => $chargeData['description'],
                        ]);
                    } else {
                        ProductCharges::create([
                            'product_id' => $product->id,
                            'charges' => $chargeData['charges'],
                            'type' => $chargeData['type'],
                            'value' => $chargeData['value'],
                            'primary_cost' => $chargeData['primary_cost'],
                            'description' => $chargeData['description'],
                        ]);
                    }
                }
            }


            if ($request->has('taxes')) {
                ProductTax::where('product_id', $product->id)->delete();
                foreach ($request->taxes as $tax) {
                    ProductTax::create([
                        'product_id'     => $product->id,
                        'tax_application' => $tax['tax_application'],
                        'type'           => $tax['type'],
                        'value'          => is_numeric($tax['value']) ? $tax['value'] : null,
                        'primary_cost'   => $tax['primary_cost'] ?? null,
                        'description'    => $tax['description'] ?? null,
                        'amount'    => $tax['amount'] ?? null,

                    ]);
                }
            }

            if ($request->has('media')) {
                foreach ($request->media as $index => $file) {
                    $path = $file->store('product_media', 'public');
                    $product->media()->create([
                        'media_type' => $request->media_types[$index],
                        'file_url' => $path
                    ]);
                }
            }

            // Handle deleted media
            if ($request->has('deleted_media')) {
                $product->media()->whereIn('id', $request->deleted_media)->delete();
            }



            DB::commit();

            return response()->json([
                'message' => 'Product and variants updated successfully',
                'product' => $product
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids');

        try {
            foreach ($ids as $id) {
                $this->deleteProductWithChildren($id);
            }

            return response()->json(['message' => 'Products deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Deletion failed'], 500);
        }
    }

    private function deleteProductWithChildren($id)
    {
        $children = Product::where('parent_id', $id)->get();

        foreach ($children as $child) {
            $this->deleteProductWithChildren($child->id);
        }

        Product::where('id', $id)->delete();
    }

    public function duplicate($id)
    {

        try {
            $id = (int) base64_decode($id, true) ?: $id;
            $original = Product::findOrFail($id);


            DB::beginTransaction();
            $skuResponse = $this->fetchSku()->getData();
            // Duplicate main product
            $duplicate = $original->replicate();
            $duplicate->name = $original->name . ' - Copy';
            $duplicate->sku = $skuResponse->status ? $skuResponse->data : null;
            $duplicate->views_count = 0;
            $duplicate->save();

            // Check and duplicate media
            if ($original->media) {
                foreach ($original->media as $media) {
                    $newMedia = $media->replicate();
                    $newMedia->product_id = $duplicate->id;
                    $newMedia->save();
                }
            }

            // Check and duplicate charges
            if ($original->charges) {
                foreach ($original->charges as $charge) {
                    $duplicate->charges()->create($charge->toArray());
                }
            }

            // Check and duplicate taxes
            if ($original->taxes) {
                foreach ($original->taxes as $tax) {
                    $duplicate->taxes()->create($tax->toArray());
                }
            }

            // Check and duplicate variants
            if ($original->variant) {
                foreach ($original->variant as $variants) {
                    $newVariant = $variants->replicate();
                    $newVariant->product_id = $duplicate->id;
                    $newVariant->save();

                    // Check and duplicate variant media
                    if ($variants->media) {
                        foreach ($variants->media as $vmedia) {
                            $newVariant->media()->create($vmedia->toArray());
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product duplicated successfully',
                'new_id' => $duplicate->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Duplication failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function changeProductStatus(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'status' => 'required'
        ]);

        $product->status = $request->status;
        $product->save();

        $product->childrenproducts()->update([
            'status' => $request->status
        ]);
        $statusText = match($product->status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'draft' => 'Draft',
            default => 'Active',
        };

        return response()->json([
            'message' => 'Status changed to ' . $statusText,
            'status'  => 'success'
        ]);
    }

    public function toggleFeatured($id)
    {
        $id = (int) base64_decode($id, true) ?: $id;
        $product = Product::findOrFail($id);

        $product->is_featured = $product->is_featured ? 0 : 1;
        $product->save();

        return response()->json([
            'is_featured' => $product->is_featured
        ]);
    }

    public function getVariantOptions(Request $request)
    {
        $attributeIds = explode(',', $request->ids);
        $subCategoryId = $request->sub_category_id;

        $rawValues = DB::table('attribute_values')
            ->whereIn('attribute_id', $attributeIds)
            ->select('attribute_id', 'value')
            ->orderBy('attribute_id')
            ->get();

        $options = [];
        foreach ($rawValues as $item) {
            $values = array_map('trim', explode(',', $item->value));
            foreach ($values as $val) {
                $options[] = [
                    'attribute_id' => $item->attribute_id,
                    'label' => $val,
                    'sku' => $val,
                    'metal_weight' => null,
                    'base_price' => null
                ];
            }
        }

        return response()->json($options);
    }


    public function getVariantOptionsFetchTable(Request $request)
    {
        $ids = explode(',', $request->query('ids', ''));
        $subCategoryId = $request->query('sub_category_id');

        $variantOptions = DB::table('size_masters')
            ->leftJoin('size_values', 'size_masters.id', '=', 'size_values.size_master_id')
            ->leftJoin('categories as cat', 'size_masters.category_id', '=', 'cat.id')
            ->leftJoin('categories as sub_cat', 'size_masters.sub_category_id', '=', 'sub_cat.id')
            ->whereIn('size_masters.attribute_id', $ids)
            ->when($subCategoryId, function ($query, $subCategoryId) {
                return $query->where('size_masters.sub_category_id', $subCategoryId);
            })
            ->select(
                'size_masters.sub_category_id',
                'size_masters.category_id',
                'cat.name as category_name',
                'sub_cat.name as sub_category_name',
                'size_values.value as label',
                'size_values.id as value',
            )
            ->orderBy('size_masters.attribute_id')
            ->get();

        return response()->json($variantOptions);
    }

    public function productListing(Request $request)
    {
        $products = Product::with([
                'category:id,name',
                'subcategory:id,name'
            ])
            ->leftJoin('product_types', 'product_types.id', '=', 'products.product_type_id')
            ->where('products.status', 'active')
            ->whereNull('products.deleted_at')
            ->whereIn('products.visible_to', ['customer', 'both'])
            ->where(function ($q) {
                $q->whereIn('product_types.name', ['Simple', 'Ready-made'])
                ->orWhere('products.parent_id', '!=', 0);
            })
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.slug',
                'products.description',
                'products.category_id',
                'products.sub_category_id',
                'products.base_price',
                'products.final_price',
                'products.main_image',
                'products.tags_id',
                'products.discount'
            ]);

        // Apply filters
        $products->when($request->has('name'), fn($q) => $q->where('products.name', 'LIKE', $request->name . '%'))
            ->when($request->has('category_id'), fn($q) => $q->where('products.category_id', $request->category_id))
            ->when($request->has('sub_category_id'), fn($q) => $q->where('products.sub_category_id', $request->sub_category_id));

        // Price Range Filter
        if ($request->has('priceRange')) {
            $priceRanges = explode(',', $request->get('priceRange'));
            if (!empty($priceRanges)) {
                $products->where(function ($q) use ($priceRanges) {
                    foreach ($priceRanges as $range) {
                        if ($range === '200000+') {
                            $q->orWhere('products.final_price', '>', 200000);
                        } else {
                            [$min, $max] = match ($range) {
                                '0-1000' => [0, 1000],
                                '1000-5000' => [1000, 5000],
                                '5000-10000' => [5000, 10000],
                                '10000-50000' => [10000, 50000],
                                '50000-100000' => [50000, 100000],
                                '100000-200000' => [100000, 200000],
                                default => [0, 0],
                            };
                            if ($min !== null && $max !== null) {
                                $q->orWhereBetween('products.final_price', [$min, $max]);
                            }
                        }
                    }
                });
            }
        }

        // Purity Filter
        if ($request->has('purity')) {

            $purities = explode(',', $request->get('purity'));
            // dd( $purities );
            if (!empty($purities)) {
                $products->whereIn('products.purity_karat_id', $purities);
            }
        }

        // Category filter (top-level categories)
        if ($request->has('products')) {
            $categoryIds = explode(',', $request->get('products'));
            $categoryIds = array_filter($categoryIds, 'is_numeric');
            if (!empty($categoryIds)) {
                $products->whereIn('products.category_id', $categoryIds);
            }
        }

        // Subcategory filter
        if ($request->has('subcategories')) {
            $subCategoryIds = explode(',', $request->get('subcategories'));
            $subCategoryIds = array_filter($subCategoryIds, 'is_numeric');
            if (!empty($subCategoryIds)) {
                $products->whereIn('products.sub_category_id', $subCategoryIds);
            }
        }

        // Gender filter
        if ($request->has('gender')) {
            $genders = (array) $request->get('gender');

            if (!empty($genders)) {
                $products->where(function ($q) use ($genders) {
                    foreach ($genders as $gender) {
                    $search = '%' . $gender . '%';
                        $q->orWhereRaw("JSON_SEARCH(CAST(products.tags_id AS JSON), 'one', ?) IS NOT NULL", [$search]);

                    }
                });
            }
        }





        // Occasion filter
        if ($request->has('occasion')) {
            $occasions = explode(',', $request->get('occasion'));
            $occasions = array_filter($occasions, 'is_numeric');
            if (!empty($occasions)) {
                // Get tag names from IDs
                $tagNames = DB::table('tags')
                    ->whereIn('id', $occasions)
                    ->pluck('name')
                    ->toArray();

                if (!empty($tagNames)) {
                    $products->where(function ($query) use ($tagNames) {
                        foreach ($tagNames as $tagName) {
                            $query->orWhere('products.tags_id', 'like', '%' . $tagName . '%');
                        }
                    });
                }
            }
        }

        // Sorting
        if ($request->has('sort')) {
            $sort = $request->get('sort');
            $products->orderBy(match ($sort) {
                'name_asc' => 'products.name',
                'name_desc' => 'products.name',
                'price_asc' => 'products.final_price',
                'price_desc' => 'products.final_price',
                'date_asc' => 'products.created_at',
                'date_desc' => 'products.created_at',
                default => 'products.id',
            }, match ($sort) {
                'name_desc', 'price_desc', 'date_desc' => 'desc',
                default => 'asc',
            });
        }

        $products = $products->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);

    }

    public function viewProduct($id)
    {
        try {
            // Decode base64 ID
            $id = (int) base64_decode($id, true) ?: $id;
            $user = Auth::user();
            
           $productQuery = Product::with([
                'category',
                'subcategory',
                'childrenproducts.charges',
                'childrenproducts.taxCharges',
                'childrenproducts.selected_attributes.attribute',
            ])
            ->leftJoin('product_types', 'product_types.id', '=', 'products.product_type_id')
            ->leftJoin('product_media', function ($join) {
                $join->on('products.id', '=', 'product_media.product_id')
                    ->where('product_media.media_type', '=', 'image');
            })
            ->leftJoin('product_media as product_video', function ($join) {
                    $join->on('products.id', '=', 'product_video.product_id')
                        ->where('product_video.media_type', '=', 'video');
            })
            ->select(
                'products.*',
                'product_types.name as product_type_name',
                DB::raw('GROUP_CONCAT(product_media.file_url) as other_images'),
                DB::raw('GROUP_CONCAT(product_video.file_url) as other_videos')
            )
            ->groupBy('products.id') ;

            $productQuery->where(function ($q) use ($user) {

                if ($user && $user->user_type === 'partner') {

                    $q->whereIn('products.visible_to', ['partner', 'both'])
                    ->where(function ($sub) use ($user) {
                        $sub->whereNull('products.visible_partner_ids')
                            ->orWhereJsonContains(
                                'products.visible_partner_ids',
                                (string) $user->id   // ✅ FIX
                            );
                    });

                } else {

                    $q->whereIn('products.visible_to', ['customer', 'both']);

                }
            });

            $product = $productQuery->findOrFail($id);

            $product->variant_attributes = collect();
            if ($product->parent_id != 0) {
                $parent = Product::find($product->parent_id);
                $variantOptions = $parent->variant_options;
                $variantAttrIds = collect($variantOptions)->pluck('attribute_id')->unique()->values()->toArray();
                if (count($variantAttrIds)) {
                    $attributes = DB::table('attributes')
                        ->leftJoin('attribute_options', 'attributes.data_type', '=', 'attribute_options.id')
                        ->whereIn('attributes.id', $variantAttrIds)
                        ->select('attributes.id', 'attributes.name', 'attribute_options.name as data_type')
                        ->get();

                    $variantOptions = DB::table('product_variants')
                        ->join('products', 'products.id', '=', 'product_variants.product_id')
                        ->where('products.parent_id', $product->parent_id)
                        ->where('products.status', 'active')
                        ->select('product_variants.attribute_id', 'product_variants.attribute_value as label', 'product_variants.attribute_value as sku', 'products.base_price', 'products.metal_weight',  'product_variants.product_id')
                        ->get();

                    $grouped = [];
                    foreach ($attributes as $attr) {
                        $options = $variantOptions->where('attribute_id', $attr->id)
                            ->map(function ($opt) {
                                return [
                                    "sku"             => $opt->sku,
                                    "label"           => $opt->label,
                                    "base_price"      => $opt->base_price,
                                    "attribute_id"    => $opt->attribute_id,
                                    "metal_weight"    => $opt->metal_weight,
                                    "product_id"      => $opt->product_id,
                                ];
                            })
                            // 🟢 Keep only unique labels (so 4,5 or Men,Women)
                            ->unique('label')
                            ->values();

                        $grouped[] = [
                            "id"        => $attr->id,
                            "name"      => $attr->name,
                            "data_type" => $attr->data_type,
                            "options"   => $options
                        ];
                    }

                    $product->variant_attributes = $grouped;

                }
            }
            

            $product->charges = ProductCharges::where('product_id', $product->id)->get();
            $product->taxes   = ProductTax::where('product_id', $product->id)->get();

            $totalCharges = 0;
            foreach ($product->charges as $charge) {
                $charge->calculated_amount = 0;
                if ($charge->type === "Percentage (%)") {
                    if ($charge->primary_cost == 'Metal Weight Cost') {
                        $metalRate = DB::table('metal_types')
                            ->where('metal_name_id', $product->material_type_id)
                            ->where('purity_id', $product->purity_karat_id)
                            ->whereNull('deleted_at')
                            ->where('status', 'active')
                            ->orderBy('updated_at', 'desc')
                            ->orderBy('id', 'desc')
                            ->value('price_per_gram');
                        $ProductChargesBasePrice = $metalRate * (float) $product->metal_weight;
                    } else {
                        $ProductChargesBasePrice = $product->base_price ?: $product->base_price;
                    }
                    $charge->calculated_amount = ($ProductChargesBasePrice * $charge->value) / 100;
                } else {
                    $charge->calculated_amount = (float)$charge->value;
                }
                $totalCharges += $charge->calculated_amount;
            }
            $product->subtotal = $product->base_price + $totalCharges;
            // Prepare API response
            return response()->json([
                "status" => true,
                "message" => "Product fetched successfully",
                "data" => [
                    "id" => $product->id,
                    "name" => $product->name,
                    "sku" => $product->sku,
                    "description" => $product->description,
                    "category" => $product->category->name ?? null,
                    "subcategory" => $product->subcategory->name ?? null,
                    "product_type" => $product->product_type_name,
                    "main_image" => $product->main_image ? asset('storage/'.$product->main_image) : null,
                    "other_images" => $product->other_images
                        ? collect(explode(',', $product->other_images))
                            ->map(fn($img) => asset('storage/'.$img))
                            ->values()
                        : [],
                    "other_videos" => $product->other_videos
                        ? collect(explode(',', $product->other_videos))
                            ->map(fn($vid) => asset('storage/'.$vid))
                            ->values()
                        : [],
                    "weight" => $product->weight,
                    "dimensions" => $product->dimensions,
                    "length" => $product->length,
                    "width" => $product->width,
                    "height" => $product->height,
                    "metal_weight" => $product->metal_weight,
                    "metal_rate" => $product->metal_rate,
                    "metal_value" => $product->metal_value,
                    "base_price" => $product->base_price,
                    "subtotal" =>  $product->subtotal,
                    "final_price" => $product->final_price,
                    "variants" => $product->variants,
                    "variant_attributes" => $product->variant_attributes, 
                    "charges" => $product->charges,
                    "taxes" => $product->taxes,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                "status" => false,
                "message" => "Product not found",
                "error" => $e->getMessage()
            ], 404);
        }
    }

    public function relatedProducts(Request $request)
    {
        try {
            $user = Auth::user();

            $productIds = $request->input('product_ids', []);

            if (empty($productIds)) {
                return response()->json([
                    'data' => [],
                    'message' => 'No product IDs provided',
                ]);
            }

            $relatedProductIds = Product::whereIn('id', $productIds)
                ->pluck('related_product_ids')
                ->filter()
                ->map(function ($ids) {
                    $decoded = json_decode($ids, true);

                    if (is_string($decoded)) {
                        $decoded = json_decode($decoded, true);
                    }

                    return is_array($decoded) ? $decoded : [];
                })
                ->flatten()
                ->unique()
                ->values()
                ->toArray();

            if (empty($relatedProductIds)) {
                return response()->json(['data' => []]);
            }

            /**
             * 🔹 Product Query
             */
            $productQuery = Product::query()
                ->leftJoin('product_types', 'product_types.id', '=', 'products.product_type_id')
                ->leftJoin('categories as cat', 'products.category_id', '=', 'cat.id')
                ->leftJoin('categories as sub_cat', 'products.sub_category_id', '=', 'sub_cat.id')
                ->leftJoin('order_products', 'products.id', '=', 'order_products.product_id')
                ->leftJoin('orders', function ($join) {
                    $join->on('orders.id', '=', 'order_products.order_id')
                        ->whereNotIn('orders.order_status', ['cancelled', 'returned']);
                })
                ->select(
                    'products.*',
                    'product_types.name as product_type_name',
                    'cat.name as category_name',
                    'sub_cat.name as sub_category_name',
                    DB::raw('(SELECT COUNT(*) FROM products as child WHERE child.parent_id = products.id) as child_count'),
                    DB::raw('CAST(GREATEST(products.total_stock - COALESCE(SUM(order_products.quantity), 0), 0) AS UNSIGNED) as available_stock_data'),
                    DB::raw('COUNT(DISTINCT orders.id) as total_orders_count')
                )
                ->whereIn('products.id', $relatedProductIds)
                ->groupBy(
                    'products.id',
                    'product_types.name',
                    'cat.name',
                    'sub_cat.name'
                );

            /**
             * 🔹 Visibility Rules (Guest / Customer / Partner)
             */
            $productQuery->where(function ($q) use ($user) {

                // ✅ Guest user
                if (!$user) {
                    $q->whereIn('products.visible_to', ['customer', 'both']);
                    return;
                }else{
                    $user = User::find($user->id);
                }

                // ✅ Partner user
                if (method_exists($user, 'isPartner') && $user->isPartner()) {
                    $q->whereIn('products.visible_to', ['partner', 'both'])
                    ->where(function ($sub) use ($user) {
                        $sub->whereNull('products.visible_partner_ids')
                            ->orWhereJsonContains(
                                'products.visible_partner_ids',
                                (string) $user->id
                            );
                    });
                    return;
                }

                // ✅ Logged-in customer
                $q->whereIn('products.visible_to', ['customer', 'both']);
            });

            /**
             * 🔹 Fetch products (no pagination)
             */
            $products = $productQuery
                ->with('variant')
                ->get();

            return response()->json([
                'data'   => $products,
                'total'  => $products->count(),
                'status' => 200,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Unable to fetch related products',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function partnerproductListing()
    {
        $user = Auth::user();

        $subcategories = Category::whereNotNull('parent_id')
            ->withWhereHas('products', function ($query) use ($user) {

                $query->leftJoin('product_types', 'product_types.id', '=', 'products.product_type_id')
                    ->where('products.status', 'active')
                    ->whereNull('products.deleted_at')

                    ->where(function ($q) {
                        $q->whereIn('product_types.name', ['Simple', 'Ready-made'])
                        ->orWhere('products.parent_id', '!=', 0);
                    })

                    ->where(function ($q) use ($user) {

                        if ($user && $user->user_type === 'partner') {

                            $q->whereIn('products.visible_to', ['partner', 'both'])
                            ->where(function ($sub) use ($user) {
                                $sub->whereNull('products.visible_partner_ids')
                                    ->orWhereJsonContains(
                                        'products.visible_partner_ids',
                                        (string) $user->id   // ✅ FIX
                                    );
                            });

                        } else {
                            $q->whereIn('products.visible_to', ['customer', 'both']);
                        }
                    })

                    ->select([
                        'products.id',
                        'products.name',
                        'products.sku',
                        'products.slug',
                        'products.description',
                        'products.category_id',
                        'products.sub_category_id',
                        'products.base_price',
                        'products.final_price',
                        'products.main_image',
                        'products.tags_id',
                    ]);
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subcategories
        ]);
    }

}

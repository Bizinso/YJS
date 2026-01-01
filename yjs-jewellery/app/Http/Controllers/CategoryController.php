<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Auth;
class CategoryController extends Controller
{
    public $output;

    public function index(Request $request)
    {
        try {
            $sortBy = $request->get('sortBy', 'id');
            $sortDesc = $request->get('sortDesc') === 'true';
            $direction = $sortDesc ? 'DESC' : 'ASC';

            $categories = Category::whereNull('parent_id')
                ->when($request->name, function ($query, $name) {
                    $query->where('name', 'like', "%$name%");
                })
                ->when($request->description, function ($query, $description) {
                    $query->where('description', 'like', "%$description%");
                })
                ->when($request->globalSearch, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%")
                            ->orWhere('description', 'like', "%$search%")
                            ->orWhere('status', 'like', "%$search%");
                    });
                })
                ->select('*', DB::raw('count(*) OVER() AS total_row_count'))
                ->orderBy($sortBy, $direction)
                ->limit($request->perPage)
                ->offset(($request->page - 1) * $request->perPage)
                ->get();

            return response($categories, 200);
        } catch (\Exception $e) {
            report($e);
            return response(['error' => 'Something went wrong'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $rules = [
                'name' => [
                    'required',
                    'regex:/^[A-Za-z ]+$/',
                    Rule::unique('categories')
                        ->where(function ($query) use ($request) {
                            return $query
                                ->where('parent_id', $request->parent_id)
                                ->whereNull('deleted_at');
                        }),
                ],
            ];

            $messages = [
                'name.required' => 'Please enter a category name.',
                'name.regex' => 'Category name must contain alphabets only.',
                'name.unique' => 'The category already exists under this parent.',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return response([
                    'errors' => $validator->errors()->messages(),
                    'code' => 422
                ], 422);
            }

            $existing = Category::where('name', $request->name)
                ->where('parent_id', $request->parent_id)
                ->whereNull('deleted_at')
                ->exists();

            if ($existing) {
                return response([
                    'errors' => ['name' => ['The name has already been taken.']],
                    'code' => 422
                ], 422);
            }

            $category = new Category();
            $category->name = ucwords(strtolower($request->name));
            $category->slug = Str::slug($request->name);
            $category->description = $request->description;
            $category->parent_id = $request->parent_id;
            $category->status = $request->status ?? 'A';
            $category->save();

            $id = $category->id;
            $categoryData = Category::getCategoryInfo($id);

            return response([
                'status' => 'success',
                'message' => 'Category added successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response([
                'error' => 'Something went wrong. ' . $e->getMessage(),
                'code' => 500
            ], 500);
        }
    }

    public function changeStatus($id)
    {
        $categories = Category::findOrFail($id);
        $categories->status = $categories->status === 'A' ? 'I' : 'A';
        $categories->save();
        $statusText = $categories->status === 'A' ? 'Active' : 'Inactive';
        return response()->json([
            'message' => 'Status changed to ' . $statusText,
            'status' => 'success'
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $existing = Category::where('name', $request->name)
                ->where('id', '!=', $id)
                ->where('parent_id', $request->parent_id)
                ->whereNull('deleted_at')
                ->exists();

            if ($existing) {
                return response([
                    'errors' => ['name' => ['The name has already been taken.']],
                    'code' => 422
                ], 422);
            }
            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'regex:/^[A-Za-z ]+$/',
                    Rule::unique('categories', 'name')
                        ->ignore($id)
                        ->where(function ($query) use ($request) {
                            return $query
                                ->where('parent_id', $request->parent_id)
                                ->whereNull('deleted_at');
                        }),
                ],
            ], [
                'name.required' => 'Please enter a category name.',
                'name.regex' => 'Category name must contain alphabets only.',
                'name.unique' => 'The category already exists under this parent.',
            ]);

            if ($validator->fails()) {
                return response([
                    'errors' => $validator->errors()->messages(),
                    'code' => 422
                ], 422);
            }
           
            $category = Category::find($id);

            if (!$category) {
                return response([
                    'message' => 'Category not found',
                    'status' => 'error'
                ], 404);
            }

            $category->name = $request->name;
            $category->description = $request->description;
            $category->parent_id = $request->parent_id;
            $category->status = $request->status ?? 'A';
            $category->save();

            $categoryId = $category->id;

            return response([
                'data' => 'Updated Successfully!',
                'status' => 'success'
            ], 200);
        } catch (\Exception $e) {
            return response([
                'error' => 'Something went wrong. ' . $e->getMessage(),
                'code' => 500
            ], 500);
        }
    }

    public function destroy($id)
    {
        $categories = Category::findOrFail($id);
        $categories->delete();
        return response()->json(['status' => 'deleted', 'message' => 'Deleted Successfully!'], 200);
    }

    public function subCategoryOptions()
    {
        $categories = Category::select('id', 'name', 'parent_id')->get();

        $tree = $this->buildCategoryTree($categories);

        return response()->json(['data' => $tree, 'status' => 'success'], 200);
    }

    private function buildCategoryTree($categories, $parentId = null)
    {
        $tree = [];

        foreach ($categories->where('parent_id', $parentId) as $category) {
            $children = $this->buildCategoryTree($categories, $category->id);

            $tree[] = [
                'id' => $category->id,
                'name' => $category->name,
                'children' => $children
            ];
        }

        return $tree;
    }

    public function subCategoryIndex(Request $request)
    {
        try {
            $sortBy = $request->get('sortBy', 'id');
            $sortDesc = $request->get('sortDesc') === 'true';
            $direction = $sortDesc ? 'DESC' : 'ASC';


            $columnMap = [
                'name' => 'categories.name',
                'description' => 'categories.description',
                'status' => 'categories.status',
                'parent_category' => 'parent.name', // important!
                'id' => 'categories.id',
            ];

            $sortColumn = $columnMap[$sortBy] ?? 'categories.id';

            $this->output = [];

            $subCategories = Category::whereNotNull('categories.parent_id')
                ->whereRaw('CAST(categories.parent_id AS UNSIGNED) > 0')
                ->leftJoin('categories as parent', 'categories.parent_id', '=', 'parent.id')
                ->when($request->name, function ($query, $name) {
                    $query->where('categories.name', 'like', "%$name%");
                })
                ->when($request->description, function ($query, $description) {
                    $query->where('categories.description', 'like', "%$description%");
                })
                ->when($request->parent_name, function ($query, $parentName) {
                    $query->where('parent.name', 'like', "%$parentName%");
                })
                ->when($request->globalSearch, function ($query, $globalSearch) {
                    $query->where(function ($subQuery) use ($globalSearch) {
                        $subQuery->where('categories.name', 'like', "%$globalSearch%")
                            ->orWhere('categories.description', 'like', "%$globalSearch%")
                            ->orWhere('parent.name', 'like', "%$globalSearch%");
                    });
                })
                ->select(
                    'categories.*',
                    'parent.name as parent_name',
                    DB::raw('count(*) OVER() AS total_row_count')
                )
                ->orderBy($sortColumn, $direction)
                ->limit($request->perPage)
                ->offset(($request->page - 1) * $request->perPage)
                ->get();

            foreach ($subCategories as $category) {
                $this->output[] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'parent_category' => $category->parent_name ?? '-',
                    'description' => $category->description,
                    'status' => $category->status,
                    'total_row_count' => $category->total_row_count
                ];
            }

            return response($this->output, 200);
        } catch (\Exception $e) {
            report($e);
            return response(['error' => 'Something went wrong'], 500);
        }
    }

    public function getcategoryproducts($slug)
    {
        $user = Auth::user();

        // 1️⃣ Fetch category by slug
        $categoryids = Category::where('slug', $slug)
            ->where('status', 'A')
            ->pluck('id');

        // 2️⃣ Fetch products under this category (same rules as partnerproductListing)
        $products = Product::leftJoin('product_types', 'product_types.id', '=', 'products.product_type_id')
            ->whereIn('products.sub_category_id', $categoryids)
            ->where('products.status', 'active')
            ->whereNull('products.deleted_at')

            // ✅ Product type rules
            ->where(function ($q) {
                $q->whereIn('product_types.name', ['Simple', 'Ready-made'])
                ->orWhere('products.parent_id', '!=', 0);
            })

            // ✅ Visibility rules
            ->where(function ($q) use ($user) {

                // 🏢 Partner logged in
                if ($user && $user->user_type === 'partner') {

                    $q->whereIn('products.visible_to', ['partner', 'both'])
                    ->where(function ($sub) use ($user) {
                        $sub->whereNull('products.visible_partner_ids')
                            ->orWhereJsonContains(
                                'products.visible_partner_ids',
                                (string) $user->id   
                            );
                    });
                }
                // 👤 Customer / guest
                else {
                    $q->whereIn('products.visible_to', ['customer', 'both']);
                }
            })

            // ✅ Required columns only
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
            ])

            ->orderByDesc('products.id')
            ->get();

        $category =  Category::where('slug', $slug)
                    ->where('status', 'A')
                    ->first();

        return response()->json([
            'success'  => true,
            'category' => $category,
            'products' => $products,
        ]);
    }

    public function getCategoryOptions()
    {
    
        $categories = Category::select('id as value', 'name as label')
            ->whereNotNull('parent_id')
            ->orderBy('name', 'ASC')
            ->get();

        return response()->json([
                'status' => 'success',
                'data'=>$categories
            ], 200);
        
    }

    public function Occasion(){
        try {
            $tags = Tag::select('id as value', 'name as label')
                ->orderBy('name', 'ASC')
                ->get();

            return response()->json([
                'data' => $tags,
                'status' => 'success'
            ], 200);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong',
            ], 500);
        }
    }
}

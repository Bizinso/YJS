<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImportJob;
use App\Models\ProductSeo;
use App\Models\ProductStatusHistory;
use App\Models\BulkPriceUpdate;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Admin Product Bulk Controller
 *
 * Handles bulk product operations.
 */
class AdminProductBulkController extends Controller
{
    // ============ BULK IMPORT ============

    /**
     * Get import template.
     */
    public function getImportTemplate(): JsonResponse
    {
        $columns = [
            'sku' => 'Product SKU (required, unique)',
            'product_title' => 'Product Title (required)',
            'short_description' => 'Short Description',
            'long_description' => 'Long Description',
            'category_id' => 'Category ID',
            'base_price' => 'Base Price (required)',
            'selling_price' => 'Selling Price',
            'stock_quantity' => 'Stock Quantity',
            'low_stock_threshold' => 'Low Stock Threshold',
            'weight' => 'Weight (grams)',
            'metal_type' => 'Metal Type',
            'purity' => 'Purity',
            'status' => 'Status (A=Active, I=Inactive, D=Draft)',
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'columns' => $columns,
                'sample_row' => [
                    'sku' => 'RING-001',
                    'product_title' => 'Gold Diamond Ring',
                    'short_description' => 'Beautiful gold ring',
                    'category_id' => '1',
                    'base_price' => '15000',
                    'selling_price' => '14500',
                    'stock_quantity' => '10',
                    'status' => 'A',
                ],
            ],
        ]);
    }

    /**
     * Upload and validate import file.
     */
    public function uploadImport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
            'update_existing' => 'nullable|boolean',
            'skip_errors' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('imports/products', $fileName);

        // Create import job
        $job = ProductImportJob::create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'status' => ProductImportJob::STATUS_PENDING,
            'options' => [
                'update_existing' => $request->boolean('update_existing', false),
                'skip_errors' => $request->boolean('skip_errors', true),
            ],
            'created_by' => auth()->id(),
        ]);

        // Parse file for preview (first 10 rows)
        $preview = $this->parseImportFile($path, 10);
        $job->total_rows = $preview['total_rows'];
        $job->save();

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'data' => [
                'job_id' => $job->id,
                'file_name' => $job->file_name,
                'total_rows' => $preview['total_rows'],
                'preview' => $preview['rows'],
                'columns' => $preview['columns'],
            ],
        ]);
    }

    /**
     * Process import job.
     */
    public function processImport(int $jobId): JsonResponse
    {
        $job = ProductImportJob::find($jobId);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Import job not found',
            ], 404);
        }

        if ($job->status !== ProductImportJob::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Import job already processed',
            ], 400);
        }

        $job->start();

        // Process in background would be better, but for now sync
        $result = $this->executeImport($job);

        return response()->json([
            'success' => true,
            'message' => 'Import completed',
            'data' => [
                'job_id' => $job->id,
                'status' => $job->status,
                'success_count' => $job->success_count,
                'error_count' => $job->error_count,
                'errors' => $job->errors,
            ],
        ]);
    }

    /**
     * Get import job status.
     */
    public function importStatus(int $jobId): JsonResponse
    {
        $job = ProductImportJob::with('createdByUser:id,first_name,last_name')
            ->find($jobId);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Import job not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $job,
        ]);
    }

    /**
     * List import history.
     */
    public function importHistory(Request $request): JsonResponse
    {
        $jobs = ProductImportJob::with('createdByUser:id,first_name,last_name')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }

    // ============ BULK EXPORT ============

    /**
     * Export products.
     */
    public function export(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'format' => 'nullable|in:xlsx,csv',
            'product_ids' => 'nullable|array',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'nullable|in:A,I,D',
            'columns' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Product::query();

        if ($request->product_ids) {
            $query->whereIn('id', $request->product_ids);
        }
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $products = $query->get();

        // For now, return JSON data - in production, generate actual file
        $exportData = $products->map(function ($product) use ($request) {
            $columns = $request->columns ?? [
                'id', 'sku', 'product_title', 'category_id', 'base_price',
                'selling_price', 'stock_quantity', 'status', 'created_at'
            ];

            return collect($product->toArray())->only($columns);
        });

        return response()->json([
            'success' => true,
            'data' => $exportData,
            'count' => $products->count(),
        ]);
    }

    // ============ BULK PRICE UPDATE ============

    /**
     * Preview bulk price update.
     */
    public function previewPriceUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric',
            'apply_to' => 'required|in:all,category,selected',
            'category_id' => 'required_if:apply_to,category|nullable|exists:categories,id',
            'product_ids' => 'required_if:apply_to,selected|nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Product::where('status', 'A');

        if ($request->apply_to === 'category') {
            $query->where('category_id', $request->category_id);
        } elseif ($request->apply_to === 'selected') {
            $query->whereIn('id', $request->product_ids);
        }

        $products = $query->limit(100)->get(['id', 'sku', 'product_title', 'base_price', 'selling_price']);

        $preview = $products->map(function ($product) use ($request) {
            $newPrice = $request->type === 'percentage'
                ? $product->selling_price * (1 + ($request->value / 100))
                : $product->selling_price + $request->value;

            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'product_title' => $product->product_title,
                'current_price' => $product->selling_price,
                'new_price' => round($newPrice, 2),
                'difference' => round($newPrice - $product->selling_price, 2),
            ];
        });

        $totalAffected = $request->apply_to === 'all'
            ? Product::where('status', 'A')->count()
            : $products->count();

        return response()->json([
            'success' => true,
            'data' => [
                'preview' => $preview,
                'total_affected' => $totalAffected,
                'type' => $request->type,
                'value' => $request->value,
            ],
        ]);
    }

    /**
     * Apply bulk price update.
     */
    public function applyPriceUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric',
            'apply_to' => 'required|in:all,category,selected',
            'category_id' => 'required_if:apply_to,category|nullable|exists:categories,id',
            'product_ids' => 'required_if:apply_to,selected|nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $query = Product::where('status', 'A');

            if ($request->apply_to === 'category') {
                $query->where('category_id', $request->category_id);
            } elseif ($request->apply_to === 'selected') {
                $query->whereIn('id', $request->product_ids);
            }

            $products = $query->get();

            foreach ($products as $product) {
                $oldPrice = $product->selling_price;
                $newPrice = $request->type === 'percentage'
                    ? $oldPrice * (1 + ($request->value / 100))
                    : $oldPrice + $request->value;

                $product->selling_price = round($newPrice, 2);
                $product->save();
            }

            // Log the bulk update
            $update = BulkPriceUpdate::create([
                'type' => $request->type,
                'value' => $request->value,
                'apply_to' => $request->apply_to,
                'product_ids' => $request->product_ids,
                'category_id' => $request->category_id,
                'status' => BulkPriceUpdate::STATUS_APPLIED,
                'products_affected' => $products->count(),
                'created_by' => auth()->id(),
                'applied_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Price updated for {$products->count()} products",
                'data' => [
                    'update_id' => $update->id,
                    'products_affected' => $products->count(),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update prices: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ============ BULK STATUS UPDATE ============

    /**
     * Bulk update product status.
     */
    public function bulkStatusUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
            'status' => 'required|in:A,I,D',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $products = Product::whereIn('id', $request->product_ids)->get();

        foreach ($products as $product) {
            $oldStatus = $product->status;
            $product->status = $request->status;
            $product->save();

            ProductStatusHistory::log(
                $product->id,
                $oldStatus,
                $request->status,
                $request->reason
            );
        }

        return response()->json([
            'success' => true,
            'message' => "Status updated for {$products->count()} products",
        ]);
    }

    // ============ SEO MANAGEMENT ============

    /**
     * Get product SEO.
     */
    public function getSeo(int $productId): JsonResponse
    {
        $product = Product::find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $seo = ProductSeo::where('product_id', $productId)->first();

        if (!$seo) {
            $seo = ProductSeo::generateFromProduct($product);
        }

        return response()->json([
            'success' => true,
            'data' => $seo,
        ]);
    }

    /**
     * Update product SEO.
     */
    public function updateSeo(Request $request, int $productId): JsonResponse
    {
        $product = Product::find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string|max:500',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|url|max:500',
            'canonical_url' => 'nullable|url|max:500',
            'robots' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $seo = ProductSeo::updateOrCreate(
            ['product_id' => $productId],
            $request->only([
                'meta_title', 'meta_description', 'meta_keywords',
                'og_title', 'og_description', 'og_image',
                'canonical_url', 'robots',
            ])
        );

        return response()->json([
            'success' => true,
            'message' => 'SEO updated',
            'data' => $seo,
        ]);
    }

    /**
     * Bulk generate SEO.
     */
    public function bulkGenerateSeo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_ids' => 'nullable|array',
            'overwrite' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Product::query();

        if ($request->product_ids) {
            $query->whereIn('id', $request->product_ids);
        }

        $products = $query->get();
        $generated = 0;

        foreach ($products as $product) {
            $existing = ProductSeo::where('product_id', $product->id)->first();

            if (!$existing || $request->boolean('overwrite')) {
                ProductSeo::generateFromProduct($product);
                $generated++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Generated SEO for {$generated} products",
        ]);
    }

    // ============ MEDIA MANAGEMENT ============

    /**
     * Reorder product media.
     */
    public function reorderMedia(Request $request, int $productId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'media_ids' => 'required|array|min:1',
            'media_ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = Product::find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        // Update sort order for each media
        foreach ($request->media_ids as $index => $mediaId) {
            DB::table('product_media')
                ->where('id', $mediaId)
                ->where('product_id', $productId)
                ->update(['sort_order' => $index]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Media order updated',
        ]);
    }

    // ============ CLONE PRODUCT ============

    /**
     * Clone a product.
     */
    public function cloneProduct(Request $request, int $productId): JsonResponse
    {
        $product = Product::with(['media', 'variants'])->find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        DB::beginTransaction();
        try {
            $newProduct = $product->replicate();
            $newProduct->sku = $product->sku . '-COPY-' . time();
            $newProduct->product_title = $product->product_title . ' (Copy)';
            $newProduct->status = 'D'; // Draft
            $newProduct->save();

            // Clone SEO
            $seo = ProductSeo::where('product_id', $productId)->first();
            if ($seo) {
                $newSeo = $seo->replicate();
                $newSeo->product_id = $newProduct->id;
                $newSeo->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product cloned successfully',
                'data' => [
                    'original_id' => $productId,
                    'new_id' => $newProduct->id,
                    'new_sku' => $newProduct->sku,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to clone product: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ============ HELPERS ============

    /**
     * Parse import file.
     */
    private function parseImportFile(string $path, int $limit = null): array
    {
        // Simplified CSV parsing - in production use a library like Maatwebsite/Excel
        $fullPath = Storage::path($path);
        $rows = [];
        $columns = [];
        $totalRows = 0;

        if (($handle = fopen($fullPath, 'r')) !== false) {
            $lineNumber = 0;
            while (($data = fgetcsv($handle)) !== false) {
                if ($lineNumber === 0) {
                    $columns = $data;
                } else {
                    if ($limit === null || $lineNumber <= $limit) {
                        $rows[] = array_combine($columns, $data);
                    }
                    $totalRows++;
                }
                $lineNumber++;
            }
            fclose($handle);
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
            'total_rows' => $totalRows,
        ];
    }

    /**
     * Execute import job.
     */
    private function executeImport(ProductImportJob $job): bool
    {
        $data = $this->parseImportFile($job->file_path);
        $options = $job->options ?? [];
        $errors = [];

        foreach ($data['rows'] as $index => $row) {
            try {
                $existing = Product::where('sku', $row['sku'] ?? '')->first();

                if ($existing && !($options['update_existing'] ?? false)) {
                    $errors[] = "Row {$index}: SKU {$row['sku']} already exists";
                    $job->incrementProgress(false);
                    continue;
                }

                $productData = [
                    'sku' => $row['sku'] ?? null,
                    'product_title' => $row['product_title'] ?? null,
                    'short_description' => $row['short_description'] ?? null,
                    'long_description' => $row['long_description'] ?? null,
                    'category_id' => $row['category_id'] ?? null,
                    'base_price' => $row['base_price'] ?? 0,
                    'selling_price' => $row['selling_price'] ?? $row['base_price'] ?? 0,
                    'stock_quantity' => $row['stock_quantity'] ?? 0,
                    'status' => $row['status'] ?? 'D',
                ];

                if ($existing) {
                    $existing->update($productData);
                } else {
                    Product::create($productData);
                }

                $job->incrementProgress(true);
            } catch (\Exception $e) {
                $errors[] = "Row {$index}: " . $e->getMessage();
                $job->incrementProgress(false);

                if (!($options['skip_errors'] ?? true)) {
                    break;
                }
            }
        }

        $job->errors = $errors;
        $job->complete();

        return $job->error_count === 0;
    }
}

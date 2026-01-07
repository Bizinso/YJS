<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Admin Inventory Controller
 *
 * Handles inventory management including stock adjustments,
 * low stock alerts, and inventory reports.
 *
 * @package App\Http\Controllers\Admin
 */
class AdminInventoryController extends Controller
{
    /**
     * Get inventory list with stock levels.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::select([
            'id', 'name', 'sku', 'main_image',
            'available_stock', 'initial_stock',
            'base_price', 'category_id', 'status'
        ])->with('category:id,name');

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by stock level
        if ($request->has('stock_status')) {
            switch ($request->stock_status) {
                case 'out_of_stock':
                    $query->where('available_stock', 0);
                    break;
                case 'low_stock':
                    $query->whereBetween('available_stock', [1, 10]);
                    break;
                case 'in_stock':
                    $query->where('available_stock', '>', 10);
                    break;
            }
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by name or SKU
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->input('sort_by', 'available_stock');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortField, $sortOrder);

        $perPage = $request->input('per_page', 20);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Get low stock products.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function lowStock(Request $request): JsonResponse
    {
        $threshold = $request->input('threshold', 10);

        $lowStockProducts = Product::select([
            'id', 'name', 'sku', 'main_image',
            'available_stock', 'initial_stock',
            'category_id', 'status'
        ])
            ->with('category:id,name')
            ->where('available_stock', '<=', $threshold)
            ->where('status', 'active')
            ->orderBy('available_stock', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'threshold' => $threshold,
            'count' => $lowStockProducts->count(),
            'data' => $lowStockProducts,
        ]);
    }

    /**
     * Get out of stock products.
     *
     * @return JsonResponse
     */
    public function outOfStock(): JsonResponse
    {
        $outOfStockProducts = Product::select([
            'id', 'name', 'sku', 'main_image',
            'initial_stock', 'available_stock', 'category_id', 'status'
        ])
            ->with('category:id,name')
            ->where('available_stock', 0)
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count' => $outOfStockProducts->count(),
            'data' => $outOfStockProducts,
        ]);
    }

    /**
     * Adjust stock for a product.
     *
     * @param Request $request
     * @param int $productId
     * @return JsonResponse
     */
    public function adjustStock(Request $request, int $productId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'adjustment' => 'required|integer',
            'reason' => 'required|string|max:500',
            'reference' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = Product::find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $adjustment = (int) $request->adjustment;
        $oldStock = $product->available_stock;
        $newStock = $oldStock + $adjustment;

        if ($newStock < 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot reduce stock below 0. Current stock: {$oldStock}",
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Update stock
            $product->available_stock = $newStock;
            $product->save();

            // Log the adjustment using activity log
            activity('inventory')
                ->performedOn($product)
                ->withProperties([
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'adjustment' => $adjustment,
                    'reason' => $request->reason,
                    'reference' => $request->reference,
                    'adjusted_by' => auth()->id(),
                ])
                ->log("Stock adjusted by {$adjustment}");

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully.',
                'data' => [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'old_stock' => $oldStock,
                    'adjustment' => $adjustment,
                    'new_stock' => $newStock,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust stock.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set stock level directly.
     *
     * @param Request $request
     * @param int $productId
     * @return JsonResponse
     */
    public function setStock(Request $request, int $productId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'stock' => 'required|integer|min:0',
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = Product::find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $oldStock = $product->available_stock;
        $newStock = (int) $request->stock;
        $adjustment = $newStock - $oldStock;

        DB::beginTransaction();
        try {
            $product->available_stock = $newStock;
            $product->save();

            activity('inventory')
                ->performedOn($product)
                ->withProperties([
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'adjustment' => $adjustment,
                    'reason' => $request->reason,
                    'type' => 'set_stock',
                    'adjusted_by' => auth()->id(),
                ])
                ->log("Stock set to {$newStock}");

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock updated successfully.',
                'data' => [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update stock.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk stock update.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'updates' => 'required|array|min:1',
            'updates.*.product_id' => 'required|exists:products,id',
            'updates.*.stock' => 'required|integer|min:0',
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $updated = [];
            $errors = [];

            foreach ($request->updates as $update) {
                $product = Product::find($update['product_id']);

                if (!$product) {
                    $errors[] = [
                        'product_id' => $update['product_id'],
                        'error' => 'Product not found',
                    ];
                    continue;
                }

                $oldStock = $product->available_stock;
                $newStock = (int) $update['stock'];
                $adjustment = $newStock - $oldStock;

                $product->available_stock = $newStock;
                $product->save();

                activity('inventory')
                    ->performedOn($product)
                    ->withProperties([
                        'old_stock' => $oldStock,
                        'new_stock' => $newStock,
                        'adjustment' => $adjustment,
                        'reason' => $request->reason,
                        'type' => 'bulk_update',
                        'adjusted_by' => auth()->id(),
                    ])
                    ->log("Bulk stock update: {$oldStock} -> {$newStock}");

                $updated[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($updated) . ' products updated successfully.',
                'data' => [
                    'updated' => $updated,
                    'errors' => $errors,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update stock.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get stock history for a product.
     *
     * @param Request $request
     * @param int $productId
     * @return JsonResponse
     */
    public function stockHistory(Request $request, int $productId): JsonResponse
    {
        $product = Product::find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $history = DB::table('activity_log')
            ->where('subject_type', Product::class)
            ->where('subject_id', $productId)
            ->where('log_name', 'inventory')
            ->orderBy('created_at', 'desc')
            ->limit($request->input('limit', 50))
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'description' => $log->description,
                    'properties' => json_decode($log->properties, true),
                    'created_at' => $log->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'current_stock' => $product->available_stock,
            ],
            'history' => $history,
        ]);
    }

    /**
     * Get inventory summary statistics.
     *
     * @return JsonResponse
     */
    public function summary(): JsonResponse
    {
        $stats = [
            'total_products' => Product::count(),
            'active_products' => Product::where('status', 'active')->count(),
            'out_of_stock' => Product::where('available_stock', 0)->count(),
            'low_stock' => Product::whereBetween('available_stock', [1, 10])->count(),
            'total_stock_value' => Product::whereRaw('available_stock > 0')
                ->selectRaw('SUM(available_stock * base_price) as value')
                ->value('value') ?? 0,
        ];

        // Stock by category
        $stockByCategory = Product::select(
            'categories.name as category_name',
            DB::raw('SUM(products.available_stock) as total_stock'),
            DB::raw('COUNT(products.id) as product_count')
        )
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_stock')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => $stats,
                'by_category' => $stockByCategory,
            ],
        ]);
    }
}

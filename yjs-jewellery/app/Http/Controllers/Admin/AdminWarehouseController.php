<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\InventoryCount;
use App\Models\InventoryCountItem;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\StockAlert;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminWarehouseController extends Controller
{
    /**
     * Warehouse dashboard overview.
     */
    public function dashboard(): JsonResponse
    {
        $stats = [
            'total_warehouses' => Warehouse::count(),
            'active_warehouses' => Warehouse::active()->count(),
            'pending_transfers' => StockTransfer::pending()->count(),
            'in_transit_transfers' => StockTransfer::inTransit()->count(),
            'active_counts' => InventoryCount::inProgress()->count(),
            'active_alerts' => StockAlert::active()->count(),
            'critical_alerts' => StockAlert::active()->critical()->count(),
        ];

        // Recent stock movements
        $recentMovements = StockMovement::with(['warehouse', 'product', 'createdByUser'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Active alerts
        $alerts = StockAlert::with(['warehouse', 'product'])
            ->active()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recent_movements' => $recentMovements,
                'alerts' => $alerts,
            ],
        ]);
    }

    // ==================== WAREHOUSES ====================

    /**
     * List all warehouses.
     */
    public function warehouses(Request $request): JsonResponse
    {
        $query = Warehouse::with('manager');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $warehouses = $query->orderBy('priority', 'desc')
            ->orderBy('name')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $warehouses,
        ]);
    }

    /**
     * Get warehouse details.
     */
    public function showWarehouse(int $id): JsonResponse
    {
        $warehouse = Warehouse::with(['manager'])
            ->withCount([
                'stock',
                'alerts' => fn($q) => $q->active(),
            ])
            ->findOrFail($id);

        // Get stock summary
        $stockSummary = WarehouseStock::where('warehouse_id', $id)
            ->selectRaw('SUM(quantity) as total_items, SUM(reserved_quantity) as reserved_items')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'warehouse' => $warehouse,
                'stock_summary' => $stockSummary,
            ],
        ]);
    }

    /**
     * Create a warehouse.
     */
    public function createWarehouse(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:warehouses,code',
            'description' => 'nullable|string',
            'type' => 'in:warehouse,store,fulfillment_center',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:2',
            'pincode' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'manager_id' => 'nullable|exists:users,id',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'accepts_returns' => 'boolean',
            'allows_pickup' => 'boolean',
            'priority' => 'integer|min:0',
            'operating_hours' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->boolean('is_default')) {
            Warehouse::where('is_default', true)->update(['is_default' => false]);
        }

        $warehouse = Warehouse::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Warehouse created successfully',
            'data' => $warehouse,
        ], 201);
    }

    /**
     * Update a warehouse.
     */
    public function updateWarehouse(Request $request, int $id): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:50|unique:warehouses,code,' . $id,
            'description' => 'nullable|string',
            'type' => 'in:warehouse,store,fulfillment_center',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:2',
            'pincode' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'manager_id' => 'nullable|exists:users,id',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'accepts_returns' => 'boolean',
            'allows_pickup' => 'boolean',
            'priority' => 'integer|min:0',
            'operating_hours' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->boolean('is_default') && !$warehouse->is_default) {
            Warehouse::where('is_default', true)->update(['is_default' => false]);
        }

        $warehouse->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Warehouse updated successfully',
            'data' => $warehouse->fresh(),
        ]);
    }

    /**
     * Delete a warehouse.
     */
    public function deleteWarehouse(int $id): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);

        if ($warehouse->stock()->where('quantity', '>', 0)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete warehouse with existing stock',
            ], 422);
        }

        $warehouse->delete();

        return response()->json([
            'success' => true,
            'message' => 'Warehouse deleted successfully',
        ]);
    }

    // ==================== STOCK ====================

    /**
     * Get warehouse stock.
     */
    public function stock(Request $request, int $warehouseId): JsonResponse
    {
        $query = WarehouseStock::with(['product', 'variant'])
            ->where('warehouse_id', $warehouseId);

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('product_title', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }

        if ($request->boolean('out_of_stock')) {
            $query->outOfStock();
        }

        $stock = $query->orderBy('product_id')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $stock,
        ]);
    }

    /**
     * Update stock levels.
     */
    public function updateStock(Request $request, int $warehouseId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|integer',
            'adjustment_type' => 'required|in:set,adjust',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $stock = WarehouseStock::getOrCreate(
            $warehouseId,
            $request->product_id,
            $request->variant_id
        );

        if ($request->adjustment_type === 'set') {
            $adjustment = $request->quantity - $stock->quantity;
        } else {
            $adjustment = $request->quantity;
        }

        $stock->adjustQuantity(
            $adjustment,
            'adjustment',
            $request->reason ?? 'Manual stock adjustment'
        );

        return response()->json([
            'success' => true,
            'message' => 'Stock updated successfully',
            'data' => $stock->fresh(),
        ]);
    }

    /**
     * Bulk update stock.
     */
    public function bulkUpdateStock(Request $request, int $warehouseId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer',
            'items.*.adjustment_type' => 'required|in:set,adjust',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updated = 0;

        DB::transaction(function () use ($request, $warehouseId, &$updated) {
            foreach ($request->items as $item) {
                $stock = WarehouseStock::getOrCreate(
                    $warehouseId,
                    $item['product_id'],
                    $item['variant_id'] ?? null
                );

                if ($item['adjustment_type'] === 'set') {
                    $adjustment = $item['quantity'] - $stock->quantity;
                } else {
                    $adjustment = $item['quantity'];
                }

                if ($adjustment !== 0) {
                    $stock->adjustQuantity($adjustment, 'adjustment', 'Bulk stock update');
                    $updated++;
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Updated {$updated} stock records",
        ]);
    }

    /**
     * Get stock movement history.
     */
    public function stockHistory(Request $request, int $warehouseId, int $productId): JsonResponse
    {
        $query = StockMovement::with('createdByUser')
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId);

        if ($request->has('variant_id')) {
            $query->where('variant_id', $request->variant_id);
        }

        if ($request->has('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        $movements = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $movements,
        ]);
    }

    // ==================== STOCK TRANSFERS ====================

    /**
     * List stock transfers.
     */
    public function transfers(Request $request): JsonResponse
    {
        $query = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'createdByUser']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('from_warehouse_id')) {
            $query->where('from_warehouse_id', $request->from_warehouse_id);
        }

        if ($request->has('to_warehouse_id')) {
            $query->where('to_warehouse_id', $request->to_warehouse_id);
        }

        $transfers = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $transfers,
        ]);
    }

    /**
     * Get transfer details.
     */
    public function showTransfer(int $id): JsonResponse
    {
        $transfer = StockTransfer::with([
            'fromWarehouse',
            'toWarehouse',
            'items.product',
            'items.variant',
            'createdByUser',
            'approvedByUser',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $transfer,
        ]);
    }

    /**
     * Create a stock transfer.
     */
    public function createTransfer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity_requested' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $transfer = DB::transaction(function () use ($request) {
            $transfer = StockTransfer::create([
                'from_warehouse_id' => $request->from_warehouse_id,
                'to_warehouse_id' => $request->to_warehouse_id,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity_requested' => $item['quantity_requested'],
                ]);
            }

            return $transfer;
        });

        return response()->json([
            'success' => true,
            'message' => 'Transfer created successfully',
            'data' => $transfer->load('items'),
        ], 201);
    }

    /**
     * Approve a transfer.
     */
    public function approveTransfer(int $id): JsonResponse
    {
        $transfer = StockTransfer::findOrFail($id);

        try {
            $transfer->approve();
            return response()->json([
                'success' => true,
                'message' => 'Transfer approved',
                'data' => $transfer->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Ship a transfer.
     */
    public function shipTransfer(Request $request, int $id): JsonResponse
    {
        $transfer = StockTransfer::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'tracking_number' => 'nullable|string|max:100',
            'quantities' => 'nullable|array',
            'quantities.*' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update sent quantities if provided
        if ($request->has('quantities')) {
            foreach ($request->quantities as $itemId => $quantity) {
                StockTransferItem::where('id', $itemId)
                    ->where('stock_transfer_id', $id)
                    ->update(['quantity_sent' => $quantity]);
            }
        }

        try {
            $transfer->ship($request->tracking_number);
            return response()->json([
                'success' => true,
                'message' => 'Transfer shipped',
                'data' => $transfer->fresh()->load('items'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Receive a transfer.
     */
    public function receiveTransfer(Request $request, int $id): JsonResponse
    {
        $transfer = StockTransfer::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'quantities' => 'nullable|array',
            'quantities.*' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $transfer->receive($request->quantities ?? []);
            return response()->json([
                'success' => true,
                'message' => 'Transfer received',
                'data' => $transfer->fresh()->load('items'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel a transfer.
     */
    public function cancelTransfer(int $id): JsonResponse
    {
        $transfer = StockTransfer::findOrFail($id);

        try {
            $transfer->cancel();
            return response()->json([
                'success' => true,
                'message' => 'Transfer cancelled',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // ==================== INVENTORY COUNTS ====================

    /**
     * List inventory counts.
     */
    public function counts(Request $request): JsonResponse
    {
        $query = InventoryCount::with(['warehouse', 'createdByUser']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $counts = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $counts,
        ]);
    }

    /**
     * Get count details.
     */
    public function showCount(int $id): JsonResponse
    {
        $count = InventoryCount::with([
            'warehouse',
            'items.product',
            'items.variant',
            'items.countedByUser',
            'createdByUser',
            'completedByUser',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $count,
        ]);
    }

    /**
     * Create an inventory count.
     */
    public function createCount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'type' => 'required|in:full,cycle,spot',
            'notes' => 'nullable|string',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $count = InventoryCount::create([
            'warehouse_id' => $request->warehouse_id,
            'type' => $request->type,
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        // Initialize items
        $count->initializeItems($request->product_ids);

        return response()->json([
            'success' => true,
            'message' => 'Inventory count created',
            'data' => $count->load('items'),
        ], 201);
    }

    /**
     * Start a count.
     */
    public function startCount(int $id): JsonResponse
    {
        $count = InventoryCount::findOrFail($id);

        try {
            $count->start();
            return response()->json([
                'success' => true,
                'message' => 'Count started',
                'data' => $count->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Record count for an item.
     */
    public function recordCountItem(Request $request, int $countId, int $itemId): JsonResponse
    {
        $item = InventoryCountItem::where('inventory_count_id', $countId)
            ->where('id', $itemId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'counted_quantity' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $item->recordCount($request->counted_quantity, $request->notes);

        return response()->json([
            'success' => true,
            'message' => 'Count recorded',
            'data' => $item->fresh(),
        ]);
    }

    /**
     * Complete a count.
     */
    public function completeCount(Request $request, int $id): JsonResponse
    {
        $count = InventoryCount::findOrFail($id);

        try {
            $count->complete($request->boolean('apply_adjustments', true));
            return response()->json([
                'success' => true,
                'message' => 'Count completed',
                'data' => $count->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel a count.
     */
    public function cancelCount(int $id): JsonResponse
    {
        $count = InventoryCount::findOrFail($id);

        try {
            $count->cancel();
            return response()->json([
                'success' => true,
                'message' => 'Count cancelled',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // ==================== ALERTS ====================

    /**
     * Get stock alerts.
     */
    public function alerts(Request $request): JsonResponse
    {
        $query = StockAlert::with(['warehouse', 'product', 'variant']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            $query->active();
        }

        if ($request->has('alert_type')) {
            $query->where('alert_type', $request->alert_type);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $alerts = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    /**
     * Acknowledge an alert.
     */
    public function acknowledgeAlert(int $id): JsonResponse
    {
        $alert = StockAlert::findOrFail($id);
        $alert->acknowledge();

        return response()->json([
            'success' => true,
            'message' => 'Alert acknowledged',
        ]);
    }

    /**
     * Bulk acknowledge alerts.
     */
    public function bulkAcknowledgeAlerts(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'alert_ids' => 'required|array|min:1',
            'alert_ids.*' => 'exists:stock_alerts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        StockAlert::whereIn('id', $request->alert_ids)
            ->where('status', StockAlert::STATUS_ACTIVE)
            ->update([
                'status' => StockAlert::STATUS_ACKNOWLEDGED,
                'acknowledged_by' => auth()->id(),
                'acknowledged_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Alerts acknowledged',
        ]);
    }

    // ==================== RESERVATIONS ====================

    /**
     * Get active reservations.
     */
    public function reservations(Request $request): JsonResponse
    {
        $query = StockReservation::with(['warehouse', 'product', 'variant', 'order'])
            ->active();

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $reservations = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $reservations,
        ]);
    }

    /**
     * Expire old reservations.
     */
    public function expireReservations(): JsonResponse
    {
        $count = StockReservation::expireOld();

        return response()->json([
            'success' => true,
            'message' => "Expired {$count} reservations",
        ]);
    }
}

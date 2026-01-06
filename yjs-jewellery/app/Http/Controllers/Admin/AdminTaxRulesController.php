<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaxZone;
use App\Models\TaxRule;
use App\Models\TaxExemption;
use App\Models\TaxRateHistory;
use App\Models\HsnCode;
use App\Models\OrderTaxDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminTaxRulesController extends Controller
{
    /**
     * Tax dashboard overview.
     */
    public function dashboard(): JsonResponse
    {
        $stats = [
            'total_zones' => TaxZone::count(),
            'active_zones' => TaxZone::active()->count(),
            'total_rules' => TaxRule::count(),
            'active_rules' => TaxRule::valid()->count(),
            'pending_exemptions' => TaxExemption::pending()->count(),
            'approved_exemptions' => TaxExemption::approved()->count(),
            'hsn_codes' => HsnCode::count(),
        ];

        // Recent rate changes
        $recentChanges = TaxRateHistory::with(['taxRule', 'changedByUser'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Tax collection summary (last 30 days)
        $taxCollection = OrderTaxDetail::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('tax_type, SUM(tax_amount) as total')
            ->groupBy('tax_type')
            ->pluck('total', 'tax_type');

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recent_changes' => $recentChanges,
                'tax_collection' => $taxCollection,
            ],
        ]);
    }

    // ==================== TAX ZONES ====================

    /**
     * List all tax zones.
     */
    public function zones(Request $request): JsonResponse
    {
        $query = TaxZone::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $zones = $query->orderBy('priority', 'desc')
            ->orderBy('name')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $zones,
        ]);
    }

    /**
     * Create a tax zone.
     */
    public function createZone(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:tax_zones,code',
            'description' => 'nullable|string',
            'countries' => 'nullable|array',
            'states' => 'nullable|array',
            'pincodes' => 'nullable|array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'priority' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // If setting as default, unset other defaults
        if ($request->boolean('is_default')) {
            TaxZone::where('is_default', true)->update(['is_default' => false]);
        }

        $zone = TaxZone::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tax zone created successfully',
            'data' => $zone,
        ], 201);
    }

    /**
     * Update a tax zone.
     */
    public function updateZone(Request $request, int $id): JsonResponse
    {
        $zone = TaxZone::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:50|unique:tax_zones,code,' . $id,
            'description' => 'nullable|string',
            'countries' => 'nullable|array',
            'states' => 'nullable|array',
            'pincodes' => 'nullable|array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'priority' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->boolean('is_default') && !$zone->is_default) {
            TaxZone::where('is_default', true)->update(['is_default' => false]);
        }

        $zone->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tax zone updated successfully',
            'data' => $zone->fresh(),
        ]);
    }

    /**
     * Delete a tax zone.
     */
    public function deleteZone(int $id): JsonResponse
    {
        $zone = TaxZone::findOrFail($id);

        if ($zone->taxRules()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete zone with associated tax rules',
            ], 422);
        }

        $zone->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tax zone deleted successfully',
        ]);
    }

    // ==================== TAX RULES ====================

    /**
     * List all tax rules.
     */
    public function rules(Request $request): JsonResponse
    {
        $query = TaxRule::with(['taxZone', 'createdByUser']);

        if ($request->has('tax_zone_id')) {
            $query->where('tax_zone_id', $request->tax_zone_id);
        }

        if ($request->has('tax_type')) {
            $query->where('tax_type', $request->tax_type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('apply_to')) {
            $query->where('apply_to', $request->apply_to);
        }

        $rules = $query->orderBy('priority', 'desc')
            ->orderBy('name')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $rules,
        ]);
    }

    /**
     * Get a single tax rule.
     */
    public function showRule(int $id): JsonResponse
    {
        $rule = TaxRule::with(['taxZone', 'createdByUser', 'rateHistory.changedByUser'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $rule,
        ]);
    }

    /**
     * Create a tax rule.
     */
    public function createRule(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:tax_rules,code',
            'description' => 'nullable|string',
            'tax_zone_id' => 'nullable|exists:tax_zones,id',
            'tax_type' => 'required|in:gst,igst,cgst_sgst,vat,custom',
            'rate' => 'required|numeric|min:0|max:100',
            'cgst_rate' => 'nullable|numeric|min:0|max:100',
            'sgst_rate' => 'nullable|numeric|min:0|max:100',
            'igst_rate' => 'nullable|numeric|min:0|max:100',
            'apply_to' => 'required|in:all,category,product,tag',
            'apply_to_ids' => 'nullable|array',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'calculation_type' => 'in:percentage,fixed',
            'is_inclusive' => 'boolean',
            'is_compound' => 'boolean',
            'priority' => 'integer|min:0',
            'is_active' => 'boolean',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['created_by'] = auth()->id();

        $rule = TaxRule::create($data);

        // Log initial rate
        TaxRateHistory::create([
            'tax_rule_id' => $rule->id,
            'new_rate' => $rule->rate,
            'new_cgst' => $rule->cgst_rate,
            'new_sgst' => $rule->sgst_rate,
            'new_igst' => $rule->igst_rate,
            'reason' => 'Initial creation',
            'changed_by' => auth()->id(),
            'effective_from' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tax rule created successfully',
            'data' => $rule,
        ], 201);
    }

    /**
     * Update a tax rule.
     */
    public function updateRule(Request $request, int $id): JsonResponse
    {
        $rule = TaxRule::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:50|unique:tax_rules,code,' . $id,
            'description' => 'nullable|string',
            'tax_zone_id' => 'nullable|exists:tax_zones,id',
            'tax_type' => 'sometimes|in:gst,igst,cgst_sgst,vat,custom',
            'rate' => 'sometimes|numeric|min:0|max:100',
            'cgst_rate' => 'nullable|numeric|min:0|max:100',
            'sgst_rate' => 'nullable|numeric|min:0|max:100',
            'igst_rate' => 'nullable|numeric|min:0|max:100',
            'apply_to' => 'sometimes|in:all,category,product,tag',
            'apply_to_ids' => 'nullable|array',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'calculation_type' => 'in:percentage,fixed',
            'is_inclusive' => 'boolean',
            'is_compound' => 'boolean',
            'priority' => 'integer|min:0',
            'is_active' => 'boolean',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Log rate change if rate is being updated
        if (isset($data['rate']) && $data['rate'] != $rule->rate) {
            $rule->updateRate(
                $data['rate'],
                $data['cgst_rate'] ?? $rule->cgst_rate,
                $data['sgst_rate'] ?? $rule->sgst_rate,
                $data['igst_rate'] ?? $rule->igst_rate,
                $request->get('rate_change_reason', 'Rate updated')
            );
            unset($data['rate'], $data['cgst_rate'], $data['sgst_rate'], $data['igst_rate']);
        }

        $rule->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Tax rule updated successfully',
            'data' => $rule->fresh(),
        ]);
    }

    /**
     * Delete a tax rule.
     */
    public function deleteRule(int $id): JsonResponse
    {
        $rule = TaxRule::findOrFail($id);

        // Check if rule is being used in orders
        if (OrderTaxDetail::where('tax_rule_id', $id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete rule that has been used in orders. Deactivate it instead.',
            ], 422);
        }

        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tax rule deleted successfully',
        ]);
    }

    /**
     * Get rate history for a rule.
     */
    public function rateHistory(int $id): JsonResponse
    {
        $history = TaxRateHistory::where('tax_rule_id', $id)
            ->with('changedByUser')
            ->orderByDesc('effective_from')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    // ==================== TAX EXEMPTIONS ====================

    /**
     * List all tax exemptions.
     */
    public function exemptions(Request $request): JsonResponse
    {
        $query = TaxExemption::with(['customer', 'product', 'category', 'taxRule', 'approvedByUser']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('exemption_type')) {
            $query->where('exemption_type', $request->exemption_type);
        }

        $exemptions = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $exemptions,
        ]);
    }

    /**
     * Show exemption details.
     */
    public function showExemption(int $id): JsonResponse
    {
        $exemption = TaxExemption::with(['customer', 'product', 'category', 'taxRule', 'approvedByUser'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $exemption,
        ]);
    }

    /**
     * Create a tax exemption.
     */
    public function createExemption(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'certificate_number' => 'nullable|string|max:100',
            'exemption_type' => 'required|in:customer,product,category',
            'customer_id' => 'required_if:exemption_type,customer|exists:users,id',
            'product_id' => 'required_if:exemption_type,product|exists:products,id',
            'category_id' => 'required_if:exemption_type,category|exists:categories,id',
            'tax_rule_id' => 'nullable|exists:tax_rules,id',
            'reason' => 'required|string|max:500',
            'valid_from' => 'required|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'documents' => 'nullable|array',
            'status' => 'in:pending,approved',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // If admin is creating and approving directly
        if ($request->get('status') === 'approved') {
            $data['approved_by'] = auth()->id();
            $data['approved_at'] = now();
        }

        $exemption = TaxExemption::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Tax exemption created successfully',
            'data' => $exemption,
        ], 201);
    }

    /**
     * Approve an exemption.
     */
    public function approveExemption(Request $request, int $id): JsonResponse
    {
        $exemption = TaxExemption::findOrFail($id);

        if ($exemption->status !== TaxExemption::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending exemptions can be approved',
            ], 422);
        }

        $exemption->approve($request->get('admin_notes'));

        return response()->json([
            'success' => true,
            'message' => 'Exemption approved successfully',
            'data' => $exemption->fresh(),
        ]);
    }

    /**
     * Reject an exemption.
     */
    public function rejectExemption(Request $request, int $id): JsonResponse
    {
        $exemption = TaxExemption::findOrFail($id);

        if ($exemption->status !== TaxExemption::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending exemptions can be rejected',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Rejection reason is required',
                'errors' => $validator->errors(),
            ], 422);
        }

        $exemption->reject($request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Exemption rejected',
            'data' => $exemption->fresh(),
        ]);
    }

    /**
     * Delete an exemption.
     */
    public function deleteExemption(int $id): JsonResponse
    {
        $exemption = TaxExemption::findOrFail($id);
        $exemption->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exemption deleted successfully',
        ]);
    }

    // ==================== HSN CODES ====================

    /**
     * List HSN codes.
     */
    public function hsnCodes(Request $request): JsonResponse
    {
        $query = HsnCode::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $hsnCodes = $query->orderBy('code')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $hsnCodes,
        ]);
    }

    /**
     * Create HSN code.
     */
    public function createHsnCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:20|unique:hsn_codes,code',
            'description' => 'required|string|max:500',
            'gst_rate' => 'required|numeric|min:0|max:100',
            'cgst_rate' => 'nullable|numeric|min:0|max:100',
            'sgst_rate' => 'nullable|numeric|min:0|max:100',
            'igst_rate' => 'nullable|numeric|min:0|max:100',
            'cess_rate' => 'nullable|numeric|min:0|max:100',
            'type' => 'in:goods,services',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Auto-calculate CGST/SGST if not provided
        if (!isset($data['cgst_rate'])) {
            $data['cgst_rate'] = $data['gst_rate'] / 2;
        }
        if (!isset($data['sgst_rate'])) {
            $data['sgst_rate'] = $data['gst_rate'] / 2;
        }
        if (!isset($data['igst_rate'])) {
            $data['igst_rate'] = $data['gst_rate'];
        }

        $hsnCode = HsnCode::create($data);

        return response()->json([
            'success' => true,
            'message' => 'HSN code created successfully',
            'data' => $hsnCode,
        ], 201);
    }

    /**
     * Update HSN code.
     */
    public function updateHsnCode(Request $request, int $id): JsonResponse
    {
        $hsnCode = HsnCode::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code' => 'sometimes|required|string|max:20|unique:hsn_codes,code,' . $id,
            'description' => 'sometimes|required|string|max:500',
            'gst_rate' => 'sometimes|numeric|min:0|max:100',
            'cgst_rate' => 'nullable|numeric|min:0|max:100',
            'sgst_rate' => 'nullable|numeric|min:0|max:100',
            'igst_rate' => 'nullable|numeric|min:0|max:100',
            'cess_rate' => 'nullable|numeric|min:0|max:100',
            'type' => 'in:goods,services',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $hsnCode->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'HSN code updated successfully',
            'data' => $hsnCode->fresh(),
        ]);
    }

    /**
     * Delete HSN code.
     */
    public function deleteHsnCode(int $id): JsonResponse
    {
        $hsnCode = HsnCode::findOrFail($id);

        if ($hsnCode->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete HSN code that is mapped to products',
            ], 422);
        }

        $hsnCode->delete();

        return response()->json([
            'success' => true,
            'message' => 'HSN code deleted successfully',
        ]);
    }

    /**
     * Search HSN codes for autocomplete.
     */
    public function searchHsnCodes(Request $request): JsonResponse
    {
        $term = $request->get('term', '');
        $results = HsnCode::search($term);

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Bulk import HSN codes.
     */
    public function importHsnCodes(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'hsn_codes' => 'required|array|min:1',
            'hsn_codes.*.code' => 'required|string|max:20',
            'hsn_codes.*.description' => 'required|string|max:500',
            'hsn_codes.*.gst_rate' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $imported = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($request->hsn_codes as $data) {
                $existing = HsnCode::where('code', $data['code'])->first();

                if ($existing) {
                    $skipped++;
                    continue;
                }

                HsnCode::create([
                    'code' => $data['code'],
                    'description' => $data['description'],
                    'gst_rate' => $data['gst_rate'],
                    'cgst_rate' => $data['cgst_rate'] ?? ($data['gst_rate'] / 2),
                    'sgst_rate' => $data['sgst_rate'] ?? ($data['gst_rate'] / 2),
                    'igst_rate' => $data['igst_rate'] ?? $data['gst_rate'],
                    'cess_rate' => $data['cess_rate'] ?? null,
                    'type' => $data['type'] ?? 'goods',
                    'is_active' => true,
                ]);

                $imported++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Imported {$imported} HSN codes, skipped {$skipped} duplicates",
                'data' => [
                    'imported' => $imported,
                    'skipped' => $skipped,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ==================== TAX CALCULATION ====================

    /**
     * Calculate tax for a cart/order preview.
     */
    public function calculateTax(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'shipping_address' => 'nullable|array',
            'shipping_address.country' => 'nullable|string',
            'shipping_address.state' => 'nullable|string',
            'shipping_address.pincode' => 'nullable|string',
            'customer_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Determine if interstate (for GST)
        $isInterstate = false;
        $address = $request->shipping_address;
        if ($address) {
            // Compare shipping state with store state (from settings)
            $storeState = config('app.store_state', 'MH');
            $isInterstate = isset($address['state']) && $address['state'] !== $storeState;
        }

        // Find applicable zone
        $zone = null;
        if ($address) {
            $zone = TaxZone::findForAddress(
                $address['country'] ?? 'IN',
                $address['state'] ?? null,
                $address['pincode'] ?? null
            );
        }

        $itemTaxes = [];
        $totalTax = 0;
        $taxSummary = [];

        foreach ($request->items as $item) {
            $product = \App\Models\Product::find($item['product_id']);
            $lineTotal = $item['quantity'] * $item['price'];

            // Check for exemption
            $customerId = $request->customer_id;
            $isExempt = false;
            $exemptionReason = null;

            if ($customerId && TaxExemption::hasValidExemption($customerId)) {
                $isExempt = true;
                $exemptionReason = 'Customer has valid tax exemption';
            }

            // Find applicable tax rule
            $taxRule = TaxRule::valid()
                ->forZone($zone?->id)
                ->orderByDesc('priority')
                ->get()
                ->first(fn($rule) => $rule->appliesToProduct($product));

            $itemTax = [
                'product_id' => $product->id,
                'product_name' => $product->product_title,
                'quantity' => $item['quantity'],
                'line_total' => $lineTotal,
                'is_exempt' => $isExempt,
                'exemption_reason' => $exemptionReason,
                'taxes' => [],
            ];

            if (!$isExempt && $taxRule) {
                $calculation = $taxRule->calculateTax($lineTotal, $isInterstate);
                $itemTax['taxes'] = $calculation;
                $itemTax['tax_amount'] = $calculation['total_tax'];
                $totalTax += $calculation['total_tax'];

                // Build summary
                if (isset($calculation['igst'])) {
                    $taxSummary['igst'] = ($taxSummary['igst'] ?? 0) + $calculation['igst'];
                }
                if (isset($calculation['cgst'])) {
                    $taxSummary['cgst'] = ($taxSummary['cgst'] ?? 0) + $calculation['cgst'];
                }
                if (isset($calculation['sgst'])) {
                    $taxSummary['sgst'] = ($taxSummary['sgst'] ?? 0) + $calculation['sgst'];
                }
            } else {
                $itemTax['tax_amount'] = 0;
            }

            $itemTaxes[] = $itemTax;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'is_interstate' => $isInterstate,
                'tax_zone' => $zone?->name,
                'items' => $itemTaxes,
                'tax_summary' => $taxSummary,
                'total_tax' => round($totalTax, 2),
            ],
        ]);
    }
}

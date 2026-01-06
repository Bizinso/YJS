<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnPolicySetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * Admin Return Policy Controller
 *
 * Manages return, exchange, and cancellation policy settings.
 */
class AdminReturnPolicyController extends Controller
{
    /**
     * Get current policy settings
     */
    public function index(): JsonResponse
    {
        try {
            $policy = ReturnPolicySetting::getActive();

            if (!$policy) {
                // Return default settings
                $policy = [
                    'policy_type' => 'standard',
                    'return_window_days' => 7,
                    'exchange_window_days' => 15,
                    'cancellation_window_hours' => 24,
                    'allow_partial_returns' => true,
                    'require_images' => true,
                    'require_reason' => true,
                    'auto_approve_cancellations' => false,
                    'restocking_fee_percent' => 0,
                    'non_returnable_categories' => [],
                    'return_reasons' => ReturnPolicySetting::getDefaultReturnReasons(),
                    'exchange_reasons' => ReturnPolicySetting::getDefaultExchangeReasons(),
                    'cancellation_reasons' => ReturnPolicySetting::getDefaultCancellationReasons(),
                    'return_instructions' => null,
                    'terms_and_conditions' => null,
                    'is_active' => false,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $policy,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch policy settings',
            ], 500);
        }
    }

    /**
     * Update policy settings
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'policy_type' => 'nullable|in:standard,extended,no_returns',
            'return_window_days' => 'nullable|integer|min:0|max:365',
            'exchange_window_days' => 'nullable|integer|min:0|max:365',
            'cancellation_window_hours' => 'nullable|integer|min:0|max:720',
            'allow_partial_returns' => 'nullable|boolean',
            'require_images' => 'nullable|boolean',
            'require_reason' => 'nullable|boolean',
            'auto_approve_cancellations' => 'nullable|boolean',
            'restocking_fee_percent' => 'nullable|numeric|min:0|max:100',
            'non_returnable_categories' => 'nullable|array',
            'non_returnable_categories.*' => 'integer|exists:categories,id',
            'return_reasons' => 'nullable|array',
            'exchange_reasons' => 'nullable|array',
            'cancellation_reasons' => 'nullable|array',
            'return_instructions' => 'nullable|string|max:5000',
            'terms_and_conditions' => 'nullable|string|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $policy = ReturnPolicySetting::getActive();

            if (!$policy) {
                $policy = new ReturnPolicySetting();
                $policy->is_active = true;
            }

            // Update fields
            $fields = [
                'policy_type', 'return_window_days', 'exchange_window_days',
                'cancellation_window_hours', 'allow_partial_returns', 'require_images',
                'require_reason', 'auto_approve_cancellations', 'restocking_fee_percent',
                'non_returnable_categories', 'return_reasons', 'exchange_reasons',
                'cancellation_reasons', 'return_instructions', 'terms_and_conditions',
            ];

            foreach ($fields as $field) {
                if ($request->has($field)) {
                    $policy->$field = $request->$field;
                }
            }

            $policy->save();

            return response()->json([
                'success' => true,
                'message' => 'Policy settings updated',
                'data' => $policy,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update policy settings',
            ], 500);
        }
    }

    /**
     * Get default reasons for a specific type
     */
    public function getDefaultReasons(string $type): JsonResponse
    {
        try {
            $reasons = match ($type) {
                'return' => ReturnPolicySetting::getDefaultReturnReasons(),
                'exchange' => ReturnPolicySetting::getDefaultExchangeReasons(),
                'cancellation' => ReturnPolicySetting::getDefaultCancellationReasons(),
                default => [],
            };

            return response()->json([
                'success' => true,
                'data' => $reasons,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch default reasons',
            ], 500);
        }
    }

    /**
     * Reset policy to defaults
     */
    public function reset(): JsonResponse
    {
        try {
            $policy = ReturnPolicySetting::getActive();

            if ($policy) {
                $policy->update([
                    'policy_type' => 'standard',
                    'return_window_days' => 7,
                    'exchange_window_days' => 15,
                    'cancellation_window_hours' => 24,
                    'allow_partial_returns' => true,
                    'require_images' => true,
                    'require_reason' => true,
                    'auto_approve_cancellations' => false,
                    'restocking_fee_percent' => 0,
                    'non_returnable_categories' => null,
                    'return_reasons' => ReturnPolicySetting::getDefaultReturnReasons(),
                    'exchange_reasons' => ReturnPolicySetting::getDefaultExchangeReasons(),
                    'cancellation_reasons' => ReturnPolicySetting::getDefaultCancellationReasons(),
                    'return_instructions' => null,
                    'terms_and_conditions' => null,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Policy reset to defaults',
                'data' => $policy,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset policy',
            ], 500);
        }
    }
}

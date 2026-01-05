<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Partner Profile Controller
 *
 * Handles all partner profile management operations including
 * profile viewing, updating, and avatar management.
 *
 * @package App\Http\Controllers\Partner
 */
class PartnerProfileController extends Controller
{
    /**
     * Get the authenticated partner's profile.
     *
     * Returns complete profile information including user data,
     * business details, and approval status.
     *
     * @return JsonResponse
     */
    public function getProfile(): JsonResponse
    {
        $user = Auth::user();
        $partner = Partner::where('user_id', $user->id)->first();

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner profile not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'mobile_code' => $user->mobile_code,
                    'profile_image' => $user->profile_image,
                    'status' => $user->status,
                ],
                'partner' => [
                    'id' => $partner->id,
                    'business_name' => $partner->business_name,
                    'business_type' => $partner->business_type,
                    'business_type_label' => $partner->business_type_label,
                    'phone_number' => $partner->phone_number,
                    'gst_number' => $partner->gst_number,
                    'address' => $partner->address,
                    'city' => $partner->city,
                    'state' => $partner->state,
                    'status' => $partner->status,
                    'status_label' => $partner->status_label,
                    'created_at' => $partner->created_at,
                ],
            ],
        ]);
    }

    /**
     * Update the partner's profile.
     *
     * Allows updating both user information (name, contact) and
     * business information (business name, GST, address).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();
        $partner = Partner::where('user_id', $user->id)->first();

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner profile not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            // User fields
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|required|digits:10|unique:users,phone,' . $user->id,
            // Partner/Business fields
            'business_name' => 'sometimes|required|string|max:255',
            'phone_number' => 'sometimes|nullable|digits:10',
            'gst_number' => 'sometimes|nullable|string|max:15',
            'address' => 'sometimes|nullable|string|max:500',
            'city' => 'sometimes|nullable|string|max:100',
            'state' => 'sometimes|nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update user fields
        $userFields = ['first_name', 'last_name', 'email', 'phone'];
        $userUpdates = $request->only($userFields);
        if (!empty($userUpdates)) {
            $user->update($userUpdates);
        }

        // Update partner/business fields
        $partnerFields = ['business_name', 'phone_number', 'gst_number', 'address', 'city', 'state'];
        $partnerUpdates = $request->only($partnerFields);
        if (!empty($partnerUpdates)) {
            $partner->update($partnerUpdates);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => [
                'user' => $user->fresh(),
                'partner' => $partner->fresh(),
            ],
        ]);
    }

    /**
     * Upload partner's business logo or avatar.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        // Delete old avatar if exists
        if ($user->profile_image) {
            Storage::disk('public')->delete($user->profile_image);
        }

        // Store new avatar
        $path = $request->file('avatar')->store('partner_avatars', 'public');
        $user->update(['profile_image' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar uploaded successfully.',
            'data' => [
                'profile_image' => $path,
                'url' => Storage::disk('public')->url($path),
            ],
        ]);
    }

    /**
     * Remove partner's avatar.
     *
     * @return JsonResponse
     */
    public function removeAvatar(): JsonResponse
    {
        $user = Auth::user();

        if ($user->profile_image) {
            Storage::disk('public')->delete($user->profile_image);
            $user->update(['profile_image' => null]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Avatar removed successfully.',
        ]);
    }

    /**
     * Get partner's approval status.
     *
     * Useful for checking if partner can place orders.
     *
     * @return JsonResponse
     */
    public function getApprovalStatus(): JsonResponse
    {
        $user = Auth::user();
        $partner = Partner::where('user_id', $user->id)->first();

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner profile not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $partner->status,
                'status_label' => $partner->status_label,
                'is_approved' => $partner->isApproved(),
                'can_place_orders' => $partner->isApproved(),
            ],
        ]);
    }
}

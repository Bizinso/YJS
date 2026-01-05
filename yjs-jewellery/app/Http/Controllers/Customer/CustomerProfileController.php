<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\UpdateProfileRequest;
use App\Http\Requests\Customer\StoreAddressRequest;
use App\Http\Requests\Customer\UpdateAddressRequest;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CustomerProfileController extends Controller
{
    /**
     * Get the authenticated customer's profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Load customer relationship with addresses
            $user->load(['customer.addresses.country']);

            $profile = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => trim($user->first_name . ' ' . $user->last_name),
                'email' => $user->email,
                'phone' => $user->phone,
                'mobile_code' => $user->mobile_code,
                'profile_image' => $user->profile_image
                    ? Storage::url($user->profile_image)
                    : null,
                'email_verified' => !is_null($user->email_verified_at),
                'member_since' => $user->created_at->format('Y-m-d'),
                'customer' => $user->customer ? [
                    'gender' => $user->customer->gender,
                    'dob' => $user->customer->dob,
                ] : null,
                'default_address' => $user->customer?->addresses
                    ->where('is_default', true)
                    ->first(),
                'addresses_count' => $user->customer?->addresses->count() ?? 0,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Profile retrieved successfully.',
                'data' => $profile,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching customer profile: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve profile. Please try again.',
            ], 500);
        }
    }

    /**
     * Update the authenticated customer's profile.
     *
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = $request->user();
            $validated = $request->validated();

            // Update user fields
            $userFields = ['first_name', 'last_name', 'email', 'phone', 'mobile_code'];
            $userUpdates = array_intersect_key($validated, array_flip($userFields));

            if (!empty($userUpdates)) {
                $user->update($userUpdates);
            }

            // Update customer fields
            $customerFields = ['gender', 'dob'];
            $customerUpdates = array_intersect_key($validated, array_flip($customerFields));

            if (!empty($customerUpdates)) {
                // Ensure customer record exists
                $customer = $user->customer;
                if (!$customer) {
                    $customer = Customer::create([
                        'user_id' => $user->id,
                        ...$customerUpdates
                    ]);
                } else {
                    $customer->update($customerUpdates);
                }
            }

            DB::commit();

            // Reload the user with relationships
            $user->refresh();
            $user->load('customer');

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'data' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => trim($user->first_name . ' ' . $user->last_name),
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'mobile_code' => $user->mobile_code,
                    'profile_image' => $user->profile_image
                        ? Storage::url($user->profile_image)
                        : null,
                    'customer' => $user->customer ? [
                        'gender' => $user->customer->gender,
                        'dob' => $user->customer->dob,
                    ] : null,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error updating customer profile: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'data' => $request->validated(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile. Please try again.',
            ], 500);
        }
    }

    /**
     * Upload/update the customer's profile picture.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'], // 2MB max
        ], [
            'avatar.required' => 'Please select an image to upload.',
            'avatar.image' => 'The file must be an image.',
            'avatar.mimes' => 'The image must be a JPEG, PNG, JPG, or WebP file.',
            'avatar.max' => 'The image size cannot exceed 2MB.',
        ]);

        try {
            $user = $request->user();

            // Delete old avatar if exists
            if ($user->profile_image && Storage::exists($user->profile_image)) {
                Storage::delete($user->profile_image);
            }

            // Store new avatar
            $path = $request->file('avatar')->store(
                'avatars/customers/' . $user->id,
                'public'
            );

            $user->update(['profile_image' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated successfully.',
                'data' => [
                    'profile_image' => Storage::url($path),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error uploading avatar: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload profile picture. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove the customer's profile picture.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function removeAvatar(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->profile_image) {
                if (Storage::exists($user->profile_image)) {
                    Storage::delete($user->profile_image);
                }
                $user->update(['profile_image' => null]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile picture removed successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('Error removing avatar: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove profile picture. Please try again.',
            ], 500);
        }
    }

    /**
     * Get all addresses for the authenticated customer.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAddresses(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $customer = $user->customer;

            if (!$customer) {
                return response()->json([
                    'success' => true,
                    'message' => 'No addresses found.',
                    'data' => [],
                ]);
            }

            $addresses = $customer->addresses()
                ->with('country')
                ->orderByDesc('is_default')
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($address) {
                    return [
                        'id' => $address->id,
                        'full_name' => $address->full_name,
                        'email' => $address->email,
                        'phone' => $address->phone,
                        'alternate_phone' => $address->alternate_phone,
                        'address_line1' => $address->address_line1,
                        'address_line2' => $address->address_line2,
                        'landmark' => $address->landmark,
                        'city' => $address->city,
                        'state' => $address->state,
                        'postal_code' => $address->postal_code,
                        'country_id' => $address->country_id,
                        'country_name' => $address->country?->name,
                        'is_default' => $address->is_default,
                        'formatted_address' => $this->formatAddress($address),
                        'created_at' => $address->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Addresses retrieved successfully.',
                'data' => $addresses,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching addresses: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve addresses. Please try again.',
            ], 500);
        }
    }

    /**
     * Get a single address by ID.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function getAddress(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $customer = $user->customer;

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Address not found.',
                ], 404);
            }

            $address = $customer->addresses()
                ->with('country')
                ->find($id);

            if (!$address) {
                return response()->json([
                    'success' => false,
                    'message' => 'Address not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Address retrieved successfully.',
                'data' => [
                    'id' => $address->id,
                    'full_name' => $address->full_name,
                    'email' => $address->email,
                    'phone' => $address->phone,
                    'alternate_phone' => $address->alternate_phone,
                    'address_line1' => $address->address_line1,
                    'address_line2' => $address->address_line2,
                    'landmark' => $address->landmark,
                    'city' => $address->city,
                    'state' => $address->state,
                    'postal_code' => $address->postal_code,
                    'country_id' => $address->country_id,
                    'country_name' => $address->country?->name,
                    'is_default' => $address->is_default,
                    'formatted_address' => $this->formatAddress($address),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching address: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'address_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve address. Please try again.',
            ], 500);
        }
    }

    /**
     * Store a new address for the customer.
     *
     * @param StoreAddressRequest $request
     * @return JsonResponse
     */
    public function storeAddress(StoreAddressRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = $request->user();

            // Ensure customer record exists
            $customer = $user->customer;
            if (!$customer) {
                $customer = Customer::create(['user_id' => $user->id]);
            }

            $validated = $request->validated();

            // If this is the first address or is_default is true, reset other defaults
            $isFirstAddress = $customer->addresses()->count() === 0;
            $setAsDefault = $isFirstAddress || ($validated['is_default'] ?? false);

            if ($setAsDefault) {
                $customer->addresses()->update(['is_default' => false]);
            }

            $address = $customer->addresses()->create([
                ...$validated,
                'is_default' => $setAsDefault,
            ]);

            $address->load('country');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Address added successfully.',
                'data' => [
                    'id' => $address->id,
                    'full_name' => $address->full_name,
                    'email' => $address->email,
                    'phone' => $address->phone,
                    'alternate_phone' => $address->alternate_phone,
                    'address_line1' => $address->address_line1,
                    'address_line2' => $address->address_line2,
                    'landmark' => $address->landmark,
                    'city' => $address->city,
                    'state' => $address->state,
                    'postal_code' => $address->postal_code,
                    'country_id' => $address->country_id,
                    'country_name' => $address->country?->name,
                    'is_default' => $address->is_default,
                    'formatted_address' => $this->formatAddress($address),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error creating address: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'data' => $request->validated(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add address. Please try again.',
            ], 500);
        }
    }

    /**
     * Update an existing address.
     *
     * @param UpdateAddressRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateAddress(UpdateAddressRequest $request, int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = $request->user();
            $customer = $user->customer;

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Address not found.',
                ], 404);
            }

            $address = $customer->addresses()->find($id);

            if (!$address) {
                return response()->json([
                    'success' => false,
                    'message' => 'Address not found.',
                ], 404);
            }

            $validated = $request->validated();

            // If setting as default, reset other addresses
            if ($validated['is_default'] ?? false) {
                $customer->addresses()
                    ->where('id', '!=', $id)
                    ->update(['is_default' => false]);
            }

            $address->update($validated);
            $address->load('country');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Address updated successfully.',
                'data' => [
                    'id' => $address->id,
                    'full_name' => $address->full_name,
                    'email' => $address->email,
                    'phone' => $address->phone,
                    'alternate_phone' => $address->alternate_phone,
                    'address_line1' => $address->address_line1,
                    'address_line2' => $address->address_line2,
                    'landmark' => $address->landmark,
                    'city' => $address->city,
                    'state' => $address->state,
                    'postal_code' => $address->postal_code,
                    'country_id' => $address->country_id,
                    'country_name' => $address->country?->name,
                    'is_default' => $address->is_default,
                    'formatted_address' => $this->formatAddress($address),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error updating address: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'address_id' => $id,
                'data' => $request->validated(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update address. Please try again.',
            ], 500);
        }
    }

    /**
     * Delete an address.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function deleteAddress(Request $request, int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = $request->user();
            $customer = $user->customer;

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Address not found.',
                ], 404);
            }

            $address = $customer->addresses()->find($id);

            if (!$address) {
                return response()->json([
                    'success' => false,
                    'message' => 'Address not found.',
                ], 404);
            }

            $wasDefault = $address->is_default;
            $address->delete();

            // If deleted address was default, set the most recent address as default
            if ($wasDefault) {
                $newDefault = $customer->addresses()
                    ->orderByDesc('created_at')
                    ->first();

                if ($newDefault) {
                    $newDefault->update(['is_default' => true]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Address deleted successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error deleting address: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'address_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete address. Please try again.',
            ], 500);
        }
    }

    /**
     * Set an address as default.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function setDefaultAddress(Request $request, int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = $request->user();
            $customer = $user->customer;

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Address not found.',
                ], 404);
            }

            $address = $customer->addresses()->find($id);

            if (!$address) {
                return response()->json([
                    'success' => false,
                    'message' => 'Address not found.',
                ], 404);
            }

            // Reset all addresses to non-default
            $customer->addresses()->update(['is_default' => false]);

            // Set selected address as default
            $address->update(['is_default' => true]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Default address updated successfully.',
                'data' => [
                    'id' => $address->id,
                    'is_default' => true,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error setting default address: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'address_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to set default address. Please try again.',
            ], 500);
        }
    }

    /**
     * Format address into a single string.
     *
     * @param CustomerAddress $address
     * @return string
     */
    private function formatAddress(CustomerAddress $address): string
    {
        $parts = array_filter([
            $address->address_line1,
            $address->address_line2,
            $address->landmark,
            $address->city,
            $address->state,
            $address->postal_code,
            $address->country?->name,
        ]);

        return implode(', ', $parts);
    }
}

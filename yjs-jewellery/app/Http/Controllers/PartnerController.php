<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
class PartnerController extends Controller
{
 public function index(Request $request)
{
    // try {
        $query = Partner::query()
            ->join('users', 'partners.user_id', '=', 'users.id')
            ->select([
                'partners.*',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as contact_person"),
                'users.email',
                'users.phone as mobile'
            ]);

        // 🔍 Global Search
        if ($request->filled('globalSearch')) {
            $search = $request->globalSearch;
            $query->where(function ($q) use ($search) {
                $q->where('partners.business_name', 'like', "%{$search}%")
                  ->orWhere('partners.gst_number', 'like', "%{$search}%")
                  ->orWhere('partners.city', 'like', "%{$search}%")
                  ->orWhere('partners.state', 'like', "%{$search}%")
                  ->orWhere('users.first_name', 'like', "%{$search}%")
                  ->orWhere('users.last_name', 'like', "%{$search}%")
                  ->orWhere('users.email', 'like', "%{$search}%")
                  ->orWhere('users.phone', 'like', "%{$search}%");
            });
        }

        // 🎯 Filters
        if ($request->filled('business_type')) {
            $query->where('partners.business_type', $request->business_type);
        }

        if ($request->filled('status')) {
            $query->where('partners.status', $request->status);
        }

        if ($request->filled('state')) {
            $query->where('partners.state', $request->state);
        }

        if ($request->filled('city')) {
            $query->where('partners.city', $request->city);
        }

        if ($request->filled('registration_date')) {
            $query->whereDate('partners.created_at', $request->registration_date);
        }

        // 🔃 Sorting (FIXED)
        $sortBy   = $request->get('sort_by', 'created_at');
        $sortDesc = $request->get('sort_desc', 1) == 1 ? 'desc' : 'asc';

        $validSortColumns = [
            'business_name' => 'partners.business_name',
            'business_type' => 'partners.business_type',
            'city'          => 'partners.city',
            'state'         => 'partners.state',
            'status'        => 'partners.status',
            'gst_number'    => 'partners.gst_number',
            'created_at'    => 'partners.created_at',
        ];

        if (array_key_exists($sortBy, $validSortColumns)) {
            $query->orderBy($validSortColumns[$sortBy], $sortDesc);
        } else {
            $query->orderBy('partners.created_at', 'desc');
        }

        // 📄 Pagination (Fully Dynamic)
        $perPage  = (int) $request->get('per_page', $request->per_page);
        $partners = $query->paginate($perPage);

        return response()->json([
            'success'       => true,
            'data'          => $partners->items(),
            'current_page'  => $partners->currentPage(),
            'per_page'      => $partners->perPage(),
            'total'         => $partners->total(),
            'last_page'     => $partners->lastPage(),
        ]);

    // } catch (\Exception $e) {
    //     return response()->json([
    //         'success' => false,
    //         'message' => 'Error fetching partners',
    //         'error'   => $e->getMessage(),
    //     ], 500);
    // }
}


    public function changeStatus(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:partners,id',
            'status' => 'required|in:rejected,approved,pending'
        ]);
        // try {
            Partner::whereIn('id', $request->ids)
                ->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully'
            ]);

        // } catch (\Exception $e) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Error updating status'
        //     ], 500);
        // }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:partners,id'
        ]);

        try {
            // Delete associated users first
            $userIds = Partner::whereIn('id', $request->ids)
                ->pluck('user_id')
                ->toArray();

            // Delete partners
            Partner::whereIn('id', $request->ids)->delete();
            
            // Delete users
            \App\Models\User::whereIn('id', $userIds)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Partners deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting partners'
            ], 500);
        }
    }

    public function show($id)
    {
            // $partner = Partner::find(base64_decode($id));
        // try {
            $partner = Partner::with('user')
                ->find(base64_decode($id));

            return response()->json([
                'success' => true,
                'data' => $partner
            ]);

        // } catch (\Exception $e) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Partner not found'
        //     ], 404);
        // }
    }

    public function update(Request $request, $id)
    {
        try {
            $partner = Partner::findOrFail(base64_decode($id));
            
            $validated = $request->validate([
                'business_name' => 'required|string|max:255',
                'business_type' => 'required|string|max:100',
                'gst_number' => 'nullable|string|max:50',
                'address' => 'required|string',
                'city' => 'required|string|max:100',
                'state' => 'required|string|max:100',
                'status' => 'required|in:active,inactive,pending,suspended'
            ]);

            $partner->update($validated);

            // Update user details if provided
            if ($request->has('user')) {
                $partner->user->update($request->user);
            }

            return response()->json([
                'success' => true,
                'message' => 'Partner updated successfully',
                'data' => $partner
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating partner'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'mobile' => [
                'required',
                'regex:/^[0-9]{10}$/',
                'unique:users,phone'
            ],            
            'email' => 'required|email|unique:users,email',
            'gst_no' => 'nullable|string|max:50',
            'business_type' => 'required|string|max:100',
            'address' => 'required|string',
            // 'city' => 'required|max:100',
            // 'state' => 'required|max:100',
        ], [
            'mobile.unique' => 'This mobile number is already registered.',
            'email.unique' => 'This email is already registered.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'message' => 'Validation failed'
            ], 422);
        }

        // Start transaction
        \DB::beginTransaction();

        try {
            // Generate a random password
            $password = Str::random(12);
            
            $nameParts = explode(' ', $request->contact_person, 2);
            $firstName = $nameParts[0];
            $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

            // Create or update user
            $user = User::updateOrCreate(
                ['email' => $request->email],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'mobile_code' => '+91', // Default to India
                    'phone' => $request->mobile,
                    'password' => Hash::make($password),
                    'user_type' => 'partner',
                    'status' => 'A', // Active
                    'email_verified_at' => now(), // Auto verify for partners
                ]
            );

            // Create or update partner details
            $partner = Partner::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'business_name' => $request->business_name,
                    'business_type' => $request->business_type,
                    'phone_number' => $request->mobile,
                    'gst_number' => $request->gst_no,
                    'address' => $request->address,
                    'city' => $request->city,
                    'state' => $request->state,
                    'status' => 'pending', // Set initial status as pending
                ]
            );

            \DB::commit();

            return response()->json([
                'message' => 'Partner registered successfully!',
                'data' => [
                    'user' => $user,
                    'partner' => $partner,
                ],
            ], 201);

        } catch (\Exception $e) {
            \DB::rollBack();
            
            return response()->json([
                'message' => 'Registration failed. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send welcome email to partner with login credentials
     */
    private function sendWelcomeEmail($user, $password)
    {
        // Implement email sending logic here
        // You can use Laravel Mail or any email service
    }
}
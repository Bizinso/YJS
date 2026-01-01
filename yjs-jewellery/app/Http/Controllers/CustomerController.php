<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
  public function index(Request $request)
{
    $query = Customer::with(['user:id,first_name,last_name,email,phone,status,created_at']);

    // 🔍 Global Search
    if ($request->filled('globalSearch')) {
        $search = $request->globalSearch;
        $query->whereHas('user', function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    // 🔹 Status Filter
    if ($request->filled('status')) {
        $query->whereHas('user', function ($q) use ($request) {
            $q->where('status', $request->status);
        });
    }

    // 🔹 Gender Filter
    if ($request->filled('gender')) {
        $query->where('gender', $request->gender);
    }

    // 🔹 Registration Date Filter
    if ($request->filled('registration_date')) {
        $query->whereDate('created_at', $request->registration_date);
    }

    // 🔹 Sorting - FIXED
    $sortBy = $request->get('sort_by', 'created_at');
    $sortDesc = $request->get('sort_desc', '1') == '1';

    // Handle sorting for user related fields
    if ($sortBy === 'name') {
        $query->join('users', 'customers.user_id', '=', 'users.id')
            ->orderBy('users.first_name', $sortDesc ? 'desc' : 'asc')
            ->orderBy('users.last_name', $sortDesc ? 'desc' : 'asc');
    } elseif ($sortBy === 'status') {
        $query->join('users', 'customers.user_id', '=', 'users.id')
            ->orderBy('users.status', $sortDesc ? 'desc' : 'asc');
    } elseif ($sortBy === 'created_at') {
        $query->orderBy('customers.created_at', $sortDesc ? 'desc' : 'asc');
    } else {
        $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');
    }

    // Select only necessary columns
    if (in_array($sortBy, ['name', 'status'])) {
        $query->select('customers.*');
    }

    // 📄 Pagination
    $perPage = $request->get('per_page', 10);
    $customers = $query->paginate($perPage);

    // 🎯 Transform data
    $data = $customers->map(function ($customer) {
        return [
            'id' => $customer->id,
            'customer_id' => $customer->id,
            'name' => $customer->user->first_name . ' ' . $customer->user->last_name,
            'email' => $customer->user->email,
            'mobile' => $customer->user->phone,
            'gender' => $customer->gender,
            'dob' => $customer->dob,
            'status' => $customer->user->status,
            'created_at' => $customer->created_at,
            'user' => $customer->user,
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'current_page' => $customers->currentPage(),
            'per_page' => $customers->perPage(),
            'total' => $customers->total(),
            'last_page' => $customers->lastPage(),
            'from' => $customers->firstItem(),
            'to' => $customers->lastItem()
        ]
    ]);
}


    /**
     * Display the specified customer.
     */
    public function show($id)
    {
        try {
            $customer = Customer::with([
                'user:id,first_name,last_name,email,phone,status,profile_image,created_at',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $customer
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Change customer status.
     */
    public function changeStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:customers,id',
            'status' => 'required|in:A,I,D'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $customers = Customer::whereIn('id', $request->ids)->with('user')->get();

            foreach ($customers as $customer) {
                if ($customer->user) {
                    $customer->user->update(['status' => $request->status]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => $customers
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete customers.
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:customers,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $customers = Customer::whereIn('id', $request->ids)->with('user')->get();

            foreach ($customers as $customer) {
                // Soft delete customer
                $customer->delete();

                // Soft delete associated user if no other roles exist
                if ($customer->user) {
                    $customer->user->delete();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customers deleted successfully',
                'count' => count($request->ids)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete customers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6',
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date',
            'profile_image' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create user
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => bcrypt($request->password),
                'user_type' => 'customer',
                'status' => 'A',
                'mobile_code' => '+91' // Default for India, adjust as needed
            ]);

            // Create customer
            $customer = Customer::create([
                'user_id' => $user->id,
                'gender' => $request->gender,
                'dob' => $request->dob
            ]);

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('profiles', 'public');
                $user->update(['profile_image' => asset('storage/' . $path)]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully',
                'data' => $customer->load('user')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $customer->user_id,
            'phone' => 'required|string|unique:users,phone,' . $customer->user_id,
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date',
            'profile_image' => 'nullable|image|max:2048',
            'password' => 'nullable|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Update user
            $userData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone
            ];

            if ($request->filled('password')) {
                $userData['password'] = bcrypt($request->password);
            }

            $customer->user->update($userData);

            // Update customer
            $customer->update([
                'gender' => $request->gender,
                'dob' => $request->dob
            ]);

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('profiles', 'public');
                $customer->user->update(['profile_image' => asset('storage/' . $path)]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully',
                'data' => $customer->load('user')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer statistics.
     */
    public function statistics()
    {
        try {
            $total = Customer::count();
            $active = Customer::whereHas('user', function ($q) {
                $q->where('status', 'A');
            })->count();
            $inactive = Customer::whereHas('user', function ($q) {
                $q->where('status', 'I');
            })->count();
            $deleted = Customer::whereHas('user', function ($q) {
                $q->where('status', 'D');
            })->count();

            $genderStats = Customer::select('gender', DB::raw('count(*) as count'))
                ->groupBy('gender')
                ->get();

            $registrationStats = Customer::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
                ->whereDate('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'active' => $active,
                    'inactive' => $inactive,
                    'deleted' => $deleted,
                    'gender_stats' => $genderStats,
                    'registration_stats' => $registrationStats
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

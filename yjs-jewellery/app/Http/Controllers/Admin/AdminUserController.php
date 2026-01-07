<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Permission;
use App\Models\LoginAttempt;
use App\Models\UserSession;
use App\Models\UserNote;
use App\Models\UserVerification;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Activitylog\Models\Activity;

/**
 * Admin User Controller
 *
 * Comprehensive user management for admin panel.
 */
class AdminUserController extends Controller
{
    // ============ USER DASHBOARD ============

    /**
     * Get user management dashboard statistics.
     */
    public function dashboard(): JsonResponse
    {
        $stats = [
            'total_users' => User::count(),
            'total_customers' => User::where('user_type', 'customer')->count(),
            'total_partners' => User::where('user_type', 'partner')->count(),
            'total_employees' => User::where('user_type', 'employee')->count(),
            'active_users' => User::where('status', 'A')->count(),
            'inactive_users' => User::where('status', 'I')->count(),
            'locked_users' => User::where('is_locked', true)->count(),
            'new_today' => User::whereDate('created_at', today())->count(),
            'new_this_week' => User::where('created_at', '>=', now()->startOfWeek())->count(),
            'new_this_month' => User::where('created_at', '>=', now()->startOfMonth())->count(),
            'by_type' => User::select('user_type', DB::raw('count(*) as count'))
                ->groupBy('user_type')
                ->pluck('count', 'user_type'),
            'pending_verifications' => UserVerification::pending()->count(),
            'failed_logins_24h' => LoginAttempt::failed()->recent(24)->count(),
            'active_sessions' => UserSession::active()->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    // ============ USER LISTING ============

    /**
     * List all users with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->user_type) {
            $query->where('user_type', $request->user_type);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->is_locked !== null) {
            $query->where('is_locked', $request->is_locked);
        }
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $sortBy = $request->sort_by ?? 'created_at';
        $sortDir = $request->sort_dir ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        $users = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Get single user details.
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with([
            'customer',
            'partner',
            'employee.department',
            'employee.userrole',
            'permissions',
        ])->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Get additional stats
        $user->order_count = DB::table('orders')->where('customer_id', $id)->count();
        $user->total_spent = DB::table('orders')
            ->where('customer_id', $id)
            ->where('order_status', '!=', 'cancelled')
            ->sum('order_total');
        $user->login_count = LoginAttempt::forUser($id)->successful()->count();
        $user->last_login = LoginAttempt::forUser($id)->successful()->latest()->first();
        $user->active_sessions = UserSession::forUser($id)->active()->count();
        $user->notes = UserNote::forUser($id)->with('createdByUser:id,first_name,last_name')->latest()->get();
        $user->verifications = UserVerification::forUser($id)->get();

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Update user status.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:A,I',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $user->status = $request->status;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User status updated',
            'data' => $user,
        ]);
    }

    /**
     * Lock a user account.
     */
    public function lock(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
            'duration_hours' => 'nullable|integer|min:1|max:8760',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $user->is_locked = true;
        $user->lock_reason = $request->reason;
        $user->locked_until = $request->duration_hours
            ? now()->addHours($request->duration_hours)
            : null;
        $user->save();

        // Revoke all active sessions
        UserSession::forUser($id)->active()->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'User account locked',
            'data' => $user,
        ]);
    }

    /**
     * Unlock a user account.
     */
    public function unlock(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $user->is_locked = false;
        $user->lock_reason = null;
        $user->locked_until = null;
        $user->failed_login_attempts = 0;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User account unlocked',
            'data' => $user,
        ]);
    }

    /**
     * Reset user password.
     */
    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8',
            'notify_user' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Revoke all sessions on password reset
        UserSession::forUser($id)->active()->update(['is_active' => false]);

        // Send password reset notification if notify_user is true
        if ($request->boolean('notify_user')) {
            $notificationService = app(NotificationService::class);
            $notificationService->sendPasswordResetNotification(
                $user,
                $request->password,
                $request->user()
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully' . ($request->boolean('notify_user') ? ' and notification sent' : ''),
        ]);
    }

    // ============ USER NOTES ============

    /**
     * Add note to user.
     */
    public function addNote(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'note' => 'required|string|max:2000',
            'type' => 'nullable|in:general,warning,important,support',
            'is_pinned' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $note = UserNote::create([
            'user_id' => $id,
            'created_by' => auth()->id(),
            'note' => $request->note,
            'type' => $request->type ?? 'general',
            'is_pinned' => $request->is_pinned ?? false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Note added',
            'data' => $note->load('createdByUser:id,first_name,last_name'),
        ]);
    }

    /**
     * Delete user note.
     */
    public function deleteNote(int $userId, int $noteId): JsonResponse
    {
        $note = UserNote::where('user_id', $userId)->where('id', $noteId)->first();

        if (!$note) {
            return response()->json([
                'success' => false,
                'message' => 'Note not found',
            ], 404);
        }

        $note->delete();

        return response()->json([
            'success' => true,
            'message' => 'Note deleted',
        ]);
    }

    // ============ USER SESSIONS ============

    /**
     * Get user sessions.
     */
    public function sessions(int $id): JsonResponse
    {
        $sessions = UserSession::forUser($id)
            ->orderBy('last_activity_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    /**
     * Revoke a user session.
     */
    public function revokeSession(int $userId, int $sessionId): JsonResponse
    {
        $session = UserSession::where('user_id', $userId)
            ->where('id', $sessionId)
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
            ], 404);
        }

        $session->revoke();

        return response()->json([
            'success' => true,
            'message' => 'Session revoked',
        ]);
    }

    /**
     * Revoke all user sessions.
     */
    public function revokeAllSessions(int $id): JsonResponse
    {
        $count = UserSession::forUser($id)->active()->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => "{$count} sessions revoked",
        ]);
    }

    // ============ ACTIVITY LOG ============

    /**
     * Get user activity log.
     */
    public function activityLog(Request $request, int $id): JsonResponse
    {
        $query = Activity::where('causer_id', $id)
            ->where('causer_type', User::class);

        if ($request->log_name) {
            $query->where('log_name', $request->log_name);
        }
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $activities = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }

    /**
     * Get login attempts for a user.
     */
    public function loginAttempts(Request $request, int $id): JsonResponse
    {
        $query = LoginAttempt::forUser($id);

        if ($request->successful !== null) {
            $query->where('successful', $request->successful);
        }

        $attempts = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $attempts,
        ]);
    }

    // ============ ROLES MANAGEMENT ============

    /**
     * List all roles.
     */
    public function roles(Request $request): JsonResponse
    {
        $query = Role::withCount('employees');

        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->search) {
            $query->where('first_name', 'like', "%{$request->search}%");
        }

        $roles = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * Create a new role.
     */
    public function createRole(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles',
            'slug' => 'nullable|string|max:255|unique:roles',
            'description' => 'nullable|string|max:500',
            'department_id' => 'nullable|exists:departments,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = Role::create([
            'name' => $request->name,
            'slug' => $request->slug ?? str()->slug($request->name),
            'description' => $request->description,
            'department_id' => $request->department_id,
            'status' => 'A',
        ]);

        if ($request->permissions) {
            $role->permissions()->sync($request->permissions);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role created',
            'data' => $role->load('permissions'),
        ], 201);
    }

    /**
     * Update a role.
     */
    public function updateRole(Request $request, int $id): JsonResponse
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255|unique:roles,name,' . $id,
            'slug' => 'nullable|string|max:255|unique:roles,slug,' . $id,
            'description' => 'nullable|string|max:500',
            'department_id' => 'nullable|exists:departments,id',
            'status' => 'nullable|in:A,I',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $role->update($request->only(['name', 'slug', 'description', 'department_id', 'status']));

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role updated',
            'data' => $role->fresh()->load('permissions'),
        ]);
    }

    /**
     * Delete a role.
     */
    public function deleteRole(int $id): JsonResponse
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete system role',
            ], 400);
        }

        // Check if role is in use
        $usersCount = Employee::where('role_id', $id)->count();
        if ($usersCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Role is assigned to {$usersCount} users",
            ], 400);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted',
        ]);
    }

    // ============ PERMISSIONS MANAGEMENT ============

    /**
     * List all permissions.
     */
    public function permissions(Request $request): JsonResponse
    {
        $query = Permission::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                    ->orWhere('slug', 'like', "%{$request->search}%");
            });
        }

        $permissions = $query->orderBy('name')->get();

        // Group by module if requested
        if ($request->grouped) {
            $grouped = $permissions->groupBy(function ($permission) {
                $parts = explode('.', $permission->slug);
                return $parts[0] ?? 'general';
            });
            return response()->json([
                'success' => true,
                'data' => $grouped,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ]);
    }

    /**
     * Assign permissions to user.
     */
    public function assignPermissions(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $user->permissions()->sync($request->permissions);

        return response()->json([
            'success' => true,
            'message' => 'Permissions updated',
            'data' => $user->permissions,
        ]);
    }

    // ============ VERIFICATION ============

    /**
     * List pending verifications.
     */
    public function verifications(Request $request): JsonResponse
    {
        $query = UserVerification::with('user:id,first_name,last_name,email,phone');

        if ($request->status) {
            $query->where('status', $request->status);
        } else {
            $query->pending();
        }

        if ($request->type) {
            $query->ofType($request->type);
        }

        $verifications = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $verifications,
        ]);
    }

    /**
     * Approve verification.
     */
    public function approveVerification(int $id): JsonResponse
    {
        $verification = UserVerification::find($id);

        if (!$verification) {
            return response()->json([
                'success' => false,
                'message' => 'Verification not found',
            ], 404);
        }

        $verification->verify(auth()->id());

        // Update user's verified status based on type
        $user = $verification->user;
        if ($verification->type === UserVerification::TYPE_EMAIL) {
            $user->email_verified_at = now();
            $user->save();
        } elseif ($verification->type === UserVerification::TYPE_PHONE) {
            $user->phone_verified_at = now();
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification approved',
            'data' => $verification->fresh(),
        ]);
    }

    /**
     * Reject verification.
     */
    public function rejectVerification(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $verification = UserVerification::find($id);

        if (!$verification) {
            return response()->json([
                'success' => false,
                'message' => 'Verification not found',
            ], 404);
        }

        $verification->reject($request->reason, auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Verification rejected',
            'data' => $verification->fresh(),
        ]);
    }

    // ============ SECURITY ============

    /**
     * Get security overview.
     */
    public function securityOverview(): JsonResponse
    {
        $data = [
            'failed_logins_24h' => LoginAttempt::failed()->recent(24)->count(),
            'failed_logins_7d' => LoginAttempt::failed()->recent(168)->count(),
            'locked_accounts' => User::where('is_locked', true)->count(),
            'active_sessions' => UserSession::active()->count(),
            'suspicious_ips' => LoginAttempt::failed()
                ->recent(24)
                ->select('ip_address', DB::raw('count(*) as attempts'))
                ->groupBy('ip_address')
                ->having('attempts', '>=', 5)
                ->orderBy('attempts', 'desc')
                ->limit(10)
                ->get(),
            'recent_lockouts' => User::where('is_locked', true)
                ->orderBy('updated_at', 'desc')
                ->limit(10)
                ->get(['id', 'first_name', 'last_name', 'email', 'lock_reason', 'locked_until']),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Bulk update user status.
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'status' => 'required|in:A,I',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $count = User::whereIn('id', $request->user_ids)
            ->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => "{$count} users updated",
        ]);
    }

    /**
     * Export users.
     */
    public function export(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->user_type) {
            $query->where('user_type', $request->user_type);
        }

        $users = $query->get([
            'id', 'first_name', 'last_name', 'email', 'phone',
            'user_type', 'status', 'created_at', 'last_login_at'
        ]);

        return response()->json([
            'success' => true,
            'data' => $users,
            'count' => $users->count(),
        ]);
    }
}

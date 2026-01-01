<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;
use App\Models\UserPermission;


class PermissionController extends Controller
{
    public function index()
    {
        // Fetch all menus and permissions once
        $menus = DB::table('menus')->get();
        $permissions = DB::table('permissions')->get();

        // Recursive function to build nested menu tree
        $buildTree = function ($parentId) use (&$buildTree, $menus, $permissions) {
            $menuList = $menus->where('parent_id', $parentId);
            $result = [];

            foreach ($menuList as $menu) {
                $menuPermissions = $permissions
                    ->where('menu_id', $menu->id)
                    ->map(function ($perm) {
                        return [
                            'id' => $perm->id,
                            'name' => $perm->name,
                            'slug' => $perm->slug,
                        ];
                    })
                    ->values();

                $children = $buildTree($menu->id);

                $result[] = [
                    'id' => $menu->id,
                    'name' => $menu->title,
                    'slug' => $menu->slug,
                    'permissions' => $menuPermissions,
                    'children' => $children,
                ];
            }

            return $result;
        };

        $tree = $buildTree(0);

        // Get active users
        $users = DB::table('users')
            ->select(DB::raw('CONCAT(first_name, " ", last_name) AS label'), 'id AS value')
            ->where('user_type', 'employee')
            ->whereNull('deleted_at')
            ->where('status', 'A')
            ->get();

        return response()->json([
            'menus' => $tree,
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user' => 'required|exists:users,id',
            'selected_value' => 'required|array',
            'selected_value.*' => 'integer'
        ]);

        try {
            DB::transaction(function() use ($request) {
                // Get the existing permission IDs from the database that are in the selected_value
                $existingPermissionIds = Permission::whereIn('id', $request->selected_value)->pluck('id')->toArray();

                // Clear existing permissions
                UserPermission::where('user_id', $request->user)->delete();

                // Add new permissions only if there are any existing permission IDs
                if (!empty($existingPermissionIds)) {
                    $permissions = [];
                    $timestamp = now();

                    foreach ($existingPermissionIds as $permissionId) {
                        $permissions[] = [
                            'user_id' => $request->user,
                            'permission_id' => $permissionId,
                        ];
                    }

                    UserPermission::insert($permissions);
                }
            });

            return response()->json([
                'data' => 'Permissions updated successfully!', 
                'status' => 'success'
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to update permissions: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Get permissions for a specific user
     */
    public function getPermissionFromUserId($id)
    {
        try {
            $userId = base64_decode($id);
            $permissionIds = UserPermission::where('user_id', $userId)
                ->pluck('permission_id');
            
            return response(['responseData' => $permissionIds], 200);
        } catch (\Exception $e) {
            return response([
                'message' => 'Failed to fetch permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Middleware to ensure the authenticated user is an admin or employee.
 * This prevents customers/partners from accessing admin routes even with valid tokens.
 */
class EnsureAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Verify user is an admin or employee
        if (!in_array($user->user_type, ['admin', 'employee'])) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin privileges required.',
            ], 403);
        }

        // Verify the token has employee ability (skip for TransientToken in tests)
        $token = $request->user()->currentAccessToken();
        if ($token && method_exists($token, 'can')) {
            // Real Sanctum token - check abilities
            $abilities = $token->abilities ?? [];
            if (!empty($abilities) && !in_array('employee', $abilities) && !in_array('*', $abilities)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token scope for admin access.',
                ], 403);
            }
        }

        return $next($request);
    }
}

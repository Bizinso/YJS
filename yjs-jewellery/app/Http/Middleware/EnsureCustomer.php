<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Middleware to ensure the authenticated user is a customer.
 * This prevents admins/partners from accessing customer-only routes.
 */
class EnsureCustomer
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

        // Verify user is a customer
        if ($user->user_type !== 'customer') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Customer account required.',
            ], 403);
        }

        // Verify the token has customer ability (skip for TransientToken in tests)
        $token = $request->user()->currentAccessToken();
        if ($token && method_exists($token, 'can')) {
            // Real Sanctum token - check abilities
            $abilities = $token->abilities ?? [];
            if (!empty($abilities) && !in_array('customer', $abilities) && !in_array('*', $abilities)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token scope for customer access.',
                ], 403);
            }
        }

        return $next($request);
    }
}

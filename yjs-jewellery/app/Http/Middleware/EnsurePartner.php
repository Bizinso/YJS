<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Partner;

/**
 * Middleware to ensure the authenticated user is an approved partner.
 * This prevents non-partners and unapproved partners from accessing partner routes.
 */
class EnsurePartner
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

        // Verify user is a partner
        if ($user->user_type !== 'partner') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Partner account required.',
            ], 403);
        }

        // Verify the token has partner ability
        $token = $request->user()->currentAccessToken();
        if ($token && !in_array('partner', $token->abilities ?? [])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token scope for partner access.',
            ], 403);
        }

        // Check if partner is approved
        $partner = Partner::where('user_id', $user->id)->first();

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner profile not found.',
            ], 404);
        }

        if (!$partner->isApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Partner account is not approved.',
                'partner_status' => $partner->status,
            ], 403);
        }

        // Attach partner to request for easy access in controllers
        $request->merge(['partner' => $partner]);

        return $next($request);
    }
}

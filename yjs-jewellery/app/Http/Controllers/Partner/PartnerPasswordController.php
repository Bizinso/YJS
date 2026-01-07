<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Carbon\Carbon;

/**
 * Partner Password Controller
 *
 * Handles password management for B2B partners including
 * password changes and password-based login.
 *
 * @package App\Http\Controllers\Partner
 */
class PartnerPasswordController extends Controller
{
    /**
     * Change password for authenticated partner.
     *
     * Partners can change their password. If no password is set,
     * they can set one without providing current password.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = Auth::user();
        $hasPassword = !empty($user->password);

        $rules = [
            'new_password' => [
                'required',
                'string',
                Password::min(8)->mixedCase()->numbers(),
            ],
            'new_password_confirmation' => 'required|same:new_password',
        ];

        if ($hasPassword) {
            $rules['current_password'] = 'required|string';
        }

        $validator = Validator::make($request->all(), $rules, [
            'current_password.required' => 'Current password is required.',
            'new_password.required' => 'New password is required.',
            'new_password_confirmation.same' => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($hasPassword && !Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }

    /**
     * Set password for the first time.
     *
     * For partners who registered via OTP and want to set a password.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setPassword(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!empty($user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password is already set. Use change password instead.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'password' => [
                'required',
                'string',
                Password::min(8)->mixedCase()->numbers(),
            ],
            'password_confirmation' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password set successfully.',
        ]);
    }

    /**
     * Check if partner has a password set.
     *
     * @return JsonResponse
     */
    public function hasPassword(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'has_password' => !empty($user->password),
        ]);
    }

    /**
     * Login with email/phone and password.
     *
     * Alternative to OTP-based login for partners.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function loginWithPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $identifier = $request->identifier;
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $field = $isEmail ? 'email' : 'phone';

        $user = User::where($field, $identifier)
            ->where('user_type', 'partner')
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if (empty($user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password not set. Please use OTP login.',
            ], 422);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if ($user->status !== 'A') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not active.',
            ], 403);
        }

        // Check partner approval status
        $partner = $user->partner;
        if (!$partner || !$partner->isApproved()) {
            $status = $partner ? $partner->status : 'not_found';
            $message = match ($status) {
                'pending' => 'Your partner account is pending approval.',
                'rejected' => 'Your partner account has been rejected.',
                default => 'Partner profile not found.',
            };

            return response()->json([
                'success' => false,
                'message' => $message,
                'partner_status' => $status,
            ], 403);
        }

        $token = $user->createToken('PARTNER_TOKEN', ['partner'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $user,
            'partner' => $partner,
        ]);
    }

    /**
     * Forgot password - sends OTP for password reset.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $identifier = $request->identifier;
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $field = $isEmail ? 'email' : 'phone';

        $user = User::where($field, $identifier)
            ->where('user_type', 'partner')
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No partner account found with this ' . ($isEmail ? 'email' : 'phone number') . '.',
            ], 404);
        }

        // Generate OTP (6-digit random number)
        $otp = rand(100000, 999999);

        DB::table('otps')->where('identifier', $identifier)->delete();

        DB::table('otps')->insert([
            'identifier' => $identifier,
            'otp' => $otp,
            'expires_at' => Carbon::now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Send OTP via email/SMS
        if ($isEmail) {
            \Mail::raw("Your password reset OTP is: {$otp}. Valid for 10 minutes.", function ($message) use ($identifier) {
                $message->to($identifier)->subject('Partner Password Reset OTP - YJS Jewellers');
            });
        } else {
            app(\App\Services\Notification\SmsService::class)->send($identifier, "Your YJS Jewellers partner password reset OTP is: {$otp}. Valid for 10 minutes.");
        }

        $response = [
            'success' => true,
            'message' => 'OTP sent to your ' . ($isEmail ? 'email' : 'phone') . '.',
        ];

        // Only include OTP in response for local/testing environment
        if (app()->environment('local', 'testing')) {
            $response['otp'] = $otp;
        }

        return response()->json($response);
    }

    /**
     * Verify OTP for password reset.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyResetOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required',
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $otpRecord = DB::table('otps')
            ->where('identifier', $request->identifier)
            ->orderBy('id', 'DESC')
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'OTP not found. Please request a new one.',
            ], 404);
        }

        if (Carbon::now()->greaterThan($otpRecord->expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'OTP has expired. Please request a new one.',
            ], 410);
        }

        if ($otpRecord->otp != $request->otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP.',
            ], 401);
        }

        $resetToken = bin2hex(random_bytes(32));

        DB::table('otps')->where('identifier', $request->identifier)->update([
            'otp' => $resetToken,
            'expires_at' => Carbon::now()->addMinutes(15),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully.',
            'reset_token' => $resetToken,
        ]);
    }

    /**
     * Reset password using reset token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required',
            'reset_token' => 'required|string',
            'password' => [
                'required',
                'string',
                Password::min(8)->mixedCase()->numbers(),
            ],
            'password_confirmation' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $tokenRecord = DB::table('otps')
            ->where('identifier', $request->identifier)
            ->where('otp', $request->reset_token)
            ->first();

        if (!$tokenRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset token.',
            ], 401);
        }

        if (Carbon::now()->greaterThan($tokenRecord->expires_at)) {
            DB::table('otps')->where('identifier', $request->identifier)->delete();
            return response()->json([
                'success' => false,
                'message' => 'Reset token has expired.',
            ], 410);
        }

        $isEmail = filter_var($request->identifier, FILTER_VALIDATE_EMAIL);
        $field = $isEmail ? 'email' : 'phone';

        $user = User::where($field, $request->identifier)
            ->where('user_type', 'partner')
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('otps')->where('identifier', $request->identifier)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully.',
        ]);
    }
}

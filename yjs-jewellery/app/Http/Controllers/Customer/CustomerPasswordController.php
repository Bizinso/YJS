<?php

namespace App\Http\Controllers\Customer;

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

class CustomerPasswordController extends Controller
{
    /**
     * Change password for authenticated customer.
     * Requires current password if one is already set.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Check if user has a password set
        $hasPassword = !empty($user->password);

        $rules = [
            'new_password' => [
                'required',
                'string',
                Password::min(8)
                    ->mixedCase()
                    ->numbers(),
            ],
            'new_password_confirmation' => 'required|same:new_password',
        ];

        // Only require current password if user already has one set
        if ($hasPassword) {
            $rules['current_password'] = 'required|string';
        }

        $validator = Validator::make($request->all(), $rules, [
            'current_password.required' => 'Current password is required.',
            'new_password.required' => 'New password is required.',
            'new_password_confirmation.required' => 'Password confirmation is required.',
            'new_password_confirmation.same' => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify current password if user has one
        if ($hasPassword && !Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        // Update password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }

    /**
     * Set password for the first time (for OTP-only users).
     */
    public function setPassword(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Check if user already has a password
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
                Password::min(8)
                    ->mixedCase()
                    ->numbers(),
            ],
            'password_confirmation' => 'required|same:password',
        ], [
            'password.required' => 'Password is required.',
            'password_confirmation.required' => 'Password confirmation is required.',
            'password_confirmation.same' => 'Password confirmation does not match.',
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
            'message' => 'Password set successfully. You can now login with email and password.',
        ]);
    }

    /**
     * Check if current user has a password set.
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
     * Initiate forgot password - sends OTP for password reset.
     * This is a public endpoint.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required', // email or phone
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

        // Check if user exists
        $user = User::where($field, $identifier)
            ->where('user_type', 'customer')
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this ' . ($isEmail ? 'email' : 'phone number') . '.',
            ], 404);
        }

        // Generate OTP
        $otp = 123456; // In production: rand(100000, 999999);

        // Delete old OTPs for this identifier
        DB::table('otps')->where('identifier', $identifier)->delete();

        // Store new OTP with purpose
        DB::table('otps')->insert([
            'identifier' => $identifier,
            'otp' => $otp,
            'expires_at' => Carbon::now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Send OTP via email/SMS (implementation depends on your notification service)
        if ($isEmail) {
            // Send email with OTP
            // Mail::to($identifier)->send(new PasswordResetOTP($otp));
        } else {
            // Send SMS with OTP
            // SMS::send($identifier, "Your password reset OTP is: {$otp}");
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your ' . ($isEmail ? 'email' : 'phone') . '.',
            'otp' => $otp, // Remove in production
        ]);
    }

    /**
     * Verify OTP for password reset.
     * Returns a reset token if OTP is valid.
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

        $identifier = $request->identifier;
        $inputOtp = $request->otp;

        // Find OTP record
        $otpRecord = DB::table('otps')
            ->where('identifier', $identifier)
            ->orderBy('id', 'DESC')
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'OTP not found. Please request a new one.',
            ], 404);
        }

        // Check expiration
        if (Carbon::now()->greaterThan($otpRecord->expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'OTP has expired. Please request a new one.',
            ], 410);
        }

        // Check OTP value
        if ($otpRecord->otp != $inputOtp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP.',
            ], 401);
        }

        // Generate a temporary reset token
        $resetToken = bin2hex(random_bytes(32));

        // Store reset token (using the same OTP table with updated identifier)
        DB::table('otps')->where('identifier', $identifier)->update([
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
     * Reset password using the reset token.
     * This is a public endpoint.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required',
            'reset_token' => 'required|string',
            'password' => [
                'required',
                'string',
                Password::min(8)
                    ->mixedCase()
                    ->numbers(),
            ],
            'password_confirmation' => 'required|same:password',
        ], [
            'password.required' => 'Password is required.',
            'password_confirmation.required' => 'Password confirmation is required.',
            'password_confirmation.same' => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $identifier = $request->identifier;
        $resetToken = $request->reset_token;

        // Verify reset token
        $tokenRecord = DB::table('otps')
            ->where('identifier', $identifier)
            ->where('otp', $resetToken)
            ->first();

        if (!$tokenRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset token.',
            ], 401);
        }

        // Check expiration
        if (Carbon::now()->greaterThan($tokenRecord->expires_at)) {
            DB::table('otps')->where('identifier', $identifier)->delete();
            return response()->json([
                'success' => false,
                'message' => 'Reset token has expired. Please start over.',
            ], 410);
        }

        // Find user
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $field = $isEmail ? 'email' : 'phone';

        $user = User::where($field, $identifier)
            ->where('user_type', 'customer')
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete the token
        DB::table('otps')->where('identifier', $identifier)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully. You can now login with your new password.',
        ]);
    }

    /**
     * Login with email/phone and password (alternative to OTP).
     * This is a public endpoint.
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
            ->where('user_type', 'customer')
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

        // Check if user is active
        if ($user->status !== 'A') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not active.',
            ], 403);
        }

        $token = $user->createToken('CUSTOMER_TOKEN', ['customer'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $user,
        ]);
    }
}

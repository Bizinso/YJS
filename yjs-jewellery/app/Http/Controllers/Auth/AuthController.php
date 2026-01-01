<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\User;

class AuthController extends Controller
{
    public function employeelogin(Request $request){
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $employee = User::whereIn('user_type', ['employee', 'admin'])
            ->where('email', $request->email)
            ->first();

        if (!$employee || !Hash::check($request->password, $employee->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $employee->createToken('EMPLOYEE_TOKEN', ['employee'])->plainTextToken;

        $employee->ability = $employee->permissions()
        ->select(DB::raw("'do' as action"), 'slug as subject')
        ->get()
        ->map(fn($item) => [
            'action' => $item->action,
            'subject' => $item->subject,
        ])->toArray();

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'employee' => $employee,
        ]);
    }

    public function employeelogout(Request $request)
    {
        $user = $request->user();

        $user->tokens()
            ->whereJsonContains('abilities', 'employee')
            ->delete();
        
        return response()->json([
            'message' => 'Employee logged out successfully'
        ]);
    }

    public function sendOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => "required",
            'role'       => "required|in:customer,partner"
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $identifier = $request->identifier;
        $role       = $request->role;

        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $field   = $isEmail ? 'email' : 'phone';

        if ($field === 'email') {
            $email = $identifier;
            $mobile = '999' . rand(1000000, 9999999);
            $mobile_code = '+91';

        } else {

            $mobile = $identifier;
            $mobile_code = '+91'; // or based on your country

            // generate dummy email (unique)
            $email = $identifier . '@example.local';
        }

        $user = User::where($field, $identifier)
                    ->where('user_type', $role)
                    ->first();

        if ($role === 'partner' && !$user) {
            return response()->json([
                'status'  => false,
                'message' => "Partner not found. Please register."
            ], 404);
        }

        if ($role === 'customer' && !$user) {
            $user = User::create([
                $field      => $identifier,
                'user_type' => 'customer',
                'first_name'      => 'New',
                'last_name'       => 'Customer',
                'email'     => $email,
                'mobile_code' => $mobile_code,
                'phone'       => $mobile,
            ]);

            Customer::create([
                'user_id' => $user->id
            ]);
        }

        // Generate OTP
        $otp =123456;// rand(100000, 999999);

        DB::table('otps')->where('identifier', $identifier)->delete();

        DB::table('otps')->insert([
            'identifier' => $identifier,
            'otp'        => $otp,
            'expires_at' => Carbon::now()->addMinutes(5),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Send Email
        if ($isEmail) {
            // mail logic...
        } else {
            // sms logic...
        }

        return response()->json([
            'status'  => true,
            'message' => 'OTP sent successfully',
            'otp'     => $otp // remove in production
        ]);
    }


    public function verifyOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => "required",
            'role'       => "required|in:customer,partner",
            'otp'        => "required|digits:6"
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $identifier = $request->identifier;
        $role       = $request->role;
        $inputOtp   = $request->otp;

        // Find OTP in DB
        $otpRecord = DB::table('otps')
            ->where('identifier', $identifier)
            ->orderBy('id', 'DESC')
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'status'  => false,
                'message' => 'OTP not found. Please request a new one.'
            ], 404);
        }

        // Check expiration
        if (Carbon::now()->greaterThan($otpRecord->expires_at)) {
            return response()->json([
                'status'  => false,
                'message' => 'OTP expired. Please request a new one.'
            ], 410);
        }
        
        // Check OTP value
        if ($otpRecord->otp != $inputOtp) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid OTP'
            ], 401);
        }

        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $field   = $isEmail ? 'email' : 'phone';

        $user = User::where($field, $identifier)
                    ->where('user_type', $role)
                    ->first();

        // PARTNER → must exist
        if ($role === 'partner' && !$user) {
            return response()->json([
                'status'  => false,
                'message' => 'Partner not found. Please register.'
            ], 404);
        }
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $field   = $isEmail ? 'email' : 'phone';

        if ($field === 'email') {
            $email = $identifier;
            $mobile = '999' . rand(1000000, 9999999);
            $mobile_code = '+91';

        } else {

            $mobile = $identifier;
            $mobile_code = '+91'; // or based on your country

            // generate dummy email (unique)
            $email = $identifier . '@example.local';
        }
        // CUSTOMER → auto-create if new
        if ($role === 'customer' && !$user) {
            $user = User::create([
                $field      => $identifier,
                'name'      => 'New Customer',
                'user_type' => 'customer',
                'email'     => $email,
                'mobile_code' => $mobile_code,
                'phone'       => $mobile
            ]);

            Customer::create([
                'user_id' => $user->id
            ]);
        }

        DB::table('otps')->where('identifier', $identifier)->delete();

        $tokenName = strtoupper($role) . "_TOKEN";
        $token     = $user->createToken($tokenName,[$role])->plainTextToken;

        return response()->json([
            'status'   => true,
            'message'  => 'OTP Verified Successfully',
            'token'    => $token,
            'user'     => $user,
        ]);
    }

    public function partnerlogout(Request $request)
    {
        $user = $request->user();

        $user->tokens()
            ->whereJsonContains('abilities', 'partner')
            ->delete();
        
        return response()->json([
            'message' => 'Partner logged out successfully'
        ]);
    }

    public function customerlogout(Request $request)
    {
        $user = $request->user();

        $user->tokens()
            ->whereJsonContains('abilities', 'customer')
            ->delete();
        
        return response()->json([
            'message' => 'Customer logged out successfully'
        ]);
    }

    public function adminprofile(){
        $userId = Auth::guard('employee')->user()->id;
        $user = User::leftJoin('employees', 'users.id', '=', 'employees.user_id')
                        ->leftJoin('roles', 'roles.id', 'employees.role_id')
                        ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
                        ->leftJoin('users as reporting_head_user', 'users.reporting_head', '=', 'reporting_head_user.id')
                            ->select(
                                'users.*',
                                'roles.name as role',
                                'departments.name as department',
                                DB::raw('CONCAT(UCASE(SUBSTRING(users.first_name, 1, 1)), LCASE(SUBSTRING(users.first_name, 2))) as first_name'),
                                DB::raw('CONCAT(UCASE(SUBSTRING(users.last_name, 1, 1)), LCASE(SUBSTRING(users.last_name, 2))) as last_name'),
                                DB::raw('CONCAT(UPPER(SUBSTRING(reporting_head_user.first_name, 1, 1)), LOWER(SUBSTRING(reporting_head_user.first_name, 2)), " ", UPPER(SUBSTRING(reporting_head_user.last_name, 1, 1)), LOWER(SUBSTRING(reporting_head_user.last_name, 2))) as reporting_head'),
                                DB::raw('CONCAT(users.first_name, " ", users.last_name) as name')
                            )
                            ->where('users.id', $userId)
                            ->first();

        if ($user) {
            return response()->json([
                'data' => $user,
            ]);
        }

        return response()->json(['error' => 'User not found'], 404);
    }

    public function updateadminprofile(Request $request, $encodedId){
        $id = base64_decode($encodedId);
        $user = User::find($id);
        if (! $user) {
            return response()->json(['message' => 'Employee not found'], 404);
        }
     
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'required',
            'profile_image' => 'nullable|image|mimes:jpg,png,gif|max:800',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->messages(), 'code' => 422], 422);
        }
      
            // $oldData = ActivityLog::getActivityInfo(
            //     User::class,
            //     $id,
            //     [
            //         'users.email',
            //         'users.phone as Phone Number',
            //         DB::raw('CONCAT(reporting_head_user.first_name, " ", reporting_head_user.last_name) as `Reporting head`'),
            //         'users.employee_code as Employee Code',
            //         'users.profile_image as Upload Image',
            //         'users.status as Status',
            //         'users.address as Address',
            //         'roles.name as role',
            //         'departments.name as department',
            //         DB::raw('CONCAT(users.first_name, " ", users.last_name) as name'),
            //         'users.created_at as Created At',
            //         'users.updated_at as Updated At',
            //     ],
            //     [
            //         ['roles', 'users.role_id', '=', 'roles.id'],
            //         ['departments', 'users.department_id', '=', 'departments.id'],
            //         ['users as reporting_head_user', 'users.reporting_head', '=', 'reporting_head_user.id']
            //     ]
            // );
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->phone = $request->phone;

        if ($request->hasFile('profile_image')) {
            if ($user->profile_image) {
                Storage::delete($user->profile_image);
            }
            $path = $request->file('profile_image')->store('profile_images', 'public');
            $user->profile_image = $path;
        }
        $user->save();

        $userId = $user->id;

            // $newData = ActivityLog::getActivityInfo(
            //     User::class,
            //     $id,
            //     [
            //         'users.email',
            //         'users.phone as Phone Number',
            //         DB::raw('CONCAT(reporting_head_user.first_name, " ", reporting_head_user.last_name) as `Reporting head`'),
            //         'users.employee_code as Employee Code',
            //         'users.dob as Date of Birth',
            //         'users.date_of_joining as Date Of Joining',
            //         'users.profile_image as Upload Image',
            //         'users.status as Status',
            //         'users.address as Address',
            //         'roles.name as role',
            //         'departments.name as department',
            //         DB::raw('CONCAT(users.first_name, " ", users.last_name) as name'),
            //         'users.created_at as Created At',
            //         'users.updated_at as Updated At',
            //     ],
            //     [
            //         ['roles', 'users.role_id', '=', 'roles.id'],
            //         ['departments', 'users.department_id', '=', 'departments.id'],
            //         ['users as reporting_head_user', 'users.reporting_head', '=', 'reporting_head_user.id']
            //     ]
            // );
        
            // ActivityLog::logUpdate($user, $oldData, $newData);
            

        return response()->json(['message' => 'Profile updated successfully', 'data' => $user]);
    }

}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Employee;
use App\Models\Permission;
class userSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 🧍‍♀️ CUSTOMER (Frontend Website Login via OTP)
        $customer = User::updateOrCreate(
            ['email' => 'customer@yjs.com'],
            [
                'first_name' => 'John',
                'last_name' => 'Customer',
                'mobile_code' => '+91',
                'phone' => '8888888888',
                'password' => Hash::make('Customer@123'),
                'user_type' => 'customer',
                'status' => 'A',
            ]
        );

        Customer::updateOrCreate(
            ['user_id' => $customer->id],
            [
                'gender' => 'male',
                'dob' => '1995-02-15',
            ]
        );

        // 🧑‍💼 PARTNER (B2B Portal Login via OTP)
        $partner = User::updateOrCreate(
            ['email' => 'partner@yjs.com'],
            [   
                'first_name' => 'ABC',
                'last_name' => 'Jewels',
                'mobile_code' => '+91',
                'phone' => '7777777777',
                'password' => Hash::make('Partner@123'),
                'user_type' => 'partner',
                'status' => 'A',
            ]
        );

        Partner::updateOrCreate(
            ['user_id' => $partner->id],
            [
                'business_name' => 'ABC Gold & Diamonds',
                'business_type' => 'Retailer',
                'phone_number' => '7777777777',
                'gst_number' => '27ABCDE1234F1Z5',
                'address' => 'MG Road, Mumbai',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'status' => 'approved',
            ]
        );

        // 👨‍💻 EMPLOYEE (Backend Panel Login via Email + Password)
        $employee = User::updateOrCreate(
            ['email' => 'employee@yjs.com'],
            [
                'first_name' => 'Ashwini',
                'last_name' => 'Sharma',
                'mobile_code' => '+91',
                'phone' => '6666666666',
                'password' => Hash::make('Employee@123'),
                'user_type' => 'employee',
                'status' => 'A',
            ]
        );

        Employee::updateOrCreate(
            ['email' => 'employee@yjs.com'],
            [
                'user_id' => $employee->id,
                'employee_code' => 'EMP001',
                'first_name' => 'Ashwini',
                'last_name' => 'Sharma',
                'phone' => '6666666666',
                'department_id' => 1,
                'branch_id' => 1,
                'role_id' => 1,
                'password' => Hash::make('Employee@123'),
                'status' => 'A',
            ]
        );

        $allPermissionIds = Permission::pluck('id')->toArray();
        $employee->permissions()->syncWithoutDetaching($allPermissionIds);

        $this->command->info('✅ Users seeded & full permissions assigned to employee.');

        $this->command->info('✅ Users (Customer, Partner, Employee) seeded successfully.');
    }
}

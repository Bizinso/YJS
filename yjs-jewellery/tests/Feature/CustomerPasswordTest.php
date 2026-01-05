<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Tests\TestCase;

class CustomerPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Customer $customer;
    protected Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        $this->country = Country::factory()->create();
        $this->user = User::factory()->customer()->create([
            'password' => null, // Customer without password (OTP-only)
        ]);
        $this->customer = Customer::factory()->forUser($this->user)->create();
    }

    /**
     * Helper to authenticate the customer.
     */
    protected function authenticateCustomer()
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    /**
     * Helper to create OTP record.
     */
    protected function createOtp(string $identifier, string $otp = '123456', int $minutesValid = 10)
    {
        DB::table('otps')->insert([
            'identifier' => $identifier,
            'otp' => $otp,
            'expires_at' => Carbon::now()->addMinutes($minutesValid),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // =====================
    // HAS PASSWORD TESTS
    // =====================

    public function test_can_check_has_password_false(): void
    {
        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/password/has-password');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'has_password' => false,
            ]);
    }

    public function test_can_check_has_password_true(): void
    {
        $this->user->update(['password' => Hash::make('Password123')]);

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/password/has-password');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'has_password' => true,
            ]);
    }

    public function test_unauthenticated_cannot_check_has_password(): void
    {
        $response = $this->getJson('/api/customer/password/has-password');

        $response->assertStatus(401);
    }

    // =====================
    // SET PASSWORD TESTS
    // =====================

    public function test_can_set_password_for_first_time(): void
    {
        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/password/set', [
                'password' => 'NewPassword123',
                'password_confirmation' => 'NewPassword123',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Verify password was set
        $this->user->refresh();
        $this->assertTrue(Hash::check('NewPassword123', $this->user->password));
    }

    public function test_cannot_set_password_if_already_set(): void
    {
        $this->user->update(['password' => Hash::make('ExistingPassword123')]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/password/set', [
                'password' => 'NewPassword123',
                'password_confirmation' => 'NewPassword123',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_set_password_validates_strength(): void
    {
        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/password/set', [
                'password' => 'weak',
                'password_confirmation' => 'weak',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_set_password_requires_confirmation(): void
    {
        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/password/set', [
                'password' => 'NewPassword123',
                'password_confirmation' => 'DifferentPassword123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password_confirmation']);
    }

    // =====================
    // CHANGE PASSWORD TESTS
    // =====================

    public function test_can_change_password_without_current_if_none_set(): void
    {
        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/password/change', [
                'new_password' => 'NewPassword123',
                'new_password_confirmation' => 'NewPassword123',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->user->refresh();
        $this->assertTrue(Hash::check('NewPassword123', $this->user->password));
    }

    public function test_can_change_password_with_current(): void
    {
        $this->user->update(['password' => Hash::make('OldPassword123')]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/password/change', [
                'current_password' => 'OldPassword123',
                'new_password' => 'NewPassword123',
                'new_password_confirmation' => 'NewPassword123',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->user->refresh();
        $this->assertTrue(Hash::check('NewPassword123', $this->user->password));
    }

    public function test_cannot_change_password_with_wrong_current(): void
    {
        $this->user->update(['password' => Hash::make('OldPassword123')]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/password/change', [
                'current_password' => 'WrongPassword123',
                'new_password' => 'NewPassword123',
                'new_password_confirmation' => 'NewPassword123',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ]);
    }

    public function test_change_password_requires_current_if_set(): void
    {
        $this->user->update(['password' => Hash::make('OldPassword123')]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/password/change', [
                'new_password' => 'NewPassword123',
                'new_password_confirmation' => 'NewPassword123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    // =====================
    // FORGOT PASSWORD TESTS
    // =====================

    public function test_can_request_forgot_password_with_email(): void
    {
        $response = $this->postJson('/api/customer/forgot-password', [
            'identifier' => $this->user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('otps', [
            'identifier' => $this->user->email,
        ]);
    }

    public function test_can_request_forgot_password_with_phone(): void
    {
        $response = $this->postJson('/api/customer/forgot-password', [
            'identifier' => $this->user->phone,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_forgot_password_fails_for_nonexistent_user(): void
    {
        $response = $this->postJson('/api/customer/forgot-password', [
            'identifier' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    // =====================
    // VERIFY RESET OTP TESTS
    // =====================

    public function test_can_verify_reset_otp(): void
    {
        $this->createOtp($this->user->email, '123456');

        $response = $this->postJson('/api/customer/verify-reset-otp', [
            'identifier' => $this->user->email,
            'otp' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'reset_token',
            ]);
    }

    public function test_verify_reset_otp_fails_for_wrong_otp(): void
    {
        $this->createOtp($this->user->email, '123456');

        $response = $this->postJson('/api/customer/verify-reset-otp', [
            'identifier' => $this->user->email,
            'otp' => '654321',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_verify_reset_otp_fails_for_expired_otp(): void
    {
        $this->createOtp($this->user->email, '123456', -5); // Expired 5 minutes ago

        $response = $this->postJson('/api/customer/verify-reset-otp', [
            'identifier' => $this->user->email,
            'otp' => '123456',
        ]);

        $response->assertStatus(410)
            ->assertJson([
                'success' => false,
            ]);
    }

    // =====================
    // RESET PASSWORD TESTS
    // =====================

    public function test_can_reset_password_with_valid_token(): void
    {
        $resetToken = bin2hex(random_bytes(32));

        DB::table('otps')->insert([
            'identifier' => $this->user->email,
            'otp' => $resetToken,
            'expires_at' => Carbon::now()->addMinutes(15),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/customer/reset-password', [
            'identifier' => $this->user->email,
            'reset_token' => $resetToken,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->user->refresh();
        $this->assertTrue(Hash::check('NewPassword123', $this->user->password));
    }

    public function test_reset_password_fails_with_invalid_token(): void
    {
        $response = $this->postJson('/api/customer/reset-password', [
            'identifier' => $this->user->email,
            'reset_token' => 'invalid_token',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_reset_password_validates_password_strength(): void
    {
        $resetToken = bin2hex(random_bytes(32));

        DB::table('otps')->insert([
            'identifier' => $this->user->email,
            'otp' => $resetToken,
            'expires_at' => Carbon::now()->addMinutes(15),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/customer/reset-password', [
            'identifier' => $this->user->email,
            'reset_token' => $resetToken,
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    // =====================
    // LOGIN WITH PASSWORD TESTS
    // =====================

    public function test_can_login_with_password(): void
    {
        $this->user->update(['password' => Hash::make('Password123')]);

        $response = $this->postJson('/api/customer/login-password', [
            'identifier' => $this->user->email,
            'password' => 'Password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'token',
                'user',
            ]);
    }

    public function test_can_login_with_phone_and_password(): void
    {
        $this->user->update(['password' => Hash::make('Password123')]);

        $response = $this->postJson('/api/customer/login-password', [
            'identifier' => $this->user->phone,
            'password' => 'Password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->user->update(['password' => Hash::make('Password123')]);

        $response = $this->postJson('/api/customer/login-password', [
            'identifier' => $this->user->email,
            'password' => 'WrongPassword123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_login_fails_for_user_without_password(): void
    {
        $response = $this->postJson('/api/customer/login-password', [
            'identifier' => $this->user->email,
            'password' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Password not set. Please use OTP login.',
            ]);
    }

    public function test_login_fails_for_inactive_user(): void
    {
        $this->user->update([
            'password' => Hash::make('Password123'),
            'status' => 'I', // Inactive
        ]);

        $response = $this->postJson('/api/customer/login-password', [
            'identifier' => $this->user->email,
            'password' => 'Password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_login_fails_for_nonexistent_user(): void
    {
        $response = $this->postJson('/api/customer/login-password', [
            'identifier' => 'nonexistent@example.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }
}

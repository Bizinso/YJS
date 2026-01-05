<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Partner Password Test
 *
 * Tests for partner password management including
 * password changes, forgot password, and password login.
 */
class PartnerPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Partner $partner;
    protected Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        $this->country = Country::factory()->create();
        $this->user = User::factory()->partner()->create([
            'password' => null,
        ]);
        $this->partner = Partner::factory()
            ->forUser($this->user)
            ->approved()
            ->create();
    }

    protected function authenticatePartner()
    {
        return $this->actingAs($this->user, 'sanctum');
    }

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
        $response = $this->authenticatePartner()
            ->getJson('/api/partner/password/has-password');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'has_password' => false,
            ]);
    }

    public function test_can_check_has_password_true(): void
    {
        $this->user->update(['password' => Hash::make('Password123')]);

        $response = $this->authenticatePartner()
            ->getJson('/api/partner/password/has-password');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'has_password' => true,
            ]);
    }

    // =====================
    // SET PASSWORD TESTS
    // =====================

    public function test_can_set_password_for_first_time(): void
    {
        $response = $this->authenticatePartner()
            ->postJson('/api/partner/password/set', [
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

    public function test_cannot_set_password_if_already_set(): void
    {
        $this->user->update(['password' => Hash::make('ExistingPassword123')]);

        $response = $this->authenticatePartner()
            ->postJson('/api/partner/password/set', [
                'password' => 'NewPassword123',
                'password_confirmation' => 'NewPassword123',
            ]);

        $response->assertStatus(422);
    }

    // =====================
    // CHANGE PASSWORD TESTS
    // =====================

    public function test_can_change_password_without_current_if_none_set(): void
    {
        $response = $this->authenticatePartner()
            ->postJson('/api/partner/password/change', [
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

        $response = $this->authenticatePartner()
            ->postJson('/api/partner/password/change', [
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

        $response = $this->authenticatePartner()
            ->postJson('/api/partner/password/change', [
                'current_password' => 'WrongPassword123',
                'new_password' => 'NewPassword123',
                'new_password_confirmation' => 'NewPassword123',
            ]);

        $response->assertStatus(422);
    }

    // =====================
    // FORGOT PASSWORD TESTS
    // =====================

    public function test_can_request_forgot_password(): void
    {
        $response = $this->postJson('/api/partner/forgot-password', [
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

    public function test_forgot_password_fails_for_nonexistent_partner(): void
    {
        $response = $this->postJson('/api/partner/forgot-password', [
            'identifier' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(404);
    }

    // =====================
    // VERIFY RESET OTP TESTS
    // =====================

    public function test_can_verify_reset_otp(): void
    {
        $this->createOtp($this->user->email, '123456');

        $response = $this->postJson('/api/partner/verify-reset-otp', [
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

        $response = $this->postJson('/api/partner/verify-reset-otp', [
            'identifier' => $this->user->email,
            'otp' => '654321',
        ]);

        $response->assertStatus(401);
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

        $response = $this->postJson('/api/partner/reset-password', [
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

    // =====================
    // LOGIN WITH PASSWORD TESTS
    // =====================

    public function test_can_login_with_password(): void
    {
        $this->user->update(['password' => Hash::make('Password123')]);

        $response = $this->postJson('/api/partner/login-password', [
            'identifier' => $this->user->email,
            'password' => 'Password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'token',
                'user',
                'partner',
            ]);
    }

    public function test_login_fails_for_unapproved_partner(): void
    {
        $this->user->update(['password' => Hash::make('Password123')]);
        $this->partner->update(['status' => 'pending']);

        $response = $this->postJson('/api/partner/login-password', [
            'identifier' => $this->user->email,
            'password' => 'Password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'partner_status' => 'pending',
            ]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->user->update(['password' => Hash::make('Password123')]);

        $response = $this->postJson('/api/partner/login-password', [
            'identifier' => $this->user->email,
            'password' => 'WrongPassword123',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_fails_for_partner_without_password(): void
    {
        $response = $this->postJson('/api/partner/login-password', [
            'identifier' => $this->user->email,
            'password' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Password not set. Please use OTP login.',
            ]);
    }
}

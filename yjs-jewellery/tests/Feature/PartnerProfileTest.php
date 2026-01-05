<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Partner Profile Test
 *
 * Tests for partner profile management including
 * viewing, updating, and avatar management.
 */
class PartnerProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Partner $partner;
    protected Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        $this->country = Country::factory()->create();
        $this->user = User::factory()->partner()->create();
        $this->partner = Partner::factory()
            ->forUser($this->user)
            ->approved()
            ->create();
    }

    protected function authenticatePartner()
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    // =====================
    // GET PROFILE TESTS
    // =====================

    public function test_can_get_partner_profile(): void
    {
        $response = $this->authenticatePartner()
            ->getJson('/api/partner/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'first_name', 'last_name', 'email', 'phone'],
                    'partner' => ['id', 'business_name', 'business_type', 'status'],
                ],
            ]);
    }

    public function test_unauthenticated_cannot_get_profile(): void
    {
        $response = $this->getJson('/api/partner/profile');

        $response->assertStatus(401);
    }

    // =====================
    // UPDATE PROFILE TESTS
    // =====================

    public function test_can_update_user_fields(): void
    {
        $response = $this->authenticatePartner()
            ->putJson('/api/partner/profile', [
                'first_name' => 'Updated',
                'last_name' => 'Name',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->user->refresh();
        $this->assertEquals('Updated', $this->user->first_name);
        $this->assertEquals('Name', $this->user->last_name);
    }

    public function test_can_update_business_fields(): void
    {
        $response = $this->authenticatePartner()
            ->putJson('/api/partner/profile', [
                'business_name' => 'New Business Name',
                'gst_number' => '22AAAAA0000A1Z5',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->partner->refresh();
        $this->assertEquals('New Business Name', $this->partner->business_name);
        $this->assertEquals('22AAAAA0000A1Z5', $this->partner->gst_number);
    }

    public function test_cannot_use_duplicate_email(): void
    {
        $otherUser = User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->authenticatePartner()
            ->putJson('/api/partner/profile', [
                'email' => 'existing@example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_cannot_use_duplicate_phone(): void
    {
        $otherUser = User::factory()->create(['phone' => '9876543210']);

        $response = $this->authenticatePartner()
            ->putJson('/api/partner/profile', [
                'phone' => '9876543210',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    // =====================
    // AVATAR TESTS
    // =====================

    public function test_can_upload_avatar(): void
    {
        Storage::fake('public');

        $response = $this->authenticatePartner()
            ->postJson('/api/partner/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('logo.jpg'),
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->user->refresh();
        $this->assertNotNull($this->user->profile_image);
        Storage::disk('public')->assertExists($this->user->profile_image);
    }

    public function test_avatar_must_be_an_image(): void
    {
        Storage::fake('public');

        $response = $this->authenticatePartner()
            ->postJson('/api/partner/profile/avatar', [
                'avatar' => UploadedFile::fake()->create('document.pdf', 100),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }

    public function test_can_remove_avatar(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('logo.jpg')->store('partner_avatars', 'public');
        $this->user->update(['profile_image' => $path]);

        $response = $this->authenticatePartner()
            ->deleteJson('/api/partner/profile/avatar');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->user->refresh();
        $this->assertNull($this->user->profile_image);
    }

    // =====================
    // APPROVAL STATUS TESTS
    // =====================

    public function test_can_get_approval_status(): void
    {
        $response = $this->authenticatePartner()
            ->getJson('/api/partner/profile/approval-status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'approved',
                    'is_approved' => true,
                    'can_place_orders' => true,
                ],
            ]);
    }

    public function test_pending_partner_cannot_place_orders(): void
    {
        $this->partner->update(['status' => 'pending']);

        $response = $this->authenticatePartner()
            ->getJson('/api/partner/profile/approval-status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'pending',
                    'is_approved' => false,
                    'can_place_orders' => false,
                ],
            ]);
    }
}

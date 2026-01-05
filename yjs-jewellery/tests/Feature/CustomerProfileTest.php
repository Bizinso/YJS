<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Customer $customer;
    protected Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a country for address tests
        $this->country = Country::factory()->create();

        $this->user = User::factory()->customer()->create();
        $this->customer = Customer::factory()->forUser($this->user)->create();
    }

    /**
     * Helper to authenticate the customer.
     */
    protected function authenticateCustomer()
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    // =====================
    // PROFILE TESTS
    // =====================

    public function test_can_get_customer_profile(): void
    {
        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'phone',
                    'mobile_code',
                    'customer' => [
                        'gender',
                        'dob',
                    ],
                ],
            ]);
    }

    public function test_unauthenticated_cannot_get_profile(): void
    {
        $response = $this->getJson('/api/customer/profile');

        $response->assertStatus(401);
    }

    public function test_can_update_profile(): void
    {
        $updateData = [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => 'updated@example.com',
        ];

        $response = $this->authenticateCustomer()
            ->putJson('/api/customer/profile', $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_can_update_customer_specific_fields(): void
    {
        $updateData = [
            'gender' => 'female',
            'dob' => '1990-05-15',
        ];

        $response = $this->authenticateCustomer()
            ->putJson('/api/customer/profile', $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('customers', [
            'id' => $this->customer->id,
            'gender' => 'female',
            'dob' => '1990-05-15',
        ]);
    }

    public function test_cannot_use_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->authenticateCustomer()
            ->putJson('/api/customer/profile', [
                'email' => 'existing@example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_cannot_use_duplicate_phone(): void
    {
        User::factory()->create(['phone' => '9876543210']);

        $response = $this->authenticateCustomer()
            ->putJson('/api/customer/profile', [
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

        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/profile/avatar', [
                'avatar' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'profile_image',
                ],
            ]);

        $this->user->refresh();
        $this->assertNotNull($this->user->profile_image);
        Storage::disk('public')->assertExists($this->user->profile_image);
    }

    public function test_avatar_must_be_an_image(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/profile/avatar', [
                'avatar' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }

    public function test_can_remove_avatar(): void
    {
        Storage::fake('public');

        $this->user->update(['profile_image' => 'avatars/test.jpg']);

        $response = $this->authenticateCustomer()
            ->deleteJson('/api/customer/profile/avatar');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->user->refresh();
        $this->assertNull($this->user->profile_image);
    }

    // =====================
    // ADDRESS TESTS
    // =====================

    public function test_can_get_addresses(): void
    {
        CustomerAddress::factory()
            ->forCustomer($this->customer)
            ->count(3)
            ->create(['country_id' => $this->country->id]);

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/addresses');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_get_single_address(): void
    {
        $address = CustomerAddress::factory()
            ->forCustomer($this->customer)
            ->create(['country_id' => $this->country->id]);

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/addresses/{$address->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $address->id,
                    'full_name' => $address->full_name,
                ],
            ]);
    }

    public function test_cannot_get_other_customers_address(): void
    {
        $otherCustomer = Customer::factory()->create();
        $address = CustomerAddress::factory()
            ->forCustomer($otherCustomer)
            ->create(['country_id' => $this->country->id]);

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/addresses/{$address->id}");

        $response->assertStatus(404);
    }

    public function test_can_store_address(): void
    {
        $addressData = [
            'full_name' => 'John Doe',
            'phone' => '9876543210',
            'address_line1' => '123 Main Street',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400001',
            'country_id' => $this->country->id,
            'address_type' => 'home',
        ];

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/addresses', $addressData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $this->customer->id,
            'full_name' => 'John Doe',
            'address_type' => 'home',
        ]);
    }

    public function test_first_address_is_set_as_default(): void
    {
        $addressData = [
            'full_name' => 'John Doe',
            'phone' => '9876543210',
            'address_line1' => '123 Main Street',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400001',
            'country_id' => $this->country->id,
        ];

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/addresses', $addressData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $this->customer->id,
            'is_default' => true,
        ]);
    }

    public function test_store_address_validation(): void
    {
        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/addresses', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'full_name',
                'phone',
                'address_line1',
                'city',
                'state',
                'postal_code',
                'country_id',
            ]);
    }

    public function test_can_update_address(): void
    {
        $address = CustomerAddress::factory()
            ->forCustomer($this->customer)
            ->create(['country_id' => $this->country->id]);

        $updateData = [
            'full_name' => 'Updated Name',
            'city' => 'Delhi',
        ];

        $response = $this->authenticateCustomer()
            ->putJson("/api/customer/addresses/{$address->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('customer_addresses', [
            'id' => $address->id,
            'full_name' => 'Updated Name',
            'city' => 'Delhi',
        ]);
    }

    public function test_cannot_update_other_customers_address(): void
    {
        $otherCustomer = Customer::factory()->create();
        $address = CustomerAddress::factory()
            ->forCustomer($otherCustomer)
            ->create(['country_id' => $this->country->id]);

        $response = $this->authenticateCustomer()
            ->putJson("/api/customer/addresses/{$address->id}", [
                'full_name' => 'Hacked Name',
            ]);

        $response->assertStatus(404);
    }

    public function test_can_delete_address(): void
    {
        $address = CustomerAddress::factory()
            ->forCustomer($this->customer)
            ->create(['country_id' => $this->country->id]);

        $response = $this->authenticateCustomer()
            ->deleteJson("/api/customer/addresses/{$address->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('customer_addresses', [
            'id' => $address->id,
        ]);
    }

    public function test_cannot_delete_other_customers_address(): void
    {
        $otherCustomer = Customer::factory()->create();
        $address = CustomerAddress::factory()
            ->forCustomer($otherCustomer)
            ->create(['country_id' => $this->country->id]);

        $response = $this->authenticateCustomer()
            ->deleteJson("/api/customer/addresses/{$address->id}");

        $response->assertStatus(404);

        $this->assertDatabaseHas('customer_addresses', [
            'id' => $address->id,
        ]);
    }

    public function test_can_set_default_address(): void
    {
        $address1 = CustomerAddress::factory()
            ->forCustomer($this->customer)
            ->default()
            ->create(['country_id' => $this->country->id]);

        $address2 = CustomerAddress::factory()
            ->forCustomer($this->customer)
            ->create(['country_id' => $this->country->id, 'is_default' => false]);

        $response = $this->authenticateCustomer()
            ->postJson("/api/customer/addresses/{$address2->id}/default");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $address1->refresh();
        $address2->refresh();

        $this->assertFalse($address1->is_default);
        $this->assertTrue($address2->is_default);
    }

    public function test_cannot_set_other_customers_address_as_default(): void
    {
        $otherCustomer = Customer::factory()->create();
        $address = CustomerAddress::factory()
            ->forCustomer($otherCustomer)
            ->create(['country_id' => $this->country->id]);

        $response = $this->authenticateCustomer()
            ->postJson("/api/customer/addresses/{$address->id}/default");

        $response->assertStatus(404);
    }

    public function test_address_type_validation(): void
    {
        $addressData = [
            'full_name' => 'John Doe',
            'phone' => '9876543210',
            'address_line1' => '123 Main Street',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400001',
            'country_id' => $this->country->id,
            'address_type' => 'invalid_type',
        ];

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/addresses', $addressData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['address_type']);
    }
}

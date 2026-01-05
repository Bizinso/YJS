<?php

namespace Tests\Feature;

use App\Models\offers;
use App\Models\OfferUsage;
use App\Models\offerTypeMaster;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin Offer Test
 *
 * Tests for admin offer management including
 * CRUD operations, activation, and analytics.
 */
class AdminOfferTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected offerTypeMaster $offerType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['user_type' => 'employee']);
        $this->offerType = offerTypeMaster::factory()->create();
    }

    protected function authenticateAdmin()
    {
        return $this->actingAs($this->admin, 'sanctum');
    }

    // =====================
    // OFFER LIST TESTS
    // =====================

    public function test_can_list_offers(): void
    {
        offers::factory()->count(3)->create();

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/offers');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data',
                'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertEquals(3, $response->json('pagination.total'));
    }

    public function test_can_filter_offers_by_status(): void
    {
        offers::factory()->create(['status' => 'active']);
        offers::factory()->create(['status' => 'inactive']);
        offers::factory()->create(['status' => 'inactive']);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/offers?status=inactive');

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('pagination.total'));
    }

    public function test_can_search_offers(): void
    {
        offers::factory()->create(['title' => 'Summer Sale']);
        offers::factory()->create(['title' => 'Winter Special']);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/offers?search=Summer');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('pagination.total'));
    }

    public function test_can_filter_active_offers_only(): void
    {
        offers::factory()->create([
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addDay(),
        ]);
        offers::factory()->expired()->create();

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/offers?active_only=true');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('pagination.total'));
    }

    public function test_unauthenticated_cannot_list_offers(): void
    {
        $response = $this->getJson('/api/admin/offers');
        $response->assertStatus(401);
    }

    // =====================
    // OFFER DETAILS TESTS
    // =====================

    public function test_can_get_offer_details(): void
    {
        $offer = offers::factory()->create();

        $response = $this->authenticateAdmin()
            ->getJson("/api/admin/offers/{$offer->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data',
                'usage_stats' => ['total_uses', 'total_discount_given', 'unique_customers'],
            ]);
    }

    public function test_returns_404_for_nonexistent_offer(): void
    {
        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/offers/99999');

        $response->assertStatus(404);
    }

    // =====================
    // CREATE OFFER TESTS
    // =====================

    public function test_can_create_flat_discount_offer(): void
    {
        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/offers', [
                'title' => 'Flat 500 Off',
                'description' => 'Get flat Rs 500 off on all products',
                'offer_type_id' => $this->offerType->id,
                'discount_type' => 'flat',
                'discount_amount' => 500,
                'valid_from' => now()->toDateTimeString(),
                'valid_to' => now()->addDays(30)->toDateTimeString(),
                'status' => 'active',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Flat 500 Off',
                    'discount_type' => 'flat',
                    'discount_amount' => 500,
                ],
            ]);
    }

    public function test_can_create_percent_discount_offer(): void
    {
        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/offers', [
                'title' => '10% Off',
                'offer_type_id' => $this->offerType->id,
                'discount_type' => 'percent',
                'discount_percent' => 10,
                'max_discount_amount' => 1000,
                'valid_from' => now()->toDateTimeString(),
                'valid_to' => now()->addDays(30)->toDateTimeString(),
                'status' => 'active',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'discount_type' => 'percent',
                    'discount_percent' => 10,
                ],
            ]);
    }

    public function test_can_create_offer_with_coupon_code(): void
    {
        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/offers', [
                'title' => 'Welcome Offer',
                'offer_type_id' => $this->offerType->id,
                'discount_type' => 'percent',
                'discount_percent' => 15,
                'coupon_code' => 'WELCOME15',
                'valid_from' => now()->toDateTimeString(),
                'valid_to' => now()->addDays(30)->toDateTimeString(),
                'status' => 'active',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'coupon_code' => 'WELCOME15',
                ],
            ]);
    }

    public function test_cannot_create_offer_with_duplicate_coupon(): void
    {
        offers::factory()->withCoupon('EXISTING123')->create();

        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/offers', [
                'title' => 'New Offer',
                'offer_type_id' => $this->offerType->id,
                'discount_type' => 'flat',
                'discount_amount' => 100,
                'coupon_code' => 'EXISTING123',
                'valid_from' => now()->toDateTimeString(),
                'valid_to' => now()->addDays(30)->toDateTimeString(),
                'status' => 'active',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['coupon_code']);
    }

    public function test_create_offer_validates_required_fields(): void
    {
        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/offers', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'discount_type', 'valid_from', 'valid_to', 'status']);
    }

    public function test_can_create_offer_with_restrictions(): void
    {
        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/offers', [
                'title' => 'First Order Special',
                'offer_type_id' => $this->offerType->id,
                'discount_type' => 'percent',
                'discount_percent' => 20,
                'valid_from' => now()->toDateTimeString(),
                'valid_to' => now()->addDays(30)->toDateTimeString(),
                'status' => 'active',
                'details' => [
                    'min_cart_value' => 1000,
                    'first_order_only' => true,
                    'max_usage_per_user' => 1,
                ],
            ]);

        $response->assertStatus(201);
        $this->assertTrue($response->json('data.details.first_order_only'));
        $this->assertEquals(1000, $response->json('data.details.min_cart_value'));
    }

    // =====================
    // UPDATE OFFER TESTS
    // =====================

    public function test_can_update_offer(): void
    {
        $offer = offers::factory()->create(['title' => 'Old Title']);

        $response = $this->authenticateAdmin()
            ->putJson("/api/admin/offers/{$offer->id}", [
                'title' => 'New Title',
                'description' => 'Updated description',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'New Title',
                ],
            ]);
    }

    public function test_can_update_offer_discount_type(): void
    {
        $offer = offers::factory()->flatDiscount(500)->create();

        $response = $this->authenticateAdmin()
            ->putJson("/api/admin/offers/{$offer->id}", [
                'discount_type' => 'percent',
                'discount_percent' => 10,
            ]);

        $response->assertStatus(200);
        $this->assertEquals('percent', $response->json('data.discount_type'));
        $this->assertNull($response->json('data.discount_amount'));
    }

    public function test_update_returns_404_for_nonexistent(): void
    {
        $response = $this->authenticateAdmin()
            ->putJson('/api/admin/offers/99999', ['title' => 'Test']);

        $response->assertStatus(404);
    }

    // =====================
    // DELETE OFFER TESTS
    // =====================

    public function test_can_delete_unused_offer(): void
    {
        $offer = offers::factory()->create();

        $response = $this->authenticateAdmin()
            ->deleteJson("/api/admin/offers/{$offer->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('offers', ['id' => $offer->id]);
    }

    public function test_used_offer_gets_expired_instead_of_deleted(): void
    {
        $offer = offers::factory()->create();
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->forUser($customer)->create();

        OfferUsage::create([
            'offer_id' => $offer->id,
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'discount_amount' => 100,
            'used_at' => now(),
            'reversed' => false,
        ]);

        $response = $this->authenticateAdmin()
            ->deleteJson("/api/admin/offers/{$offer->id}");

        $response->assertStatus(200);
        $this->assertEquals('expired', $offer->fresh()->status);
    }

    // =====================
    // ACTIVATE/DEACTIVATE TESTS
    // =====================

    public function test_can_activate_offer(): void
    {
        $offer = offers::factory()->inactive()->create([
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addDay(),
        ]);

        $response = $this->authenticateAdmin()
            ->postJson("/api/admin/offers/{$offer->id}/activate");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals('active', $offer->fresh()->status);
    }

    public function test_cannot_activate_expired_offer(): void
    {
        $offer = offers::factory()->expired()->create();

        $response = $this->authenticateAdmin()
            ->postJson("/api/admin/offers/{$offer->id}/activate");

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    public function test_can_deactivate_offer(): void
    {
        $offer = offers::factory()->create(['status' => 'active']);

        $response = $this->authenticateAdmin()
            ->postJson("/api/admin/offers/{$offer->id}/deactivate");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals('inactive', $offer->fresh()->status);
    }

    // =====================
    // USAGE ANALYTICS TESTS
    // =====================

    public function test_can_get_offer_usage_analytics(): void
    {
        $offer = offers::factory()->create();
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->forUser($customer)->create();

        OfferUsage::create([
            'offer_id' => $offer->id,
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'discount_amount' => 500,
            'used_at' => now(),
            'reversed' => false,
        ]);

        $response = $this->authenticateAdmin()
            ->getJson("/api/admin/offers/{$offer->id}/usage");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'offer',
                'overall_stats' => ['total_uses', 'total_discount_given', 'unique_customers'],
                'period_stats',
                'daily_breakdown',
                'recent_usage',
            ]);

        $this->assertEquals(1, $response->json('overall_stats.total_uses'));
        $this->assertEquals(500, $response->json('overall_stats.total_discount_given'));
    }

    // =====================
    // SUMMARY TESTS
    // =====================

    public function test_can_get_offers_summary(): void
    {
        offers::factory()->create([
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addDay(),
        ]);
        offers::factory()->inactive()->create();
        offers::factory()->expired()->create();

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/offers/summary');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_offers',
                    'active_offers',
                    'expired_offers',
                    'inactive_offers',
                    'discount_given_30d',
                    'top_offers',
                ],
            ]);

        $this->assertEquals(3, $response->json('data.total_offers'));
        $this->assertEquals(1, $response->json('data.active_offers'));
    }

    // =====================
    // BULK UPDATE TESTS
    // =====================

    public function test_can_bulk_update_offer_status(): void
    {
        $offers = offers::factory()->count(3)->create(['status' => 'active']);

        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/offers/bulk-status', [
                'offer_ids' => $offers->pluck('id')->toArray(),
                'status' => 'inactive',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'updated_count' => 3,
            ]);

        foreach ($offers as $offer) {
            $this->assertEquals('inactive', $offer->fresh()->status);
        }
    }

    public function test_bulk_update_validates_offer_ids(): void
    {
        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/offers/bulk-status', [
                'offer_ids' => [99999],
                'status' => 'inactive',
            ]);

        $response->assertStatus(422);
    }

    // =====================
    // OFFER TYPES TESTS
    // =====================

    public function test_can_get_offer_types(): void
    {
        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/offers/types');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}

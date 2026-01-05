<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Order;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Notification Test
 *
 * Tests for notification functionality including
 * listing, marking as read, and clearing notifications.
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        $this->country = Country::factory()->create();
        $this->customer = User::factory()->customer()->create();
    }

    protected function authenticate()
    {
        return $this->actingAs($this->customer, 'sanctum');
    }

    protected function createNotification(array $data = []): string
    {
        $id = Str::uuid()->toString();
        $this->customer->notifications()->create([
            'id' => $id,
            'type' => 'App\\Notifications\\TestNotification',
            'data' => array_merge([
                'type' => 'test',
                'title' => 'Test Notification',
                'message' => 'This is a test notification.',
                'data' => [],
                'created_at' => now()->toIso8601String(),
            ], $data),
        ]);

        return $id;
    }

    // =====================
    // LIST NOTIFICATIONS TESTS
    // =====================

    public function test_can_get_notifications(): void
    {
        $this->createNotification();
        $this->createNotification(['title' => 'Second Notification']);

        $response = $this->authenticate()
            ->getJson('/api/customer/notifications');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data',
                'unread_count',
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_notifications_respects_limit(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->createNotification(['title' => "Notification {$i}"]);
        }

        $response = $this->authenticate()
            ->getJson('/api/customer/notifications?limit=10');

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data'));
    }

    public function test_can_filter_unread_only(): void
    {
        $unreadId = $this->createNotification(['title' => 'Unread']);
        $readId = $this->createNotification(['title' => 'Read']);

        // Mark one as read
        $this->customer->notifications()->find($readId)->markAsRead();

        $response = $this->authenticate()
            ->getJson('/api/customer/notifications?unread_only=true');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_unauthenticated_cannot_get_notifications(): void
    {
        $response = $this->getJson('/api/customer/notifications');
        $response->assertStatus(401);
    }

    // =====================
    // UNREAD COUNT TESTS
    // =====================

    public function test_can_get_unread_count(): void
    {
        $this->createNotification();
        $this->createNotification();
        $readId = $this->createNotification();

        $this->customer->notifications()->find($readId)->markAsRead();

        $response = $this->authenticate()
            ->getJson('/api/customer/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'unread_count' => 2,
            ]);
    }

    public function test_unread_count_returns_zero_when_empty(): void
    {
        $response = $this->authenticate()
            ->getJson('/api/customer/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'unread_count' => 0,
            ]);
    }

    // =====================
    // MARK AS READ TESTS
    // =====================

    public function test_can_mark_notification_as_read(): void
    {
        $id = $this->createNotification();

        $response = $this->authenticate()
            ->postJson("/api/customer/notifications/{$id}/read");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notification marked as read.',
            ]);

        // Verify it's marked as read
        $this->assertNotNull($this->customer->notifications()->find($id)->read_at);
    }

    public function test_mark_as_read_returns_404_for_nonexistent(): void
    {
        $response = $this->authenticate()
            ->postJson('/api/customer/notifications/nonexistent-id/read');

        $response->assertStatus(404);
    }

    public function test_cannot_mark_other_users_notification(): void
    {
        $otherUser = User::factory()->customer()->create();
        $id = Str::uuid()->toString();
        $otherUser->notifications()->create([
            'id' => $id,
            'type' => 'App\\Notifications\\TestNotification',
            'data' => ['type' => 'test', 'title' => 'Other user notification'],
        ]);

        $response = $this->authenticate()
            ->postJson("/api/customer/notifications/{$id}/read");

        $response->assertStatus(404);
    }

    // =====================
    // MARK ALL AS READ TESTS
    // =====================

    public function test_can_mark_all_as_read(): void
    {
        $this->createNotification();
        $this->createNotification();
        $this->createNotification();

        $response = $this->authenticate()
            ->postJson('/api/customer/notifications/read-all');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'marked_count' => 3,
            ]);

        // Verify all are read
        $this->assertEquals(0, $this->customer->unreadNotifications()->count());
    }

    public function test_mark_all_as_read_returns_zero_when_empty(): void
    {
        $response = $this->authenticate()
            ->postJson('/api/customer/notifications/read-all');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'marked_count' => 0,
            ]);
    }

    // =====================
    // DELETE NOTIFICATION TESTS
    // =====================

    public function test_can_delete_notification(): void
    {
        $id = $this->createNotification();

        $response = $this->authenticate()
            ->deleteJson("/api/customer/notifications/{$id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notification deleted.',
            ]);

        $this->assertNull($this->customer->notifications()->find($id));
    }

    public function test_delete_returns_404_for_nonexistent(): void
    {
        $response = $this->authenticate()
            ->deleteJson('/api/customer/notifications/nonexistent-id');

        $response->assertStatus(404);
    }

    public function test_cannot_delete_other_users_notification(): void
    {
        $otherUser = User::factory()->customer()->create();
        $id = Str::uuid()->toString();
        $otherUser->notifications()->create([
            'id' => $id,
            'type' => 'App\\Notifications\\TestNotification',
            'data' => ['type' => 'test', 'title' => 'Other user notification'],
        ]);

        $response = $this->authenticate()
            ->deleteJson("/api/customer/notifications/{$id}");

        $response->assertStatus(404);
    }

    // =====================
    // CLEAR ALL TESTS
    // =====================

    public function test_can_clear_all_notifications(): void
    {
        $this->createNotification();
        $this->createNotification();
        $this->createNotification();

        $response = $this->authenticate()
            ->deleteJson('/api/customer/notifications');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'cleared_count' => 3,
            ]);

        $this->assertEquals(0, $this->customer->notifications()->count());
    }

    public function test_clear_all_returns_zero_when_empty(): void
    {
        $response = $this->authenticate()
            ->deleteJson('/api/customer/notifications');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'cleared_count' => 0,
            ]);
    }

    public function test_clear_only_clears_own_notifications(): void
    {
        $this->createNotification();
        $this->createNotification();

        $otherUser = User::factory()->customer()->create();
        $otherUser->notifications()->create([
            'id' => Str::uuid()->toString(),
            'type' => 'App\\Notifications\\TestNotification',
            'data' => ['type' => 'test'],
        ]);

        $response = $this->authenticate()
            ->deleteJson('/api/customer/notifications');

        $response->assertStatus(200);
        $this->assertEquals(1, $otherUser->notifications()->count());
    }

    // =====================
    // NOTIFICATION SERVICE TESTS
    // =====================

    public function test_notification_service_order_placed(): void
    {
        $order = Order::factory()
            ->forUser($this->customer)
            ->create(['country_id' => $this->country->id]);

        $service = app(NotificationService::class);
        $result = $service->sendOrderPlaced($order);

        $this->assertTrue($result['success']);
        $this->assertEquals('order_placed', $result['type']);
        $this->assertTrue($result['channels']['database']);
    }

    public function test_notification_service_status_update(): void
    {
        $order = Order::factory()
            ->forUser($this->customer)
            ->create(['country_id' => $this->country->id]);

        $service = app(NotificationService::class);
        $result = $service->sendOrderStatusUpdate($order, 'pending', 'confirmed');

        $this->assertTrue($result['success']);
        $this->assertEquals('order_status_update', $result['type']);
    }

    public function test_notification_service_payment_success(): void
    {
        $order = Order::factory()
            ->forUser($this->customer)
            ->create(['country_id' => $this->country->id]);

        $service = app(NotificationService::class);
        $result = $service->sendPaymentSuccess($order, 10000, 'txn_test123');

        $this->assertTrue($result['success']);
        $this->assertEquals('payment_success', $result['type']);
    }

    public function test_notification_service_refund(): void
    {
        $order = Order::factory()
            ->forUser($this->customer)
            ->create(['country_id' => $this->country->id]);

        $service = app(NotificationService::class);
        $result = $service->sendRefundProcessed($order, 5000, 'rfnd_test123');

        $this->assertTrue($result['success']);
        $this->assertEquals('refund_processed', $result['type']);
    }

    public function test_notification_service_low_stock_alert(): void
    {
        // Create admin user (status 'A' = Active in this DB schema)
        User::factory()->create(['user_type' => 'employee', 'status' => 'A']);

        $service = app(NotificationService::class);
        $result = $service->sendLowStockAlert([
            ['id' => 1, 'name' => 'Gold Ring', 'stock' => 5],
            ['id' => 2, 'name' => 'Silver Necklace', 'stock' => 3],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('low_stock_alert', $result['type']);
        $this->assertEquals(2, $result['product_count']);
    }

    public function test_notification_service_handles_missing_customer(): void
    {
        $order = Order::factory()->create([
            'country_id' => $this->country->id,
            'customer_id' => null,
        ]);

        $service = app(NotificationService::class);
        $result = $service->sendOrderPlaced($order);

        $this->assertFalse($result['success']);
        $this->assertEquals('No customer found', $result['error']);
    }
}

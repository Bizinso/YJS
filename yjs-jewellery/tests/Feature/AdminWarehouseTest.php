<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Admin Warehouse Test
 *
 * Tests for admin warehouse management endpoints.
 */
class AdminWarehouseTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['user_type' => 'employee']);
    }

    protected function authenticateAdmin()
    {
        return $this->actingAs($this->admin, 'sanctum');
    }

    protected function createWarehouse(array $overrides = []): int
    {
        $data = array_merge([
            'name' => 'Test Warehouse',
            'code' => 'WH-TEST-' . uniqid(),
            'type' => 'warehouse',
            'address_line1' => '123 Test Street',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001',
            'country' => 'IN',
            'is_active' => true,
            'is_default' => false,
            'accepts_returns' => true,
            'priority' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return DB::table('warehouses')->insertGetId($data);
    }

    // =====================
    // WAREHOUSE DASHBOARD TESTS
    // =====================

    public function test_can_get_warehouse_dashboard(): void
    {
        $this->createWarehouse();

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/warehouse/dashboard');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_unauthenticated_cannot_access_warehouse(): void
    {
        $response = $this->getJson('/api/admin/warehouse/dashboard');

        $response->assertStatus(401);
    }

    // =====================
    // WAREHOUSE LIST TESTS
    // =====================

    public function test_can_list_warehouses(): void
    {
        $this->createWarehouse(['name' => 'Warehouse 1']);
        $this->createWarehouse(['name' => 'Warehouse 2']);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/warehouse');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_filter_warehouses_by_type(): void
    {
        $this->createWarehouse(['type' => 'warehouse']);
        $this->createWarehouse(['type' => 'store']);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/warehouse?type=warehouse');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_search_warehouses(): void
    {
        $this->createWarehouse(['name' => 'Mumbai Main Warehouse']);
        $this->createWarehouse(['name' => 'Delhi Branch']);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/warehouse?search=Mumbai');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // =====================
    // WAREHOUSE CRUD TESTS
    // =====================

    public function test_can_create_warehouse(): void
    {
        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/warehouse', [
                'name' => 'New Warehouse',
                'code' => 'WH-NEW-001',
                'type' => 'warehouse',
                'address_line1' => '456 New Street',
                'city' => 'Bangalore',
                'state' => 'Karnataka',
                'pincode' => '560001',
                'country' => 'IN',
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('warehouses', [
            'code' => 'WH-NEW-001',
            'name' => 'New Warehouse'
        ]);
    }

    public function test_create_warehouse_validates_required_fields(): void
    {
        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/warehouse', []);

        $response->assertStatus(422);
    }

    public function test_cannot_create_warehouse_with_duplicate_code(): void
    {
        $this->createWarehouse(['code' => 'WH-DUP-001']);

        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/warehouse', [
                'name' => 'Another Warehouse',
                'code' => 'WH-DUP-001',
                'type' => 'warehouse',
            ]);

        $response->assertStatus(422);
    }

    public function test_can_get_warehouse_details(): void
    {
        $id = $this->createWarehouse(['name' => 'Detail Warehouse']);

        $response = $this->authenticateAdmin()
            ->getJson("/api/admin/warehouse/{$id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_get_warehouse_returns_404_for_nonexistent(): void
    {
        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/warehouse/99999');

        $response->assertStatus(404);
    }

    public function test_can_update_warehouse(): void
    {
        $id = $this->createWarehouse();

        $response = $this->authenticateAdmin()
            ->putJson("/api/admin/warehouse/{$id}", [
                'name' => 'Updated Warehouse Name',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('warehouses', [
            'id' => $id,
            'name' => 'Updated Warehouse Name'
        ]);
    }

    public function test_can_delete_warehouse(): void
    {
        $id = $this->createWarehouse();

        $response = $this->authenticateAdmin()
            ->deleteJson("/api/admin/warehouse/{$id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // =====================
    // WAREHOUSE STOCK TESTS
    // =====================

    public function test_can_get_warehouse_stock(): void
    {
        $id = $this->createWarehouse();

        $response = $this->authenticateAdmin()
            ->getJson("/api/admin/warehouse/{$id}/stock");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}

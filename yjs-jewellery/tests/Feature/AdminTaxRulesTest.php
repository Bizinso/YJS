<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Admin Tax Rules Test
 *
 * Tests for admin tax rules management endpoints.
 */
class AdminTaxRulesTest extends TestCase
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

    protected function createTaxZone(array $overrides = []): int
    {
        $data = array_merge([
            'name' => 'Test Zone',
            'code' => 'TZ-' . uniqid(),
            'description' => 'Test tax zone',
            'countries' => json_encode(['IN']),
            'states' => json_encode(['MH']),
            'is_default' => false,
            'is_active' => true,
            'priority' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return DB::table('tax_zones')->insertGetId($data);
    }

    protected function createTaxRule(int $zoneId, array $overrides = []): int
    {
        $data = array_merge([
            'name' => 'Test Tax Rule',
            'code' => 'TAX-' . uniqid(),
            'description' => 'Test tax rule',
            'tax_zone_id' => $zoneId,
            'tax_type' => 'gst',
            'rate' => 3.00,
            'cgst_rate' => 1.50,
            'sgst_rate' => 1.50,
            'igst_rate' => 3.00,
            'apply_to' => 'all',
            'calculation_type' => 'percentage',
            'is_compound' => false,
            'is_inclusive' => false,
            'is_active' => true,
            'priority' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return DB::table('tax_rules')->insertGetId($data);
    }

    protected function createHsnCode(array $overrides = []): int
    {
        $data = array_merge([
            'code' => 'HSN-' . uniqid(),
            'description' => 'Test HSN Code',
            'gst_rate' => 3.00,
            'cgst_rate' => 1.50,
            'sgst_rate' => 1.50,
            'igst_rate' => 3.00,
            'type' => 'goods',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return DB::table('hsn_codes')->insertGetId($data);
    }

    protected function createProduct(array $overrides = []): int
    {
        $data = array_merge([
            'name' => 'Test Product',
            'sku' => 'SKU-' . uniqid(),
            'slug' => 'test-product-' . uniqid(),
            'description' => 'Test product description',
            'base_price' => 10000.00,
            'final_price' => 10000.00,
            'initial_stock' => 10,
            'available_stock' => 10,
            'visibility' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return DB::table('products')->insertGetId($data);
    }

    // =====================
    // TAX DASHBOARD TESTS
    // =====================

    public function test_can_get_tax_dashboard(): void
    {
        $this->createTaxZone();

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/tax/dashboard');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_unauthenticated_cannot_access_tax(): void
    {
        $response = $this->getJson('/api/admin/tax/dashboard');

        $response->assertStatus(401);
    }

    // =====================
    // TAX ZONE TESTS
    // =====================

    public function test_can_list_tax_zones(): void
    {
        $this->createTaxZone(['name' => 'Zone 1']);
        $this->createTaxZone(['name' => 'Zone 2']);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/tax/zones');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_create_tax_zone(): void
    {
        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/tax/zones', [
                'name' => 'Maharashtra',
                'code' => 'MH-NEW',
                'description' => 'Maharashtra state zone',
                'countries' => ['IN'],
                'states' => ['MH'],
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('tax_zones', [
            'code' => 'MH-NEW',
            'name' => 'Maharashtra'
        ]);
    }

    public function test_cannot_create_zone_with_duplicate_code(): void
    {
        $this->createTaxZone(['code' => 'DUP-ZONE']);

        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/tax/zones', [
                'name' => 'Another Zone',
                'code' => 'DUP-ZONE',
            ]);

        $response->assertStatus(422);
    }

    public function test_can_update_tax_zone(): void
    {
        $id = $this->createTaxZone();

        $response = $this->authenticateAdmin()
            ->putJson("/api/admin/tax/zones/{$id}", [
                'name' => 'Updated Zone Name',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('tax_zones', [
            'id' => $id,
            'name' => 'Updated Zone Name'
        ]);
    }

    public function test_can_delete_tax_zone(): void
    {
        $id = $this->createTaxZone();

        $response = $this->authenticateAdmin()
            ->deleteJson("/api/admin/tax/zones/{$id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // =====================
    // TAX RULES TESTS
    // =====================

    public function test_can_list_tax_rules(): void
    {
        $zoneId = $this->createTaxZone();
        $this->createTaxRule($zoneId, ['name' => 'Rule 1']);
        $this->createTaxRule($zoneId, ['name' => 'Rule 2']);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/tax/rules');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_create_tax_rule(): void
    {
        $zoneId = $this->createTaxZone();

        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/tax/rules', [
                'name' => 'GST 3% - Gold Jewellery',
                'code' => 'GST-GOLD-3',
                'tax_zone_id' => $zoneId,
                'tax_type' => 'gst',
                'rate' => 3.00,
                'cgst_rate' => 1.50,
                'sgst_rate' => 1.50,
                'igst_rate' => 3.00,
                'apply_to' => 'all',
                'calculation_type' => 'percentage',
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('tax_rules', [
            'code' => 'GST-GOLD-3'
        ]);
    }

    public function test_can_get_tax_rule_details(): void
    {
        $zoneId = $this->createTaxZone();
        $ruleId = $this->createTaxRule($zoneId);

        $response = $this->authenticateAdmin()
            ->getJson("/api/admin/tax/rules/{$ruleId}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_update_tax_rule(): void
    {
        $zoneId = $this->createTaxZone();
        $ruleId = $this->createTaxRule($zoneId);

        $response = $this->authenticateAdmin()
            ->putJson("/api/admin/tax/rules/{$ruleId}", [
                'rate' => 5.00,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_delete_tax_rule(): void
    {
        $zoneId = $this->createTaxZone();
        $ruleId = $this->createTaxRule($zoneId);

        $response = $this->authenticateAdmin()
            ->deleteJson("/api/admin/tax/rules/{$ruleId}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // =====================
    // HSN CODE TESTS
    // =====================

    public function test_can_list_hsn_codes(): void
    {
        $this->createHsnCode(['code' => '7113']);
        $this->createHsnCode(['code' => '7117']);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/tax/hsn');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_search_hsn_codes(): void
    {
        $this->createHsnCode(['code' => '7113', 'description' => 'Gold jewellery']);
        $this->createHsnCode(['code' => '7117', 'description' => 'Imitation jewellery']);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/tax/hsn/search?q=7113');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_create_hsn_code(): void
    {
        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/tax/hsn', [
                'code' => '71131919',
                'description' => 'Other gold jewellery',
                'gst_rate' => 3.00,
                'cgst_rate' => 1.50,
                'sgst_rate' => 1.50,
                'igst_rate' => 3.00,
                'type' => 'goods',
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('hsn_codes', [
            'code' => '71131919'
        ]);
    }

    public function test_can_update_hsn_code(): void
    {
        $id = $this->createHsnCode();

        $response = $this->authenticateAdmin()
            ->putJson("/api/admin/tax/hsn/{$id}", [
                'gst_rate' => 5.00,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_delete_hsn_code(): void
    {
        $id = $this->createHsnCode();

        $response = $this->authenticateAdmin()
            ->deleteJson("/api/admin/tax/hsn/{$id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // =====================
    // TAX EXEMPTION TESTS
    // =====================

    public function test_can_list_tax_exemptions(): void
    {
        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/tax/exemptions');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // =====================
    // TAX CALCULATION TESTS
    // =====================

    public function test_can_calculate_tax(): void
    {
        $zoneId = $this->createTaxZone(['code' => 'MH', 'is_default' => true]);
        $this->createTaxRule($zoneId, [
            'rate' => 3.00,
            'tax_type' => 'gst',
            'is_active' => true
        ]);
        $productId = $this->createProduct(['final_price' => 10000.00]);

        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/tax/calculate', [
                'items' => [
                    [
                        'product_id' => $productId,
                        'quantity' => 1,
                        'price' => 10000.00,
                    ],
                ],
                'shipping_address' => [
                    'country' => 'IN',
                    'state' => 'MH',
                    'pincode' => '400001',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}

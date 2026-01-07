<?php

namespace Tests\Unit\Middleware;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    // ============ ENSURE ADMIN MIDDLEWARE TESTS ============

    public function test_admin_middleware_allows_employee(): void
    {
        $employee = User::factory()->create([
            'user_type' => 'employee',
            'status' => 'A',
        ]);

        $token = $employee->createToken('test', ['employee'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/dashboard');

        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_admin_middleware_blocks_customer(): void
    {
        $customer = User::factory()->create([
            'user_type' => 'customer',
            'status' => 'A',
        ]);

        $token = $customer->createToken('test', ['customer'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_admin_middleware_blocks_partner(): void
    {
        $partnerUser = User::factory()->create([
            'user_type' => 'partner',
            'status' => 'A',
        ]);

        Partner::factory()->forUser($partnerUser)->approved()->create();

        $token = $partnerUser->createToken('test', ['partner'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(403);
    }

    // ============ ENSURE CUSTOMER MIDDLEWARE TESTS ============

    public function test_customer_middleware_allows_customer(): void
    {
        $customer = User::factory()->create([
            'user_type' => 'customer',
            'status' => 'A',
        ]);

        $token = $customer->createToken('test', ['customer'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/customer/cart');

        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_customer_middleware_blocks_partner(): void
    {
        $partnerUser = User::factory()->create([
            'user_type' => 'partner',
            'status' => 'A',
        ]);

        Partner::factory()->forUser($partnerUser)->approved()->create();

        $token = $partnerUser->createToken('test', ['partner'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/customer/cart');

        $response->assertStatus(403);
    }

    public function test_customer_middleware_blocks_employee(): void
    {
        $employee = User::factory()->create([
            'user_type' => 'employee',
            'status' => 'A',
        ]);

        $token = $employee->createToken('test', ['employee'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/customer/cart');

        $response->assertStatus(403);
    }

    // ============ ENSURE PARTNER MIDDLEWARE TESTS ============

    public function test_partner_middleware_allows_approved_partner(): void
    {
        $partnerUser = User::factory()->create([
            'user_type' => 'partner',
            'status' => 'A',
        ]);

        Partner::factory()->forUser($partnerUser)->approved()->create();

        $token = $partnerUser->createToken('test', ['partner'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/partner/products');

        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_partner_middleware_blocks_pending_partner(): void
    {
        $partnerUser = User::factory()->create([
            'user_type' => 'partner',
            'status' => 'A',
        ]);

        Partner::factory()->forUser($partnerUser)->pending()->create();

        $token = $partnerUser->createToken('test', ['partner'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/partner/products');

        $response->assertStatus(403);
    }

    public function test_partner_middleware_blocks_customer(): void
    {
        $customer = User::factory()->create([
            'user_type' => 'customer',
            'status' => 'A',
        ]);

        $token = $customer->createToken('test', ['customer'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/partner/products');

        $response->assertStatus(403);
    }

    // ============ PENDING PARTNER ACCESS TESTS ============

    public function test_pending_partner_can_access_approval_status(): void
    {
        $partnerUser = User::factory()->create([
            'user_type' => 'partner',
            'status' => 'A',
        ]);

        Partner::factory()->forUser($partnerUser)->pending()->create();

        $token = $partnerUser->createToken('test', ['partner'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/partner/profile/approval-status');

        // Should be accessible (200) even for pending partners
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_pending_partner_can_logout(): void
    {
        $partnerUser = User::factory()->create([
            'user_type' => 'partner',
            'status' => 'A',
        ]);

        Partner::factory()->forUser($partnerUser)->pending()->create();

        $token = $partnerUser->createToken('test', ['partner'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/partner/logout');

        // Should be accessible even for pending partners
        $this->assertNotEquals(403, $response->getStatusCode());
    }
}

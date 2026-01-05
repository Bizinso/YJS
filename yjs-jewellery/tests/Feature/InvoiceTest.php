<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Invoice Test
 *
 * Tests for invoice generation functionality including
 * customer invoice access and admin invoice management.
 */
class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->customer()->create();
        $this->admin = User::factory()->create(['user_type' => 'employee']);
    }

    protected function authenticateCustomer()
    {
        return $this->actingAs($this->customer, 'sanctum');
    }

    protected function authenticateAdmin()
    {
        return $this->actingAs($this->admin, 'sanctum');
    }

    // =====================
    // CUSTOMER INVOICE TESTS
    // =====================

    public function test_customer_can_get_invoice_for_paid_order(): void
    {
        $order = Order::factory()->forUser($this->customer)->paid()->create();

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/orders/{$order->id}/invoice");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'invoice' => [
                    'invoice_number',
                    'invoice_date',
                    'order_number',
                    'company',
                    'customer',
                    'items',
                    'subtotal',
                    'taxes',
                    'grand_total',
                    'amount_in_words',
                ],
            ]);
    }

    public function test_invoice_number_format_is_correct(): void
    {
        $order = Order::factory()->forUser($this->customer)->paid()->create();

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/orders/{$order->id}/invoice");

        $response->assertStatus(200);

        $invoiceNumber = $response->json('invoice.invoice_number');
        $this->assertMatchesRegularExpression('/^INV\d{4}-\d+$/', $invoiceNumber);
    }

    public function test_customer_cannot_get_invoice_for_unpaid_order(): void
    {
        $order = Order::factory()->forUser($this->customer)->create([
            'payment_status' => 'pending',
        ]);

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/orders/{$order->id}/invoice");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => 'Invoice available only for paid orders',
            ]);
    }

    public function test_customer_cannot_get_other_users_invoice(): void
    {
        $otherCustomer = User::factory()->customer()->create();
        $order = Order::factory()->forUser($otherCustomer)->paid()->create();

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/orders/{$order->id}/invoice");

        $response->assertStatus(403);
    }

    public function test_customer_can_get_invoice_html(): void
    {
        $order = Order::factory()->forUser($this->customer)->paid()->create();

        $response = $this->authenticateCustomer()
            ->get("/api/customer/orders/{$order->id}/invoice/html");

        $response->assertStatus(200);
        $this->assertStringContainsString('text/html', $response->headers->get('Content-Type'));

        $this->assertStringContainsString('TAX INVOICE', $response->getContent());
        $this->assertStringContainsString($order->custom_order_code, $response->getContent());
    }

    public function test_customer_can_download_invoice(): void
    {
        $order = Order::factory()->forUser($this->customer)->paid()->create();

        $response = $this->authenticateCustomer()
            ->get("/api/customer/orders/{$order->id}/invoice/download");

        $response->assertStatus(200);
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_invoice_includes_amount_in_words(): void
    {
        $order = Order::factory()->forUser($this->customer)->paid()->create([
            'order_total' => 15500.00,
        ]);

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/orders/{$order->id}/invoice");

        $response->assertStatus(200);

        $amountInWords = $response->json('invoice.amount_in_words');
        $this->assertStringContainsString('Rupees', $amountInWords);
        $this->assertStringContainsString('Fifteen', $amountInWords);
        $this->assertStringContainsString('Thousand', $amountInWords);
    }

    public function test_invoice_includes_company_details(): void
    {
        $order = Order::factory()->forUser($this->customer)->paid()->create();

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/orders/{$order->id}/invoice");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'invoice' => [
                    'company' => [
                        'name',
                        'address',
                        'city',
                        'state',
                        'gstin',
                        'pan',
                    ],
                ],
            ]);
    }

    public function test_invoice_includes_tax_breakdown(): void
    {
        $order = Order::factory()->forUser($this->customer)->paid()->create([
            'total_taxes' => 300.00,
        ]);

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/orders/{$order->id}/invoice");

        $response->assertStatus(200);

        $taxes = $response->json('invoice.taxes');
        $this->assertIsArray($taxes);
    }

    // =====================
    // ADMIN INVOICE TESTS
    // =====================

    public function test_admin_can_get_any_order_invoice(): void
    {
        $order = Order::factory()->forUser($this->customer)->paid()->create();

        $response = $this->authenticateAdmin()
            ->getJson("/api/admin/orders/{$order->id}/invoice");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'invoice' => [
                    'invoice_number',
                    'order_number',
                    'grand_total',
                ],
            ]);
    }

    public function test_admin_can_download_any_invoice(): void
    {
        $order = Order::factory()->forUser($this->customer)->paid()->create();

        $response = $this->authenticateAdmin()
            ->get("/api/admin/orders/{$order->id}/invoice/download");

        $response->assertStatus(200);
    }

    public function test_admin_can_bulk_get_invoices(): void
    {
        $orders = Order::factory()->forUser($this->customer)->paid()->count(3)->create();

        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/invoices/bulk', [
                'order_ids' => $orders->pluck('id')->toArray(),
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 3,
            ])
            ->assertJsonStructure([
                'success',
                'invoices',
                'count',
            ]);
    }

    public function test_bulk_invoices_validates_order_ids(): void
    {
        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/invoices/bulk', [
                'order_ids' => [],
            ]);

        $response->assertStatus(422);
    }

    public function test_bulk_invoices_only_includes_paid_orders(): void
    {
        $paidOrder = Order::factory()->forUser($this->customer)->paid()->create();
        $unpaidOrder = Order::factory()->forUser($this->customer)->create([
            'payment_status' => 'pending',
        ]);

        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/invoices/bulk', [
                'order_ids' => [$paidOrder->id, $unpaidOrder->id],
            ]);

        $response->assertStatus(200);
        // Should only return 1 invoice (the paid one)
        $this->assertEquals(1, $response->json('count'));
    }

    // =====================
    // AUTH TESTS
    // =====================

    public function test_unauthenticated_cannot_get_invoice(): void
    {
        $order = Order::factory()->forUser($this->customer)->paid()->create();

        $response = $this->getJson("/api/customer/orders/{$order->id}/invoice");

        $response->assertStatus(401);
    }

    public function test_unauthenticated_cannot_access_admin_invoice(): void
    {
        $order = Order::factory()->forUser($this->customer)->paid()->create();

        $response = $this->getJson("/api/admin/orders/{$order->id}/invoice");

        $response->assertStatus(401);
    }
}

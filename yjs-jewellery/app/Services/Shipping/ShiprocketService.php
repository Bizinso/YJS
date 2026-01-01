<?php

namespace App\Services\Shipping;

use App\Models\Order;
use App\Models\ShipmentTracking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * SHIPROCKET SHIPPING SERVICE
 * Based on Official API: https://apidocs.shiprocket.in/
 * 
 * BACKWARD COMPATIBLE: Uses EXISTING Order fields:
 *   - shiprocket_order_id ✅
 *   - shipment_id ✅
 *   - awb_number ✅
 *   - courier_name ✅
 *   - courier_id ✅
 *   - shipping_status ✅
 *   - pickup_scheduled_date ✅
 */
class ShiprocketService
{
    private const API_BASE_URL = 'https://apiv2.shiprocket.in/v1/external';
    private const TOKEN_CACHE_KEY = 'shiprocket_auth_token';
    private const TOKEN_TTL_DAYS = 9;

    /**
     * Get auth token (cached for 9 days per Shiprocket spec)
     */
    private function getToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addDays(self::TOKEN_TTL_DAYS), function () {
            $response = Http::timeout(30)
                ->post(self::API_BASE_URL . '/auth/login', [
                    'email' => config('services.shiprocket.email'),
                    'password' => config('services.shiprocket.password'),
                ]);

            if (!$response->successful()) {
                throw new ShiprocketException('Authentication failed', 'AUTH_FAILED', $response->status());
            }

            return $response->json()['token'];
        });
    }

    /**
     * Check serviceability for delivery pincode
     */
    public function checkServiceability(string $pincode, int $weightGrams = 100): array
    {
        $cacheKey = "shiprocket_serviceability_{$pincode}_{$weightGrams}";
        
        return Cache::remember($cacheKey, 3600, function () use ($pincode, $weightGrams) {
            $response = $this->request('GET', '/courier/serviceability', [
                'pickup_postcode' => config('services.shiprocket.pickup_pincode'),
                'delivery_postcode' => $pincode,
                'weight' => $weightGrams / 1000,
                'cod' => 0, // No COD for jewellery
            ]);

            $couriers = $response['data']['available_courier_companies'] ?? [];

            if (empty($couriers)) {
                return [
                    'serviceable' => false,
                    'message' => 'Delivery not available to this pincode',
                ];
            }

            // Prefer surface shipping for jewellery (safer)
            $surfaceCouriers = array_filter($couriers, fn($c) => 
                stripos($c['courier_name'] ?? '', 'surface') !== false
            );

            $availableCouriers = !empty($surfaceCouriers) ? $surfaceCouriers : $couriers;
            $cheapest = collect($availableCouriers)->sortBy('rate')->first();

            return [
                'serviceable' => true,
                'estimated_days' => $cheapest['etd'] ?? '5-7',
                'shipping_charge' => (float) ($cheapest['rate'] ?? 0),
                'courier_name' => $cheapest['courier_name'] ?? 'Standard',
                'courier_id' => $cheapest['courier_company_id'] ?? null,
            ];
        });
    }

    /**
     * Create order in Shiprocket
     * Uses EXISTING Order fields: shiprocket_order_id, shipment_id
     */
    public function createOrder(Order $order): array
    {
        // Idempotency check using EXISTING field
        if ($order->shiprocket_order_id) {
            return [
                'success' => true,
                'shiprocket_order_id' => $order->shiprocket_order_id,
                'shipment_id' => $order->shipment_id,
                'message' => 'Order already exists in Shiprocket',
            ];
        }

        $shippingAddress = $order->shippingAddress;
        if (!$shippingAddress) {
            throw new ShiprocketException('No shipping address found', 'NO_ADDRESS', 400);
        }

        $orderItems = $order->orderProducts->map(function ($item) {
            return [
                'name' => $item->product->name ?? $item->product_name ?? 'Jewellery Item',
                'sku' => $item->product->sku ?? 'SKU-' . $item->product_id,
                'units' => $item->quantity,
                'selling_price' => (float) $item->price,
                'hsn' => $item->product->hsn_code ?? '7113', // Default HSN for jewellery
            ];
        })->toArray();

        $payload = [
            'order_id' => $order->custom_order_code, // Use EXISTING field
            'order_date' => $order->order_date?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
            'pickup_location' => config('services.shiprocket.pickup_location', 'Primary'),
            'channel_id' => config('services.shiprocket.channel_id'),
            'billing_customer_name' => $shippingAddress->first_name ?? $order->customer?->first_name ?? 'Customer',
            'billing_last_name' => $shippingAddress->last_name ?? $order->customer?->last_name ?? '',
            'billing_address' => $shippingAddress->address_line_1 ?? $shippingAddress->address ?? '',
            'billing_address_2' => $shippingAddress->address_line_2 ?? '',
            'billing_city' => $shippingAddress->city ?? '',
            'billing_pincode' => $shippingAddress->pincode ?? $shippingAddress->postal_code ?? '',
            'billing_state' => $shippingAddress->state ?? '',
            'billing_country' => 'India',
            'billing_email' => $order->email ?? $order->customer?->email ?? '',
            'billing_phone' => $shippingAddress->phone ?? $order->customer?->phone ?? '',
            'shipping_is_billing' => true,
            'order_items' => $orderItems,
            'payment_method' => 'Prepaid', // No COD for jewellery
            'sub_total' => (float) $order->order_total,
            'length' => 10,
            'breadth' => 10,
            'height' => 5,
            'weight' => 0.1, // 100 grams default for jewellery
        ];

        $response = $this->request('POST', '/orders/create/adhoc', $payload);

        // Update order using EXISTING fields
        $order->update([
            'shiprocket_order_id' => $response['order_id'] ?? null,
            'shipment_id' => $response['shipment_id'] ?? null,
            'shipping_status' => 'created',
        ]);

        // Create tracking record (new table)
        ShipmentTracking::updateOrCreate(
            ['order_id' => $order->id],
            [
                'shiprocket_order_id' => $response['order_id'] ?? null,
                'shipment_id' => $response['shipment_id'] ?? null,
                'current_status' => 'NEW',
            ]
        );

        return [
            'success' => true,
            'shiprocket_order_id' => $response['order_id'] ?? null,
            'shipment_id' => $response['shipment_id'] ?? null,
        ];
    }

    /**
     * Generate AWB for shipment
     * Uses EXISTING Order fields: shipment_id, awb_number, courier_name, courier_id
     */
    public function generateAWB(Order $order, ?int $courierId = null): array
    {
        if (!$order->shipment_id) {
            throw new ShiprocketException('No shipment found. Create order first.', 'NO_SHIPMENT', 400);
        }

        // Idempotency using EXISTING field
        if ($order->awb_number) {
            return [
                'success' => true,
                'awb' => $order->awb_number,
                'courier_name' => $order->courier_name,
                'message' => 'AWB already generated',
            ];
        }

        $payload = ['shipment_id' => $order->shipment_id];
        if ($courierId) {
            $payload['courier_id'] = $courierId;
        }

        $response = $this->request('POST', '/courier/assign/awb', $payload);

        $awb = $response['response']['data']['awb_code'] ?? null;
        $courierName = $response['response']['data']['courier_name'] ?? null;
        $courierCompanyId = $response['response']['data']['courier_company_id'] ?? null;

        if ($awb) {
            // Update using EXISTING fields
            $order->update([
                'awb_number' => $awb,
                'courier_name' => $courierName,
                'courier_id' => (string) $courierCompanyId,
                'order_status' => 'processing', // Use existing enum
                'shipping_status' => 'awb_assigned',
            ]);

            ShipmentTracking::where('order_id', $order->id)->update([
                'awb_code' => $awb,
                'courier_name' => $courierName,
                'courier_company_id' => $courierCompanyId,
                'current_status' => 'AWB_ASSIGNED',
            ]);
        }

        return [
            'success' => true,
            'awb' => $awb,
            'courier_name' => $courierName,
        ];
    }

    /**
     * Schedule pickup
     * Uses EXISTING Order field: pickup_scheduled_date
     */
    public function schedulePickup(Order $order, ?string $pickupDate = null): array
    {
        if (!$order->shipment_id) {
            throw new ShiprocketException('No shipment found', 'NO_SHIPMENT', 400);
        }

        $scheduledDate = $pickupDate ?? now()->addDay()->format('Y-m-d');

        $response = $this->request('POST', '/courier/generate/pickup', [
            'shipment_id' => [$order->shipment_id],
            'pickup_date' => $scheduledDate,
        ]);

        // Update using EXISTING field
        $order->update([
            'pickup_scheduled_date' => $scheduledDate,
            'order_status' => 'pickup_generated', // Use existing enum
            'shipping_status' => 'pickup_scheduled',
        ]);

        ShipmentTracking::where('order_id', $order->id)->update([
            'pickup_scheduled_date' => $scheduledDate,
            'current_status' => 'PICKUP_SCHEDULED',
        ]);

        return [
            'success' => true,
            'pickup_token' => $response['response']['pickup_token_number'] ?? null,
            'pickup_date' => $scheduledDate,
        ];
    }

    /**
     * Track shipment by AWB
     */
    public function trackByAWB(string $awb): array
    {
        $response = $this->request('GET', "/courier/track/awb/{$awb}");

        $tracking = $response['tracking_data'] ?? [];
        $activities = $tracking['shipment_track_activities'] ?? [];

        return [
            'awb' => $awb,
            'status' => $tracking['shipment_status'] ?? null,
            'current_status' => $tracking['shipment_track'][0]['current_status'] ?? 'Unknown',
            'delivered_date' => $tracking['shipment_track'][0]['delivered_date'] ?? null,
            'etd' => $tracking['etd'] ?? null,
            'activities' => array_map(fn($a) => [
                'date' => $a['date'] ?? '',
                'status' => $a['sr-status-label'] ?? $a['activity'] ?? '',
                'location' => $a['location'] ?? '',
            ], $activities),
        ];
    }

    /**
     * Sync tracking status for order
     * Updates EXISTING Order fields: order_status, shipping_status
     */
    public function syncOrderTracking(Order $order): void
    {
        if (!$order->awb_number) return;

        $tracking = $this->trackByAWB($order->awb_number);
        
        // Map Shiprocket status to EXISTING order_status enum
        $statusMap = [
            'DELIVERED' => 'delivered',
            'OUT FOR DELIVERY' => 'shipped',
            'IN TRANSIT' => 'shipped',
            'PICKED UP' => 'picked_up',
            'RTO INITIATED' => 'returned',
            'RTO DELIVERED' => 'returned',
        ];

        $newStatus = $statusMap[strtoupper($tracking['current_status'] ?? '')] ?? null;

        DB::transaction(function () use ($order, $tracking, $newStatus) {
            ShipmentTracking::where('order_id', $order->id)->update([
                'current_status' => $tracking['current_status'] ?? null,
                'activities' => json_encode($tracking['activities'] ?? []),
                'etd' => $tracking['etd'] ?? null,
                'is_delivered' => strtoupper($tracking['status'] ?? '') === 'DELIVERED',
                'is_rto' => str_contains(strtoupper($tracking['current_status'] ?? ''), 'RTO'),
                'last_synced_at' => now(),
            ]);

            // Update using EXISTING enum values
            if ($newStatus && $order->order_status !== $newStatus) {
                $updateData = [
                    'order_status' => $newStatus,
                    'shipping_status' => $tracking['current_status'],
                ];
                
                if ($newStatus === 'delivered') {
                    $updateData['delivery_date'] = now()->toDateString();
                }
                
                $order->update($updateData);
            }
        });
    }

    /**
     * Cancel Shiprocket order
     */
    public function cancelOrder(Order $order): array
    {
        if (!$order->shiprocket_order_id) {
            return ['success' => true, 'message' => 'No Shiprocket order to cancel'];
        }

        $response = $this->request('POST', '/orders/cancel', [
            'ids' => [$order->shiprocket_order_id],
        ]);

        $order->update([
            'shipping_status' => 'cancelled',
        ]);

        return [
            'success' => true,
            'message' => 'Shiprocket order cancelled',
        ];
    }

    /**
     * Generate shipping label
     */
    public function generateLabel(int $shipmentId): ?string
    {
        $response = $this->request('POST', '/courier/generate/label', [
            'shipment_id' => [$shipmentId],
        ]);

        return $response['label_url'] ?? null;
    }

    /**
     * Create return order for customer returns
     */
    public function createReturnOrder(Order $order, array $items, string $reason): array
    {
        $shippingAddress = $order->shippingAddress;

        $payload = [
            'order_id' => 'RET-' . $order->custom_order_code,
            'order_date' => now()->format('Y-m-d'),
            'pickup_customer_name' => $shippingAddress->first_name ?? 'Customer',
            'pickup_last_name' => $shippingAddress->last_name ?? '',
            'pickup_address' => $shippingAddress->address_line_1 ?? $shippingAddress->address ?? '',
            'pickup_city' => $shippingAddress->city ?? '',
            'pickup_state' => $shippingAddress->state ?? '',
            'pickup_country' => 'India',
            'pickup_pincode' => $shippingAddress->pincode ?? '',
            'pickup_email' => $order->email ?? '',
            'pickup_phone' => $shippingAddress->phone ?? '',
            'shipping_customer_name' => config('services.shiprocket.company_name', 'YJS Jewellers'),
            'shipping_address' => config('services.shiprocket.return_address'),
            'shipping_city' => config('services.shiprocket.return_city'),
            'shipping_state' => config('services.shiprocket.return_state'),
            'shipping_country' => 'India',
            'shipping_pincode' => config('services.shiprocket.pickup_pincode'),
            'shipping_phone' => config('services.shiprocket.return_phone'),
            'order_items' => array_map(fn($item) => [
                'name' => $item['name'],
                'sku' => $item['sku'],
                'units' => $item['quantity'],
                'selling_price' => $item['price'],
            ], $items),
            'payment_method' => 'Prepaid',
            'sub_total' => array_sum(array_column($items, 'price')),
            'length' => 10,
            'breadth' => 10,
            'height' => 5,
            'weight' => 0.1,
        ];

        $response = $this->request('POST', '/orders/create/return', $payload);

        return [
            'success' => true,
            'return_order_id' => $response['order_id'] ?? null,
            'return_shipment_id' => $response['shipment_id'] ?? null,
        ];
    }

    /**
     * Process webhook from Shiprocket
     * Updates EXISTING Order fields
     */
    public function processWebhook(array $payload): array
    {
        $awb = $payload['awb'] ?? null;
        $status = $payload['current_status'] ?? null;
        $statusId = $payload['current_status_id'] ?? null;

        if (!$awb) {
            return ['success' => false, 'error' => 'No AWB in payload'];
        }

        Log::info('Shiprocket webhook received', ['awb' => $awb, 'status' => $status]);

        // Find order using EXISTING field
        $order = Order::where('awb_number', $awb)->first();
        if (!$order) {
            return ['success' => false, 'error' => 'Order not found'];
        }

        // Map to EXISTING order_status enum
        $statusMap = [
            6 => 'shipped',
            7 => 'delivered',
            8 => 'cancelled',
            9 => 'returned',    // RTO Initiated
            10 => 'returned',   // RTO Delivered
            17 => 'shipped',    // Out for Delivery
            18 => 'shipped',    // In Transit
            19 => 'picked_up',  // Picked Up
        ];

        $newStatus = $statusMap[$statusId] ?? null;

        DB::transaction(function () use ($order, $status, $newStatus, $payload) {
            ShipmentTracking::where('order_id', $order->id)->update([
                'current_status' => $status,
                'current_status_id' => $payload['current_status_id'] ?? null,
                'current_location' => $payload['current_location'] ?? null,
                'is_delivered' => $newStatus === 'delivered',
                'is_rto' => in_array($newStatus, ['returned']),
                'last_synced_at' => now(),
            ]);

            // Update EXISTING fields
            if ($newStatus) {
                $updateData = [
                    'order_status' => $newStatus,
                    'shipping_status' => $status,
                ];
                
                if ($newStatus === 'delivered') {
                    $updateData['delivery_date'] = now()->toDateString();
                }
                
                $order->update($updateData);

                // Handle RTO - restore inventory
                if ($newStatus === 'returned' && str_contains(strtoupper($status), 'RTO DELIVERED')) {
                    $this->handleRTODelivered($order);
                }
            }
        });

        return ['success' => true];
    }

    /**
     * Handle RTO delivered - restore inventory
     */
    private function handleRTODelivered(Order $order): void
    {
        foreach ($order->orderProducts as $item) {
            $product = $item->product;
            if ($product) {
                $product->increment('available_stock', $item->quantity);
            }
        }

        Log::info("RTO delivered - inventory restored for order {$order->id}");
    }

    /**
     * Make API request with authentication
     */
    private function request(string $method, string $endpoint, array $data = []): array
    {
        $token = $this->getToken();
        $url = self::API_BASE_URL . $endpoint;

        $response = Http::withToken($token)
            ->timeout(30)
            ->acceptJson();

        $response = match (strtoupper($method)) {
            'GET' => $response->get($url, $data),
            'POST' => $response->post($url, $data),
            default => throw new \InvalidArgumentException("Unsupported method: {$method}"),
        };

        if ($response->successful()) {
            return $response->json();
        }

        // Token expired - clear cache and retry once
        if ($response->status() === 401) {
            Cache::forget(self::TOKEN_CACHE_KEY);
            $token = $this->getToken();
            
            $response = Http::withToken($token)->timeout(30)->acceptJson();
            $response = match (strtoupper($method)) {
                'GET' => $response->get($url, $data),
                'POST' => $response->post($url, $data),
            };
            
            if ($response->successful()) {
                return $response->json();
            }
        }

        throw new ShiprocketException(
            $response->json()['message'] ?? 'API error',
            'API_ERROR',
            $response->status()
        );
    }
}

class ShiprocketException extends \Exception
{
    public function __construct(
        string $message,
        public string $errorCode,
        public int $httpStatus
    ) {
        parent::__construct($message);
    }
}

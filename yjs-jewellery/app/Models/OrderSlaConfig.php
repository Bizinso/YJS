<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Order SLA Configuration Model
 *
 * Defines SLA rules for order processing.
 */
class OrderSlaConfig extends Model
{
    use HasFactory;

    protected $table = 'order_sla_config';

    protected $fillable = [
        'sla_type',
        'description',
        'hours_limit',
        'is_active',
        'send_alerts',
        'alert_before_hours',
        'applicable_statuses',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'send_alerts' => 'boolean',
        'applicable_statuses' => 'array',
    ];

    // SLA Types
    const TYPE_CONFIRMATION = 'confirmation_time';
    const TYPE_PROCESSING = 'processing_time';
    const TYPE_SHIPPING = 'shipping_time';
    const TYPE_DELIVERY = 'delivery_time';
    const TYPE_RESPONSE = 'response_time';

    /**
     * Get default SLA configurations.
     */
    public static function getDefaults(): array
    {
        return [
            [
                'sla_type' => self::TYPE_CONFIRMATION,
                'description' => 'Time to confirm order after payment',
                'hours_limit' => 2,
                'alert_before_hours' => 1,
                'applicable_statuses' => ['pending'],
            ],
            [
                'sla_type' => self::TYPE_PROCESSING,
                'description' => 'Time to process order after confirmation',
                'hours_limit' => 24,
                'alert_before_hours' => 4,
                'applicable_statuses' => ['confirmed'],
            ],
            [
                'sla_type' => self::TYPE_SHIPPING,
                'description' => 'Time to ship order after processing',
                'hours_limit' => 48,
                'alert_before_hours' => 8,
                'applicable_statuses' => ['processing'],
            ],
            [
                'sla_type' => self::TYPE_DELIVERY,
                'description' => 'Expected delivery time after shipping',
                'hours_limit' => 168, // 7 days
                'alert_before_hours' => 24,
                'applicable_statuses' => ['shipped'],
            ],
        ];
    }

    /**
     * Get active SLA config by type.
     */
    public static function getByType(string $type): ?self
    {
        return self::where('sla_type', $type)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Check if an order is breaching SLA.
     */
    public static function checkOrderSla(Order $order): array
    {
        $breaches = [];
        $configs = self::where('is_active', true)->get();

        foreach ($configs as $config) {
            if (!in_array($order->order_status, $config->applicable_statuses ?? [])) {
                continue;
            }

            $startTime = match ($config->sla_type) {
                self::TYPE_CONFIRMATION => $order->created_at,
                self::TYPE_PROCESSING => $order->confirmed_at,
                self::TYPE_SHIPPING => $order->processing_started_at,
                self::TYPE_DELIVERY => $order->shipped_at,
                default => null,
            };

            if (!$startTime) continue;

            $hoursElapsed = $startTime->diffInHours(now());
            $isBreaching = $hoursElapsed >= $config->hours_limit;
            $isAtRisk = $hoursElapsed >= ($config->hours_limit - $config->alert_before_hours);

            if ($isBreaching || $isAtRisk) {
                $breaches[] = [
                    'sla_type' => $config->sla_type,
                    'description' => $config->description,
                    'hours_limit' => $config->hours_limit,
                    'hours_elapsed' => $hoursElapsed,
                    'hours_remaining' => max(0, $config->hours_limit - $hoursElapsed),
                    'status' => $isBreaching ? 'breached' : 'at_risk',
                    'started_at' => $startTime->toDateTimeString(),
                ];
            }
        }

        return $breaches;
    }

    /**
     * Get orders that are breaching or at risk.
     */
    public static function getBreachingOrders(): array
    {
        $result = [
            'breached' => [],
            'at_risk' => [],
        ];

        $configs = self::where('is_active', true)->get();

        foreach ($configs as $config) {
            if (empty($config->applicable_statuses)) continue;

            $orders = Order::whereIn('order_status', $config->applicable_statuses)
                ->where('is_on_hold', false)
                ->get();

            foreach ($orders as $order) {
                $slaCheck = self::checkOrderSla($order);
                foreach ($slaCheck as $breach) {
                    if ($breach['sla_type'] === $config->sla_type) {
                        $orderData = [
                            'order_id' => $order->id,
                            'order_code' => $order->custom_order_code,
                            'status' => $order->order_status,
                            'sla_info' => $breach,
                        ];

                        if ($breach['status'] === 'breached') {
                            $result['breached'][] = $orderData;
                        } else {
                            $result['at_risk'][] = $orderData;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

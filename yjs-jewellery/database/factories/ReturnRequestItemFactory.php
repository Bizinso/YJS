<?php

namespace Database\Factories;

use App\Models\ReturnRequestItem;
use App\Models\ReturnRequest;
use App\Models\OrderProduct;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReturnRequestItemFactory extends Factory
{
    protected $model = ReturnRequestItem::class;

    public function definition(): array
    {
        return [
            'return_request_id' => ReturnRequest::factory(),
            'order_item_id' => OrderProduct::factory(),
            'product_id' => Product::factory(),
            'quantity' => $this->faker->numberBetween(1, 3),
            'reason_code' => $this->faker->randomElement(['defective', 'wrong_item', 'not_as_described']),
            'item_status' => 'pending',
            'refund_amount' => $this->faker->randomFloat(2, 100, 2000),
        ];
    }
}

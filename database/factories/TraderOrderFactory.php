<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\TraderOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TraderOrder>
 */
class TraderOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'status' => TraderOrder::STATUS_NEW,
            'estimated_total' => 0,
        ];
    }
}

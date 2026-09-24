<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\TraderReceipt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TraderReceipt>
 */
class TraderReceiptFactory extends Factory
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
            'received_at' => now()->format('Y-m-d H:i:s'),
            'amount' => fake()->numberBetween(100, 5000),
            'method' => 'cash',
        ];
    }
}

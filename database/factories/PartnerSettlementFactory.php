<?php

namespace Database\Factories;

use App\Models\PartnerSettlement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartnerSettlement>
 */
class PartnerSettlementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'settled_on' => now()->toDateString(),
            'from_user_id' => User::factory(),
            'to_user_id' => User::factory(),
            'amount' => fake()->numberBetween(100, 10000),
            'method' => 'cash',
        ];
    }
}

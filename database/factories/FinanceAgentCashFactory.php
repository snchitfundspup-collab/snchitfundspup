<?php

namespace Database\Factories;

use App\Models\FinanceAgentCash;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceAgentCash>
 */
class FinanceAgentCashFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'entry_at' => now(config('app.business_timezone'))->format('Y-m-d H:i:s'),
            'type' => FinanceAgentCash::FROM_TREASURY,
            'agent_id' => User::factory(),
            'amount' => 10000,
        ];
    }
}

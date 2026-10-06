<?php

namespace Database\Factories;

use App\Models\FinanceCapital;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceCapital>
 */
class FinanceCapitalFactory extends Factory
{
    /**
     * ₹5,00,000 invested today.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'entry_on' => now()->toDateString(),
            'type' => FinanceCapital::INVEST,
            'amount' => 500000,
            'method' => 'cash',
        ];
    }
}

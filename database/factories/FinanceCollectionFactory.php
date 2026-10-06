<?php

namespace Database\Factories;

use App\Models\FinanceCollection;
use App\Models\FinanceLoan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceCollection>
 */
class FinanceCollectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'finance_loan_id' => FinanceLoan::factory(),
            'customer_id' => fn (array $attributes) => FinanceLoan::find($attributes['finance_loan_id'])->customer_id,
            'collected_at' => now()->format('Y-m-d H:i:s'),
            'amount' => 100,
            'method' => 'cash',
        ];
    }
}

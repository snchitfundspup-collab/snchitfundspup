<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'spent_on' => now()->toDateString(),
            'description' => fake()->sentence(3),
            'amount' => fake()->numberBetween(100, 20000),
            'paid_by' => User::factory(),
            'paid_to' => fake()->company(),
            'method' => 'cash',
            'reference' => 'BILL-'.fake()->numberBetween(100, 999),
        ];
    }
}

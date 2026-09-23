<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_code' => 'SN'.fake()->unique()->numberBetween(2601, 9999),
            'name' => fake()->name(),
            'phone' => fake()->numerify('9#########'),
            'email' => fake()->optional()->safeEmail(),
            'address' => fake()->optional()->address(),
            'remarks' => fake()->optional()->sentence(3),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the customer is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

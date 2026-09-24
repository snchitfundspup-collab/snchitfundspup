<?php

namespace Database\Factories;

use App\Models\RiceVariety;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiceVariety>
 */
class RiceVarietyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Ponni', 'Sona Masoori', 'Idli Rice', 'Basmati', 'Kichadi Samba', 'Seeraga Samba', 'Boiled Rice', 'Raw Rice']).' '.fake()->unique()->numberBetween(1, 9999),
            'bag_kg' => 26,
            'is_active' => true,
        ];
    }
}

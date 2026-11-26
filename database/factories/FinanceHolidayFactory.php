<?php

namespace Database\Factories;

use App\Models\FinanceHoliday;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceHoliday>
 */
class FinanceHolidayFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'starts_on' => '2026-11-01',
            'ends_on' => '2026-11-07',
            'agent_id' => User::factory(),
        ];
    }
}

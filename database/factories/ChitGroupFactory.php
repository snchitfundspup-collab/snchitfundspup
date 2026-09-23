<?php

namespace Database\Factories;

use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChitGroup>
 */
class ChitGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Group '.fake()->unique()->numberBetween(100, 9999),
            'type' => ChitGroup::TYPE_DRAW,
            'amount' => 200000,
            'months' => 20,
            'installment_amount' => 10000,
            'member_count' => 20,
            'commission_amount' => 0,
            'start_date' => now()->startOfMonth()->addDays(14),
            'status' => ChitGroup::STATUS_FORMING,
        ];
    }

    /**
     * Indicate that the group has been started.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ChitGroup::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    /**
     * Fill the group with members (defaults to its planned member count).
     */
    public function withMembers(?int $count = null): static
    {
        return $this->afterCreating(function (ChitGroup $group) use ($count) {
            ChitGroupMember::factory()
                ->count($count ?? $group->member_count)
                ->create(['chit_group_id' => $group->id]);
        });
    }

    /**
     * Add a withdrawal schedule: month 1 = base, rising by step each month.
     */
    public function withPayouts(int $base = 160000, int $step = 2000): static
    {
        return $this->afterCreating(function (ChitGroup $group) use ($base, $step) {
            foreach (range(1, $group->months) as $monthNumber) {
                $group->payouts()->create([
                    'month_number' => $monthNumber,
                    'withdrawal_amount' => $base + ($monthNumber - 1) * $step,
                ]);
            }
        });
    }
}

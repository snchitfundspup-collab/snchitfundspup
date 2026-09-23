<?php

namespace Database\Factories;

use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChitGroupMember>
 */
class ChitGroupMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chit_group_id' => ChitGroup::factory(),
            'customer_id' => Customer::factory(),
            'member_code' => fn (array $attributes) => Customer::find($attributes['customer_id'])->customer_code,
        ];
    }
}

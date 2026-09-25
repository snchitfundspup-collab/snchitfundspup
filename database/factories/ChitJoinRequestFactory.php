<?php

namespace Database\Factories;

use App\Models\ChitGroup;
use App\Models\ChitJoinRequest;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChitJoinRequest>
 */
class ChitJoinRequestFactory extends Factory
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
            'seats' => 1,
            'status' => ChitJoinRequest::STATUS_PENDING,
        ];
    }
}

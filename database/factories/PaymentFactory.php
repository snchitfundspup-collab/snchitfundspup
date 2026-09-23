<?php

namespace Database\Factories;

use App\Models\ChitGroupMember;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Raw payment rows (no month allocations). In tests prefer
 * Payment::record(), which allocates to months like the real screen does.
 *
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chit_group_member_id' => ChitGroupMember::factory(),
            'chit_group_id' => fn (array $attributes) => ChitGroupMember::find($attributes['chit_group_member_id'])->chit_group_id,
            'customer_id' => fn (array $attributes) => ChitGroupMember::find($attributes['chit_group_member_id'])->customer_id,
            'amount' => 10000,
            'paid_at' => now(config('app.business_timezone'))->format('Y-m-d H:i:s'),
            'method' => 'cash',
        ];
    }
}

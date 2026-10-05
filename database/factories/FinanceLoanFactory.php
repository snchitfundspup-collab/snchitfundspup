<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\FinanceLoan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceLoan>
 */
class FinanceLoanFactory extends Factory
{
    /**
     * A ₹10,000 loan given today on the standard terms (1% fee + 18% GST
     * cut, 26% a year for 100 days = ₹712), repaid ₹108 a day from
     * tomorrow.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'loaned_on' => now()->toDateString(),
            ...FinanceLoan::terms(10000, FinanceLoan::DEFAULT_PROCESSING_FEE_RATE, FinanceLoan::DEFAULT_GST_RATE, FinanceLoan::DEFAULT_INTEREST_RATE, FinanceLoan::DAILY, 100),
            'frequency' => FinanceLoan::DAILY,
            'first_due_on' => now()->addDay()->toDateString(),
            'status' => FinanceLoan::STATUS_ACTIVE,
        ];
    }

    /**
     * Repaid weekly over $weeks weeks.
     */
    public function weekly(int $weeks = 14, ?int $installmentAmount = null): static
    {
        return $this->state(fn (array $attributes) => [
            ...FinanceLoan::terms($attributes['principal'], $attributes['processing_fee_rate'], $attributes['gst_rate'], $attributes['interest_rate'], FinanceLoan::WEEKLY, $weeks, $installmentAmount),
            'frequency' => FinanceLoan::WEEKLY,
            'first_due_on' => now()->addWeek()->toDateString(),
        ]);
    }

    /**
     * Numbered like a loan given through the app.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (FinanceLoan $loan) {
            if ($loan->loan_number === null) {
                $loan->update(['loan_number' => 'L'.str_pad((string) $loan->id, 6, '0', STR_PAD_LEFT)]);
            }
        });
    }
}

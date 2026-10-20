<?php

use App\Models\FinanceCapital;
use App\Models\FinanceCollection;
use App\Models\FinanceExpense;
use App\Models\FinanceLoan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * ₹1,00,000 invested; Ranjith's customer gets a ₹10,000 loan (₹200 fee →
 * ₹9,800 in hand, ₹11,200 to repay), Dinesh's a ₹5,000 loan (₹100 fee →
 * ₹4,900); ₹1,000 comes back on Ranjith's loan; ₹200 is spent.
 */
test('Capital & Cash shows where the money went by agent, what came back and what is left to lend', function () {
    $ranjith = User::factory()->create(['name' => 'Ranjith']);
    $dinesh = User::factory()->create(['name' => 'Dinesh']);
    $this->actingAs($ranjith, 'web');

    FinanceCapital::factory()->create(['type' => FinanceCapital::INVEST, 'amount' => 100000, 'entry_on' => '2026-10-01']);
    $loan = FinanceLoan::factory()->create(['agent_id' => $ranjith->id, 'principal' => 10000, 'processing_fee' => 200, 'gst' => 0, 'loan_amount' => 11200]);
    FinanceLoan::factory()->create(['agent_id' => $dinesh->id, 'principal' => 5000, 'processing_fee' => 100, 'gst' => 0, 'loan_amount' => 5600]);
    FinanceCollection::factory()->create(['finance_loan_id' => $loan->id, 'amount' => 1000]);
    FinanceExpense::create(['spent_on' => now()->toDateString(), 'description' => 'Petrol', 'amount' => 200, 'paid_by' => $ranjith->id, 'method' => 'cash']);

    $rows = FinanceCapital::byAgent()->keyBy('agent');

    expect($rows['Ranjith'])->toMatchArray(['loans' => 1, 'principal' => 10000, 'fee' => 200, 'in_hand' => 9800, 'collected' => 1000, 'to_collect' => 10200])
        ->and($rows['Dinesh'])->toMatchArray(['in_hand' => 4900, 'collected' => 0, 'to_collect' => 5600])
        ->and(FinanceCapital::position()['available'])->toBe(86100);

    $this->get(route('finance.capital.index'))
        ->assertOk()
        ->assertSeeTextInOrder(['Given to customers of', 'Dinesh', '₹4,900', 'Given to customers of', 'Ranjith', '₹9,800'])
        ->assertSeeText('Collected back from customers of')
        ->assertSeeText('Money in hand to lend again')
        ->assertSeeText('₹86,100')
        ->assertSeeText('Processing fee kept (profit)')
        ->assertSeeText('₹300')
        ->assertSeeText('Still with customers (to collect)')
        ->assertSeeText('₹15,800')
        ->assertSeeText('Money by agent');
});

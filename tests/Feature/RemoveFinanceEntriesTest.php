<?php

use App\Models\Customer;
use App\Models\FinanceCollection;
use App\Models\FinanceLoan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/**
 * An old loan was entered yesterday and fully paid before 6 am today
 * (office time); after 6 am a wrong receipt went on it and a wrong loan
 * (with a receipt) was added.
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-19 10:00:00', 'Asia/Kolkata'));
    $this->customer = Customer::factory()->create(['name' => 'Kavitha']);
    $this->oldLoan = FinanceLoan::factory()->create(['customer_id' => $this->customer->id, 'loaned_on' => '2026-10-19', 'first_due_on' => '2026-10-20']);
    $this->kept = FinanceCollection::factory()->create(['finance_loan_id' => $this->oldLoan->id, 'amount' => $this->oldLoan->loan_amount]);
    $this->oldLoan->refreshStatus();

    $this->travelTo(Carbon::parse('2026-10-20 06:30:00', 'Asia/Kolkata'));
    $this->wrongReceipt = FinanceCollection::factory()->create(['finance_loan_id' => $this->oldLoan->id, 'amount' => 1]);
    $this->wrongLoan = FinanceLoan::factory()->create(['customer_id' => $this->customer->id]);
    FinanceCollection::factory()->create(['finance_loan_id' => $this->wrongLoan->id, 'amount' => 50]);
});

test('without --force it only lists what would go', function () {
    $this->artisan('app:remove-finance-entries', ['since' => '2026-10-20 06:00'])
        ->expectsOutputToContain('1 loan(s) and 2 collection(s)')
        ->assertSuccessful();

    expect(FinanceLoan::count())->toBe(2)->and(FinanceCollection::count())->toBe(3);
});

test('loans and collections entered after the time go; earlier ones and customers stay', function () {
    $this->oldLoan->update(['status' => FinanceLoan::STATUS_CLOSED]);
    $this->kept->update(['amount' => 100]);

    $this->artisan('app:remove-finance-entries', ['since' => '2026-10-20 06:00', '--force' => true])->assertSuccessful();

    expect(FinanceLoan::pluck('id')->all())->toBe([$this->oldLoan->id])
        ->and(FinanceCollection::pluck('id')->all())->toBe([$this->kept->id])
        ->and(Customer::count())->toBe(1)
        ->and($this->oldLoan->refresh()->status)->toBe(FinanceLoan::STATUS_ACTIVE);
});

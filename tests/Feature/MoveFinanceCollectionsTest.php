<?php

use App\Models\FinanceCollection;
use App\Models\FinanceLoan;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->sathiya = User::factory()->create(['name' => 'Sathiya', 'username' => 'sathiya']);
    $this->ranjith = User::factory()->create(['name' => 'Ranjith', 'username' => 'ranjith']);
    $this->dinesh = User::factory()->create(['name' => 'Dinesh', 'username' => 'dinesh']);
    $loan = FinanceLoan::factory()->create();

    $this->bySathiya = FinanceCollection::factory()->create(['finance_loan_id' => $loan->id, 'recorded_by' => $this->sathiya->id, 'collected_by' => $this->sathiya->id]);
    $this->enteredForRanjith = FinanceCollection::factory()->create(['finance_loan_id' => $loan->id, 'recorded_by' => $this->sathiya->id, 'collected_by' => $this->ranjith->id]);
    $this->forDinesh = FinanceCollection::factory()->create(['finance_loan_id' => $loan->id, 'recorded_by' => $this->sathiya->id, 'collected_by' => $this->dinesh->id]);
});

test('without --force nothing changes', function () {
    $this->artisan('app:move-finance-collections', ['from' => 'sathiya', 'to' => 'ranjith'])
        ->expectsOutputToContain('2 collection(s)')
        ->assertSuccessful();

    expect($this->bySathiya->refresh()->collected_by)->toBe($this->sathiya->id);
});

test('Sathiya\'s collections get Ranjith as collector and signature; others stay', function () {
    $payment = Payment::factory()->create(['recorded_by' => $this->sathiya->id, 'collected_by' => $this->sathiya->id]);

    $this->artisan('app:move-finance-collections', ['from' => 'sathiya', 'to' => 'ranjith', '--force' => true])->assertSuccessful();

    foreach ([$this->bySathiya, $this->enteredForRanjith] as $collection) {
        $collection->refresh();
        expect($collection->collected_by)->toBe($this->ranjith->id)
            ->and($collection->recorded_by)->toBe($this->ranjith->id);
    }

    expect($this->forDinesh->refresh()->collected_by)->toBe($this->dinesh->id)
        ->and($this->forDinesh->recorded_by)->toBe($this->sathiya->id)
        ->and($payment->refresh()->collected_by)->toBe($this->sathiya->id);
});

<?php

use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\FinanceCapital;
use App\Models\FinanceCollection;
use App\Models\FinanceLoan;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['name' => 'Narayanan']);
    $this->group = ChitGroup::factory()->create();
    ChitGroupMember::factory()->create(['chit_group_id' => $this->group->id]);
    FinanceCollection::factory()->create(['finance_loan_id' => FinanceLoan::factory()]);
    Sale::factory()->create();
    FinanceCapital::factory()->create();
    Expense::factory()->create(['paid_by' => $this->admin->id]);
});

test('without --force nothing is deleted', function () {
    $this->artisan('app:clear-customer-data')->assertSuccessful();

    expect(Customer::count())->toBe(3)
        ->and(FinanceLoan::count())->toBe(1);
});

test('customers, their records, expenses and capital go; staff and setup stay; numbering starts again', function () {
    $this->artisan('app:clear-customer-data', ['--force' => true])->assertSuccessful();

    expect(Customer::count())->toBe(0)
        ->and(ChitGroupMember::count())->toBe(0)
        ->and(FinanceLoan::count())->toBe(0)
        ->and(FinanceCollection::count())->toBe(0)
        ->and(Sale::count())->toBe(0)
        ->and(User::count())->toBe(1)
        ->and(ChitGroup::count())->toBe(1)
        ->and(FinanceCapital::count())->toBe(0)
        ->and(Expense::count())->toBe(0)
        ->and(Customer::nextCode())->toBe('SL2601');

    expect(FinanceLoan::factory()->create()->loan_number)->toBe('L000001');
});

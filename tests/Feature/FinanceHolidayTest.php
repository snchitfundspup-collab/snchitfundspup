<?php

use App\Models\Customer;
use App\Models\FinanceHoliday;
use App\Models\FinanceLoan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/**
 * Weekly loans from 3 Nov 2026 (₹1,000 × 14). Today is 20 Nov. A festival
 * week 10–16 Nov is a holiday for Ranjith's customers only.
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-20 10:00:00', 'Asia/Kolkata'));

    $this->sathiya = User::factory()->create(['name' => 'Sathiya', 'username' => 'sathiya']);
    $this->ranjith = User::factory()->agent()->create(['name' => 'Ranjith', 'username' => 'ranjith']);
    $this->dinesh = User::factory()->agent()->create(['name' => 'Dinesh', 'username' => 'dinesh']);

    $terms = ['frequency' => FinanceLoan::WEEKLY, 'installments' => 14, 'installment_amount' => 1000, 'loan_amount' => 14000, 'principal' => 12000, 'loaned_on' => '2026-10-27', 'first_due_on' => '2026-11-03'];
    $this->ranjithLoan = FinanceLoan::factory()->create($terms + ['agent_id' => $this->ranjith->id, 'customer_id' => Customer::factory()->create(['name' => 'Kavitha'])->id]);
    $this->dineshLoan = FinanceLoan::factory()->create($terms + ['agent_id' => $this->dinesh->id]);
});

test('a holiday moves that week and every later instalment one week on, for the chosen agent only', function () {
    /* 3, 10 and 17 Nov have passed */
    expect($this->ranjithLoan->standing()['overdue'])->toBe(3000);

    $this->actingAs($this->sathiya, 'web')
        ->post(route('finance.holidays.store'), ['starts_on' => '2026-11-10', 'ends_on' => '2026-11-16', 'agent_ids' => [$this->ranjith->id], 'notes' => 'Festival'])
        ->assertRedirect(route('finance.holidays.index'));

    $loan = FinanceLoan::find($this->ranjithLoan->id);

    expect($loan->dueDateFor(1)->toDateString())->toBe('2026-11-03')
        ->and($loan->dueDateFor(2)->toDateString())->toBe('2026-11-17')
        ->and($loan->dueDateFor(3)->toDateString())->toBe('2026-11-24')
        ->and($loan->lastDueDate()->toDateString())->toBe('2027-02-09')
        ->and($loan->standing()['overdue'])->toBe(2000)
        ->and($loan->schedule()[1]['moved'])->toBeTrue()
        ->and($loan->schedule()[2]['moved'])->toBeFalse();

    /* Dinesh's customers still pay that week */
    expect(FinanceLoan::find($this->dineshLoan->id)->standing()['overdue'])->toBe(3000);

    $this->get(route('finance.loans.kfs', $loan))->assertOk()->assertSee('17 Nov 26*', false)->assertSeeText('Moved later for a holiday');

    $this->delete(route('finance.holidays.destroy', FinanceHoliday::sole()))->assertRedirect();
    expect(FinanceLoan::find($this->ranjithLoan->id)->dueDateFor(2)->toDateString())->toBe('2026-11-10');
});

test('only the owner manages holidays, and at least one agent must be ticked', function () {
    $this->actingAs($this->sathiya, 'web')
        ->post(route('finance.holidays.store'), ['starts_on' => '2026-11-10', 'ends_on' => '2026-11-16', 'agent_ids' => []])
        ->assertSessionHasErrors('agent_ids');

    $this->get(route('finance.holidays.index'))->assertOk()->assertSeeText('Add a holiday');

    $this->actingAs($this->ranjith, 'web');
    $this->get(route('finance.holidays.index'))->assertForbidden();
    $this->post(route('finance.holidays.store'), ['starts_on' => '2026-11-10', 'ends_on' => '2026-11-16', 'agent_ids' => [$this->ranjith->id]])->assertForbidden();
});

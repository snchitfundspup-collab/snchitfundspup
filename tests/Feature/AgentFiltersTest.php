<?php

use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use App\Models\Customer;
use App\Models\FinanceCollection;
use App\Models\FinanceLoan;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/**
 * Ranjith brought Kavitha in and collects from her; Dinesh brought Murugan.
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-20 10:00:00'));

    $this->ranjith = User::factory()->create(['name' => 'Ranjith']);
    $this->dinesh = User::factory()->create(['name' => 'Dinesh']);
    $this->actingAs($this->ranjith, 'web');

    $this->kavitha = Customer::factory()->create(['name' => 'Kavitha']);
    $this->murugan = Customer::factory()->create(['name' => 'Murugan']);

    $this->kavithaLoan = FinanceLoan::factory()->create(['customer_id' => $this->kavitha->id, 'agent_id' => $this->ranjith->id, 'loaned_on' => '2026-10-10', 'first_due_on' => '2026-10-11']);
    $this->kavithaLoan->update(['loan_number' => 'L000901']);
    $this->muruganLoan = FinanceLoan::factory()->create(['customer_id' => $this->murugan->id, 'agent_id' => $this->dinesh->id, 'loaned_on' => '2026-10-10', 'first_due_on' => '2026-10-11']);
    $this->muruganLoan->update(['loan_number' => 'L000902']);

    FinanceCollection::factory()->create(['finance_loan_id' => $this->kavithaLoan->id, 'collected_by' => $this->ranjith->id, 'collected_at' => '2026-10-20 09:00:00', 'receipt_number' => 'C000901']);
    FinanceCollection::factory()->create(['finance_loan_id' => $this->muruganLoan->id, 'collected_by' => $this->dinesh->id, 'collected_at' => '2026-10-20 09:30:00', 'receipt_number' => 'C000902']);
});

test('Micro Finance lists and reports show only the chosen agent\'s loans and receipts', function () {
    $agent = ['agent' => $this->ranjith->id];

    $this->get(route('finance.loans.index'))->assertSeeText('L000901')->assertSeeText('L000902');
    $this->get(route('finance.loans.index', $agent))->assertSeeText('L000901')->assertDontSeeText('L000902');

    $this->get(route('finance.collections.index', $agent))->assertSeeText('C000901')->assertDontSeeText('C000902');

    $this->get(route('finance.collect', $agent + ['tab' => 'all']))->assertSeeText('Kavitha')->assertDontSeeText('Murugan');

    foreach (['outstanding', 'overdue', 'day-book'] as $report) {
        $this->get(route('finance.reports.show', ['report' => $report] + $agent))->assertOk()->assertDontSeeText('Murugan');
    }

    $this->get(route('finance.reports.show', ['report' => 'day-book', 'range' => 'month'] + $agent))->assertSeeText('C000901')->assertDontSeeText('C000902');
    $this->get(route('finance.reports.print', ['report' => 'outstanding'] + $agent))->assertOk()->assertSeeText('Agent: Ranjith')->assertDontSeeText('Murugan');
    $this->get(route('finance.reports.pdf', ['report' => 'day-book'] + $agent))->assertOk();

    /* customers under Micro Finance: those with a loan the agent brought in */
    $this->get(route('finance.dashboard'));
    $this->get(route('customers.index', $agent))->assertSeeText('Kavitha')->assertDontSeeText('Murugan');
});

test('Chit Funds pages show only the chosen agent\'s seats and receipts', function () {
    $group = ChitGroup::factory()->running()->create(['start_date' => '2026-10-01', 'installment_amount' => 5000]);
    $kavithaSeat = ChitGroupMember::factory()->create(['chit_group_id' => $group->id, 'customer_id' => $this->kavitha->id, 'agent_id' => $this->ranjith->id]);
    ChitGroupMember::factory()->create(['chit_group_id' => $group->id, 'customer_id' => $this->murugan->id, 'agent_id' => $this->dinesh->id]);

    $mine = Payment::factory()->create(['chit_group_member_id' => $kavithaSeat->id, 'chit_group_id' => $group->id, 'customer_id' => $this->kavitha->id, 'collected_by' => $this->ranjith->id, 'paid_at' => '2026-10-20 09:00:00', 'receipt_number' => 'RC000901']);
    Payment::factory()->create(['chit_group_id' => $group->id, 'collected_by' => $this->dinesh->id, 'paid_at' => '2026-10-20 09:30:00', 'receipt_number' => 'RC000902']);

    $agent = ['agent' => $this->ranjith->id];

    $this->get(route('payments.index', $agent))->assertSeeText($mine->receipt_number)->assertDontSeeText('RC000902');
    $this->get(route('payments.create', $agent))->assertOk()->assertSeeText('Kavitha')->assertDontSeeText('Murugan');
    $this->get(route('reports.dues', $agent))->assertOk()->assertSeeText('Kavitha')->assertDontSeeText('Murugan');
    $this->get(route('reports.dues.print', $agent))->assertOk()->assertSeeText('Ranjith');

    /* customers under Chit Funds: those with a seat the agent brought in */
    $this->get(route('dashboard'));
    $this->get(route('customers.index', $agent))->assertSeeText('Kavitha')->assertDontSeeText('Murugan');
});

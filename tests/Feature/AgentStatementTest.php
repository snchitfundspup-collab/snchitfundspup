<?php

use App\Http\Middleware\SetLanguage;
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
 * Today is 15 Oct 2026. Sathiya is signed in; Ranjith is the agent who
 * brings Kavitha in and collects from her.
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-15 10:00:00'));

    $this->sathiya = User::factory()->create(['name' => 'Sathiya']);
    $this->ranjith = User::factory()->create(['name' => 'Ranjith']);
    $this->customer = Customer::factory()->create(['name' => 'Kavitha', 'remarks' => 'Tailor']);

    $this->actingAs($this->sathiya, 'web');
});

test('a loan and its collections save the agent chosen, or whoever is signed in', function () {
    $this->post(route('finance.loans.store'), [
        'customer_id' => $this->customer->id,
        'loaned_on' => '2026-10-01',
        'principal' => '10,000',
        'processing_fee_rate' => '1',
        'gst_rate' => '18',
        'frequency' => 'daily',
        'installments' => '100',
        'installment_amount' => '108',
        'first_due_on' => '2026-10-02',
        'agent_id' => $this->ranjith->id,
    ])->assertSessionHasNoErrors();

    $loan = FinanceLoan::sole();

    expect($loan->agent_id)->toBe($this->ranjith->id)
        ->and($loan->recorded_by)->toBe($this->sathiya->id);

    $this->post(route('finance.collections.store', $loan), [
        'amount' => '108', 'collected_at' => '2026-10-02T09:00', 'method' => 'cash', 'collected_by' => $this->ranjith->id,
    ])->assertSessionHasNoErrors();

    $this->post(route('finance.collections.store', $loan), [
        'amount' => '108', 'collected_at' => '2026-10-03T09:00', 'method' => 'cash',
    ])->assertSessionHasNoErrors();

    expect(FinanceCollection::orderBy('id')->pluck('collected_by')->all())->toBe([$this->ranjith->id, $this->sathiya->id]);
});

test('the Micro Finance agent statement shows the agent\'s customers, collections by day and by month', function () {
    $loan = FinanceLoan::factory()->create([
        'customer_id' => $this->customer->id, 'loaned_on' => '2026-09-20', 'first_due_on' => '2026-09-21', 'agent_id' => $this->ranjith->id,
    ]);
    $loan->update(['loan_number' => 'L000001']);
    FinanceCollection::factory()->create(['finance_loan_id' => $loan->id, 'collected_at' => '2026-09-21 09:00:00', 'amount' => 300, 'collected_by' => $this->ranjith->id, 'receipt_number' => 'C000001']);
    FinanceCollection::factory()->create(['finance_loan_id' => $loan->id, 'collected_at' => '2026-10-02 09:00:00', 'amount' => 200, 'collected_by' => $this->ranjith->id, 'receipt_number' => 'C000002']);
    FinanceCollection::factory()->create(['finance_loan_id' => $loan->id, 'collected_at' => '2026-10-03 09:00:00', 'amount' => 999, 'collected_by' => $this->sathiya->id, 'receipt_number' => 'C000003']);

    $this->get(route('finance.reports.agent', ['agent' => $this->ranjith->id, 'range' => 'all']))
        ->assertOk()
        ->assertSeeText('Kavitha (Tailor)')
        ->assertSeeText('L000001')
        ->assertSeeText('September 2026')
        ->assertSeeText('October 2026')
        ->assertSeeText('Mon, 21 Sep 2026')
        ->assertSeeText('C000002')
        ->assertSeeText('₹500')
        ->assertDontSeeText('C000003');

    /* this month only: the September loan and receipt drop out */
    $this->get(route('finance.reports.agent', ['agent' => $this->ranjith->id]))
        ->assertOk()
        ->assertSeeText('C000002')
        ->assertDontSeeText('C000001');

    $this->get(route('finance.reports.agent.print', ['agent' => $this->ranjith->id, 'range' => 'all']))->assertOk()->assertSeeText('Ranjith');
    $this->get(route('finance.reports.agent.pdf', ['agent' => $this->ranjith->id, 'range' => 'all']))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->withUnencryptedCookie(SetLanguage::COOKIE, 'ta')
        ->get(route('finance.reports.agent.pdf', ['agent' => $this->ranjith->id, 'range' => 'all']))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('chit seats and payments carry the agent into the chit agent statement', function () {
    $group = ChitGroup::factory()->running()->create(['name' => 'Two Lakh Oct', 'start_date' => '2026-10-01', 'months' => 20, 'installment_amount' => 10000]);
    $forming = ChitGroup::factory()->create(['name' => 'Forming Group']);

    $this->post(route('groups.members.store', $forming), ['customer_id' => $this->customer->id, 'agent_id' => $this->ranjith->id])->assertSessionHasNoErrors();
    expect(ChitGroupMember::sole()->agent_id)->toBe($this->ranjith->id);

    $member = ChitGroupMember::factory()->create(['chit_group_id' => $group->id, 'customer_id' => $this->customer->id, 'agent_id' => $this->ranjith->id]);

    $this->post(route('payments.store'), [
        'chit_group_member_id' => $member->id, 'month_number' => 1, 'amount' => '10,000', 'paid_at' => '2026-10-15T09:30', 'method' => 'cash', 'collected_by' => $this->ranjith->id,
    ])->assertSessionHasNoErrors();

    $payment = Payment::sole();
    expect($payment->collected_by)->toBe($this->ranjith->id)
        ->and($payment->recorded_by)->toBe($this->sathiya->id);

    $this->get(route('reports.agent', ['agent' => $this->ranjith->id]))
        ->assertOk()
        ->assertSeeText('Two Lakh Oct')
        ->assertSeeText('Forming Group')
        ->assertSeeText($payment->receipt_number)
        ->assertSeeText('₹10,000');

    $this->get(route('reports.agent', ['agent' => $this->sathiya->id]))
        ->assertOk()
        ->assertSeeText('Nothing for this agent in this period');

    $this->get(route('reports.agent.pdf', ['agent' => $this->ranjith->id]))->assertOk()->assertHeader('content-type', 'application/pdf');
});

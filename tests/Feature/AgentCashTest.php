<?php

use App\Models\Customer;
use App\Models\FinanceAgentCash;
use App\Models\FinanceCapital;
use App\Models\FinanceCollection;
use App\Models\FinanceLoan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/**
 * Sathiya (owner) recorded Dinesh's opening balance of ₹9,700 at 10:00
 * today (office time). A loan Dinesh gave yesterday does not count; after
 * 10:00 he lends ₹4,900 in hand, collects ₹500, gives Ranjith ₹1,000 and
 * returns ₹200 to the treasury.
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-26 09:00:00', 'Asia/Kolkata'));

    $this->sathiya = User::factory()->create(['name' => 'Sathiya', 'username' => 'sathiya']);
    $this->dinesh = User::factory()->agent()->create(['name' => 'Dinesh', 'username' => 'dinesh']);
    $this->ranjith = User::factory()->agent()->create(['name' => 'Ranjith', 'username' => 'ranjith']);

    $this->kavitha = Customer::factory()->create(['name' => 'Kavitha']);
    $this->murugan = Customer::factory()->create(['name' => 'Murugan']);

    FinanceCapital::factory()->create(['type' => FinanceCapital::INVEST, 'amount' => 100000, 'entry_on' => '2026-11-01']);

    $this->oldLoan = FinanceLoan::factory()->create(['customer_id' => $this->kavitha->id, 'agent_id' => $this->dinesh->id, 'principal' => 3000, 'processing_fee' => 60, 'gst' => 0, 'loan_amount' => 3300]);
    $this->oldLoan->update(['loan_number' => 'L000901']);
    $this->ranjithLoan = FinanceLoan::factory()->create(['customer_id' => $this->murugan->id, 'agent_id' => $this->ranjith->id, 'principal' => 8000, 'processing_fee' => 160, 'gst' => 0, 'loan_amount' => 8800, 'installment_amount' => 800, 'installments' => 11]);
    $this->ranjithLoan->update(['loan_number' => 'L000902']);

    $this->travelTo(Carbon::parse('2026-11-26 10:00:00', 'Asia/Kolkata'));
    FinanceAgentCash::factory()->create(['type' => FinanceAgentCash::OPENING, 'agent_id' => $this->dinesh->id, 'amount' => 9700, 'entry_at' => '2026-11-26 10:00:00']);
    FinanceAgentCash::factory()->create(['type' => FinanceAgentCash::OPENING, 'agent_id' => $this->ranjith->id, 'amount' => 29100, 'entry_at' => '2026-11-26 10:00:00']);

    $this->travelTo(Carbon::parse('2026-11-26 11:00:00', 'Asia/Kolkata'));
    $this->newLoan = FinanceLoan::factory()->create(['customer_id' => $this->kavitha->id, 'agent_id' => $this->dinesh->id, 'principal' => 5000, 'processing_fee' => 100, 'gst' => 0, 'loan_amount' => 5600, 'installment_amount' => 400, 'installments' => 14]);
    $this->newLoan->update(['loan_number' => 'L000903']);
    FinanceCollection::factory()->create(['finance_loan_id' => $this->oldLoan->id, 'amount' => 500, 'collected_by' => $this->dinesh->id, 'receipt_number' => 'C000901']);
    FinanceAgentCash::factory()->create(['type' => FinanceAgentCash::TRANSFER, 'agent_id' => $this->dinesh->id, 'other_agent_id' => $this->ranjith->id, 'amount' => 1000, 'entry_at' => '2026-11-26 11:00:00']);
    FinanceAgentCash::factory()->create(['type' => FinanceAgentCash::TO_TREASURY, 'agent_id' => $this->dinesh->id, 'amount' => 200, 'entry_at' => '2026-11-26 11:00:00']);
});

test('money in hand: opening, then loans given, collections, transfers and returns after it', function () {
    /* 9,700 − 4,900 lent in hand + 500 collected − 1,000 to Ranjith − 200 returned */
    expect(FinanceAgentCash::inHand($this->dinesh))->toBe(4100)
        ->and(FinanceAgentCash::inHand($this->ranjith))->toBe(30100);

    $overview = FinanceAgentCash::overview();

    expect($overview['with_agents'])->toBe(34200)
        ->and($overview['treasury'])->toBe($overview['available'] - 34200);
});

test('the owner records taken / given / agent to agent and sees every agent; giving more than in hand is refused', function () {
    $this->actingAs($this->sathiya, 'web');

    $this->get(route('finance.agent-cash.index'))
        ->assertOk()
        ->assertSeeText('In the treasury')
        ->assertSeeText('Ranjith')
        ->assertSeeText('₹4,100')
        ->assertSeeText('Taken from treasury')
        ->assertSeeText('Given to treasury')
        ->assertSeeText('Agent to agent')
        ->assertDontSee('name="entry_at"', false);

    $this->post(route('finance.agent-cash.store'), ['type' => 'to_treasury', 'agent_id' => $this->dinesh->id, 'amount' => '5,000'])
        ->assertSessionHasErrors('amount');
    $this->post(route('finance.agent-cash.store'), ['type' => 'opening', 'agent_id' => $this->dinesh->id, 'amount' => '5,000'])
        ->assertSessionHasErrors('type');
    $this->post(route('finance.agent-cash.store'), ['type' => 'transfer', 'agent_id' => $this->dinesh->id, 'other_agent_id' => $this->dinesh->id, 'amount' => '100'])
        ->assertSessionHasErrors('other_agent_id');

    $this->post(route('finance.agent-cash.store'), ['type' => 'from_treasury', 'agent_id' => $this->dinesh->id, 'amount' => '2,000'])->assertRedirect();
    $this->post(route('finance.agent-cash.store'), ['type' => 'transfer', 'agent_id' => $this->ranjith->id, 'other_agent_id' => $this->dinesh->id, 'amount' => '500'])->assertRedirect();

    expect(FinanceAgentCash::inHand($this->dinesh))->toBe(6600)
        ->and(FinanceAgentCash::inHand($this->ranjith))->toBe(29600);
});

test('the Agent Balance Sheet lists every rupee in and out of one agent, with the customers', function () {
    $this->actingAs($this->sathiya, 'web');

    $this->get(route('finance.reports.agent-balance', ['agent' => $this->dinesh->id, 'range' => 'today']))
        ->assertOk()
        ->assertViewHas('summary', fn (array $summary) => $summary['opening'] === 0 && $summary['in'] === 10300 && $summary['out'] === 6200 && $summary['closing'] === 4100)
        ->assertSeeText('Loan given L000903')
        ->assertSeeText('Processing fee kept L000903')
        ->assertSeeText('Collected C000901')
        ->assertSeeText('Kavitha')
        ->assertSeeText('Given to Ranjith')
        ->assertSeeText('Given to treasury')
        ->assertDontSeeText('L000901 ·');

    $this->get(route('finance.reports.agent-balance.print', ['agent' => $this->dinesh->id, 'range' => 'today']))->assertOk()->assertSeeText('Dinesh');
    $this->get(route('finance.reports.agent-balance.pdf', ['agent' => $this->dinesh->id, 'range' => 'today']))->assertOk()->assertHeader('content-type', 'application/pdf');
});

test('an agent sees only their own cash book, loans, collections, customers and reports', function () {
    $this->actingAs($this->dinesh, 'web');

    $this->get(route('finance.agent-cash.index'))
        ->assertOk()
        ->assertSeeText('₹4,100')
        ->assertDontSeeText('In the treasury')
        ->assertDontSee('name="agent_id"', false);
    $this->post(route('finance.agent-cash.store'), ['type' => 'from_treasury', 'agent_id' => $this->dinesh->id, 'amount' => '100'])->assertForbidden();
    $this->get(route('finance.reports.agent-balance', ['agent' => $this->ranjith->id]))->assertOk()->assertViewHas('agent', fn ($agent) => $agent->id === $this->dinesh->id);

    $this->get(route('finance.loans.index', ['agent' => $this->ranjith->id]))->assertSeeText('L000903')->assertDontSeeText('L000902');
    $this->get(route('finance.loans.show', $this->ranjithLoan))->assertNotFound();
    $this->get(route('finance.collect', ['tab' => 'all']))->assertDontSeeText('Murugan');
    $this->get(route('finance.reports.show', ['report' => 'outstanding']))->assertDontSeeText('Murugan');
    $this->get(route('finance.accounts.show', $this->murugan))->assertNotFound();

    $this->get(route('finance.capital.index'))->assertForbidden();
    $this->get(route('finance.expenses.index'))->assertForbidden();
    $this->get(route('finance.reports.show', ['report' => 'profit']))->assertForbidden();

    $this->get(route('finance.dashboard'))->assertOk()->assertSeeText('Cash in my hand')->assertSeeText('₹4,100');
    $this->get(route('customers.index'))->assertSeeText('Kavitha')->assertDontSeeText('Murugan');
    $this->get(route('finance.reports.agent', ['agent' => $this->ranjith->id]))->assertOk()->assertViewHas('agent', fn (User $agent) => $agent->is($this->dinesh));
});

test('an agent always records loans and collections under their own name', function () {
    $this->actingAs($this->dinesh, 'web');

    $this->post(route('finance.loans.store'), [
        'customer_id' => $this->kavitha->id, 'loaned_on' => '2026-11-26', 'principal' => '10,000', 'processing_fee_rate' => '2', 'gst_rate' => '0',
        'frequency' => 'weekly', 'installments' => '14', 'installment_amount' => '800', 'first_due_on' => '2026-12-03', 'agent_id' => $this->ranjith->id,
    ])->assertSessionHasNoErrors();

    expect(FinanceLoan::latest('id')->first()->agent_id)->toBe($this->dinesh->id);

    $this->post(route('finance.collections.store', $this->newLoan), ['amount' => '400', 'collected_at' => '2026-11-26T10:55', 'method' => 'cash', 'collected_by' => $this->ranjith->id])
        ->assertSessionHasNoErrors();

    expect(FinanceCollection::latest('id')->first()->collected_by)->toBe($this->dinesh->id);
});

test('the collection amount starts at one instalment, not the whole overdue amount', function () {
    $this->travelTo(Carbon::parse('2026-12-30 10:00:00', 'Asia/Kolkata'));
    $this->actingAs($this->sathiya, 'web');

    $this->ranjithLoan->update(['first_due_on' => '2026-11-27', 'frequency' => FinanceLoan::WEEKLY]);

    expect($this->ranjithLoan->refresh()->standing()['overdue'])->toBeGreaterThan(800);

    $this->get(route('finance.loans.show', $this->ranjithLoan))->assertSeeInOrder(['id="amount" name="amount"', 'value="800"'], false);
});

test('Collect shows the whole overdue; ticking a customer confirms one collection and comes back', function () {
    $this->travelTo(Carbon::parse('2026-12-30 10:00:00', 'Asia/Kolkata'));
    $this->actingAs($this->sathiya, 'web');
    $this->ranjithLoan->update(['first_due_on' => '2026-11-27', 'frequency' => FinanceLoan::WEEKLY]);

    $overdue = $this->ranjithLoan->refresh()->standing()['to_collect'];

    $this->get(route('finance.collect', ['tab' => 'all']))
        ->assertOk()
        ->assertSee('data-quick-collect', false)
        ->assertSee('data-amount="800"', false)
        ->assertSeeText('5 instalments overdue')
        ->assertSeeText('₹'.number_format($overdue));

    $this->from(route('finance.collect'))
        ->post(route('finance.collections.store', $this->ranjithLoan), ['amount' => '800', 'collected_at' => '2026-12-30T09:30', 'method' => 'cash', 'back' => 'collect'])
        ->assertRedirect(route('finance.collect'));

    expect($this->ranjithLoan->collections()->sum('amount'))->toBe(800);
});

test('Profit & Loss shows what the company holds now: with customers, in each agent\'s hand and in the treasury', function () {
    $this->actingAs($this->sathiya, 'web');

    $this->get(route('finance.reports.show', ['report' => 'profit']))
        ->assertOk()
        ->assertSeeText('Company worth now')
        ->assertSeeText('In the hand of')
        ->assertViewHas('worth', function (array $worth) {
            $available = FinanceCapital::position()['available'];

            /* 3,300 − 500 + 8,800 + 5,600 still to collect; Dinesh 4,100 + Ranjith 30,100 */
            return $worth['customers'] === 17200
                && $worth['with_agents'] === 34200
                && $worth['treasury'] === $available - 34200
                && $worth['total'] === 17200 + $available;
        });

    $this->get(route('finance.reports.pdf', ['report' => 'profit']))->assertOk();
});

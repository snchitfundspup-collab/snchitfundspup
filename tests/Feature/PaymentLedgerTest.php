<?php

use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 3, 20)->setTime(10, 0));

    $this->actingAs(User::factory()->create());

    /* started 15 Jan 2026 → months 1–3 due on 20 Mar 2026 */
    $this->group = ChitGroup::factory()->running()->create([
        'name' => 'Ledger Group',
        'start_date' => '2026-01-15',
        'months' => 6,
        'installment_amount' => 10000,
    ]);

    $this->anand = ChitGroupMember::factory()->create([
        'chit_group_id' => $this->group->id,
        'customer_id' => Customer::factory()->create(['name' => 'Anand'])->id,
        'position' => 1,
    ]);

    $this->bharathi = ChitGroupMember::factory()->create([
        'chit_group_id' => $this->group->id,
        'customer_id' => Customer::factory()->create(['name' => 'Bharathi'])->id,
        'position' => 2,
    ]);

    /* Anand: months 1–2 paid, month 3 part paid (4,000) */
    Payment::record($this->anand->load('chitGroup', 'allocations'), ['amount' => 24000, 'paid_at' => '2026-03-20 09:00', 'method' => 'cash']);

    /* Bharathi: month 1 paid only */
    Payment::record($this->bharathi->load('chitGroup', 'allocations'), ['amount' => 10000, 'paid_at' => '2026-03-20 09:00', 'method' => 'cash']);
});

test('guests cannot open the ledger', function () {
    auth()->logout();

    $this->get(route('payments.ledger'))->assertRedirect(route('login'));
});

test('the ledger shows members by month with statuses and totals', function () {
    $response = $this->get(route('payments.ledger', ['group' => $this->group->id]))->assertOk();

    $rows = $response->viewData('rows');

    expect($rows->pluck('member.customer.name')->all())->toBe(['Anand', 'Bharathi'])
        ->and(collect($rows[0]['cells'])->pluck('status')->all())->toBe(['paid', 'paid', 'partial', 'upcoming', 'upcoming', 'upcoming'])
        ->and(collect($rows[1]['cells'])->pluck('status')->all())->toBe(['paid', 'due', 'due', 'upcoming', 'upcoming', 'upcoming'])
        ->and($rows[0]['paid'])->toBe(24000)
        ->and($rows[0]['balance_due'])->toBe(6000)
        ->and($rows[1]['balance_due'])->toBe(20000);

    $response
        ->assertViewHas('monthTotals', fn (array $totals) => $totals[1] === ['paid' => 20000, 'expected' => 20000]
            && $totals[3] === ['paid' => 4000, 'expected' => 20000])
        ->assertViewHas('totalPaid', 34000)
        ->assertViewHas('totalDue', 26000)
        ->assertSee('Ledger Group')
        ->assertSee('4,000')
        ->assertSee('Print ledger');
});

test('the ledger can show a range of months', function () {
    $this->get(route('payments.ledger', ['group' => $this->group->id, 'from' => 2, 'to' => 3]))
        ->assertOk()
        ->assertViewHas('monthNumbers', [2, 3])
        ->assertViewHas('totalPaid', 14000);
});

test('an out of range month range is clamped to the group', function () {
    $this->get(route('payments.ledger', ['group' => $this->group->id, 'from' => 5, 'to' => 99]))
        ->assertOk()
        ->assertViewHas('monthNumbers', [5, 6]);
});

test('only started groups are offered and the first is shown by default', function () {
    ChitGroup::factory()->create(['name' => 'Still Forming']);

    $this->get(route('payments.ledger'))
        ->assertOk()
        ->assertSee('Ledger Group')
        ->assertDontSee('Still Forming');
});

test('the ledger handles having no started groups', function () {
    ChitGroup::query()->update(['status' => ChitGroup::STATUS_FORMING]);

    $this->get(route('payments.ledger'))
        ->assertOk()
        ->assertSee('No started groups yet');
});

test('names are shown with identification on the ledger, receipt and lists', function () {
    $this->anand->customer->update(['remarks' => 'Textile shop']);

    $receipt = Payment::where('chit_group_member_id', $this->anand->id)->firstOrFail();

    $nameWithIdentification = 'Anand<span class="customer-ident"> (Textile shop)</span>';

    $this->get(route('payments.ledger', ['group' => $this->group->id]))->assertSee($nameWithIdentification, false);
    $this->get(route('payments.show', $receipt))->assertSee($nameWithIdentification, false);
    $this->get(route('payments.index'))->assertSee($nameWithIdentification, false);
    $this->get(route('payments.create'))->assertSee($nameWithIdentification, false);
});

test('a name without identification is shown on its own', function () {
    $this->bharathi->customer->update(['remarks' => null]);

    $this->get(route('payments.ledger', ['group' => $this->group->id]))
        ->assertSee('Bharathi</span>', false);
});

test('the ledger downloads as a pdf for the chosen months', function () {
    $response = $this->get(route('payments.ledger.pdf', ['group' => $this->group->id, 'from' => 1, 'to' => 3]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))->toContain('Ledger-ledger-group-M1-3.pdf')
        ->and(substr($response->getContent(), 0, 4))->toBe('%PDF');

    $this->get(route('payments.ledger', ['group' => $this->group->id, 'from' => 2, 'to' => 4]))
        ->assertSee(route('payments.ledger.pdf', ['group' => $this->group->id, 'from' => 2, 'to' => 4]));
});

test('a ledger pdf is not available for a group that has not started', function () {
    $forming = ChitGroup::factory()->create();

    $this->get(route('payments.ledger.pdf', ['group' => $forming->id]))->assertNotFound();
});

test('a receipt downloads as a pdf', function () {
    $payment = Payment::where('chit_group_member_id', $this->anand->id)->firstOrFail();

    $this->get(route('payments.show', $payment))
        ->assertSee(route('payments.receipt.pdf', $payment));

    $response = $this->get(route('payments.receipt.pdf', $payment))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))->toContain("Receipt-{$payment->receipt_number}.pdf")
        ->and(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

test('a running group links to its ledger', function () {
    $this->get(route('groups.show', $this->group))
        ->assertOk()
        ->assertSee(route('payments.ledger', ['group' => $this->group->id]));
});

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

    $this->admin = User::factory()->create(['name' => 'Sathiya']);
    $this->actingAs($this->admin);

    /* running since 15 Jan 2026 → months 1–3 are due on 20 Mar 2026 */
    $this->group = ChitGroup::factory()->running()->create([
        'name' => 'Two Lakh Jan',
        'start_date' => '2026-01-15',
        'months' => 20,
        'installment_amount' => 10000,
    ]);

    $this->customer = Customer::factory()->create(['name' => 'Kumaran Shop', 'phone' => '9876543210']);

    $this->member = ChitGroupMember::factory()->create([
        'chit_group_id' => $this->group->id,
        'customer_id' => $this->customer->id,
        'member_code' => $this->customer->customer_code,
    ]);
});

/**
 * @return array<string, mixed>
 */
function paymentInput(ChitGroupMember $member, array $overrides = []): array
{
    return array_merge([
        'chit_group_member_id' => $member->id,
        'amount' => '10,000',
        'paid_at' => '2026-03-20T09:45',
        'method' => 'cash',
    ], $overrides);
}

function freshMember(ChitGroupMember $member): ChitGroupMember
{
    return ChitGroupMember::with('chitGroup', 'allocations')->find($member->id);
}

test('guests cannot reach payments', function () {
    auth()->logout();

    $this->get(route('payments.create'))->assertRedirect(route('login'));
    $this->get(route('payments.index'))->assertRedirect(route('login'));
});

test('dues count only the months that have fallen due', function () {
    $member = freshMember($this->member);

    expect($member->dueMonthCount())->toBe(3)
        ->and($member->balanceDue())->toBe(30000)
        ->and($member->nextUnpaidMonth())->toBe(1);
});

test('the collect page finds group members and lists their seats with dues', function () {
    Customer::factory()->create(['name' => 'Kumaran Not A Member']);

    $this->get(route('payments.create', ['q' => 'Kumaran']))
        ->assertOk()
        ->assertSee('Kumaran Shop')
        ->assertDontSee('Kumaran Not A Member');

    $this->get(route('payments.create', ['customer' => $this->customer->id]))
        ->assertOk()
        ->assertSee('Two Lakh Jan')
        ->assertSee('₹30,000');

    $this->get(route('payments.create', ['customer' => $this->customer->id, 'member' => $this->member->id]))
        ->assertOk()
        ->assertSee('id="paymentForm"', false)
        ->assertSee('Full month(s)')
        ->assertSee('Partial / daily amount');
});

test('a full payment covers the oldest months and gets a receipt', function () {
    $response = $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '20,000', 'method' => 'upi', 'reference' => 'UPI123']));

    $payment = Payment::firstOrFail();

    $response->assertRedirect(route('payments.show', $payment));

    expect($payment)
        ->receipt_number->toBe('RC'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT))
        ->amount->toBe(20000)
        ->method->toBe('upi')
        ->reference->toBe('UPI123')
        ->recorded_by->toBe($this->admin->id)
        ->and($payment->allocations->pluck('amount', 'month_number')->all())->toBe([1 => 10000, 2 => 10000])
        ->and(freshMember($this->member)->balanceDue())->toBe(10000);
});

test('daily partial payments fill the oldest month bit by bit', function () {
    foreach (range(1, 3) as $day) {
        $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '300']))->assertSessionHasNoErrors();
    }

    $member = freshMember($this->member);

    expect($member->paidByMonth())->toBe([1 => 900])
        ->and($member->ledger()[0]['status'])->toBe('partial')
        ->and($member->ledger()[0]['balance'])->toBe(9100)
        ->and($member->balanceDue())->toBe(29100)
        ->and(Payment::count())->toBe(3);
});

test('a partial payment larger than one month spills into the next month', function () {
    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '4,000']));
    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '15,000']));

    expect(Payment::latest('id')->first()->allocations->pluck('amount', 'month_number')->all())
        ->toBe([1 => 6000, 2 => 9000]);
});

test('advance payments for future months are allowed up to the whole group', function () {
    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '2,00,000']))
        ->assertSessionHasNoErrors();

    expect(freshMember($this->member)->nextUnpaidMonth())->toBeNull();

    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '1']))
        ->assertSessionHasErrors('amount');

    expect(Payment::count())->toBe(1);
});

test('invalid payments are rejected', function (array $overrides, string $errorField) {
    $this->post(route('payments.store'), paymentInput($this->member, $overrides))
        ->assertSessionHasErrors($errorField);

    expect(Payment::count())->toBe(0);
})->with([
    'zero amount' => [['amount' => '0'], 'amount'],
    'unknown method' => [['method' => 'gold'], 'method'],
    'future date' => [['paid_at' => '2026-03-21T09:00'], 'paid_at'],
    'later today in IST' => [['paid_at' => '2026-03-20T23:30'], 'paid_at'],
    'missing date and time' => [['paid_at' => ''], 'paid_at'],
]);

test('the payment keeps the date and time the admin entered', function () {
    $this->post(route('payments.store'), paymentInput($this->member, ['paid_at' => '2026-03-19T18:05']));

    $payment = Payment::firstOrFail();

    expect($payment->paid_at->format('Y-m-d H:i'))->toBe('2026-03-19 18:05');

    $this->get(route('payments.show', $payment))
        ->assertOk()
        ->assertSee('19 Mar 2026, 06:05 PM');
});

test('the collect form defaults the date and time to now in office time', function () {
    /* 10:00 UTC on 20 Mar = 15:30 in India */
    $this->get(route('payments.create', ['customer' => $this->customer->id, 'member' => $this->member->id]))
        ->assertOk()
        ->assertSee('value="2026-03-20T15:30"', false);
});

test('once a month is part paid only partial payments are offered', function () {
    $collectUrl = route('payments.create', ['customer' => $this->customer->id, 'member' => $this->member->id]);

    $this->get($collectUrl)->assertOk()->assertSee('value="full"', false);

    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '300']));

    $this->get($collectUrl)
        ->assertOk()
        ->assertDontSee('value="full"', false)
        ->assertSee('value="partial"', false)
        ->assertSee('is part paid');

    /* completing the month brings the full option back */
    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '9,700']));

    $this->get($collectUrl)->assertOk()->assertSee('value="full"', false);
});

test('the receipt prints the amount in words', function (int $amount, string $words) {
    $payment = Payment::record(freshMember($this->member), [
        'amount' => $amount,
        'paid_at' => '2026-03-20 09:00',
        'method' => 'cash',
    ]);

    expect($payment->amountInWords())->toBe($words);
})->with([
    [300, 'Rupees Three Hundred Only'],
    [10000, 'Rupees Ten Thousand Only'],
    [12345, 'Rupees Twelve Thousand Three Hundred Forty Five Only'],
    [125000, 'Rupees One Lakh Twenty Five Thousand Only'],
    [200000, 'Rupees Two Lakh Only'],
]);

test('the collect list shows only seats that still owe for the current month', function () {
    /* Bala: fully paid up to month 3 → not listed */
    $paidUp = ChitGroupMember::factory()->create([
        'chit_group_id' => $this->group->id,
        'customer_id' => Customer::factory()->create(['name' => 'Bala Paid Up'])->id,
    ]);
    $this->post(route('payments.store'), paymentInput($paidUp, ['amount' => '30,000']));

    /* Chitra: months 1–2 paid, month 3 part paid → Part paid */
    $partial = ChitGroupMember::factory()->create([
        'chit_group_id' => $this->group->id,
        'customer_id' => Customer::factory()->create(['name' => 'Chitra Part'])->id,
    ]);
    $this->post(route('payments.store'), paymentInput($partial, ['amount' => '24,000']));

    /* Deepa: months 1–2 paid, month 3 not yet → Due */
    $due = ChitGroupMember::factory()->create([
        'chit_group_id' => $this->group->id,
        'customer_id' => Customer::factory()->create(['name' => 'Deepa Due'])->id,
    ]);
    $this->post(route('payments.store'), paymentInput($due, ['amount' => '20,000']));

    /* Kumaran (from setup) owes months 1–3 → Pending, listed first */

    /* a member of a group that has not started is never listed */
    ChitGroupMember::factory()->create([
        'chit_group_id' => ChitGroup::factory()->create()->id,
        'customer_id' => Customer::factory()->create(['name' => 'Elan Forming'])->id,
    ]);

    $this->get(route('payments.create'))
        ->assertOk()
        ->assertDontSee('Bala Paid Up')
        ->assertDontSee('Elan Forming')
        ->assertSeeInOrder(['Kumaran Shop', 'Pending', '₹30,000', 'Chitra Part', 'Part paid', '₹6,000', 'Deepa Due', 'Due', '₹10,000'])
        ->assertSee('Two Lakh Jan');

    $this->get(route('payments.create', ['q' => 'Bala']))
        ->assertOk()
        ->assertDontSee('Bala Paid Up')
        ->assertSee('No members with dues match your search.');
});

test('the collect list is paginated 20 per page and keeps the search', function () {
    ChitGroupMember::factory()->count(24)->create([
        'chit_group_id' => $this->group->id,
    ]);

    $this->get(route('payments.create'))
        ->assertOk()
        ->assertViewHas('results', fn ($results) => $results->count() === 20 && $results->total() === 25)
        ->assertSee('1–20');

    $this->get(route('payments.create', ['page' => 2]))
        ->assertOk()
        ->assertViewHas('results', fn ($results) => $results->count() === 5)
        ->assertSee('21–25');

    ChitGroupMember::factory()->count(21)->create([
        'chit_group_id' => ChitGroup::factory()->running()->create(['name' => 'Zebra Group', 'start_date' => '2026-01-15'])->id,
    ]);

    $this->get(route('payments.create', ['q' => 'Zebra']))
        ->assertOk()
        ->assertSee('q=Zebra&amp;page=2', false);
});

test('a collect row opens the form for that seat', function () {
    $this->get(route('payments.create', ['q' => 'Kumaran']))
        ->assertOk()
        ->assertSee(route('payments.create', ['customer' => $this->customer->id, 'member' => $this->member->id]).'#collect');
});

test('the collect search also finds customers by group name', function () {
    $this->get(route('payments.create', ['q' => 'Two Lakh']))
        ->assertOk()
        ->assertSee('Kumaran Shop');
});

test('payments cannot be taken for a group that has not started', function () {
    $formingMember = ChitGroupMember::factory()->create([
        'chit_group_id' => ChitGroup::factory()->create()->id,
    ]);

    $this->post(route('payments.store'), paymentInput($formingMember))
        ->assertSessionHasErrors('chit_group_member_id');

    expect(Payment::count())->toBe(0);
});

test('the receipt shows the payment and the months it covered', function () {
    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '12,000']));

    $payment = Payment::firstOrFail();

    $this->get(route('payments.show', $payment))
        ->assertOk()
        ->assertSee($payment->receipt_number)
        ->assertSee('₹12,000')
        ->assertSee('Kumaran Shop')
        ->assertSee('Two Lakh Jan')
        ->assertSee('Sathiya')
        ->assertSeeInOrder(['Month', '1', '₹10,000', 'Month', '2', '₹2,000']);
});

test('the receipt links back to the members to collect list', function () {
    $this->post(route('payments.store'), paymentInput($this->member));

    $this->get(route('payments.show', Payment::firstOrFail()))
        ->assertOk()
        ->assertSee('href="'.route('payments.create').'"', false)
        ->assertSee('Back to members to collect')
        ->assertSee('15 Jan – 14 Feb 2026');
});

test('a receipt can be cancelled and its months become unpaid again', function () {
    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '10,000']));

    $payment = Payment::firstOrFail();

    $this->delete(route('payments.destroy', $payment))
        ->assertRedirect(route('payments.create', ['customer' => $this->customer->id, 'member' => $this->member->id]))
        ->assertSessionHas('success');

    expect(Payment::count())->toBe(0)
        ->and(freshMember($this->member)->balanceDue())->toBe(30000);
});

test('a member with payments cannot be removed from the group', function () {
    $this->post(route('payments.store'), paymentInput($this->member));

    $this->delete(route('groups.members.destroy', [$this->group, $this->member]))
        ->assertSessionHasErrors('member');

    expect(ChitGroupMember::whereKey($this->member->id)->exists())->toBeTrue();
});

test('all payments lists receipts with today\'s total and filters', function () {
    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '10,000', 'method' => 'cash']));
    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '500', 'method' => 'upi']));

    $this->get(route('payments.index'))
        ->assertOk()
        ->assertSee('₹10,500')
        ->assertSee('Kumaran Shop');

    $this->get(route('payments.index', ['method' => 'upi']))
        ->assertOk()
        ->assertViewHas('payments', fn ($payments) => $payments->count() === 1 && $payments->first()->amount === 500);
});

test('all payments can be searched by group name, customer or receipt number', function () {
    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '1,000']));

    $otherGroup = ChitGroup::factory()->running()->create(['name' => 'Five Lakh Mar', 'start_date' => '2026-01-15']);
    $otherMember = ChitGroupMember::factory()->create([
        'chit_group_id' => $otherGroup->id,
        'customer_id' => Customer::factory()->create(['name' => 'Selvi Other'])->id,
    ]);
    $this->post(route('payments.store'), paymentInput($otherMember, ['amount' => '2,000']));

    $amountsFor = fn (string $search) => $this->get(route('payments.index', ['q' => $search]))
        ->assertOk()
        ->viewData('payments')
        ->pluck('amount')
        ->all();

    expect($amountsFor('Five Lakh'))->toBe([2000])
        ->and($amountsFor('Two Lakh'))->toBe([1000])
        ->and($amountsFor('Selvi'))->toBe([2000])
        ->and($amountsFor(Payment::first()->receipt_number))->toBe([1000]);
});

test('the all payments list lives in a results area that live filtering replaces', function () {
    $this->get(route('payments.index'))
        ->assertOk()
        ->assertSee('id="paymentFilterForm"', false)
        ->assertSee('id="paymentsResults"', false);
});

test('the group page shows each member\'s dues', function () {
    $this->get(route('groups.show', $this->group))
        ->assertOk()
        ->assertSee('₹30,000')
        ->assertSee('due');

    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '30,000']));

    $this->get(route('groups.show', $this->group))
        ->assertOk()
        ->assertSee('Paid up');
});

test('the dashboard shows today\'s collection', function () {
    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '7,500']));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('todayCollection', 7500)
        ->assertViewHas('todayReceipts', 1);
});

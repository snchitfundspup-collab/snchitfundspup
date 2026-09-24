<?php

use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 3, 20)->setTime(10, 0));

    $this->admin = User::factory()->create(['name' => 'Sathiya']);
    $this->actingAs($this->admin);

    /* running since 15 Jan 2026 → months 1–3 are past their due dates (pending) on 20 Mar 2026 */
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
        'month_number' => 1,
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

test('every month past its due date is pending', function () {
    $member = freshMember($this->member);

    expect(collect($member->collectableMonths())->pluck('status', 'month')->all())
        ->toBe([1 => 'pending', 2 => 'pending', 3 => 'pending'])
        ->and($member->balanceDue())->toBe(30000)
        ->and($member->collectionStatus()['state'])->toBe('pending');
});

test('months are collected one at a time: the next month shows once the previous is paid', function () {
    $group = ChitGroup::factory()->running()->create(['start_date' => '2026-09-15', 'months' => 20, 'installment_amount' => 5000]);
    $member = ChitGroupMember::factory()->create(['chit_group_id' => $group->id]);

    $statusOn = fn (string $date) => freshMember($member)->collectionStatus(Carbon::parse($date));

    /* month 1 is due on the start date and pending after it; month 2 is not shown yet */
    expect($statusOn('2026-09-10'))->toMatchArray(['state' => 'due', 'months' => [1], 'amount_due' => 5000])
        ->and($statusOn('2026-09-15'))->toMatchArray(['state' => 'due', 'months' => [1]])
        ->and($statusOn('2026-09-24'))->toMatchArray(['state' => 'pending', 'months' => [1], 'amount_due' => 5000, 'pending' => 5000]);

    /* both due dates passed → both months pending together */
    expect($statusOn('2026-10-20'))->toMatchArray(['state' => 'pending', 'months' => [1, 2], 'amount_due' => 10000]);

    Payment::record(freshMember($member), ['amount' => 5000, 'paid_at' => '2026-09-20 10:00', 'method' => 'cash', 'month_number' => 1]);

    /* month 1 paid on 20 Sep → month 2 (due 15 Oct) shows: upcoming until 30 Sep, due 1 – 15 Oct */
    expect($statusOn('2026-09-24'))->toMatchArray(['state' => 'upcoming', 'months' => [2], 'amount_due' => 0, 'next_balance' => 5000, 'pending' => 0, 'in_due_window' => false])
        ->and(freshMember($member)->balanceDue(Carbon::parse('2026-09-24')))->toBe(0)
        ->and(freshMember($member)->balanceDue(Carbon::parse('2026-10-01')))->toBe(5000)
        ->and($statusOn('2026-10-01'))->toMatchArray(['state' => 'due', 'months' => [2], 'in_due_window' => true])
        ->and($statusOn('2026-10-15'))->toMatchArray(['state' => 'due', 'months' => [2]])
        ->and($statusOn('2026-10-16'))->toMatchArray(['state' => 'pending', 'months' => [2]]);

    Payment::record(freshMember($member), ['amount' => 500, 'paid_at' => '2026-09-24 10:00', 'method' => 'cash', 'month_number' => 2]);

    /* once part paid, month 2 counts as due straight away (Due tab), not upcoming */
    expect($statusOn('2026-09-24'))->toMatchArray(['state' => 'partial', 'months' => [2], 'amount_due' => 4500, 'in_due_window' => true])
        ->and($statusOn('2026-10-16'))->toMatchArray(['state' => 'pending', 'months' => [2], 'amount_due' => 4500])
        ->and(collect(freshMember($member)->ledger(Carbon::parse('2026-09-24')))->pluck('status')->take(3)->all())
        ->toBe(['paid', 'partial', 'upcoming']);
});

test('the collect list shows only pending seats unless searching', function () {
    $this->travelTo(now()->setDate(2026, 9, 24)->setTime(10, 0));

    $group = ChitGroup::factory()->running()->create(['name' => 'Sep Group', 'start_date' => '2026-09-15', 'installment_amount' => 5000]);
    $unpaid = ChitGroupMember::factory()->create(['chit_group_id' => $group->id, 'customer_id' => Customer::factory()->create(['name' => 'Unpaid Uma'])->id]);
    $paid = ChitGroupMember::factory()->create(['chit_group_id' => $group->id, 'customer_id' => Customer::factory()->create(['name' => 'Paid Priya'])->id]);

    Payment::record(freshMember($paid), ['amount' => 5000, 'paid_at' => '2026-09-15 10:00', 'method' => 'cash', 'month_number' => 1]);

    $this->get(route('payments.create'))
        ->assertOk()
        ->assertSee('Unpaid Uma')
        ->assertDontSee('Paid Priya');

    $this->get(route('payments.create', ['q' => 'Sep Group']))
        ->assertOk()
        ->assertSee('Unpaid Uma')
        ->assertSee('Paid Priya')
        ->assertSeeTextInOrder(['Unpaid Uma', 'Pending', 'Paid Priya', 'Month 2', 'Upcoming']);
});

test('the due tab lists seats from the 1st of the month until the due date', function () {
    $this->travelTo(now()->setDate(2026, 10, 5)->setTime(10, 0));

    $group = ChitGroup::factory()->running()->create(['name' => 'Tab Group', 'start_date' => '2026-09-15', 'installment_amount' => 5000]);

    $seat = function (string $name, array $payments) use ($group) {
        $member = ChitGroupMember::factory()->create(['chit_group_id' => $group->id, 'customer_id' => Customer::factory()->create(['name' => $name])->id]);

        foreach ($payments as $month => $amount) {
            Payment::record(freshMember($member), ['amount' => $amount, 'paid_at' => '2026-09-20 10:00', 'method' => 'cash', 'month_number' => $month]);
        }

        return $member;
    };

    /* month 1 unpaid (due 15 Sep) → pending (Kumaran from setup is pending too) */
    $seat('Ravi Pending', []);

    /* month 1 paid, month 2 (due 15 Oct) unpaid → due on 5 Oct */
    $seat('Sita Due', [1 => 5000]);

    /* month 2 part paid → due tab, shown as part paid */
    $seat('Tara Part', [1 => 5000, 2 => 1000]);

    $this->get(route('payments.create'))
        ->assertOk()
        ->assertViewHas('tabCounts', ['pending' => 2, 'due' => 2])
        ->assertSee('Ravi Pending')
        ->assertDontSee('Sita Due');

    $this->get(route('payments.create', ['tab' => 'due']))
        ->assertOk()
        ->assertDontSee('Ravi Pending')
        ->assertSeeTextInOrder(['Sita Due', 'Month 2', 'Due', '₹5,000', 'Tara Part', 'Month 2', 'Part paid', '₹4,000']);

    /* after 15 Oct month 2 is pending: the due tab empties */
    $this->travelTo(now()->setDate(2026, 10, 16)->setTime(10, 0));

    $this->get(route('payments.create', ['tab' => 'due']))
        ->assertViewHas('tabCounts', ['pending' => 4, 'due' => 0])
        ->assertSee('No members are due right now.');
});

test('month 1 is taken in full only, later months can be partial but never more than the balance', function () {
    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '4,000', 'month_number' => 1]))
        ->assertSessionHasErrors(['amount' => 'Month 1 must be paid in full (₹10,000).']);

    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '12,000', 'month_number' => 1]))
        ->assertSessionHasErrors('amount');

    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '4,000', 'month_number' => 2]))
        ->assertSessionHasNoErrors();

    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '6,001', 'month_number' => 2]))
        ->assertSessionHasErrors('amount');

    /* month 4 is not due yet and months 1–3 are unpaid → cannot be chosen */
    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '10,000', 'month_number' => 4]))
        ->assertSessionHasErrors('month_number');

    expect(freshMember($this->member)->paidByMonth())->toBe([2 => 4000]);
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
        ->assertSee('Which month is this for?')
        ->assertSee('Full month')
        ->assertSee('Partial / daily amount');
});

test('a full month payment goes to the chosen month and gets a receipt', function () {
    $response = $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '10,000', 'method' => 'upi', 'reference' => 'UPI123']));

    $payment = Payment::firstOrFail();

    $response->assertRedirect(route('payments.show', $payment));

    expect($payment)
        ->receipt_number->toBe('RC'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT))
        ->amount->toBe(10000)
        ->method->toBe('upi')
        ->reference->toBe('UPI123')
        ->recorded_by->toBe($this->admin->id)
        ->and($payment->allocations->pluck('amount', 'month_number')->all())->toBe([1 => 10000])
        ->and(freshMember($this->member)->balanceDue())->toBe(20000);
});

test('daily partial payments build up a month bit by bit', function () {
    foreach (range(1, 3) as $day) {
        $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '300', 'month_number' => 2]))->assertSessionHasNoErrors();
    }

    $member = freshMember($this->member);

    expect($member->paidByMonth())->toBe([2 => 900])
        ->and($member->ledger()[1]['status'])->toBe('partial')
        ->and($member->ledger()[1]['balance'])->toBe(9100)
        ->and($member->balanceDue())->toBe(29100)
        ->and(Payment::count())->toBe(3);
});

test('a payment only ever goes to the chosen month', function () {
    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '4,000', 'month_number' => 3]));

    expect(Payment::sole()->allocations->pluck('amount', 'month_number')->all())->toBe([3 => 4000]);
});

test('the next month can be paid ahead only once the earlier months are paid', function () {
    foreach ([1, 2, 3] as $month) {
        $this->post(route('payments.store'), paymentInput($this->member, ['month_number' => $month]))->assertSessionHasNoErrors();
    }

    /* month 4 (due 15 Apr) is now shown as due; month 5 is not */
    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '10,000', 'month_number' => 5]))
        ->assertSessionHasErrors('month_number');

    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '2,500', 'month_number' => 4]))
        ->assertSessionHasNoErrors();

    expect(freshMember($this->member)->paidByMonth())->toBe([1 => 10000, 2 => 10000, 3 => 10000, 4 => 2500]);
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

test('month 1 offers only the full amount and a part-paid month only partial', function () {
    $collectUrl = route('payments.create', ['customer' => $this->customer->id, 'member' => $this->member->id]);

    $isHidden = fn (string $html, string $id) => (bool) preg_match('/id="'.$id.'"\s+hidden/', $html);

    /* month 1 is selected first: full only */
    $html = $this->get($collectUrl)->assertOk()->assertSee('Month 1 is paid in full')->getContent();

    expect($isHidden($html, 'payModePartial'))->toBeTrue()
        ->and($isHidden($html, 'payModeFull'))->toBeFalse();

    $this->post(route('payments.store'), paymentInput($this->member));
    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '300', 'month_number' => 2]));

    /* month 2 is now the oldest and part paid: partial only */
    $html = $this->get($collectUrl)->assertOk()->getContent();

    expect($isHidden($html, 'payModeFull'))->toBeTrue()
        ->and($isHidden($html, 'payModePartial'))->toBeFalse()
        ->and($isHidden($html, 'partialOnlyNote'))->toBeFalse();
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

test('the collect list shows pending seats with their months and amounts', function () {
    $seat = function (string $name, int $paid) {
        $member = ChitGroupMember::factory()->create([
            'chit_group_id' => $this->group->id,
            'customer_id' => Customer::factory()->create(['name' => $name])->id,
        ]);

        if ($paid > 0) {
            Payment::record(freshMember($member), ['amount' => $paid, 'paid_at' => '2026-03-01 10:00', 'method' => 'cash']);
        }

        return $member;
    };

    /* Bala: months 1–3 paid → month 4 not due until 15 Apr → not pending */
    $seat('Bala Paid Up', 30000);

    /* Chitra: month 3 part paid (past due) → pending ₹6,000 */
    $seat('Chitra Part', 24000);

    /* Deepa: month 3 unpaid (past due) → pending ₹10,000 */
    $seat('Deepa Behind', 20000);

    /* Kumaran (from setup) owes months 1–3 → pending ₹30,000 */

    /* a member of a group that has not started is never listed */
    ChitGroupMember::factory()->create([
        'chit_group_id' => ChitGroup::factory()->create()->id,
        'customer_id' => Customer::factory()->create(['name' => 'Elan Forming'])->id,
    ]);

    $this->get(route('payments.create'))
        ->assertOk()
        ->assertDontSee('Bala Paid Up')
        ->assertDontSee('Elan Forming')
        ->assertSeeTextInOrder(['Chitra Part', 'Month 3', 'Pending', '₹6,000', 'Deepa Behind', 'Month 3', 'Pending', '₹10,000', 'Kumaran Shop', 'Months 1, 2, 3', 'Pending', '₹30,000'])
        ->assertSee('Two Lakh Jan');

    $this->get(route('payments.create', ['q' => 'Bala']))
        ->assertOk()
        ->assertSeeTextInOrder(['Bala Paid Up', 'Month 4', 'Upcoming', '₹10,000']);
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

test('a collect row opens the form for that seat and has a call button', function () {
    $this->get(route('payments.create', ['q' => 'Kumaran']))
        ->assertOk()
        ->assertSee(route('payments.create', ['customer' => $this->customer->id, 'member' => $this->member->id]).'#collect')
        ->assertSee('href="tel:9876543210"', false);
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
    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '2,000', 'month_number' => 2]));

    $payment = Payment::firstOrFail();

    $this->get(route('payments.show', $payment))
        ->assertOk()
        ->assertSee($payment->receipt_number)
        ->assertSee('₹2,000')
        ->assertSee('Kumaran Shop')
        ->assertSee('Two Lakh Jan')
        ->assertSee('Sathiya')
        ->assertSeeInOrder(['Month', '2', '₹2,000']);
});

test('the receipt links back to the members to collect list', function () {
    $this->post(route('payments.store'), paymentInput($this->member));

    $this->get(route('payments.show', Payment::firstOrFail()))
        ->assertOk()
        ->assertSee('href="'.route('payments.create').'"', false)
        ->assertSee('Next member')
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
    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '500', 'method' => 'upi', 'month_number' => 2]));

    $this->get(route('payments.index'))
        ->assertOk()
        ->assertSee('₹10,500')
        ->assertSee('Kumaran Shop');

    $this->get(route('payments.index', ['method' => 'upi']))
        ->assertOk()
        ->assertViewHas('payments', fn ($payments) => $payments->count() === 1 && $payments->first()->amount === 500);
});

test('all payments can be searched by group name, customer or receipt number', function () {
    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '1,000', 'month_number' => 2]));

    $otherGroup = ChitGroup::factory()->running()->create(['name' => 'Five Lakh Mar', 'start_date' => '2026-01-15']);
    $otherMember = ChitGroupMember::factory()->create([
        'chit_group_id' => $otherGroup->id,
        'customer_id' => Customer::factory()->create(['name' => 'Selvi Other'])->id,
    ]);
    $this->post(route('payments.store'), paymentInput($otherMember, ['amount' => '2,000', 'month_number' => 2]));

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
        ->assertSee('pending');

    foreach ([1, 2, 3] as $month) {
        $this->post(route('payments.store'), paymentInput($this->member, ['month_number' => $month]));
    }

    $this->get(route('groups.show', $this->group))
        ->assertOk()
        ->assertSee('Paid up');
});

test('the dashboard shows today\'s collection', function () {
    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '7,500', 'month_number' => 2]));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('todayCollection', 7500)
        ->assertViewHas('todayReceipts', 1);
});

test('each collect row has a quick collect button with the months to collect', function () {
    $html = $this->get(route('payments.create'))->assertOk()->getContent();

    expect($html)->toContain('data-quick-collect')
        ->toContain('data-member="'.$this->member->id.'"')
        ->toContain('id="quickCollectModal"');

    preg_match('/data-member="'.$this->member->id.'"[^>]*data-months="([^"]+)"/s', $html, $match);

    expect(collect(json_decode(html_entity_decode($match[1]), true))->pluck('month')->all())->toBe([1, 2, 3]);
});

test('a quick collect payment is saved like the full form', function () {
    $this->post(route('payments.store'), [
        'chit_group_member_id' => $this->member->id,
        'month_number' => 1,
        'amount' => '10,000',
        'method' => 'cash',
        'paid_at' => '2026-03-20T15:25',
    ])->assertRedirect(route('payments.show', Payment::sole()));

    expect(freshMember($this->member)->paidByMonth())->toBe([1 => 10000]);
});

test('a customer with one seat goes straight to the payment form', function () {
    $this->get(route('payments.create', ['customer' => $this->customer->id]))
        ->assertOk()
        ->assertSee('id="paymentForm"', false)
        ->assertDontSee('Choose the seat')
        ->assertSee('<span class="step-number">2</span>', false);

    ChitGroupMember::factory()->create(['chit_group_id' => $this->group->id, 'customer_id' => $this->customer->id, 'member_code' => $this->customer->customer_code.'-2']);

    $this->get(route('payments.create', ['customer' => $this->customer->id]))
        ->assertOk()
        ->assertSee('Choose the seat')
        ->assertDontSee('id="paymentForm"', false);
});

test('the receipt offers the next member on the tab the admin came from', function () {
    $this->get(route('payments.create', ['tab' => 'due']));
    $this->post(route('payments.store'), paymentInput($this->member));

    $this->get(route('payments.show', Payment::sole()))
        ->assertSee('Next member')
        ->assertSee(route('payments.create', ['tab' => 'due']));
});

test('date, time and notes are folded under more details', function () {
    $this->get(route('payments.create', ['customer' => $this->customer->id, 'member' => $this->member->id]))
        ->assertOk()
        ->assertSeeInOrder(['class="payment-more"', 'More details', 'id="paid_at"', 'id="notes"'], false);
});

test('member cards show how far the member has paid', function () {
    $this->get(route('groups.show', $this->group))->assertSee('Nothing paid yet');

    $this->post(route('payments.store'), paymentInput($this->member));
    $this->post(route('payments.store'), paymentInput($this->member, ['amount' => '2,000', 'month_number' => 2]));

    $this->get(route('groups.show', $this->group))
        ->assertSeeTextInOrder(['Paid up to', 'Month', '1', 'Month', '2', 'part paid', '₹2,000']);
});

/**
 * Today is 20 Mar 2026 (see beforeEach). Records payments on several days.
 *
 * @return array<string, Payment>
 */
function paymentsOnSeveralDays(ChitGroupMember $member, ChitGroupMember $other): array
{
    return [
        'today_cash' => Payment::record(freshMember($member), ['amount' => 10000, 'month_number' => 1, 'paid_at' => '2026-03-20 09:00', 'method' => 'cash']),
        'today_upi' => Payment::record(freshMember($member), ['amount' => 500, 'month_number' => 2, 'paid_at' => '2026-03-20 11:00', 'method' => 'upi']),
        'yesterday' => Payment::record(freshMember($member), ['amount' => 700, 'month_number' => 2, 'paid_at' => '2026-03-19 16:00', 'method' => 'cash']),
        'last_month' => Payment::record(freshMember($other), ['amount' => 10000, 'month_number' => 1, 'paid_at' => '2026-02-10 10:00', 'method' => 'cash']),
    ];
}

test('all payments shows only today by default with totals by method', function () {
    $other = ChitGroupMember::factory()->create(['chit_group_id' => ChitGroup::factory()->running()->create(['name' => 'Other Group', 'start_date' => '2026-01-15'])->id]);
    paymentsOnSeveralDays($this->member, $other);

    $response = $this->get(route('payments.index'))
        ->assertOk()
        ->assertViewHas('payments', fn ($payments) => $payments->pluck('amount')->all() === [500, 10000])
        ->assertViewHas('summary', [
            'total' => 10500,
            'count' => 2,
            'by_method' => ['cash' => ['amount' => 10000, 'count' => 1], 'upi' => ['amount' => 500, 'count' => 1]],
        ])
        ->assertViewHas('filters', fn ($filters) => $filters['range'] === 'today' && $filters['from'] === '2026-03-20')
        ->assertSee('Fri, 20 Mar 2026');

    /* "Back to today" is hidden when already on today */
    expect(preg_match('/id="paymentFilterClear"\s+hidden/', $response->getContent()))->toBe(1);
});

test('earlier payments are shown only when a range or dates are chosen', function () {
    $other = ChitGroupMember::factory()->create(['chit_group_id' => ChitGroup::factory()->running()->create(['name' => 'Other Group', 'start_date' => '2026-01-15'])->id]);
    paymentsOnSeveralDays($this->member, $other);

    $amounts = fn (array $query) => $this->get(route('payments.index', $query))->viewData('payments')->pluck('amount')->sort()->values()->all();

    expect($amounts(['range' => 'yesterday']))->toBe([700])
        ->and($amounts(['range' => 'month']))->toBe([500, 700, 10000])
        ->and($amounts(['range' => 'last_month']))->toBe([10000])
        ->and($amounts(['from' => '2026-02-01', 'to' => '2026-03-19']))->toBe([700, 10000])
        /* dates the wrong way round are swapped */
        ->and($amounts(['from' => '2026-03-20', 'to' => '2026-03-19']))->toBe([500, 700, 10000]);

    $this->get(route('payments.index', ['range' => 'yesterday']))->assertSee('Back to today');
});

test('all payments filters by group and method within the dates', function () {
    $otherGroup = ChitGroup::factory()->running()->create(['name' => 'Other Group', 'start_date' => '2026-01-15']);
    $other = ChitGroupMember::factory()->create(['chit_group_id' => $otherGroup->id]);
    paymentsOnSeveralDays($this->member, $other);
    Payment::record(freshMember($other), ['amount' => 3000, 'month_number' => 2, 'paid_at' => '2026-03-20 12:00', 'method' => 'cash']);

    $this->get(route('payments.index', ['group' => $otherGroup->id]))
        ->assertViewHas('payments', fn ($payments) => $payments->pluck('amount')->all() === [3000])
        ->assertSee('Other Group');

    $this->get(route('payments.index', ['method' => 'upi', 'range' => 'month']))
        ->assertViewHas('summary', fn ($summary) => $summary['total'] === 500 && $summary['count'] === 1);
});

test('an exact receipt number is found whatever the dates', function () {
    $other = ChitGroupMember::factory()->create(['chit_group_id' => ChitGroup::factory()->running()->create(['start_date' => '2026-01-15'])->id]);
    $payments = paymentsOnSeveralDays($this->member, $other);

    $this->get(route('payments.index', ['q' => strtolower($payments['last_month']->receipt_number)]))
        ->assertOk()
        ->assertViewHas('payments', fn ($found) => $found->pluck('id')->all() === [$payments['last_month']->id])
        ->assertSeeText('Receipt '.$payments['last_month']->receipt_number);
});

test('the filtered payment list prints and downloads as a pdf', function () {
    $other = ChitGroupMember::factory()->create(['chit_group_id' => ChitGroup::factory()->running()->create(['start_date' => '2026-01-15'])->id]);
    paymentsOnSeveralDays($this->member, $other);

    $this->get(route('payments.index', ['range' => 'month']))
        ->assertSee(route('payments.print', ['from' => '2026-03-01', 'to' => '2026-03-20']))
        ->assertSee(route('payments.pdf', ['from' => '2026-03-01', 'to' => '2026-03-20']));

    $this->get(route('payments.print', ['from' => '2026-03-01', 'to' => '2026-03-20']))
        ->assertOk()
        ->assertSeeTextInOrder(['01 Mar 2026 – 20 Mar 2026', 'Total collected', '₹11,200', '19 Mar 2026', '20 Mar 2026', 'Total (3 receipts)', '₹11,200'])
        ->assertSee('window.print()', false);

    $response = $this->get(route('payments.pdf', ['from' => '2026-03-01', 'to' => '2026-03-20']))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))->toContain('Payments-2026-03-01-to-2026-03-20.pdf');
});

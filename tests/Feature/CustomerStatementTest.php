<?php

use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use App\Models\Customer;
use App\Models\Draw;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 10, 20)->setTime(10, 0));

    $this->actingAs(User::factory()->create());

    /* started 15 Sep → month 2 due 15 Oct is pending on 20 Oct */
    $this->group = ChitGroup::factory()->running()->create([
        'name' => 'Statement Group',
        'start_date' => '2026-09-15',
        'installment_amount' => 5000,
    ]);

    $this->customer = Customer::factory()->create(['name' => 'Murugan Stores', 'phone' => '9000011111', 'remarks' => 'Fruit shop']);

    $this->member = ChitGroupMember::factory()->create([
        'chit_group_id' => $this->group->id,
        'customer_id' => $this->customer->id,
    ]);

    $pay = fn (int $amount, int $month, string $paidAt) => Payment::record(
        ChitGroupMember::with('chitGroup', 'allocations')->find($this->member->id),
        ['amount' => $amount, 'month_number' => $month, 'paid_at' => $paidAt, 'method' => 'cash'],
    );

    $pay(5000, 1, '2026-09-15 10:00');
    $pay(300, 2, '2026-09-17 11:30');
    $pay(200, 2, '2026-09-16 09:15');
});

test('guests cannot open the customer statement', function () {
    auth()->logout();

    $this->get(route('reports.customer'))->assertRedirect(route('login'));
});

test('typing a name finds customers who are in a group', function () {
    Customer::factory()->create(['name' => 'Murugan Not In Group']);

    $this->get(route('reports.customer'))
        ->assertOk()
        ->assertSee('Start typing a name');

    $this->get(route('reports.customer', ['q' => 'Murugan']))
        ->assertOk()
        ->assertSee('Murugan Stores')
        ->assertDontSee('Murugan Not In Group')
        ->assertSee(route('reports.customer', ['customer' => $this->customer->id]));
});

test('the statement lists each payment of a month in date order with totals', function () {
    $response = $this->get(route('reports.customer', ['customer' => $this->customer->id]))->assertOk();

    $months = $response->viewData('seats')->first()['months'];

    expect(collect($months)->pluck('month')->all())->toBe([1, 2])
        ->and(collect($months[1]['entries'])->pluck('amount')->all())->toBe([200, 300])
        ->and($months[1]['paid'])->toBe(500)
        ->and($months[1]['balance'])->toBe(4500)
        ->and($months[1]['status'])->toBe('partial');

    $response
        ->assertSee('Murugan Stores')
        ->assertSee('Fruit shop')
        ->assertSee('9000011111')
        ->assertSee('href="tel:9000011111"', false)
        ->assertSeeTextInOrder(['Month 1', '15 Sep 2026, 10:00 AM', '₹5,000', 'Month 2', '16 Sep 2026, 09:15 AM', '₹200', '17 Sep 2026, 11:30 AM', '₹300', 'Balance', '₹4,500'])
        ->assertSee('Print statement')
        ->assertSee(route('reports.customer.pdf', $this->customer));
});

test('the statement downloads as a pdf', function () {
    $response = $this->get(route('reports.customer.pdf', $this->customer))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))->toContain('Statement-'.$this->customer->customer_code.'-murugan-stores.pdf')
        ->and(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

test('the reports menu links to the customer statement', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('id="reportsSubmenu"', false)
        ->assertSee(route('reports.customer'));
});

test('the statement shows the prize the customer won and its payout', function () {
    $draw = Draw::create([
        'chit_group_id' => $this->group->id,
        'month_number' => 1,
        'winner_member_id' => $this->member->id,
        'withdrawal_amount' => 85000,
        'drawn_at' => '2026-09-15 18:00',
    ]);

    $this->get(route('reports.customer', ['customer' => $this->customer->id]))
        ->assertOk()
        /* the prize has its own box under the group's table */
        ->assertSeeTextInOrder(['Prizes won', '₹85,000', 'Month 1', 'Month 2', 'Total paid', 'Prize won', 'Month 1', 'Drawn on 15 Sep 2026, 06:00 PM', 'Awaiting payout', '₹85,000'])
        ->assertSee('class="statement-prize"', false);

    $draw->update(['payout_amount' => 85000, 'payout_method' => 'upi', 'payout_reference' => 'UPI777', 'paid_at' => '2026-09-16 11:00', 'voucher_number' => 'PV000009']);

    $this->get(route('reports.customer', ['customer' => $this->customer->id]))
        ->assertSeeTextInOrder(['Paid out', '16 Sep 2026, 11:00 AM', 'UPI (UPI777)', 'Voucher No.', 'PV000009']);

    $this->get(route('reports.customer.pdf', $this->customer))->assertOk();
});

test('a customer who has not won shows no prize section', function () {
    $this->get(route('reports.customer', ['customer' => $this->customer->id]))
        ->assertDontSee('Prizes won')
        ->assertDontSee('class="statement-prize"', false);
});

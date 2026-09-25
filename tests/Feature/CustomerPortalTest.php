<?php

use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use App\Models\ChitJoinRequest;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\RiceVariety;
use App\Models\Sale;
use App\Models\TraderOrder;
use App\Models\UsageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

/**
 * Today is 20 Oct 2026. Lakshmi (phone "+91 98765 43210", own password
 * "lakshmi123") has a seat in a running group that started 15 Sep: month 1
 * paid, month 2 (due 15 Oct) part paid and now Pending. Another group is
 * forming.
 */
beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 10, 20)->setTime(10, 0));

    $this->customer = Customer::factory()->create(['name' => 'Lakshmi', 'phone' => '+91 98765 43210', 'remarks' => 'Teacher']);
    $this->customer->choosePassword('lakshmi123');
    $this->other = Customer::factory()->create(['name' => 'Kumar', 'phone' => '9000000002']);

    $this->running = ChitGroup::factory()->running()->withPayouts(95000, 1000)->create([
        'name' => 'Diwali Group', 'start_date' => '2026-09-15', 'installment_amount' => 5000, 'months' => 20, 'member_count' => 20,
    ]);
    $this->seat = ChitGroupMember::factory()->create(['chit_group_id' => $this->running->id, 'customer_id' => $this->customer->id]);
    $this->otherSeat = ChitGroupMember::factory()->create(['chit_group_id' => $this->running->id, 'customer_id' => $this->other->id]);

    $pay = fn (ChitGroupMember $member, int $amount, int $month) => Payment::record(
        ChitGroupMember::with('chitGroup', 'allocations')->find($member->id),
        ['amount' => $amount, 'month_number' => $month, 'paid_at' => '2026-09-20 10:00', 'method' => 'cash'],
    );
    $this->receipt = $pay($this->seat, 5000, 1);
    $pay($this->seat, 2000, 2);
    $this->otherReceipt = $pay($this->otherSeat, 5000, 1);

    $this->forming = ChitGroup::factory()->withPayouts(190000, 2000)->create([
        'name' => 'Pongal Group', 'start_date' => '2026-11-15', 'member_count' => 2, 'months' => 2, 'installment_amount' => 100000, 'amount' => 200000,
    ]);

    $this->admin = User::factory()->create(['name' => 'Narayanan']);
});

function signIn(string $phone = '9876543210', string $password = 'lakshmi123'): TestResponse
{
    return test()->post(route('portal.login.store'), ['phone' => $phone, 'password' => $password]);
}

test('a customer signs in with their phone number and their password', function () {
    signIn()->assertRedirect(route('portal.dashboard'));

    $this->assertAuthenticatedAs($this->customer, 'customer');
    $this->assertGuest('web');

    expect($this->customer->refresh()->last_login_at)->not->toBeNull()
        ->and(UsageLog::where('customer_id', $this->customer->id)->where('event', 'login')->exists())->toBeTrue();

    $this->get(route('portal.dashboard'))
        ->assertOk()
        ->assertSeeText('Lakshmi')
        ->assertSeeText('Diwali Group')
        ->assertSeeText('Pongal Group')
        ->assertSee('tel:9842510159', false)
        ->assertViewHas('seats', fn ($seats) => $seats->first()['months_done'] === 2 && $seats->first()['months_left'] === 18)
        ->assertSeeTextInOrder(['Diwali Group', 'Months completed', '2 / 20', 'Months remaining', '18'])
        ->assertSeeTextInOrder(['Next due', 'Month 2', '15 Oct 2026'])
        ->assertDontSeeText('15 Oct – 14 Nov')
        ->assertDontSeeText('Chit — to pay now')
        ->assertSeeTextInOrder(['SN Traders', 'Rice — balance', 'My Orders']);
});

test('signed in with the default password, a customer must choose their own before anything else', function () {
    $this->customer->resetPassword();

    signIn(password: 'snchitfunds')->assertRedirect(route('portal.password.edit'));

    $this->get(route('portal.dashboard'))->assertRedirect(route('portal.password.edit'));
    $this->get(route('portal.groups.show', $this->seat))->assertRedirect(route('portal.password.edit'));
    $this->post(route('portal.orders.store'), ['bags' => [1 => 2]])->assertRedirect(route('portal.password.edit'));

    $this->get(route('portal.password.edit'))
        ->assertOk()
        ->assertSeeText('Please choose your own password to continue.')
        ->assertDontSee('name="current_password"', false);

    /* the default password cannot be chosen again */
    $this->put(route('portal.password.update'), ['password' => 'snchitfunds', 'password_confirmation' => 'snchitfunds'])
        ->assertSessionHasErrors('password');

    $this->put(route('portal.password.update'), ['password' => 'newsecret1', 'password_confirmation' => 'newsecret1'])
        ->assertRedirect(route('portal.dashboard'));

    expect($this->customer->refresh()->mustChangePassword())->toBeFalse();

    $this->get(route('portal.dashboard'))->assertOk();
});

test('the login page tells customers to call the office when they forget their password', function () {
    $this->get(route('portal.login'))
        ->assertOk()
        ->assertSeeText('Forgot your password? Call the office to reset it:')
        ->assertSee('tel:9842510159', false);
});

test('staff and customers have separate login pages without links to each other', function () {
    expect(route('login'))->toEndWith('/login/admin')
        ->and(route('portal.login'))->toEndWith('/login/customer');

    $this->get('/login')->assertRedirect('/login/admin');

    $this->get(route('login'))->assertOk()->assertDontSee(route('portal.login'));
    $this->get(route('portal.login'))->assertOk()->assertSeeText('Phone number')->assertDontSee(route('login'));
});

test('wrong passwords, unknown phones and inactive customers cannot sign in', function () {
    signIn(password: 'wrong')->assertSessionHasErrors('phone');
    signIn(phone: '9999999999')->assertSessionHasErrors('phone');

    $this->customer->update(['is_active' => false]);
    signIn()->assertSessionHasErrors('phone');

    $this->assertGuest('customer');
});

test('customers sharing a phone choose whose account to open', function () {
    $sister = Customer::factory()->create(['name' => 'Meena', 'phone' => '98765-43210']);
    $this->customer->resetPassword();

    signIn(password: 'snchitfunds')->assertRedirect(route('portal.choose'));

    $this->get(route('portal.choose'))->assertOk()->assertSeeText('Lakshmi')->assertSeeText('Meena');

    /* only one of the offered accounts can be picked */
    $this->post(route('portal.choose.store'), ['customer' => $this->other->id])->assertRedirect(route('portal.login'));
    $this->assertGuest('customer');

    signIn(password: 'snchitfunds');
    $this->post(route('portal.choose.store'), ['customer' => $sister->id])->assertRedirect(route('portal.password.edit'));
    $this->assertAuthenticatedAs($sister, 'customer');
});

test('customer pages need a customer sign-in, and a customer cannot open office pages', function () {
    $this->get(route('portal.groups'))->assertRedirect(route('portal.login'));

    $this->actingAs($this->admin, 'web')->get(route('portal.groups'))->assertRedirect(route('portal.login'));

    auth()->logout();
    $this->actingAs($this->customer, 'customer')->get(route('customers.index'))->assertRedirect(route('login'));
});

test('the office resets a forgotten password or sets one, and the customer must replace it', function () {
    expect(Customer::forLogin('9876543210', 'snchitfunds'))->toBeEmpty()
        ->and(Customer::forLogin('9876543210', 'lakshmi123')->first()?->is($this->customer))->toBeTrue();

    $this->actingAs($this->admin, 'web')->get(route('customers.index'))->assertSee('data-customer-password-state="own"', false);

    /* forgot it → the office resets it to the default */
    $this->actingAs($this->admin, 'web')
        ->deleteJson(route('customers.password.reset', $this->customer))
        ->assertOk()
        ->assertJson(['password_state' => 'default']);

    expect($this->customer->refresh()->usesDefaultPassword())->toBeTrue()
        ->and($this->customer->mustChangePassword())->toBeTrue();

    /* or the office types one in over the phone: it works once, then must be replaced */
    $this->actingAs($this->admin, 'web')
        ->putJson(route('customers.update', $this->customer), ['name' => 'Lakshmi', 'phone' => '+91 98765 43210', 'is_active' => true, 'password' => 'office99'])
        ->assertOk()
        ->assertJson(['password_state' => 'office']);

    expect($this->customer->refresh()->passwordMatches('office99'))->toBeTrue()
        ->and($this->customer->mustChangePassword())->toBeTrue();

    auth('web')->logout();
    signIn(password: 'office99')->assertRedirect(route('portal.password.edit'));

    $this->put(route('portal.password.update'), ['password' => 'mine2026', 'password_confirmation' => 'mine2026'])
        ->assertRedirect(route('portal.dashboard'));

    expect($this->customer->refresh()->passwordState())->toBe('own');
});

test('a customer sees their own group month by month, receipts and withdrawal plan only', function () {
    $this->actingAs($this->customer, 'customer');

    $this->get(route('portal.groups.show', $this->seat))
        ->assertOk()
        ->assertSeeTextInOrder(['Monthly payments', '1', 'Paid', '2', '₹3,000', 'Pending', '3', 'Upcoming'])
        ->assertSeeText($this->receipt->receipt_number)
        ->assertSeeTextInOrder(['Withdrawal plan', '₹95,000'])
        ->assertDontSeeText('Kumar');

    $this->get(route('portal.groups.show', $this->otherSeat))->assertNotFound();

    $this->get(route('portal.receipts.pdf', $this->receipt))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->get(route('portal.receipts.pdf', $this->otherReceipt))->assertNotFound();

    $this->get(route('portal.statement.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');
});

test('the group page shows key details and a withdrawal plan by date, with months already withdrawn marked done', function () {
    $response = $this->actingAs($this->customer, 'customer')->get(route('portal.groups.show', $this->seat))->assertOk();

    /* month 1 paid; month 2 (due 15 Oct) part paid and pending */
    expect($response->viewData('monthsPaid'))->toBe(1)
        ->and($response->viewData('monthsPending'))->toBe(1);

    $plan = $response->viewData('plan');

    /* 20 Oct: the 15 Sep and 15 Oct withdrawals are over, 15 Nov is not */
    expect($plan[0]['date']->toDateString())->toBe('2026-09-15')
        ->and($plan[0]['over'])->toBeTrue()
        ->and($plan[1]['over'])->toBeTrue()
        ->and($plan[2]['over'])->toBeFalse();

    $response->assertSeeTextInOrder(['Months paid', '1 / 20', 'Months pending', '1'])
        ->assertSeeTextInOrder(['Withdrawal plan', '15 Sep 2026', '₹95,000', 'Done'])
        ->assertSee('<details class="group-panel glass payment-step portal-plan"', false);
});

test('customers can print their chit and rice statements', function () {
    $this->actingAs($this->customer, 'customer');

    $this->get(route('portal.statement.print'))
        ->assertOk()
        ->assertSee('window.print()', false)
        ->assertSeeText('Customer Statement')
        ->assertSeeText('Diwali Group');

    $this->get(route('portal.bills.statement.print'))
        ->assertOk()
        ->assertSee('window.print()', false)
        ->assertSeeText('Customer Account');

    $this->get(route('portal.bills'))->assertSee(route('portal.bills.statement.print'));
    $this->get(route('portal.groups'))->assertSee(route('portal.statement.print'));
});

test('a customer says they want to join a forming group, and the office adds them', function () {
    $this->actingAs($this->customer, 'customer');

    $this->get(route('portal.upcoming'))->assertOk()->assertSeeText('Pongal Group')->assertDontSeeText('Diwali Group');
    $this->get(route('portal.upcoming.show', $this->forming))->assertOk()->assertSeeText("I'm interested")->assertSeeText('₹1,90,000');
    $this->get(route('portal.upcoming.show', $this->running))->assertNotFound();

    $this->post(route('portal.upcoming.interest', $this->forming), ['seats' => 2, 'note' => 'Two seats please'])->assertRedirect(route('portal.upcoming.show', $this->forming));
    $this->post(route('portal.upcoming.interest', $this->forming), ['seats' => 1]);

    $joinRequest = ChitJoinRequest::sole();

    expect($joinRequest->seats)->toBe(2)->and($joinRequest->isPending())->toBeTrue()
        ->and($this->forming->members()->count())->toBe(0);

    $this->get(route('portal.upcoming.show', $this->forming))->assertSeeText('Request sent');

    /* the office decides */
    $this->actingAs($this->admin, 'web')->get(route('dashboard'))->assertSeeText('customer wants to join a group');
    $this->actingAs($this->admin, 'web')->get(route('groups.requests.index'))->assertOk()->assertSeeText('Lakshmi')->assertSeeText('Two seats please');

    $this->actingAs($this->admin, 'web')->post(route('groups.requests.approve', $joinRequest), ['seats' => 3])->assertSessionHasErrors('seats');

    $this->actingAs($this->admin, 'web')->post(route('groups.requests.approve', $joinRequest), ['seats' => 2])->assertRedirect(route('groups.requests.index'));

    expect($joinRequest->refresh()->status)->toBe(ChitJoinRequest::STATUS_APPROVED)
        ->and($joinRequest->decided_by)->toBe($this->admin->id)
        ->and($this->forming->members()->where('customer_id', $this->customer->id)->pluck('member_code')->all())
        ->toBe([$this->customer->customer_code, $this->customer->customer_code.'-2']);
});

test('the office can dismiss a request, and a customer can withdraw one', function () {
    $this->actingAs($this->customer, 'customer')->post(route('portal.upcoming.interest', $this->forming), ['seats' => 1]);
    $first = ChitJoinRequest::sole();

    $this->actingAs($this->admin, 'web')->post(route('groups.requests.dismiss', $first), ['reply' => 'Group is only for shop owners'])->assertRedirect();

    expect($first->refresh()->status)->toBe(ChitJoinRequest::STATUS_DISMISSED)
        ->and($this->forming->members()->count())->toBe(0);

    $this->actingAs($this->customer, 'customer')->get(route('portal.upcoming.show', $this->forming))->assertSeeText('Group is only for shop owners');

    $this->actingAs($this->customer, 'customer')->post(route('portal.upcoming.interest', $this->forming), ['seats' => 1]);
    $second = ChitJoinRequest::latest('id')->first();

    $this->actingAs($this->customer, 'customer')->post(route('portal.upcoming.withdraw', $second))->assertRedirect();
    expect($second->refresh()->status)->toBe(ChitJoinRequest::STATUS_WITHDRAWN);

    $otherRequest = ChitJoinRequest::factory()->create(['chit_group_id' => $this->forming->id, 'customer_id' => $this->other->id]);
    $this->actingAs($this->customer, 'customer')->post(route('portal.upcoming.withdraw', $otherRequest))->assertNotFound();
});

test('a customer orders rice, and the office turns the order into a sale', function () {
    $ponni = RiceVariety::factory()->create(['name' => 'Ponni', 'bag_kg' => 26, 'selling_price' => 1450, 'purchase_price' => 1300]);
    $old = RiceVariety::factory()->create(['name' => 'Old Rice', 'is_active' => false]);

    $this->actingAs($this->customer, 'customer');

    $this->get(route('portal.rice'))->assertOk()->assertSeeText('Ponni')->assertSeeText('₹1,450')->assertDontSeeText('Old Rice');

    $this->post(route('portal.orders.store'), ['bags' => [$ponni->id => 0]])->assertSessionHasErrors('bags');

    $this->post(route('portal.orders.store'), ['bags' => [$ponni->id => 3, $old->id => 5], 'notes' => 'Deliver Saturday'])
        ->assertRedirect(route('portal.orders'));

    $order = TraderOrder::with('items')->sole();

    expect($order->order_number)->toBe('O'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT))
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->bags)->toBe(3)
        ->and($order->estimated_total)->toBe(4350.0);

    $this->get(route('portal.orders'))->assertSeeText($order->order_number)->assertSeeText('Waiting');

    /* the office */
    $this->actingAs($this->admin, 'web')->get(route('traders.dashboard'))->assertSeeText('new order from a customer');
    $this->actingAs($this->admin, 'web')->get(route('traders.orders.index'))->assertOk()->assertSeeText('Lakshmi')->assertSeeText('Deliver Saturday');

    $this->actingAs($this->admin, 'web')->get(route('traders.sales.create', ['order' => $order->id]))
        ->assertOk()
        ->assertSee('name="order_id" value="'.$order->id.'"', false)
        ->assertSee('value="3"', false);

    $this->actingAs($this->admin, 'web')->post(route('traders.sales.store'), [
        'customer_id' => $this->customer->id,
        'sold_on' => '2026-10-20',
        'lines' => [['variety_id' => $ponni->id, 'bags' => '3', 'rate' => '1450']],
        'received_amount' => '0',
        'received_method' => 'cash',
        'order_id' => $order->id,
    ])->assertRedirect();

    $sale = Sale::sole();

    expect($order->refresh()->status)->toBe(TraderOrder::STATUS_COMPLETED)
        ->and($order->sale_id)->toBe($sale->id);

    /* the customer's bills */
    $this->actingAs($this->customer, 'customer')->get(route('portal.bills'))
        ->assertOk()
        ->assertSeeText($sale->invoice_number)
        ->assertSeeText('₹4,350');
    $this->actingAs($this->customer, 'customer')->get(route('portal.bills.invoice.pdf', $sale))->assertOk();

    $otherSale = Sale::factory()->create(['customer_id' => $this->other->id]);
    $this->actingAs($this->customer, 'customer')->get(route('portal.bills.invoice.pdf', $otherSale))->assertNotFound();
});

test('a customer can cancel a waiting order but not someone else\'s', function () {
    $mine = TraderOrder::factory()->create(['customer_id' => $this->customer->id]);
    $theirs = TraderOrder::factory()->create(['customer_id' => $this->other->id]);

    $this->actingAs($this->customer, 'customer')->post(route('portal.orders.cancel', $mine))->assertRedirect(route('portal.orders'));
    $this->actingAs($this->customer, 'customer')->post(route('portal.orders.cancel', $theirs))->assertNotFound();

    expect($mine->refresh()->status)->toBe(TraderOrder::STATUS_CANCELLED)
        ->and($theirs->refresh()->isNew())->toBeTrue();
});

test('customer page views count on the Usage report', function () {
    $this->actingAs($this->customer, 'customer')->get(route('portal.groups'))->assertOk();

    $log = UsageLog::sole();

    expect($log->customer_id)->toBe($this->customer->id)
        ->and($log->user_id)->toBeNull()
        ->and($log->pageLabel())->toBe('My groups');
});

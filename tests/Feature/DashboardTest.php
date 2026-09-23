<?php

use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use App\Models\Customer;
use App\Models\Draw;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are sent to login from the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('the dashboard is the home page after login', function () {
    User::factory()->create(['username' => 'sathiya']);

    $this->post(route('login.store'), ['username' => 'sathiya', 'password' => 'password'])
        ->assertRedirect(route('dashboard'));

    expect(route('dashboard'))->toBe(url('/'));
});

test('the dashboard shows collections and groups, not customer details', function () {
    $this->travelTo(now()->setDate(2026, 3, 20)->setTime(10, 0));

    /* running since 15 Jan → months 1–3 due on 20 Mar */
    $group = ChitGroup::factory()->running()->create([
        'name' => 'Dash Group',
        'start_date' => '2026-01-15',
        'months' => 10,
        'installment_amount' => 10000,
        'member_count' => 2,
    ]);

    $paysMonthly = ChitGroupMember::factory()->create([
        'chit_group_id' => $group->id,
        'customer_id' => Customer::factory()->create(['name' => 'Hidden Customer Name'])->id,
    ]);
    $behind = ChitGroupMember::factory()->create(['chit_group_id' => $group->id]);

    /* earlier this month: months 1–3 for the first member */
    Payment::record($paysMonthly->load('chitGroup', 'allocations'), ['amount' => 30000, 'paid_at' => '2026-03-05 11:00', 'method' => 'cash']);

    /* today: month 1 for the second member */
    Payment::record($behind->load('chitGroup', 'allocations'), ['amount' => 10000, 'paid_at' => '2026-03-20 09:30', 'method' => 'upi']);

    /* last month — not in this month's total */
    Payment::record($behind->fresh()->load('chitGroup', 'allocations'), ['amount' => 1000, 'paid_at' => '2026-02-10 09:30', 'method' => 'cash']);

    ChitGroup::factory()->create(['name' => 'Forming Dash Group']);

    $this->actingAs(User::factory()->create(['name' => 'Narayanan']))
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Narayanan')
        ->assertViewHas('todayCollection', 10000)
        ->assertViewHas('todayReceipts', 1)
        ->assertViewHas('monthCollection', 40000)
        ->assertViewHas('monthReceipts', 2)
        /* second member owes months 2–3 minus the ₹1,000 → ₹19,000; month 2 is overdue */
        ->assertViewHas('pending', ['amount' => 19000, 'members' => 1, 'overdue' => 9000])
        ->assertViewHas('groupCards', function (array $cards) {
            $running = collect($cards)->firstWhere('group.name', 'Dash Group');

            return $running['current_month'] === 3
                && $running['collected'] === 10000
                && $running['expected'] === 20000
                && $running['percent'] === 50
                && $running['due_now'] === 19000
                && collect($cards)->pluck('group.name')->all() === ['Dash Group', 'Forming Dash Group'];
        })
        ->assertSee('Collected today')
        ->assertSee('Pending collections')
        ->assertSee('Group details')
        ->assertDontSee('Hidden Customer Name')
        ->assertDontSee('Total Customers');
});

test('the dashboard shows draws due, payouts and recent winners', function () {
    $this->travelTo(now()->setDate(2026, 3, 20)->setTime(10, 0));

    /* running since 15 Jan → months 1–3 can be drawn on 20 Mar */
    $group = ChitGroup::factory()->running()->withPayouts(150000, 5000)->create([
        'name' => 'Draw Dash Group',
        'start_date' => '2026-01-15',
        'months' => 5,
        'member_count' => 3,
    ]);

    $anand = ChitGroupMember::factory()->create(['chit_group_id' => $group->id, 'customer_id' => Customer::factory()->create(['name' => 'Anand'])->id]);
    $bharathi = ChitGroupMember::factory()->create(['chit_group_id' => $group->id, 'customer_id' => Customer::factory()->create(['name' => 'Bharathi'])->id]);

    Draw::create(['chit_group_id' => $group->id, 'month_number' => 1, 'winner_member_id' => $anand->id, 'withdrawal_amount' => 150000, 'drawn_at' => '2026-01-20 18:00', 'payout_amount' => 150000, 'payout_method' => 'cash', 'paid_at' => '2026-03-05 11:00', 'voucher_number' => 'PV000001']);
    Draw::create(['chit_group_id' => $group->id, 'month_number' => 2, 'winner_member_id' => $bharathi->id, 'withdrawal_amount' => 155000, 'drawn_at' => '2026-02-20 18:00']);

    ChitGroup::factory()->running()->create(['name' => 'Not Started Month', 'start_date' => '2026-04-15']);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('drawsDue', fn ($due) => $due->count() === 1
            && $due[0]['group']->is($group)
            && $due[0]['month'] === 3
            && $due[0]['prize'] === 160000)
        ->assertViewHas('pendingPayouts', ['amount' => 155000, 'count' => 1])
        ->assertViewHas('paidThisMonth', ['amount' => 150000, 'count' => 1])
        ->assertViewHas('recentDraws', fn ($draws) => $draws->pluck('month_number')->all() === [2, 1])
        ->assertSee('Draw Details')
        ->assertSee(route('draws.create', ['group' => $group->id]))
        ->assertSeeInOrder(['Recent winners', 'Bharathi', 'Awaiting payout', 'Anand', 'Paid out'])
        ->assertSee('2 / 5');
});

test('the dashboard works with no groups or payments', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('todayCollection', 0)
        ->assertViewHas('pending', ['amount' => 0, 'members' => 0, 'overdue' => 0])
        ->assertSee('No groups yet')
        ->assertSee('No draws due right now.');
});

test('the logo in the header, menu and footer links to home', function () {
    $response = $this->actingAs(User::factory()->create())
        ->get(route('customers.index'))
        ->assertOk();

    $home = preg_quote(route('dashboard'), '/');

    foreach (['brand-link', 'menu-brand brand-link', 'footer-brand brand-link'] as $class) {
        expect(preg_match_all('/href="'.$home.'"\s+class="'.$class.'"/', $response->getContent()))->toBe(1);
    }
});

test('the customers menu has all customers and add customer only', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('customers.create'))
        ->assertOk()
        ->assertSee('id="customersSubmenu"', false)
        ->assertSee(route('customers.create'))
        ->assertDontSee('Edit Customer')
        ->assertDontSee(route('customers.index', ['edit' => 1]));
});

<?php

use App\Models\ChitGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * A valid Add Group form submission.
 *
 * @return array<string, mixed>
 */
function validGroupInput(array $overrides = []): array
{
    $months = $overrides['months'] ?? 3;

    return array_merge([
        'name' => '2 Lakh - Jan 2027',
        'type' => 'draw',
        'amount' => '2,00,000',
        'months' => $months,
        'installment_amount' => '9,500',
        'member_count' => 20,
        'commission_amount' => '',
        'start_date' => '2027-01-15',
        'remarks' => 'First group',
        'payouts' => collect(range(1, $months))->mapWithKeys(fn ($month) => [$month => (string) (160000 + $month * 1000)])->all(),
    ], $overrides);
}

test('guests cannot reach the group pages', function () {
    auth()->logout();

    $this->get(route('groups.index'))->assertRedirect(route('login'));
    $this->get(route('groups.create'))->assertRedirect(route('login'));
});

test('the add group form defaults to 20 members and 20 months', function () {
    $this->get(route('groups.create'))
        ->assertOk()
        ->assertSee('Add Group')
        ->assertSee('name="member_count"', false)
        ->assertSee('value="20"', false)
        ->assertSee('name="payouts[20]"', false);
});

test('an admin can create a group with its withdrawal schedule', function () {
    $response = $this->post(route('groups.store'), validGroupInput());

    $group = ChitGroup::firstOrFail();

    $response->assertRedirect(route('groups.show', $group));

    expect($group)
        ->name->toBe('2 Lakh - Jan 2027')
        ->amount->toBe(200000)
        ->months->toBe(3)
        ->member_count->toBe(20)
        ->commission_amount->toBe(0)
        ->status->toBe(ChitGroup::STATUS_FORMING)
        ->installment_amount->toBe(9500)
        ->and($group->monthlyInstallment())->toBe(9500)
        ->and($group->payouts->pluck('withdrawal_amount', 'month_number')->all())
        ->toBe([1 => 161000, 2 => 162000, 3 => 163000]);
});

test('an auction group can be created', function () {
    $this->post(route('groups.store'), validGroupInput(['type' => 'auction']))
        ->assertSessionHasNoErrors();

    expect(ChitGroup::first()->type)->toBe('auction');
});

test('the group is rejected when the input is invalid', function (array $overrides, string $errorField) {
    ChitGroup::factory()->create(['name' => 'Taken Name']);

    $this->from(route('groups.create'))
        ->post(route('groups.store'), validGroupInput($overrides))
        ->assertRedirect(route('groups.create'))
        ->assertSessionHasErrors($errorField);

    expect(ChitGroup::count())->toBe(1);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'duplicate name' => [['name' => 'Taken Name'], 'name'],
    'unknown type' => [['type' => 'lottery'], 'type'],
    'zero months' => [['months' => 0, 'payouts' => []], 'months'],
    'missing installment' => [['installment_amount' => ''], 'installment_amount'],
    'zero installment' => [['installment_amount' => '0'], 'installment_amount'],
    'schedule shorter than months' => [['months' => 4, 'payouts' => [1 => '160000', 2 => '161000', 3 => '162000']], 'payouts'],
    'blank withdrawal amount' => [['payouts' => [1 => '160000', 2 => '', 3 => '165000']], 'payouts.2'],
]);

test('a withdrawal amount can be more than the chit amount', function () {
    $this->post(route('groups.store'), validGroupInput([
        'payouts' => [1 => '1,90,000', 2 => '2,05,000', 3 => '2,20,000'],
    ]))->assertSessionHasNoErrors();

    expect(ChitGroup::firstOrFail()->payouts->pluck('withdrawal_amount')->all())
        ->toBe([190000, 205000, 220000]);
});

test('the group list shows groups and filters by status', function () {
    ChitGroup::factory()->create(['name' => 'Forming Group']);
    ChitGroup::factory()->running()->create(['name' => 'Running Group']);

    $this->get(route('groups.index'))
        ->assertOk()
        ->assertSee('Forming Group')
        ->assertSee('Running Group');

    $this->get(route('groups.index', ['status' => 'running']))
        ->assertOk()
        ->assertSee('Running Group')
        ->assertDontSee('Forming Group');
});

test('the group list shows 12 groups per page with numbered pages', function () {
    ChitGroup::factory()->count(14)->create();
    ChitGroup::factory()->running()->create(['name' => 'Newest Running']);

    $this->get(route('groups.index'))
        ->assertOk()
        ->assertSee('Newest Running')
        ->assertViewHas('groups', fn ($groups) => $groups->count() === 12 && $groups->total() === 15)
        ->assertSee('1–12')
        ->assertSee(route('groups.index', ['page' => 2]));

    $this->get(route('groups.index', ['page' => 2]))
        ->assertOk()
        ->assertViewHas('groups', fn ($groups) => $groups->count() === 3)
        ->assertSee('13–15');
});

test('the status filter is kept when changing pages', function () {
    ChitGroup::factory()->running()->count(13)->create();

    $this->get(route('groups.index', ['status' => 'running']))
        ->assertOk()
        ->assertSee('status=running&amp;page=2', false);
});

test('view group shows the details and the schedule in rupees', function () {
    $group = ChitGroup::factory()->withPayouts()->create(['name' => 'Two Lakh Draw']);

    $this->get(route('groups.show', $group))
        ->assertOk()
        ->assertSee('Two Lakh Draw')
        ->assertSee('₹2,00,000')
        ->assertSee('₹10,000')
        ->assertSee('₹1,60,000')
        ->assertSee('Start Group');
});

test('the schedule can be copied from an existing group', function () {
    ChitGroup::factory()->withPayouts()->create(['name' => 'Template Group']);

    $response = $this->get(route('groups.create'))->assertOk();

    expect($response->getContent())
        ->toContain('Template Group (20 months)')
        ->toContain('"payouts":[160000,162000');
});

test('a forming group can be edited and its schedule replaced', function () {
    $group = ChitGroup::factory()->withPayouts()->create();

    $this->get(route('groups.edit', $group))->assertOk()->assertSee('Edit Group');

    $this->put(route('groups.update', $group), validGroupInput(['name' => 'Renamed Group', 'months' => 2]))
        ->assertRedirect(route('groups.show', $group));

    $group->refresh();

    expect($group->name)->toBe('Renamed Group')
        ->and($group->months)->toBe(2)
        ->and($group->payouts)->toHaveCount(2);
});

test('an admin can start a group', function () {
    $group = ChitGroup::factory()->withPayouts()->withMembers()->create();

    $this->post(route('groups.start', $group))
        ->assertRedirect(route('groups.show', $group))
        ->assertSessionHas('success');

    $group->refresh();

    expect($group->status)->toBe(ChitGroup::STATUS_RUNNING)
        ->and($group->started_at)->not->toBeNull();
});

test('a started group can still be edited', function () {
    $group = ChitGroup::factory()->running()->withPayouts()->create(['name' => 'Running Group']);

    $this->get(route('groups.show', $group))
        ->assertOk()
        ->assertSee(route('groups.edit', $group))
        ->assertDontSee('Start Group');

    $this->get(route('groups.edit', $group))->assertOk()->assertSee('Edit Group');

    $this->put(route('groups.update', $group), validGroupInput(['name' => 'Renamed While Running', 'installment_amount' => '11,000']))
        ->assertRedirect(route('groups.show', $group))
        ->assertSessionHas('success');

    $group->refresh();

    expect($group->name)->toBe('Renamed While Running')
        ->and($group->installment_amount)->toBe(11000)
        ->and($group->isRunning())->toBeTrue();
});

test('a started group cannot be started again', function () {
    $group = ChitGroup::factory()->running()->withMembers()->create();
    $startedAt = $group->started_at;

    $this->post(route('groups.start', $group))->assertSessionHas('error');

    expect($group->fresh()->started_at->equalTo($startedAt))->toBeTrue();
});

test('group months run from the start date, not the calendar month', function (string $today, int $expectedMonth) {
    $this->travelTo(Carbon::parse($today.' 10:00', config('app.business_timezone')));

    $group = ChitGroup::factory()->running()->create(['start_date' => '2026-09-15', 'months' => 20]);

    expect($group->currentMonthNumber())->toBe($expectedMonth);
})->with([
    'before the start' => ['2026-09-14', 0],
    'start day' => ['2026-09-15', 1],
    'still month 1 in October' => ['2026-10-10', 1],
    'last day of month 1' => ['2026-10-14', 1],
    'month 2 starts on the 15th' => ['2026-10-15', 2],
    'month 3' => ['2026-11-20', 3],
    'after the last month' => ['2029-01-01', 20],
]);

test('each group month is labelled with its date range', function () {
    $group = ChitGroup::factory()->make(['start_date' => '2026-09-15']);

    expect($group->monthPeriodLabel(1))->toBe('15 Sep – 14 Oct 2026')
        ->and($group->monthPeriodLabel(4))->toBe('15 Dec 2026 – 14 Jan 2027')
        ->and($group->monthPeriodLabel(2, withYear: false))->toBe('15 Oct – 14 Nov');
});

test('view group highlights the group month, not the calendar month', function () {
    $this->travelTo(Carbon::parse('2026-10-10 10:00', config('app.business_timezone')));

    $group = ChitGroup::factory()->running()->withPayouts()->create(['start_date' => '2026-09-15']);

    $this->get(route('groups.show', $group))
        ->assertOk()
        ->assertSeeInOrder(['15 Sep – 14 Oct 2026', 'This month', '15 Oct – 14 Nov 2026']);
});

test('the groups menu has all groups and add group', function () {
    $this->get(route('groups.index'))
        ->assertOk()
        ->assertSee('id="groupsSubmenu"', false)
        ->assertSee(route('groups.create'));
});

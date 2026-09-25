<?php

use App\Models\Customer;
use App\Models\UsageDaily;
use App\Models\UsageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 9, 24)->setTime(10, 0));

    $this->sathiya = User::factory()->create(['name' => 'Sathiya', 'username' => 'sathiya', 'can_view_usage' => true]);
    $this->narayanan = User::factory()->create(['name' => 'Narayanan', 'username' => 'narayanan']);
});

/**
 * A stored usage log for a person on a day.
 */
function usageOn(string $day, ?User $user = null, ?Customer $customer = null, string $route = 'dashboard'): UsageLog
{
    $log = UsageLog::create([
        'user_id' => $user?->id,
        'customer_id' => $customer?->id,
        'event' => 'view',
        'business' => 'chit',
        'route_name' => $route,
        'path' => '/',
        'device' => 'phone',
        'visited_on' => $day,
    ]);

    $log->forceFill(['created_at' => $day.' 05:00:00'])->save();

    return $log;
}

test('each page a signed-in person opens is logged', function () {
    $this->actingAs($this->narayanan)
        ->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14; Pixel 8) Mobile Safari')
        ->get(route('customers.index'))
        ->assertOk();

    $log = UsageLog::sole();

    expect($log->user_id)->toBe($this->narayanan->id)
        ->and($log->route_name)->toBe('customers.index')
        ->and($log->event)->toBe('view')
        ->and($log->device)->toBe('phone')
        ->and($log->visited_on->toDateString())->toBe('2026-09-24')
        ->and($log->pageLabel())->toBe('Customers');
});

test('form posts, live-filter refreshes, missing pages and downloads are not counted', function () {
    $this->actingAs($this->narayanan);

    $this->get(route('payments.index'), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
    $this->get('/no-such-page')->assertNotFound();
    $this->get(route('traders.stock.pdf'))->assertOk();
    $this->put(route('password.update'), [])->assertSessionHasErrors();

    expect(UsageLog::count())->toBe(0);
});

test('signing in is logged', function () {
    $this->narayanan->update(['password' => 'secret-pass-123']);

    $this->post(route('login.store'), ['username' => 'narayanan', 'password' => 'secret-pass-123'])
        ->assertRedirect(route('dashboard'));

    expect(UsageLog::where('event', 'login')->sole()->user_id)->toBe($this->narayanan->id);
});

test('only Sathiya sees the Usage menu and page', function () {
    $this->actingAs($this->narayanan)->get(route('dashboard'))->assertDontSee(route('usage.index'));
    $this->actingAs($this->narayanan)->get(route('usage.index'))->assertForbidden();

    $this->actingAs($this->sathiya)->get(route('dashboard'))->assertSee(route('usage.index'));
    $this->actingAs($this->sathiya)->get(route('usage.index'))->assertOk();
});

test('the usage dashboard counts today, this month, each day and each month', function () {
    usageOn('2026-09-24', $this->narayanan);
    usageOn('2026-09-24', $this->narayanan);
    usageOn('2026-09-20', $this->narayanan);
    usageOn('2026-09-02', $this->sathiya);
    usageOn('2026-08-15', $this->narayanan);
    usageOn('2025-12-01', $this->narayanan);

    $response = $this->actingAs($this->sathiya)->get(route('usage.index', ['who' => 'staff']))->assertOk();

    $summary = $response->viewData('summary');
    $daily = $response->viewData('daily');
    $monthly = $response->viewData('monthly');

    /* opening the page itself logs one more view today, after the numbers are read */
    expect($summary['today_views'])->toBe(2)
        ->and($summary['today_people'])->toBe(1)
        ->and($summary['month_views'])->toBe(4)
        ->and($summary['month_people'])->toBe(2)
        ->and($summary['month_active_days'])->toBe(3)
        ->and($daily)->toHaveCount(30)
        ->and($daily->last()['views'])->toBe(2)
        ->and($daily->firstWhere(fn ($day) => $day['date']->toDateString() === '2026-09-20')['views'])->toBe(1)
        ->and($monthly)->toHaveCount(12)
        ->and($monthly->first()['month']->format('Y-m'))->toBe('2025-10')
        ->and($monthly->firstWhere(fn ($month) => $month['month']->format('Y-m') === '2026-08')['views'])->toBe(1)
        ->and($monthly->last()['people'])->toBe(2);
});

test('who used the app recently lists staff and customers, latest first', function () {
    $customer = Customer::factory()->create(['name' => 'Lakshmi', 'remarks' => 'Teacher']);

    usageOn('2026-09-20', $this->narayanan, route: 'payments.create');
    usageOn('2026-09-23', customer: $customer);

    $people = $this->actingAs($this->sathiya)->get(route('usage.index'))->viewData('people');

    expect($people->map(fn ($person) => $person['person']->name)->all())->toBe(['Lakshmi', 'Narayanan'])
        ->and($people[1]['log']->pageLabel())->toBe('Collect payment')
        ->and($people[1]['month'])->toBe(1);

    $this->actingAs($this->sathiya)->get(route('usage.index', ['who' => 'customers']))
        ->assertViewHas('people', fn ($people) => $people->count() === 1)
        ->assertSeeText('Teacher');

    $this->actingAs($this->sathiya)->get(route('usage.index', ['who' => 'staff']))
        ->assertViewHas('people', fn ($people) => $people->every(fn ($person) => $person['person'] instanceof User));
});

test('the customers tab explains when no customer has used the app', function () {
    $this->actingAs($this->sathiya)->get(route('usage.index', ['who' => 'customers']))
        ->assertOk()
        ->assertSeeText('Customers will appear here once they can sign in.');
});

test('page detail is kept for a month, while the daily counts keep the charts for a year', function () {
    $lastYear = usageOn('2025-08-01', $this->narayanan);
    $lastMonth = usageOn('2026-08-10', $this->narayanan, route: 'payments.create');
    usageOn('2026-08-10', $this->narayanan);
    $recent = usageOn('2026-09-01', $this->narayanan);

    expect(UsageDaily::where('person', 'u'.$this->narayanan->id)->orderBy('visited_on')->pluck('views')->all())->toBe([1, 2, 1]);

    $this->artisan('model:prune', ['--model' => [UsageLog::class, UsageDaily::class]])->assertSuccessful();

    expect(UsageLog::whereKey([$lastYear->id, $lastMonth->id])->exists())->toBeFalse()
        ->and(UsageLog::whereKey($recent->id)->exists())->toBeTrue()
        ->and(UsageDaily::whereDate('visited_on', '2025-08-01')->exists())->toBeFalse();

    $response = $this->actingAs($this->sathiya)->get(route('usage.index', ['who' => 'staff']));

    /* August still counts on the monthly chart; the person's total keeps it too */
    expect($response->viewData('monthly')->firstWhere(fn ($month) => $month['month']->format('Y-m') === '2026-08')['views'])->toBe(2)
        ->and($response->viewData('people')->firstWhere(fn ($person) => $person['person']->is($this->narayanan))['total'])->toBe(3);
});

test('someone seen only before the last month still shows, without page detail', function () {
    usageOn('2026-07-01', $this->narayanan);
    UsageLog::query()->delete();

    $this->actingAs($this->sathiya)->get(route('usage.index', ['who' => 'staff']))
        ->assertOk()
        ->assertViewHas('people', fn ($people) => $people->firstWhere(fn ($person) => $person['person']->is($this->narayanan))['log'] === null)
        ->assertSeeText('01 Jul 2026');
});

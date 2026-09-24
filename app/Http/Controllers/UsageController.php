<?php

namespace App\Http\Controllers;

use App\Models\UsageLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Usage (Sathiya only): how much the app is used — today and this month,
 * day by day for 30 days, month by month for 12 months — who used it
 * recently (staff, and customers once they can sign in) and the latest
 * pages opened.
 */
class UsageController extends Controller
{
    /**
     * Whose usage to show.
     *
     * @var array<string, string>
     */
    public const WHO = ['all' => 'Everyone', 'staff' => 'Staff', 'customers' => 'Customers'];

    private const DAYS = 30;

    private const MONTHS = 12;

    private const PEOPLE_LIMIT = 100;

    private const PER_PAGE = 25;

    public function index(Request $request): View
    {
        $who = array_key_exists((string) $request->input('who'), self::WHO) ? (string) $request->input('who') : 'all';
        $today = Carbon::parse(today(config('app.business_timezone'))->toDateString());

        $perDay = $this->viewsPerDayAndPerson($who, $today->copy()->subMonthsNoOverflow(self::MONTHS - 1)->startOfMonth());

        $daily = $this->daily($perDay, $today);
        $monthly = $this->monthly($perDay, $today);

        return view('usage.index', [
            'who' => $who,
            'whoOptions' => self::WHO,
            'today' => $today,
            'summary' => [
                'today_views' => $daily->last()['views'],
                'today_people' => $daily->last()['people'],
                'month_views' => $monthly->last()['views'],
                'month_people' => $monthly->last()['people'],
                'month_active_days' => $monthly->last()['active_days'],
                'average_per_day' => round($monthly->last()['views'] / $today->day, 1),
            ],
            'daily' => $daily,
            'monthly' => $monthly,
            'people' => $this->people($who, $today),
            'activity' => $this->forWho(UsageLog::query(), $who)
                ->with(['user:id,name,username', 'customer:id,customer_code,name,remarks'])
                ->latest('id')
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
        ]);
    }

    /**
     * @param  Builder<UsageLog>  $query
     * @return Builder<UsageLog>
     */
    private function forWho(Builder $query, string $who): Builder
    {
        return $query
            ->when($who === 'staff', fn ($query) => $query->whereNotNull('user_id'))
            ->when($who === 'customers', fn ($query) => $query->whereNotNull('customer_id'));
    }

    /**
     * Views per day per person since a date (one grouped query).
     *
     * @return Collection<int, object{day: string, person: string, views: int}>
     */
    private function viewsPerDayAndPerson(string $who, Carbon $since): Collection
    {
        return $this->forWho(UsageLog::query(), $who)->toBase()
            ->where('visited_on', '>=', $since->toDateString())
            ->selectRaw('visited_on, user_id, customer_id, COUNT(*) as views')
            ->groupBy('visited_on', 'user_id', 'customer_id')
            ->get()
            ->map(fn ($row) => (object) [
                'day' => substr((string) $row->visited_on, 0, 10),
                'person' => $row->user_id ? 'u'.$row->user_id : 'c'.$row->customer_id,
                'views' => (int) $row->views,
            ]);
    }

    /**
     * The last 30 days, oldest first, with views and people each day.
     *
     * @param  Collection<int, object{day: string, person: string, views: int}>  $perDay
     * @return Collection<int, array{date: Carbon, views: int, people: int}>
     */
    private function daily(Collection $perDay, Carbon $today): Collection
    {
        $byDay = $perDay->groupBy('day');

        return collect(range(self::DAYS - 1, 0))->map(function (int $daysAgo) use ($byDay, $today) {
            $date = $today->copy()->subDays($daysAgo);
            $rows = $byDay->get($date->toDateString(), collect());

            return [
                'date' => $date,
                'views' => (int) $rows->sum('views'),
                'people' => $rows->pluck('person')->unique()->count(),
            ];
        });
    }

    /**
     * The last 12 months, oldest first, with views, people and active days.
     *
     * @param  Collection<int, object{day: string, person: string, views: int}>  $perDay
     * @return Collection<int, array{month: Carbon, views: int, people: int, active_days: int}>
     */
    private function monthly(Collection $perDay, Carbon $today): Collection
    {
        $byMonth = $perDay->groupBy(fn ($row) => substr($row->day, 0, 7));

        return collect(range(self::MONTHS - 1, 0))->map(function (int $monthsAgo) use ($byMonth, $today) {
            $month = $today->copy()->startOfMonth()->subMonthsNoOverflow($monthsAgo);
            $rows = $byMonth->get($month->format('Y-m'), collect());

            return [
                'month' => $month,
                'views' => (int) $rows->sum('views'),
                'people' => $rows->pluck('person')->unique()->count(),
                'active_days' => $rows->pluck('day')->unique()->count(),
            ];
        });
    }

    /**
     * Everyone who has used the app, most recent first: last seen, the page
     * and device, and how many pages today / this month / in all.
     *
     * @return Collection<int, array{log: UsageLog, today: int, month: int, total: int, days: int}>
     */
    private function people(string $who, Carbon $today): Collection
    {
        $totals = $this->forWho(UsageLog::query(), $who)->toBase()
            ->selectRaw('user_id, customer_id, MAX(id) as last_id, COUNT(*) as total, COUNT(DISTINCT visited_on) as days')
            ->selectRaw('SUM(CASE WHEN visited_on >= ? THEN 1 ELSE 0 END) as today_views', [$today->toDateString()])
            ->selectRaw('SUM(CASE WHEN visited_on >= ? THEN 1 ELSE 0 END) as month_views', [$today->copy()->startOfMonth()->toDateString()])
            ->groupBy('user_id', 'customer_id')
            ->orderByDesc('last_id')
            ->limit(self::PEOPLE_LIMIT)
            ->get();

        $lastLogs = UsageLog::query()
            ->whereKey($totals->pluck('last_id'))
            ->with(['user:id,name,username', 'customer:id,customer_code,name,remarks,phone'])
            ->get()
            ->keyBy('id');

        return $totals
            ->filter(fn ($row) => $lastLogs->has($row->last_id))
            ->map(fn ($row) => [
                'log' => $lastLogs[$row->last_id],
                'today' => (int) $row->today_views,
                'month' => (int) $row->month_views,
                'total' => (int) $row->total,
                'days' => (int) $row->days,
            ])
            ->values();
    }
}

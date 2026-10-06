<?php

namespace App\Http\Controllers;

use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use App\Models\ChitJoinRequest;
use App\Models\Draw;
use App\Models\Payment;
use App\Models\TraderOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Admin home: today's and this month's collection, what is still
     * pending, and a card per group with its collection progress.
     */
    public function index(): View
    {
        $businessNow = now(config('app.business_timezone'));
        $today = $businessNow->toDateString();

        $groups = ChitGroup::query()
            ->with([
                'members.customer',
                'members.allocations' => fn ($query) => ChitGroupMember::monthTotalsOnly($query),
            ])
            ->withCount(['members', 'draws'])
            ->withMax('draws', 'month_number')
            ->orderByRaw("CASE status WHEN 'running' THEN 0 WHEN 'forming' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->get();

        $groups->each(fn (ChitGroup $group) => $group->members->each->setRelation('chitGroup', $group));

        return view('dashboard', [
            'waitingJoinRequests' => ChitJoinRequest::query()->pending()->count(),
            'newRiceOrders' => TraderOrder::query()->open()->count(),
            'todayCollection' => (int) Payment::whereDate('paid_at', $today)->sum('amount'),
            'todayReceipts' => Payment::whereDate('paid_at', $today)->count(),
            'monthCollection' => (int) $this->paymentsThisMonth($businessNow)->sum('amount'),
            'monthReceipts' => $this->paymentsThisMonth($businessNow)->count(),
            ...$this->collectionSummary($groups),
            'groupCards' => $groups->map(fn (ChitGroup $group) => $this->groupCard($group))->all(),
            'today' => $today,
            ...$this->drawDetails($groups, $businessNow),
        ]);
    }

    /**
     * Draws at a glance: groups whose draw is due, prizes still to hand
     * over, prizes paid this month and the latest winners.
     *
     * @param  Collection<int, ChitGroup>  $groups
     * @return array{drawsDue: Collection<int, array{group: ChitGroup, month: int, prize: int}>, pendingPayouts: array{amount: int, count: int}, paidThisMonth: array{amount: int, count: int}, recentDraws: Collection<int, Draw>}
     */
    private function drawDetails(Collection $groups, Carbon $businessNow): array
    {
        $drawsDue = $groups
            ->filter(fn (ChitGroup $group) => $group->canDrawNow())
            ->map(function (ChitGroup $group) {
                $month = $group->nextDrawMonth();

                return ['group' => $group, 'month' => $month, 'prize' => $group->withdrawalForMonth($month)];
            })
            ->values();

        $paidThisMonth = Draw::whereBetween('paid_at', [
            $businessNow->copy()->startOfMonth()->format('Y-m-d 00:00:00'),
            $businessNow->copy()->endOfMonth()->format('Y-m-d 23:59:59'),
        ]);

        return [
            'drawsDue' => $drawsDue,
            'pendingPayouts' => [
                'amount' => (int) Draw::whereNull('paid_at')->sum('withdrawal_amount'),
                'count' => Draw::whereNull('paid_at')->count(),
            ],
            'paidThisMonth' => [
                'amount' => (int) $paidThisMonth->clone()->sum('payout_amount'),
                'count' => $paidThisMonth->count(),
            ],
            'recentDraws' => Draw::with(['chitGroup', 'winner.customer'])
                ->latest('drawn_at')
                ->latest('id')
                ->take(5)
                ->get(),
        ];
    }

    /**
     * Payments dated in the current (office) calendar month.
     *
     * @return Builder<Payment>
     */
    private function paymentsThisMonth(Carbon $businessNow): Builder
    {
        return Payment::whereBetween('paid_at', [
            $businessNow->copy()->startOfMonth()->format('Y-m-d 00:00:00'),
            $businessNow->copy()->endOfMonth()->format('Y-m-d 23:59:59'),
        ]);
    }

    /**
     * The two collection lists across running groups, as on the Collect
     * page: pending (past the due date) and due (in the due window).
     *
     * @param  Collection<int, ChitGroup>  $groups
     * @return array{pending: array{amount: int, members: int}, due: array{amount: int, members: int}}
     */
    private function collectionSummary(Collection $groups): array
    {
        $statuses = $groups
            ->filter(fn (ChitGroup $group) => $group->isRunning())
            ->flatMap(fn (ChitGroup $group) => $group->members)
            ->map(fn (ChitGroupMember $member) => $member->collectionStatus());

        $pending = $statuses->filter(fn (array $status) => $status['state'] === 'pending');
        $due = $statuses->filter(fn (array $status) => $status['state'] !== 'pending' && $status['in_due_window']);

        return [
            'pending' => ['amount' => (int) $pending->sum('pending'), 'members' => $pending->count()],
            'due' => ['amount' => (int) $due->sum('amount_due'), 'members' => $due->count()],
        ];
    }

    /**
     * One dashboard card: members, and for a running group this month's
     * collection against what is expected plus the amount due now.
     *
     * @return array<string, mixed>
     */
    private function groupCard(ChitGroup $group): array
    {
        $card = [
            'group' => $group,
            'members' => $group->members_count,
        ];

        if (! $group->isRunning()) {
            return $card;
        }

        $currentMonth = $group->currentMonthNumber();

        $collected = $currentMonth > 0
            ? (int) $group->members->sum(fn (ChitGroupMember $member) => $member->paidByMonth()[$currentMonth] ?? 0)
            : 0;

        $expected = $group->installment_amount * $group->members_count;

        return $card + [
            'current_month' => $currentMonth,
            'collected' => $collected,
            'expected' => $expected,
            'percent' => $expected > 0 ? min(100, (int) round($collected / $expected * 100)) : 0,
            'due_now' => (int) $group->members->sum(fn (ChitGroupMember $member) => $member->collectionStatus()['pending']),
        ];
    }
}

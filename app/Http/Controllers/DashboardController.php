<?php

namespace App\Http\Controllers;

use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use App\Models\Payment;
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
            ->with(['members.customer', 'members.allocations'])
            ->withCount('members')
            ->orderByRaw("CASE status WHEN 'running' THEN 0 WHEN 'forming' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->get();

        $groups->each(fn (ChitGroup $group) => $group->members->each->setRelation('chitGroup', $group));

        return view('dashboard', [
            'todayCollection' => (int) Payment::whereDate('paid_at', $today)->sum('amount'),
            'todayReceipts' => Payment::whereDate('paid_at', $today)->count(),
            'monthCollection' => (int) $this->paymentsThisMonth($businessNow)->sum('amount'),
            'monthReceipts' => $this->paymentsThisMonth($businessNow)->count(),
            'pending' => $this->pendingCollections($groups),
            'groupCards' => $groups->map(fn (ChitGroup $group) => $this->groupCard($group))->all(),
            'today' => $today,
        ]);
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
     * Everything owed up to the current month across running groups.
     *
     * @param  Collection<int, ChitGroup>  $groups
     * @return array{amount: int, members: int, overdue: int}
     */
    private function pendingCollections(Collection $groups): array
    {
        $statuses = $groups
            ->filter(fn (ChitGroup $group) => $group->isRunning())
            ->flatMap(fn (ChitGroup $group) => $group->members)
            ->map(fn (ChitGroupMember $member) => $member->collectionStatus())
            ->reject(fn (array $status) => $status['state'] === 'clear');

        return [
            'amount' => (int) $statuses->sum('amount_due'),
            'members' => $statuses->count(),
            'overdue' => (int) $statuses->sum('pending'),
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

        $currentMonth = $group->members->first()?->dueMonthCount() ?? 0;

        $collected = $currentMonth > 0
            ? (int) $group->members->sum(fn (ChitGroupMember $member) => $member->paidByMonth()[$currentMonth] ?? 0)
            : 0;

        $expected = $group->installment_amount * $group->members_count;

        return $card + [
            'current_month' => $currentMonth,
            'collected' => $collected,
            'expected' => $expected,
            'percent' => $expected > 0 ? min(100, (int) round($collected / $expected * 100)) : 0,
            'due_now' => (int) $group->members->sum(fn (ChitGroupMember $member) => $member->balanceDue()),
        ];
    }
}

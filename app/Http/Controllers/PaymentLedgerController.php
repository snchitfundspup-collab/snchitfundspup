<?php

namespace App\Http\Controllers;

use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Payment Ledger: one group, members × months, how much each member paid
 * towards each month, with row / column totals. Shown on screen
 * (printable) and downloadable as an A4 landscape PDF.
 */
class PaymentLedgerController extends Controller
{
    public function index(Request $request): View
    {
        $groups = $this->startedGroups();

        $group = $groups->firstWhere('id', $request->integer('group')) ?? $groups->first();

        if ($group === null) {
            return view('payments.ledger', ['groups' => $groups, 'group' => null]);
        }

        return view('payments.ledger', [
            'groups' => $groups,
            ...$this->ledgerFor($group->id, $request),
        ]);
    }

    /**
     * Download the ledger shown on screen (same group and month range).
     */
    public function pdf(Request $request): Response
    {
        $group = $this->startedGroups()->firstWhere('id', $request->integer('group')) ?? abort(404);

        $ledger = $this->ledgerFor($group->id, $request);

        $fileName = 'Ledger-'.Str::slug($group->name).'-M'.$ledger['fromMonth'].'-'.$ledger['toMonth'].'.pdf';

        return Pdf::loadView('pdf.ledger', $ledger)
            ->setPaper('a4', 'landscape')
            ->download($fileName);
    }

    /**
     * Running and completed groups (forming groups have no payments).
     *
     * @return Collection<int, ChitGroup>
     */
    private function startedGroups(): Collection
    {
        return ChitGroup::query()
            ->whereIn('status', [ChitGroup::STATUS_RUNNING, ChitGroup::STATUS_COMPLETED])
            ->orderBy('name')
            ->get(['id', 'name', 'months', 'status']);
    }

    /**
     * The grid: rows per member with a cell per month, plus totals.
     *
     * @return array<string, mixed>
     */
    private function ledgerFor(int $groupId, Request $request): array
    {
        $group = ChitGroup::with(['members.customer', 'members.allocations', 'draws'])->findOrFail($groupId);
        $group->members->each->setRelation('chitGroup', $group);

        /* member id → the draw they won (prize money shown in the ledger) */
        $drawsByWinner = $group->draws->keyBy('winner_member_id');

        $fromMonth = min(max(1, $request->integer('from', 1)), $group->months);
        $toMonth = min(max($fromMonth, $request->integer('to', $group->months)), $group->months);

        $monthNumbers = range($fromMonth, $toMonth);

        $rows = $group->members->map(function (ChitGroupMember $member) use ($monthNumbers, $drawsByWinner) {
            $ledger = collect($member->ledger())->keyBy('month');

            return [
                'member' => $member,
                'cells' => collect($monthNumbers)->mapWithKeys(fn (int $month) => [$month => $ledger[$month]])->all(),
                'paid' => collect($monthNumbers)->sum(fn (int $month) => $ledger[$month]['paid']),
                'balance_due' => $member->balanceDue(),
                'pending' => $member->collectionStatus()['pending'],
                'won' => $drawsByWinner->get($member->id),
            ];
        });

        $monthTotals = collect($monthNumbers)->mapWithKeys(fn (int $month) => [$month => [
            'paid' => $rows->sum(fn (array $row) => $row['cells'][$month]['paid']),
            'expected' => $group->installment_amount * $rows->count(),
        ]])->all();

        return [
            'group' => $group,
            'rows' => $rows,
            'monthNumbers' => $monthNumbers,
            'monthTotals' => $monthTotals,
            'fromMonth' => $fromMonth,
            'toMonth' => $toMonth,
            'totalPaid' => $rows->sum('paid'),
            'totalDue' => $rows->sum('balance_due'),
            'totalPending' => $rows->sum('pending'),
            'totalPrizes' => (int) $group->draws->sum(fn ($draw) => $draw->prizeAmount()),
        ];
    }
}

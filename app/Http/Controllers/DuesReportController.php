<?php

namespace App\Http\Controllers;

use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use App\Support\ReportPdf as Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Pending & Due report (Reports): the same two lists as the Collect page —
 * Pending (a month past its due date and unpaid) and Due (the month in its
 * due window, 1st of the month until the due date, or already part paid) —
 * for all running groups or one, with amounts, days overdue and totals.
 * Printable and downloadable as an A4 PDF.
 */
class DuesReportController extends Controller
{
    private const TYPES = ['all', 'pending', 'due'];

    public function index(Request $request): View
    {
        return view('reports.dues', $this->report($request));
    }

    /**
     * The report on its own page, which opens the print dialog.
     */
    public function printReport(Request $request): View
    {
        return view('reports.dues-print', $this->report($request));
    }

    public function pdf(Request $request): Response
    {
        $report = $this->report($request);

        return Pdf::loadView('pdf.dues', $report)
            ->setPaper('a4', 'portrait')
            ->download('Pending-and-Due-'.$report['today']->format('Y-m-d').'.pdf');
    }

    /**
     * @return array{type: string, group: ?ChitGroup, groups: Collection<int, ChitGroup>, pending: Collection<int, array<string, mixed>>, due: Collection<int, array<string, mixed>>, byGroup: Collection<int, array<string, mixed>>, today: Carbon}
     */
    private function report(Request $request): array
    {
        $type = in_array($request->input('type'), self::TYPES, true) ? $request->input('type') : 'all';
        $today = today(config('app.business_timezone'));

        $groups = ChitGroup::query()
            ->where('status', ChitGroup::STATUS_RUNNING)
            ->orderBy('name')
            ->get(['id', 'name']);

        $group = $groups->firstWhere('id', $request->integer('group'));

        $rows = ChitGroupMember::query()
            ->whereHas('chitGroup', fn ($query) => $query->where('status', ChitGroup::STATUS_RUNNING))
            ->when($group, fn ($query) => $query->where('chit_group_id', $group->id))
            ->with([
                'customer',
                'chitGroup',
                'allocations' => fn ($query) => ChitGroupMember::monthTotalsOnly($query),
            ])
            ->get()
            ->map(fn (ChitGroupMember $member) => $this->row($member, $today))
            ->filter();

        /* pending: longest overdue first; due: soonest due date first */
        $pending = $rows->where('list', 'pending')
            ->sortBy([['days_overdue', 'desc'], ['name', 'asc']])
            ->values();

        $due = $rows->where('list', 'due')
            ->sortBy([['due_date', 'asc'], ['name', 'asc']])
            ->values();

        $byGroup = $rows
            ->groupBy('group_id')
            ->map(fn (Collection $groupRows) => [
                'name' => $groupRows->first()['group'],
                'pending_count' => $groupRows->where('list', 'pending')->count(),
                'pending_amount' => $groupRows->where('list', 'pending')->sum('amount'),
                'due_count' => $groupRows->where('list', 'due')->count(),
                'due_amount' => $groupRows->where('list', 'due')->sum('amount'),
            ])
            ->sortBy('name')
            ->values();

        return compact('type', 'group', 'groups', 'pending', 'due', 'byGroup', 'today');
    }

    /**
     * One member's line on the report, or null when they are on neither list.
     *
     * @return array<string, mixed>|null
     */
    private function row(ChitGroupMember $member, Carbon $today): ?array
    {
        $status = $member->collectionStatus($today);

        $list = match (true) {
            $status['state'] === 'pending' => 'pending',
            $status['in_due_window'] => 'due',
            default => null,
        };

        if ($list === null) {
            return null;
        }

        $months = collect($member->collectableMonths($today));
        $listed = $list === 'pending' ? $months->where('status', 'pending') : $months->take(1);
        $first = $listed->first();

        $dueDate = $member->chitGroup->dateForMonth($first['month']);

        return [
            'list' => $list,
            'member' => $member,
            'name' => $member->customer->name,
            'group_id' => $member->chit_group_id,
            'group' => $member->chitGroup->name,
            'months' => $listed->pluck('month')->all(),
            'part_paid' => $listed->sum('paid'),
            'due_date' => $dueDate->toDateString(),
            /* whole days between the plain dates (the two may be in different time zones) */
            'days_overdue' => $list === 'pending'
                ? (int) Carbon::parse($dueDate->toDateString())->diffInDays(Carbon::parse($today->toDateString()))
                : 0,
            'amount' => (int) ($list === 'pending' ? $status['pending'] : $status['amount_due']),
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ChitGroupMember;
use App\Models\FinanceCollection;
use App\Models\FinanceLoan;
use App\Models\Payment;
use App\Models\User;
use App\Support\ReportPdf as Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Agent Statement (SN Chit Funds and Sri Lakshmi Micro Finance): for one
 * staff member and a period, the customers they brought in (chit seats /
 * loans, with what is collected and left), every receipt they collected,
 * the day-by-day totals and the month-by-month totals. The business comes
 * from the route (finance.* → Micro Finance, otherwise Chit Funds).
 */
class AgentStatementController extends Controller
{
    public function show(Request $request): View
    {
        return view('reports.agent-statement', $this->build($request));
    }

    public function printReport(Request $request): View
    {
        return view('reports.agent-statement-print', $this->build($request));
    }

    public function pdf(Request $request): Response
    {
        $data = $this->build($request);

        return Pdf::loadView('pdf.agent-statement', $data)
            ->setPaper('a4', 'portrait')
            ->download(Str::slug($data['companyName']).'-Agent-Statement-'.Str::slug($data['agent']->name).'-'.$data['filters']['from'].'-to-'.$data['filters']['to'].'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function build(Request $request): array
    {
        $isFinance = $request->routeIs('finance.*');
        $staff = User::query()->orderBy('name')->get(['id', 'name']);
        $agent = $staff->firstWhere('id', $request->integer('agent')) ?? $staff->firstWhere('id', $request->user()->id) ?? $staff->first();

        [$from, $to, $rangeKey] = ExpenseController::dateRange($request, 'month');
        $filters = ['q' => '', 'from' => $from, 'to' => $to, 'range' => $rangeKey];

        [$joined, $collections] = $isFinance
            ? [$this->financeJoined($agent, $from, $to), $this->financeCollections($agent, $from, $to)]
            : [$this->chitJoined($agent, $from, $to), $this->chitCollections($agent, $from, $to)];

        $days = $collections
            ->groupBy(fn (array $row) => $row['at']->toDateString())
            ->map(fn (Collection $rows, string $date) => [
                'date' => Carbon::parse($date),
                'receipts' => $rows->count(),
                'amount' => (int) $rows->sum('amount'),
                'rows' => $rows->values(),
            ])
            ->sortKeys()
            ->values();

        $monthKeys = $joined->map(fn (array $row) => $row['date']->format('Y-m'))
            ->concat($collections->map(fn (array $row) => $row['at']->format('Y-m')))
            ->unique()
            ->sort()
            ->values();

        $months = $monthKeys->map(fn (string $month) => [
            'month' => Carbon::parse($month.'-01'),
            'joined' => $joined->filter(fn (array $row) => $row['date']->format('Y-m') === $month)->count(),
            'joined_amount' => (int) $joined->filter(fn (array $row) => $row['date']->format('Y-m') === $month)->sum('amount'),
            'receipts' => $collections->filter(fn (array $row) => $row['at']->format('Y-m') === $month)->count(),
            'collected' => (int) $collections->filter(fn (array $row) => $row['at']->format('Y-m') === $month)->sum('amount'),
        ]);

        return [
            'companyName' => $isFinance ? 'Micro Finance' : 'Chit Funds',
            'isFinance' => $isFinance,
            'routePrefix' => $isFinance ? 'finance.reports.agent' : 'reports.agent',
            'staff' => $staff,
            'agent' => $agent,
            'filters' => $filters,
            'ranges' => ExpenseController::quickRanges(),
            'query' => ['agent' => $agent->id] + ($rangeKey !== '' ? ['range' => $rangeKey] : ['from' => $from, 'to' => $to]),
            'joined' => $joined,
            'collections' => $collections,
            'days' => $days,
            'months' => $months,
            'summary' => [
                'joined' => $joined->count(),
                'customers' => $joined->pluck('customer.id')->unique()->count(),
                'joined_amount' => (int) $joined->sum('amount'),
                'given' => (int) $joined->sum('given'),
                'joined_collected' => (int) $joined->sum('collected'),
                'left' => (int) $joined->sum('left'),
                'receipts' => $collections->count(),
                'collected' => (int) $collections->sum('amount'),
            ],
        ];
    }

    /**
     * Loans given in the period that the agent brought in.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function financeJoined(User $agent, string $from, string $to): Collection
    {
        return FinanceLoan::query()
            ->with(['customer', 'collections'])
            ->where('agent_id', $agent->id)
            ->whereBetween('loaned_on', [$from.' 00:00:00', $to.' 23:59:59'])
            ->orderBy('loaned_on')
            ->orderBy('id')
            ->get()
            ->toBase()
            ->map(fn (FinanceLoan $loan) => [
                'date' => Carbon::parse($loan->loaned_on->toDateString()),
                'customer' => $loan->customer,
                'reference' => $loan->loan_number,
                'link' => route('finance.loans.show', $loan),
                'amount' => $loan->principal,
                'given' => $loan->amountGiven(),
                'collected' => $loan->collected(),
                'left' => $loan->balance(),
                'state' => $loan->standing()['state'],
            ]);
    }

    /**
     * Money the agent collected on loans in the period.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function financeCollections(User $agent, string $from, string $to): Collection
    {
        return FinanceCollection::query()
            ->with(['customer', 'loan'])
            ->where('collected_by', $agent->id)
            ->whereBetween('collected_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->orderBy('collected_at')
            ->orderBy('id')
            ->get()
            ->toBase()
            ->map(fn (FinanceCollection $collection) => [
                'at' => $collection->collected_at,
                'number' => $collection->receipt_number,
                'link' => route('finance.collections.show', $collection),
                'customer' => $collection->customer,
                'reference' => $collection->loan->loan_number,
                'method' => $collection->methodLabel(),
                'amount' => $collection->amount,
            ]);
    }

    /**
     * Chit seats added in the period that the agent brought in.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function chitJoined(User $agent, string $from, string $to): Collection
    {
        $timezone = config('app.business_timezone');

        return ChitGroupMember::query()
            ->with(['customer', 'chitGroup', 'allocations'])
            ->where('agent_id', $agent->id)
            ->whereBetween('created_at', [
                Carbon::parse($from, $timezone)->startOfDay()->utc(),
                Carbon::parse($to, $timezone)->endOfDay()->utc(),
            ])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->toBase()
            ->map(function (ChitGroupMember $member) use ($timezone) {
                $status = $member->chitGroup->isForming() ? null : $member->collectionStatus();

                return [
                    'date' => Carbon::parse($member->created_at->timezone($timezone)->toDateString()),
                    'customer' => $member->customer,
                    'reference' => $member->chitGroup->name.' · '.$member->member_code,
                    'link' => route('groups.show', $member->chitGroup),
                    'amount' => $member->chitGroup->amount,
                    'given' => 0,
                    'collected' => $member->totalPaid(),
                    'left' => $status ? $member->balanceDue() : 0,
                    'state' => $status['state'] ?? 'forming',
                ];
            });
    }

    /**
     * Chit payments the agent collected in the period.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function chitCollections(User $agent, string $from, string $to): Collection
    {
        return Payment::query()
            ->with(['customer', 'chitGroup', 'member'])
            ->where('collected_by', $agent->id)
            ->whereBetween('paid_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->orderBy('paid_at')
            ->orderBy('id')
            ->get()
            ->toBase()
            ->map(fn (Payment $payment) => [
                'at' => $payment->paid_at,
                'number' => $payment->receipt_number,
                'link' => route('payments.show', $payment),
                'customer' => $payment->customer,
                'reference' => $payment->chitGroup->name.' · '.$payment->member->member_code,
                'method' => $payment->methodLabel(),
                'amount' => $payment->amount,
            ]);
    }
}

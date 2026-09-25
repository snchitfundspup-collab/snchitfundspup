<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use App\Models\Customer;
use App\Models\Payment;
use App\Support\ReportPdf as Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * Seats per page on the Collect Payment list.
     */
    private const COLLECT_PER_PAGE = 20;

    /**
     * The most rows a printed / PDF payment list holds; narrow the filters
     * for more.
     */
    private const REPORT_LIMIT = 3000;

    /**
     * All Payments: today's receipts by default. Older payments are only
     * loaded when asked for with the date range (quick ranges or from / to),
     * group, method or search filters. Shows the filtered total, split by
     * method, and can print / download the whole filtered list.
     */
    public function index(Request $request): View
    {
        $filters = $this->paymentFilters($request);

        $payments = $this->filteredPayments($filters)
            ->with(['customer', 'chitGroup', 'member', 'allocations'])
            ->latest('paid_at')
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('payments.index', [
            'payments' => $payments,
            'filters' => $filters,
            'summary' => $this->paymentSummary($filters),
            'groups' => $this->groupsForFilter(),
            'ranges' => $this->quickRanges(),
        ]);
    }

    /**
     * Printable list of every payment matching the filters (not just one page).
     */
    public function printList(Request $request): View
    {
        $filters = $this->paymentFilters($request);

        return view('payments.print', [
            'filters' => $filters,
            'summary' => $this->paymentSummary($filters),
            'payments' => $this->reportRows($filters),
            'limit' => self::REPORT_LIMIT,
        ]);
    }

    /**
     * Download the filtered payment list as an A4 PDF.
     */
    public function listPdf(Request $request): Response
    {
        $filters = $this->paymentFilters($request);

        $fileName = 'Payments-'.$filters['from'].($filters['from'] === $filters['to'] ? '' : '-to-'.$filters['to']).'.pdf';

        return Pdf::loadView('pdf.payments', [
            'filters' => $filters,
            'summary' => $this->paymentSummary($filters),
            'payments' => $this->reportRows($filters),
            'limit' => self::REPORT_LIMIT,
        ])
            ->setPaper('a4', 'portrait')
            ->download($fileName);
    }

    /**
     * The filters from the query string, defaulting to today. An exact
     * receipt number (RC000123) is found whatever the dates.
     *
     * @return array{q: string, method: string, group: ?int, from: string, to: string, range: string, receipt_lookup: bool, group_name: ?string}
     */
    private function paymentFilters(Request $request): array
    {
        $today = today(config('app.business_timezone'));
        $ranges = $this->quickRanges();

        $range = array_key_exists((string) $request->input('range'), $ranges) ? (string) $request->input('range') : '';

        if ($range !== '') {
            [$from, $to] = [$ranges[$range]['from'], $ranges[$range]['to']];
        } else {
            $from = $request->date('from')?->toDateString() ?? $request->date('date')?->toDateString() ?? $today->toDateString();
            $to = $request->date('to')?->toDateString() ?? ($request->filled('date') ? $from : $today->toDateString());

            if ($from > $to) {
                [$from, $to] = [$to, $from];
            }

            /* name the range when the dates match a quick range */
            foreach ($ranges as $key => $quick) {
                if ($quick['from'] === $from && $quick['to'] === $to) {
                    $range = $key;
                    break;
                }
            }
        }

        $search = trim((string) $request->input('q', ''));
        $groupId = $request->integer('group') ?: null;

        return [
            'q' => $search,
            'method' => array_key_exists((string) $request->input('method'), Payment::METHODS) ? (string) $request->input('method') : '',
            'group' => $groupId,
            'group_name' => $groupId ? ChitGroup::whereKey($groupId)->value('name') : null,
            'from' => $from,
            'to' => $to,
            'range' => $range,
            'receipt_lookup' => (bool) preg_match('/^RC\d{6}$/i', $search),
        ];
    }

    /**
     * @param  array{q: string, method: string, group: ?int, from: string, to: string, receipt_lookup: bool}  $filters
     * @return Builder<Payment>
     */
    private function filteredPayments(array $filters): Builder
    {
        $search = $filters['q'];

        return Payment::query()
            /* plain range on paid_at so the index is used */
            ->when(! $filters['receipt_lookup'], fn ($query) => $query->whereBetween('paid_at', [
                $filters['from'].' 00:00:00',
                $filters['to'].' 23:59:59',
            ]))
            ->when($filters['receipt_lookup'], fn ($query) => $query->where('receipt_number', strtoupper($search)))
            ->when($search !== '' && ! $filters['receipt_lookup'], function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('receipt_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->search($search))
                        ->orWhereHas('chitGroup', fn ($group) => $group->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['method'] !== '', fn ($query) => $query->where('method', $filters['method']))
            ->when($filters['group'], fn ($query) => $query->where('chit_group_id', $filters['group']));
    }

    /**
     * Total, receipt count and the split by method for the filtered payments.
     *
     * @param  array<string, mixed>  $filters
     * @return array{total: int, count: int, by_method: array<string, array{amount: int, count: int}>}
     */
    private function paymentSummary(array $filters): array
    {
        $rows = $this->filteredPayments($filters)
            ->toBase()
            ->selectRaw('method, SUM(amount) as amount, COUNT(*) as receipts')
            ->groupBy('method')
            ->get();

        $byMethod = [];

        foreach (Payment::METHODS as $method => $label) {
            $row = $rows->firstWhere('method', $method);

            if ($row) {
                $byMethod[$method] = ['amount' => (int) $row->amount, 'count' => (int) $row->receipts];
            }
        }

        return [
            'total' => (int) $rows->sum('amount'),
            'count' => (int) $rows->sum('receipts'),
            'by_method' => $byMethod,
        ];
    }

    /**
     * Every filtered payment for printing, oldest first (up to the limit).
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Payment>
     */
    private function reportRows(array $filters): Collection
    {
        return $this->filteredPayments($filters)
            ->with(['customer', 'chitGroup', 'member', 'allocations'])
            ->oldest('paid_at')
            ->oldest('id')
            ->limit(self::REPORT_LIMIT)
            ->get();
    }

    /**
     * Groups that can have payments, for the group filter.
     *
     * @return Collection<int, ChitGroup>
     */
    private function groupsForFilter(): Collection
    {
        return ChitGroup::query()
            ->whereIn('status', [ChitGroup::STATUS_RUNNING, ChitGroup::STATUS_COMPLETED])
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Quick date ranges in office time.
     *
     * @return array<string, array{label: string, i18n: string, from: string, to: string}>
     */
    private function quickRanges(): array
    {
        $today = today(config('app.business_timezone'));

        return [
            'today' => ['label' => 'Today', 'i18n' => 'range_today', 'from' => $today->toDateString(), 'to' => $today->toDateString()],
            'yesterday' => ['label' => 'Yesterday', 'i18n' => 'range_yesterday', 'from' => $today->copy()->subDay()->toDateString(), 'to' => $today->copy()->subDay()->toDateString()],
            'week' => ['label' => 'This week', 'i18n' => 'range_week', 'from' => $today->copy()->startOfWeek()->toDateString(), 'to' => $today->toDateString()],
            'month' => ['label' => 'This month', 'i18n' => 'range_month', 'from' => $today->copy()->startOfMonth()->toDateString(), 'to' => $today->toDateString()],
            'last_month' => ['label' => 'Last month', 'i18n' => 'range_last_month', 'from' => $today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), 'to' => $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()],
        ];
    }

    /**
     * Collect Payment: a Pending tab (months past their due date) and a Due
     * tab (months in their due window, e.g. 1 – 15 Sep for 15 Sep); a
     * search finds every matching seat whatever its status → pick one →
     * form.
     */
    public function create(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));
        $tab = $request->input('tab') === 'due' ? 'due' : 'pending';

        /* remember the tab so the receipt's "Next member" returns to it */
        if (! $request->filled('customer') && $search === '') {
            $request->session()->put('collect_tab', $tab);
        }

        $customer = $request->filled('customer')
            ? Customer::with([
                'memberships' => fn ($query) => $query->orderBy('id'),
                'memberships.chitGroup',
                'memberships.allocations',
            ])->find($request->integer('customer'))
            : null;

        $seats = $customer === null ? $this->runningSeats($search) : collect();

        $tabCounts = [
            'pending' => $seats->filter(fn (ChitGroupMember $seat) => $this->isOnTab($seat, 'pending'))->count(),
            'due' => $seats->filter(fn (ChitGroupMember $seat) => $this->isOnTab($seat, 'due'))->count(),
        ];

        $listed = $search === ''
            ? $seats->filter(fn (ChitGroupMember $seat) => $this->isOnTab($seat, $tab))->values()
            : $seats;

        $page = max(1, $request->integer('page', 1));

        $results = (new LengthAwarePaginator(
            $listed->forPage($page, self::COLLECT_PER_PAGE)->values(),
            $listed->count(),
            self::COLLECT_PER_PAGE,
            $page,
            ['path' => route('payments.create')],
        ))->appends(array_filter(['q' => $search, 'tab' => $search === '' && $tab === 'due' ? 'due' : null]));

        $member = $customer?->memberships->firstWhere('id', $request->integer('member'));

        /* a customer with a single seat goes straight to the form */
        if ($customer && $member === null && $customer->memberships->count() === 1) {
            $member = $customer->memberships->first();
        }

        if ($member && ! $member->chitGroup->isRunning()) {
            $member = null;
        }

        return view('payments.create', compact('search', 'tab', 'tabCounts', 'customer', 'results', 'member'));
    }

    /**
     * Pending tab: a month past its due date. Due tab: not pending, and the
     * next month is in its due window.
     */
    private function isOnTab(ChitGroupMember $seat, string $tab): bool
    {
        $status = $seat->collection_status;

        return $tab === 'pending'
            ? $status['state'] === 'pending'
            : $status['state'] !== 'pending' && $status['in_due_window'];
    }

    /**
     * Seats in running groups with their collection status, matching the
     * search when there is one (customer name / ID / phone / identification,
     * member ID or group name). Pending first, then due, then by name.
     *
     * @return Collection<int, ChitGroupMember>
     */
    private function runningSeats(string $search): Collection
    {
        $order = ['pending' => 0, 'due' => 1, 'partial' => 1, 'upcoming' => 2, 'clear' => 3];

        return ChitGroupMember::query()
            ->whereHas('chitGroup', fn ($group) => $group->where('status', ChitGroup::STATUS_RUNNING))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->whereHas('customer', fn ($customer) => $customer->search($search))
                        ->orWhereHas('chitGroup', fn ($group) => $group->where('name', 'like', "%{$search}%"))
                        ->orWhere('member_code', 'like', "%{$search}%");
                });
            })
            ->with([
                'customer',
                'chitGroup',
                'allocations' => fn ($query) => ChitGroupMember::monthTotalsOnly($query),
            ])
            ->get()
            ->each(fn (ChitGroupMember $seat) => $seat->setAttribute('collection_status', $seat->collectionStatus()))
            ->sortBy([
                fn (ChitGroupMember $a, ChitGroupMember $b) => $order[$a->collection_status['state']] <=> $order[$b->collection_status['state']],
                fn (ChitGroupMember $a, ChitGroupMember $b) => strcasecmp($a->customer->name, $b->customer->name),
            ])
            ->values();
    }

    /**
     * Save a payment and open its receipt.
     */
    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $payment = Payment::record($request->member(), $request->validated(), $request->user());

        return redirect()
            ->route('payments.show', $payment)
            ->with('success', "Payment saved. Receipt {$payment->receipt_number}.");
    }

    /**
     * Receipt page (printable).
     */
    public function show(Payment $payment): View
    {
        $payment->load(['customer', 'chitGroup', 'member.allocations', 'allocations', 'recorder']);

        $payment->member->setRelation('chitGroup', $payment->chitGroup);

        return view('payments.show', compact('payment'));
    }

    /**
     * Download the receipt as a PDF (A5 portrait).
     */
    public function receiptPdf(Payment $payment): Response
    {
        $payment->load(['customer', 'chitGroup', 'member.allocations', 'allocations', 'recorder']);

        $payment->member->setRelation('chitGroup', $payment->chitGroup);

        return Pdf::loadView('pdf.receipt', [
            'payment' => $payment,
            'balanceNow' => $payment->member->balanceDue(),
            'hasPending' => $payment->member->collectionStatus()['pending'] > 0,
        ])
            ->setPaper('a5', 'portrait')
            ->download("Receipt-{$payment->receipt_number}.pdf");
    }

    /**
     * Cancel a receipt entered by mistake: the money is taken off the
     * months it covered.
     */
    public function destroy(Payment $payment): RedirectResponse
    {
        $receiptNumber = $payment->receipt_number;
        $customerId = $payment->customer_id;
        $memberId = $payment->chit_group_member_id;

        DB::transaction(fn () => $payment->delete());

        return redirect()
            ->route('payments.create', ['customer' => $customerId, 'member' => $memberId])
            ->with('success', "Receipt {$receiptNumber} cancelled.");
    }
}

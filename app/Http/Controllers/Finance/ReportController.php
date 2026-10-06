<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ExpenseController;
use App\Models\Customer;
use App\Models\FinanceCollection;
use App\Models\FinanceExpense;
use App\Models\FinanceLoan;
use App\Support\ReportPdf as Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Reports (Sri Lakshmi Micro Finance): outstanding loans, overdue loans,
 * the day book (money lent and collected each day), profit & loss
 * (processing fees on loans given plus the interest inside the money
 * collected, less expenses, all kept by the business; GST is kept apart as it
 * is owed to the government) and each customer's statement. Each shows on
 * screen, prints and downloads as a PDF.
 */
class ReportController extends Controller
{
    /**
     * Report key => title and default date range (null: as on today).
     *
     * @var array<string, array{title: string, range: ?string}>
     */
    public const REPORTS = [
        'outstanding' => ['title' => 'Outstanding Loans', 'range' => null],
        'overdue' => ['title' => 'Overdue Loans', 'range' => null],
        'day-book' => ['title' => 'Day Book', 'range' => 'month'],
        'profit' => ['title' => 'Profit & Loss', 'range' => 'month'],
    ];

    private const SEARCH_LIMIT = 30;

    public function show(Request $request, string $report): View
    {
        return view('finance.reports.show', $this->build($request, $report));
    }

    public function printReport(Request $request, string $report): View
    {
        return view('finance.reports.print', $this->build($request, $report));
    }

    public function pdf(Request $request, string $report): Response
    {
        $data = $this->build($request, $report);

        $period = $data['filters']
            ? $data['filters']['from'].'-to-'.$data['filters']['to']
            : $data['today']->toDateString();

        return Pdf::loadView('pdf.finance.report', $data)
            ->setPaper('a4', in_array($report, ['outstanding', 'day-book'], true) ? 'landscape' : 'portrait')
            ->download('Micro-Finance-'.Str::slug(str_replace('&', 'and', $data['title'])).'-'.$period.'.pdf');
    }

    /**
     * Find a customer who has had a loan, then open their statement.
     */
    public function customerStatement(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $customers = Customer::query()
            ->whereHas('financeLoans')
            ->when($search !== '', fn ($query) => $query->search($search))
            ->withMax('financeLoans as last_loan', 'loaned_on')
            ->withCount(['financeLoans as running_loans' => fn ($query) => $query->active()])
            ->orderByDesc('last_loan')
            ->orderBy('name')
            ->limit(self::SEARCH_LIMIT)
            ->get();

        return view('finance.reports.customer-statement', ['search' => $search, 'customers' => $customers]);
    }

    public function account(Customer $customer): View
    {
        return view('finance.account', self::accountData($customer));
    }

    public function accountPrint(Customer $customer): View
    {
        return view('finance.account-print', self::accountData($customer));
    }

    public function accountPdf(Customer $customer): Response
    {
        return Pdf::loadView('pdf.finance.account', self::accountData($customer))
            ->setPaper('a4', 'portrait')
            ->download('Loan-Statement-'.$customer->customer_code.'.pdf');
    }

    /**
     * A customer's loans (newest first) with every collection, and totals.
     *
     * @return array<string, mixed>
     */
    public static function accountData(Customer $customer): array
    {
        $loans = $customer->financeLoans()
            ->with(['collections'])
            ->latest('loaned_on')
            ->latest('id')
            ->get()
            ->map(fn (FinanceLoan $loan) => ['loan' => $loan, 'standing' => $loan->standing()]);

        return [
            'companyName' => 'Micro Finance',
            'customer' => $customer,
            'loans' => $loans,
            'totals' => [
                'given' => (int) $loans->sum(fn (array $row) => $row['loan']->amountGiven()),
                'repayable' => (int) $loans->sum(fn (array $row) => $row['loan']->loan_amount),
                'collected' => (int) $loans->sum(fn (array $row) => $row['loan']->collected()),
                'balance' => (int) $loans->sum(fn (array $row) => $row['loan']->balance()),
                'overdue' => (int) $loans->sum('standing.overdue'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function build(Request $request, string $report): array
    {
        $range = self::REPORTS[$report]['range'];
        $filters = null;

        if ($range !== null) {
            [$from, $to, $rangeKey] = ExpenseController::dateRange($request, $range);
            $filters = ['q' => '', 'from' => $from, 'to' => $to, 'range' => $rangeKey];
        }

        $data = match ($report) {
            'outstanding' => $this->outstanding(),
            'overdue' => $this->overdue(),
            'day-book' => $this->dayBook($filters),
            'profit' => $this->profit($filters),
        };

        return [
            'companyName' => 'Micro Finance',
            'report' => $report,
            'title' => self::REPORTS[$report]['title'],
            'filters' => $filters,
            'ranges' => ExpenseController::quickRanges(),
            'today' => today(config('app.business_timezone')),
        ] + $data;
    }

    /**
     * Every running loan: given, to repay, collected, balance, overdue.
     *
     * @return array<string, mixed>
     */
    private function outstanding(): array
    {
        $rows = CollectionController::runningLoans()
            ->sortBy([
                fn (array $a, array $b) => strcmp($a['loan']->customer->name, $b['loan']->customer->name),
                fn (array $a, array $b) => $a['loan']->id <=> $b['loan']->id,
            ])
            ->values();

        return [
            'rows' => $rows,
            'summary' => [
                'loans' => $rows->count(),
                'customers' => $rows->pluck('loan.customer_id')->unique()->count(),
                'repayable' => (int) $rows->sum('loan.loan_amount'),
                'collected' => (int) $rows->sum(fn (array $row) => $row['loan']->collected()),
                'balance' => (int) $rows->sum(fn (array $row) => $row['loan']->balance()),
                'overdue' => (int) $rows->sum('standing.overdue'),
            ],
        ];
    }

    /**
     * Running loans with overdue instalments, longest overdue first, with
     * the last time each paid.
     *
     * @return array<string, mixed>
     */
    private function overdue(): array
    {
        $rows = CollectionController::runningLoans()->where('standing.state', 'overdue')->values();

        $lastPaid = FinanceCollection::query()->toBase()
            ->whereIn('finance_loan_id', $rows->pluck('loan.id'))
            ->selectRaw('finance_loan_id, MAX(collected_at) as last_paid')
            ->groupBy('finance_loan_id')
            ->pluck('last_paid', 'finance_loan_id');

        return [
            'rows' => $rows->map(fn (array $row) => $row + [
                'last_paid' => isset($lastPaid[$row['loan']->id]) ? substr((string) $lastPaid[$row['loan']->id], 0, 10) : null,
            ]),
            'summary' => [
                'loans' => $rows->count(),
                'overdue' => (int) $rows->sum('standing.overdue'),
                'balance' => (int) $rows->sum(fn (array $row) => $row['loan']->balance()),
            ],
        ];
    }

    /**
     * Day Book: each day's money lent (in hand), interest / charges cut and
     * money collected, with every entry.
     *
     * @param  array{from: string, to: string}  $filters
     * @return array<string, mixed>
     */
    private function dayBook(array $filters): array
    {
        $between = [$filters['from'].' 00:00:00', $filters['to'].' 23:59:59'];

        $loans = FinanceLoan::query()->with('customer')->whereBetween('loaned_on', $between)->orderBy('loaned_on')->orderBy('id')->get();
        $collections = FinanceCollection::query()->with(['customer', 'loan'])->whereBetween('collected_at', $between)->orderBy('collected_at')->orderBy('id')->get();

        $entries = $loans->map(fn (FinanceLoan $loan) => [
            'date' => $loan->loaned_on->toDateString(),
            'sort' => $loan->loaned_on->toDateString().' 00:00:00|a'.str_pad((string) $loan->id, 10, '0', STR_PAD_LEFT),
            'type' => 'loan',
            'number' => $loan->loan_number,
            'customer' => $loan->customer,
            'details' => __('Loan').' '.number_format($loan->principal).' · '.__('fee').' '.number_format($loan->processing_fee).' · '.__('GST').' '.number_format($loan->gst),
            'out' => $loan->amountGiven(),
            'in' => 0,
            'cut' => $loan->charges(),
        ])->concat($collections->map(fn (FinanceCollection $collection) => [
            'date' => $collection->collected_at->toDateString(),
            'sort' => $collection->collected_at->format('Y-m-d H:i:s').'|b'.str_pad((string) $collection->id, 10, '0', STR_PAD_LEFT),
            'type' => 'collection',
            'number' => $collection->receipt_number,
            'customer' => $collection->customer,
            'details' => $collection->loan->loan_number.' · '.$collection->methodLabel(),
            'out' => 0,
            'in' => $collection->amount,
            'cut' => 0,
        ]))->sortBy('sort')->values();

        $days = $entries->groupBy('date')->map(fn (Collection $dayEntries, string $date) => [
            'date' => $date,
            'out' => (int) $dayEntries->sum('out'),
            'in' => (int) $dayEntries->sum('in'),
            'cut' => (int) $dayEntries->sum('cut'),
            'loans' => $dayEntries->where('type', 'loan')->count(),
            'collections' => $dayEntries->where('type', 'collection')->count(),
        ])->values();

        return [
            'entries' => $entries,
            'days' => $days,
            'summary' => [
                'out' => (int) $entries->sum('out'),
                'in' => (int) $entries->sum('in'),
                'cut' => (int) $entries->sum('cut'),
                'net' => (int) $entries->sum('in') - (int) $entries->sum('out'),
                'loans' => $loans->count(),
                'collections' => $collections->count(),
            ],
        ];
    }

    /**
     * Profit & Loss: processing fees on loans given in the period plus the
     * interest inside the money collected in the period (each collection
     * pays loan and interest in the same proportion as the loan's total),
     * less the period's expenses; the business keeps the whole net profit.
     * GST on the fees is shown apart — it is owed to the government. Money
     * lent and collected are shown for reference.
     *
     * @param  array{from: string, to: string}  $filters
     * @return array<string, mixed>
     */
    private function profit(array $filters): array
    {
        $between = [$filters['from'].' 00:00:00', $filters['to'].' 23:59:59'];

        $loans = FinanceLoan::query()->whereBetween('loaned_on', $between);
        $fees = (int) (clone $loans)->sum('processing_fee');

        $collections = FinanceCollection::query()->with('loan')->whereBetween('collected_at', $between)->get();
        $interest = (int) round($collections->sum(fn (FinanceCollection $collection) => $collection->loan->interestIn($collection->amount)));

        $income = $fees + $interest;

        $expenses = FinanceExpense::query()->whereBetween('spent_on', $between);
        $expenseTotal = (int) (clone $expenses)->sum('amount');

        $net = $income - $expenseTotal;

        $running = CollectionController::runningLoans();

        return [
            'summary' => [
                'fees' => $fees,
                'interest' => $interest,
                'income' => $income,
                'loans' => (clone $loans)->count(),
                'expenses' => $expenseTotal,
                'expense_count' => (clone $expenses)->count(),
                'net' => $net,
                'gst' => (int) (clone $loans)->sum('gst'),
                'lent' => (int) (clone $loans)->sum('principal') - $fees - (int) (clone $loans)->sum('gst'),
                'collected' => (int) $collections->sum('amount'),
                'outstanding' => (int) $running->sum(fn (array $row) => $row['loan']->balance()),
                'overdue' => (int) $running->sum('standing.overdue'),
            ],
        ];
    }
}

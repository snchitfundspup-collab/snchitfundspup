<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ExpenseController;
use App\Http\Requests\Finance\StoreCollectionRequest;
use App\Models\FinanceCollection;
use App\Models\FinanceLoan;
use App\Support\ReportPdf as Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Collections (Sri Lakshmi Micro Finance): the loans to collect today —
 * Overdue (red) and Due today (orange) — recording the money with a
 * receipt, and the list of all collections.
 */
class CollectionController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * Collect tabs: overdue, due today, every running loan.
     *
     * @var list<string>
     */
    public const TABS = ['overdue', 'due', 'all'];

    public function collect(Request $request): View
    {
        $tab = in_array($request->input('tab'), self::TABS, true) ? $request->input('tab') : null;
        $search = trim((string) $request->input('q', ''));

        $rows = self::runningLoans()
            ->when($search !== '', function (Collection $rows) use ($search) {
                $needle = mb_strtolower($search);

                return $rows->filter(function (array $row) use ($needle) {
                    $customer = $row['loan']->customer;

                    return str_contains(mb_strtolower(implode(' ', [$row['loan']->loan_number, $customer->customer_code, $customer->name, $customer->phone, $customer->remarks])), $needle);
                });
            });

        $counts = [
            'overdue' => $rows->where('standing.state', 'overdue')->count(),
            'due' => $rows->where('standing.state', 'due')->count(),
            'all' => $rows->count(),
        ];

        /* open on Overdue when there is any, else Due today; search looks everywhere */
        $tab ??= $search !== '' ? 'all' : ($counts['overdue'] > 0 ? 'overdue' : 'due');

        return view('finance.collect', [
            'rows' => $tab === 'all' ? $rows->values() : $rows->where('standing.state', $tab)->values(),
            'tab' => $tab,
            'search' => $search,
            'counts' => $counts,
            'totals' => [
                'overdue' => (int) $rows->sum('standing.overdue'),
                'due' => (int) $rows->sum('standing.due_today'),
            ],
        ]);
    }

    public function store(StoreCollectionRequest $request, FinanceLoan $loan): RedirectResponse
    {
        $collection = FinanceCollection::record($loan, $request->validated(), $request->user());

        return redirect()
            ->route('finance.collections.show', $collection)
            ->with('success', "Receipt {$collection->receipt_number} saved.".($loan->refresh()->isClosed() ? " Loan {$loan->loan_number} is fully paid and closed." : ''));
    }

    public function index(Request $request): View
    {
        [$from, $to, $range] = ExpenseController::dateRange($request, 'today');
        $search = trim((string) $request->input('q', ''));

        $query = FinanceCollection::query()
            ->whereBetween('collected_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('receipt_number', 'like', "%{$search}%")
                    ->orWhereHas('loan', fn ($loan) => $loan->where('loan_number', 'like', "%{$search}%"))
                    ->orWhereHas('customer', fn ($customer) => $customer->search($search));
            }));

        return view('finance.collections.index', [
            'collections' => (clone $query)->with(['customer', 'loan'])->latest('collected_at')->latest('id')->paginate(self::PER_PAGE)->withQueryString(),
            'total' => (int) (clone $query)->sum('amount'),
            'count' => (clone $query)->count(),
            'filters' => ['q' => $search, 'from' => $from, 'to' => $to, 'range' => $range],
            'ranges' => ExpenseController::quickRanges(),
        ]);
    }

    public function show(FinanceCollection $collection): View
    {
        return view('finance.collections.show', self::receiptData($collection));
    }

    public function pdf(FinanceCollection $collection): Response
    {
        return Pdf::loadView('pdf.finance.receipt', self::receiptData($collection))
            ->setPaper('a5', 'portrait')
            ->download("Receipt-{$collection->receipt_number}.pdf");
    }

    /**
     * Cancel a receipt entered by mistake; a closed loan opens again.
     */
    public function destroy(FinanceCollection $collection): RedirectResponse
    {
        $loan = $collection->loan;
        $number = $collection->receipt_number;

        $collection->delete();
        $loan->refreshStatus();

        return redirect()
            ->route('finance.loans.show', $loan)
            ->with('success', "Receipt {$number} cancelled.");
    }

    /**
     * Every running loan with its customer and where it stands today, the
     * longest overdue first, then due today, then the rest.
     *
     * @return Collection<int, array{loan: FinanceLoan, standing: array<string, mixed>}>
     */
    public static function runningLoans(): Collection
    {
        $order = ['overdue' => 0, 'due' => 1, 'on_track' => 2, 'not_started' => 3, 'closed' => 4];

        return FinanceLoan::query()
            ->active()
            ->with('customer')
            ->withSum('collections as collected_total', 'amount')
            ->get()
            ->map(fn (FinanceLoan $loan) => ['loan' => $loan, 'standing' => $loan->standing()])
            ->sortBy([
                fn (array $a, array $b) => $order[$a['standing']['state']] <=> $order[$b['standing']['state']],
                fn (array $a, array $b) => $b['standing']['days_overdue'] <=> $a['standing']['days_overdue'],
                fn (array $a, array $b) => strcmp($a['loan']->customer->name, $b['loan']->customer->name),
            ])
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public static function receiptData(FinanceCollection $collection): array
    {
        $collection->load(['customer', 'loan', 'recorder']);

        $loan = $collection->loan;

        /* the balance right after this receipt */
        $collectedUpTo = (int) $loan->collections()
            ->where(fn ($query) => $query->where('collected_at', '<', $collection->collected_at)
                ->orWhere(fn ($query) => $query->where('collected_at', $collection->collected_at)->where('id', '<=', $collection->id)))
            ->sum('amount');

        return [
            'collection' => $collection,
            'loan' => $loan,
            'balanceAfter' => max(0, $loan->loan_amount - $collectedUpTo),
            'companyName' => 'Micro Finance',
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Models\Expense;
use App\Models\TraderExpense;
use App\Models\User;
use App\Support\ReportPdf as Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Expenses (Management): business spending paid by the partners. The list
 * opens on this month; date ranges, partner and search narrow it, with
 * totals by partner, print and PDF. The same screens serve SN Chit Funds
 * (expenses table) and SN Traders (trader_expenses, routes traders.*).
 */
class ExpenseController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * The most rows a printed / PDF expense list holds.
     */
    private const REPORT_LIMIT = 3000;

    /**
     * Expense (chit fund) or TraderExpense (SN Traders), set per request.
     *
     * @var class-string<Expense>
     */
    private string $model = Expense::class;

    /**
     * Route names are "expenses.*" or "traders.expenses.*".
     */
    private string $routePrefix = '';

    /**
     * Work out which business the request is for.
     */
    private function forBusinessOf(Request $request): void
    {
        $isTraders = $request->routeIs('traders.*');

        $this->model = $isTraders ? TraderExpense::class : Expense::class;
        $this->routePrefix = $isTraders ? 'traders.' : '';
    }

    /**
     * What every expense view needs to link and title within its business.
     *
     * @return array{routePrefix: string, companyName: string}
     */
    private function businessViewData(): array
    {
        return [
            'routePrefix' => $this->routePrefix,
            'companyName' => $this->routePrefix === 'traders.' ? 'Traders' : 'Chit Funds',
        ];
    }

    private function findExpense(string $expense): Expense
    {
        return $this->model::findOrFail($expense);
    }

    public function index(Request $request): View
    {
        $this->forBusinessOf($request);

        $filters = $this->filters($request);

        return view('expenses.index', [
            ...$this->businessViewData(),
            'expenses' => $this->filtered($filters)
                ->with('payer')
                ->latest('spent_on')
                ->latest('id')
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
            'filters' => $filters,
            'summary' => $this->summary($filters),
            'partners' => User::partners()->get(),
            'ranges' => self::quickRanges(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->forBusinessOf($request);

        return view('expenses.form', [
            ...$this->businessViewData(),
            'expense' => new $this->model([
                'spent_on' => today(config('app.business_timezone')),
                'method' => 'cash',
                'paid_by' => auth()->user()?->is_partner ? auth()->id() : null,
            ]),
            'partners' => User::partners()->get(),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $this->forBusinessOf($request);

        $expense = $this->model::create([...$request->validated(), 'recorded_by' => $request->user()->id]);

        return redirect()
            ->route($this->routePrefix.($request->boolean('add_another') ? 'expenses.create' : 'expenses.index'), $request->boolean('add_another') ? [] : ['range' => 'month'])
            ->with('success', 'Expense of ₹'.number_format($expense->amount).' saved.');
    }

    public function edit(Request $request, string $expense): View
    {
        $this->forBusinessOf($request);

        return view('expenses.form', [
            ...$this->businessViewData(),
            'expense' => $this->findExpense($expense),
            'partners' => User::partners()->get(),
        ]);
    }

    public function update(StoreExpenseRequest $request, string $expense): RedirectResponse
    {
        $this->forBusinessOf($request);

        $expense = $this->findExpense($expense);
        $expense->update($request->validated());

        return redirect()
            ->route($this->routePrefix.'expenses.index', ['from' => $expense->spent_on->toDateString(), 'to' => $expense->spent_on->toDateString()])
            ->with('success', 'Expense updated.');
    }

    public function destroy(Request $request, string $expense): RedirectResponse
    {
        $this->forBusinessOf($request);

        $this->findExpense($expense)->delete();

        return redirect()
            ->route($this->routePrefix.'expenses.index')
            ->with('success', 'Expense deleted.');
    }

    /**
     * Printable list of every expense matching the filters.
     */
    public function printList(Request $request): View
    {
        $this->forBusinessOf($request);

        $filters = $this->filters($request);

        return view('expenses.print', [
            ...$this->businessViewData(),
            'filters' => $filters,
            'summary' => $this->summary($filters),
            'expenses' => $this->reportRows($filters),
            'limit' => self::REPORT_LIMIT,
        ]);
    }

    public function pdf(Request $request): Response
    {
        $this->forBusinessOf($request);

        $filters = $this->filters($request);

        return Pdf::loadView('pdf.expenses', [
            ...$this->businessViewData(),
            'filters' => $filters,
            'summary' => $this->summary($filters),
            'expenses' => $this->reportRows($filters),
            'limit' => self::REPORT_LIMIT,
        ])
            ->setPaper('a4', 'portrait')
            ->download(($this->routePrefix === 'traders.' ? 'Traders-' : '').'Expenses-'.$filters['from'].($filters['from'] === $filters['to'] ? '' : '-to-'.$filters['to']).'.pdf');
    }

    /**
     * Quick date ranges in office time; "all" covers every expense.
     *
     * @return array<string, array{label: string, i18n: string, from: string, to: string}>
     */
    public static function quickRanges(): array
    {
        $today = today(config('app.business_timezone'));

        return [
            'today' => ['label' => 'Today', 'i18n' => 'range_today', 'from' => $today->toDateString(), 'to' => $today->toDateString()],
            'month' => ['label' => 'This month', 'i18n' => 'range_month', 'from' => $today->copy()->startOfMonth()->toDateString(), 'to' => $today->toDateString()],
            'last_month' => ['label' => 'Last month', 'i18n' => 'range_last_month', 'from' => $today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), 'to' => $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            'year' => ['label' => 'This year', 'i18n' => 'range_year', 'from' => $today->copy()->startOfYear()->toDateString(), 'to' => $today->toDateString()],
            'all' => ['label' => 'All time', 'i18n' => 'range_all', 'from' => '2000-01-01', 'to' => $today->toDateString()],
        ];
    }

    /**
     * Date range (a quick range, or from / to; default this month), partner
     * and search.
     *
     * @return array{q: string, partner: ?int, partner_name: ?string, from: string, to: string, range: string}
     */
    private function filters(Request $request): array
    {
        [$from, $to, $range] = self::dateRange($request, 'month');

        $partnerId = $request->integer('partner') ?: null;

        return [
            'q' => trim((string) $request->input('q', '')),
            'partner' => $partnerId,
            'partner_name' => $partnerId ? User::whereKey($partnerId)->value('name') : null,
            'from' => $from,
            'to' => $to,
            'range' => $range,
        ];
    }

    /**
     * [from, to, range key] from the request: a quick range wins, else the
     * from / to dates (swapped if reversed), else the default range.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    public static function dateRange(Request $request, string $default): array
    {
        $ranges = self::quickRanges();
        $range = (string) $request->input('range');

        if (! array_key_exists($range, $ranges) && ! $request->filled('from') && ! $request->filled('to')) {
            $range = $default;
        }

        if (array_key_exists($range, $ranges)) {
            return [$ranges[$range]['from'], $ranges[$range]['to'], $range];
        }

        $today = today(config('app.business_timezone'))->toDateString();
        $from = $request->date('from')?->toDateString() ?? $today;
        $to = $request->date('to')?->toDateString() ?? $today;

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        foreach ($ranges as $key => $quick) {
            if ($quick['from'] === $from && $quick['to'] === $to) {
                return [$from, $to, $key];
            }
        }

        return [$from, $to, ''];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Expense>
     */
    private function filtered(array $filters): Builder
    {
        $search = $filters['q'];

        return $this->model::query()
            /* whole days, so it matches however the database stores dates */
            ->whereBetween('spent_on', [$filters['from'].' 00:00:00', $filters['to'].' 23:59:59'])
            ->when($filters['partner'], fn ($query) => $query->where('paid_by', $filters['partner']))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('paid_to', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            });
    }

    /**
     * Total, count and the split by partner.
     *
     * @param  array<string, mixed>  $filters
     * @return array{total: int, count: int, by_partner: Collection<int, array{name: string, amount: int, count: int}>}
     */
    private function summary(array $filters): array
    {
        $byPartner = $this->filtered($filters)
            ->toBase()
            ->selectRaw('paid_by, SUM(amount) as amount, COUNT(*) as items')
            ->groupBy('paid_by')
            ->get()
            ->keyBy('paid_by');

        $names = User::whereIn('id', $byPartner->keys())->pluck('name', 'id');

        return [
            'total' => (int) $byPartner->sum('amount'),
            'count' => (int) $byPartner->sum('items'),
            'by_partner' => $byPartner
                ->map(fn ($row, $id) => ['name' => $names[$id] ?? '—', 'amount' => (int) $row->amount, 'count' => (int) $row->items])
                ->sortBy('name')
                ->values(),
        ];
    }

    /**
     * Every filtered expense for printing, oldest first (up to the limit).
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Expense>
     */
    private function reportRows(array $filters): Collection
    {
        return $this->filtered($filters)
            ->with('payer')
            ->oldest('spent_on')
            ->oldest('id')
            ->limit(self::REPORT_LIMIT)
            ->get();
    }
}

<?php

namespace App\Http\Controllers\Traders;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ExpenseController;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RiceVariety;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\TraderExpense;
use App\Models\TraderReceipt;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Reports (SN Traders): which rice sells fastest, profit & loss, customer
 * dues by age, the day book and a customer statement finder. Each report
 * shows on screen, prints and downloads as a PDF.
 */
class ReportController extends Controller
{
    /**
     * Report key => title and default date range (null: as on today).
     *
     * @var array<string, array{title: string, range: ?string}>
     */
    public const REPORTS = [
        'rice-sales' => ['title' => 'Rice Sales', 'range' => 'month'],
        'profit' => ['title' => 'Profit & Loss', 'range' => 'month'],
        'dues' => ['title' => 'Customer Dues', 'range' => null],
        'day-book' => ['title' => 'Day Book', 'range' => 'month'],
    ];

    /**
     * Customer dues age filters: key => minimum days.
     *
     * @var array<string, int>
     */
    public const DUE_AGES = ['all' => 0, '30' => 31, '60' => 61, '90' => 91];

    private const TOP_CUSTOMERS = 10;

    private const SEARCH_LIMIT = 30;

    public function show(Request $request, string $report): View
    {
        return view('traders.reports.'.$report, $this->build($request, $report));
    }

    public function printReport(Request $request, string $report): View
    {
        return view('traders.reports.print', $this->build($request, $report));
    }

    public function pdf(Request $request, string $report): Response
    {
        $data = $this->build($request, $report);

        $period = $data['filters']
            ? $data['filters']['from'].'-to-'.$data['filters']['to']
            : $data['today']->toDateString();

        return Pdf::loadView('pdf.traders.report', $data)
            ->setPaper('a4', $report === 'day-book' ? 'landscape' : 'portrait')
            ->download(Str::slug(str_replace('&', 'and', $data['title'])).'-'.$period.'.pdf');
    }

    /**
     * Find a customer who has traded, then open their account statement.
     */
    public function customerStatement(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $customers = Customer::query()
            ->where(fn ($query) => $query->whereHas('traderSales')->orWhereHas('traderReceipts'))
            ->when($search !== '', fn ($query) => $query->search($search))
            ->withMax('traderSales as last_sale', 'sold_on')
            ->orderByDesc('last_sale')
            ->orderBy('name')
            ->limit(self::SEARCH_LIMIT)
            ->get();

        $balances = CustomerAccountController::balances()->keyBy(fn (array $row) => $row['customer']->id);

        return view('traders.reports.customer-statement', [
            'search' => $search,
            'customers' => $customers,
            'balances' => $balances->map(fn (array $row) => $row['balance']),
        ]);
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
            'rice-sales' => $this->riceSales($filters),
            'profit' => $this->profit($filters),
            'dues' => $this->dues($request),
            'day-book' => $this->dayBook($filters),
        };

        return [
            'companyName' => 'Traders',
            'report' => $report,
            'title' => self::REPORTS[$report]['title'],
            'filters' => $filters,
            'ranges' => ExpenseController::quickRanges(),
            'today' => today(config('app.business_timezone')),
        ] + $data;
    }

    /**
     * Bags, amount, invoices and customers sold per variety in the period,
     * with the purchase cost saved on the lines (and the bags sold without
     * one).
     *
     * @param  array{from: string, to: string}  $filters
     * @return Collection<int, object>
     */
    private function salesByVariety(array $filters): Collection
    {
        return SaleItem::query()->toBase()
            ->join('trader_sales', 'trader_sales.id', '=', 'trader_sale_items.sale_id')
            ->whereBetween('trader_sales.sold_on', [$filters['from'].' 00:00:00', $filters['to'].' 23:59:59'])
            ->selectRaw('trader_sale_items.variety_id as variety_id, SUM(trader_sale_items.bags) as bags, SUM(trader_sale_items.amount) as amount, COUNT(DISTINCT trader_sale_items.sale_id) as invoices, COUNT(DISTINCT trader_sales.customer_id) as customers, SUM(CASE WHEN trader_sale_items.cost_rate IS NULL THEN 0 ELSE trader_sale_items.bags * trader_sale_items.cost_rate END) as saved_cost, SUM(CASE WHEN trader_sale_items.cost_rate IS NULL THEN trader_sale_items.bags ELSE 0 END) as uncosted_bags')
            ->groupBy('trader_sale_items.variety_id')
            ->get()
            ->keyBy('variety_id');
    }

    /**
     * Rice Sales: every variety ranked by bags sold in the period, with its
     * share, average rate, bags a day, stock left and how many days that
     * stock lasts at this pace; plus the top customers.
     *
     * @param  array{from: string, to: string, range: string}  $filters
     * @return array<string, mixed>
     */
    private function riceSales(array $filters): array
    {
        $sold = $this->salesByVariety($filters);
        $stock = StockController::stockByVariety()->keyBy('id');
        $days = $this->daysInPeriod($filters);

        $totalBags = (int) $sold->sum('bags');
        $soldVarieties = $sold->filter(fn ($row) => (int) $row->bags > 0)->count();
        $averagePerDay = $soldVarieties > 0 ? $totalBags / $days / $soldVarieties : 0;

        $rows = RiceVariety::query()
            ->whereIn('id', $sold->keys()->merge($stock->keys()))
            ->orderBy('name')
            ->get()
            ->map(function (RiceVariety $variety) use ($sold, $stock, $days, $totalBags, $averagePerDay) {
                $bags = (int) ($sold[$variety->id]->bags ?? 0);
                $amount = (float) ($sold[$variety->id]->amount ?? 0);
                $perDay = $bags / $days;
                $stockBags = (int) ($stock[$variety->id]['stock_bags'] ?? 0);

                return [
                    'name' => $variety->name,
                    'bag_kg' => (float) $variety->bag_kg,
                    'bags' => $bags,
                    'amount' => $amount,
                    'average_rate' => $bags > 0 ? round($amount / $bags, 2) : 0.0,
                    'invoices' => (int) ($sold[$variety->id]->invoices ?? 0),
                    'customers' => (int) ($sold[$variety->id]->customers ?? 0),
                    'share' => $totalBags > 0 ? round($bags / $totalBags * 100, 1) : 0.0,
                    'per_day' => round($perDay, 1),
                    'stock_bags' => $stockBags,
                    'days_left' => $perDay > 0 && $stockBags > 0 ? (int) floor($stockBags / $perDay) : null,
                    'speed' => $this->speed($perDay, $averagePerDay),
                ];
            })
            ->sortBy([['bags', 'desc'], ['amount', 'desc'], ['name', 'asc']])
            ->values();

        $topCustomers = Sale::query()->toBase()
            ->join('trader_sale_items', 'trader_sale_items.sale_id', '=', 'trader_sales.id')
            ->whereBetween('trader_sales.sold_on', [$filters['from'].' 00:00:00', $filters['to'].' 23:59:59'])
            ->selectRaw('trader_sales.customer_id as customer_id, SUM(trader_sale_items.bags) as bags, SUM(trader_sale_items.amount) as amount, COUNT(DISTINCT trader_sales.id) as invoices')
            ->groupBy('trader_sales.customer_id')
            ->orderByDesc('amount')
            ->limit(self::TOP_CUSTOMERS)
            ->get();

        $customers = Customer::query()->whereKey($topCustomers->pluck('customer_id'))->get()->keyBy('id');

        return [
            'rows' => $rows,
            'topCustomers' => $topCustomers->map(fn ($row) => [
                'customer' => $customers[$row->customer_id],
                'bags' => (int) $row->bags,
                'amount' => (float) $row->amount,
                'invoices' => (int) $row->invoices,
            ]),
            'summary' => [
                'bags' => $totalBags,
                'amount' => (float) $sold->sum('amount'),
                'invoices' => Sale::query()->whereBetween('sold_on', [$filters['from'].' 00:00:00', $filters['to'].' 23:59:59'])->count(),
                'days' => $days,
                'per_day' => round($totalBags / $days, 1),
            ],
        ];
    }

    /**
     * Fast / steady / slow against the average variety's bags a day.
     */
    private function speed(float $perDay, float $averagePerDay): string
    {
        return match (true) {
            $perDay <= 0 => 'none',
            $perDay >= $averagePerDay * 1.25 => 'fast',
            $perDay >= $averagePerDay * 0.5 => 'steady',
            default => 'slow',
        };
    }

    /**
     * Days in the period, up to today (from the first sale for "All time").
     *
     * @param  array{from: string, to: string, range: string}  $filters
     */
    private function daysInPeriod(array $filters): int
    {
        $today = today(config('app.business_timezone'))->toDateString();
        $from = $filters['from'];

        if ($filters['range'] === 'all') {
            $first = Sale::query()->min('sold_on');
            $from = $first ? substr((string) $first, 0, 10) : $today;
        }

        $to = min($filters['to'], $today);

        return max(1, (int) Carbon::parse($from)->diffInDays(Carbon::parse(max($from, $to))) + 1);
    }

    /**
     * Profit & Loss: sales in the period less the cost of the rice sold (the
     * purchase price saved on each sale line; for lines without one, the
     * variety's average purchase cost up to the period end), less the
     * period's expenses; the net profit split between partners.
     *
     * @param  array{from: string, to: string, range: string}  $filters
     * @return array<string, mixed>
     */
    private function profit(array $filters): array
    {
        $sold = $this->salesByVariety($filters);
        $averageCost = $this->averageCostPerBag($filters['to']);

        $rows = RiceVariety::query()
            ->whereKey($sold->keys())
            ->orderBy('name')
            ->get()
            ->map(function (RiceVariety $variety) use ($sold, $averageCost) {
                $row = $sold[$variety->id];
                $bags = (int) $row->bags;
                $sales = (float) $row->amount;
                $uncostedBags = (int) $row->uncosted_bags;
                $average = $averageCost[$variety->id] ?? null;
                $cost = round((float) $row->saved_cost + $uncostedBags * ($average ?? 0), 2);
                $profit = round($sales - $cost, 2);
                $known = $uncostedBags === 0 || $average !== null;

                return [
                    'name' => $variety->name,
                    'bags' => $bags,
                    'sales' => $sales,
                    'sale_rate' => $bags > 0 ? round($sales / $bags, 2) : 0.0,
                    'cost_rate' => $known && $bags > 0 ? round($cost / $bags, 2) : null,
                    'cost' => $cost,
                    'profit' => $profit,
                    'profit_per_bag' => $bags > 0 ? round($profit / $bags, 2) : 0.0,
                    'margin' => $sales > 0 ? round($profit / $sales * 100, 1) : 0.0,
                ];
            })
            ->sortByDesc('profit')
            ->values();

        $expenses = TraderExpense::query()->whereBetween('spent_on', [$filters['from'].' 00:00:00', $filters['to'].' 23:59:59']);
        $expenseTotal = (float) (clone $expenses)->sum('amount');

        $sales = (float) $rows->sum('sales');
        $cost = (float) $rows->sum('cost');
        $gross = round($sales - $cost, 2);
        $net = round($gross - $expenseTotal, 2);

        $partners = User::partners()->get(['id', 'name']);

        $stockValue = StockController::stockByVariety()
            ->sum(fn (array $row) => max(0, $row['stock_bags']) * ($averageCost[$row['id']] ?? 0));

        return [
            'rows' => $rows,
            'summary' => [
                'sales' => $sales,
                'cost' => $cost,
                'gross' => $gross,
                'margin' => $sales > 0 ? round($gross / $sales * 100, 1) : 0.0,
                'expenses' => $expenseTotal,
                'expense_count' => (clone $expenses)->count(),
                'net' => $net,
                'purchases' => (float) Purchase::query()->whereBetween('purchased_on', [$filters['from'].' 00:00:00', $filters['to'].' 23:59:59'])->sum('total_amount'),
                'stock_value' => round((float) $stockValue, 2),
                'missing_cost' => $rows->whereNull('cost_rate')->pluck('name')->all(),
            ],
            'partnerShares' => $partners->map(fn (User $partner) => [
                'name' => $partner->name,
                'share' => round($net / $partners->count(), 2),
            ]),
        ];
    }

    /**
     * Average purchase cost of a bag, per variety, for purchases up to a date.
     *
     * @return Collection<int, float>
     */
    private function averageCostPerBag(string $upTo): Collection
    {
        return PurchaseItem::query()->toBase()
            ->join('trader_purchases', 'trader_purchases.id', '=', 'trader_purchase_items.purchase_id')
            ->where('trader_purchases.purchased_on', '<=', $upTo.' 23:59:59')
            ->selectRaw('trader_purchase_items.variety_id as variety_id, SUM(trader_purchase_items.bags) as bags, SUM(trader_purchase_items.amount) as amount')
            ->groupBy('trader_purchase_items.variety_id')
            ->get()
            ->filter(fn ($row) => (int) $row->bags > 0)
            ->mapWithKeys(fn ($row) => [(int) $row->variety_id => round((float) $row->amount / (int) $row->bags, 2)]);
    }

    /**
     * Customer Dues: who owes money, how old the unpaid invoices are (money
     * received pays off the oldest invoices first) and when they last paid.
     *
     * @return array<string, mixed>
     */
    private function dues(Request $request): array
    {
        $today = Carbon::parse(today(config('app.business_timezone'))->toDateString());
        $age = array_key_exists((string) $request->input('age'), self::DUE_AGES) ? (string) $request->input('age') : 'all';
        $search = trim((string) $request->input('q', ''));

        $owing = CustomerAccountController::balances()->filter(fn (array $row) => $row['balance'] >= 0.01);
        $customerIds = $owing->map(fn (array $row) => $row['customer']->id);

        $sales = Sale::query()
            ->whereIn('customer_id', $customerIds)
            ->orderBy('sold_on')
            ->orderBy('id')
            ->get(['id', 'customer_id', 'sold_on', 'total_amount'])
            ->groupBy('customer_id');

        $lastPaid = TraderReceipt::query()->toBase()
            ->whereIn('customer_id', $customerIds)
            ->selectRaw('customer_id, MAX(received_at) as last_paid')
            ->groupBy('customer_id')
            ->pluck('last_paid', 'customer_id');

        $all = $owing->map(function (array $row) use ($sales, $lastPaid, $today) {
            $paid = $row['received'];
            $buckets = ['b30' => 0.0, 'b60' => 0.0, 'b90' => 0.0, 'over90' => 0.0];
            $oldest = null;

            foreach ($sales[$row['customer']->id] ?? [] as $sale) {
                $covered = min($paid, (float) $sale->total_amount);
                $paid -= $covered;
                $unpaid = round((float) $sale->total_amount - $covered, 2);

                if ($unpaid < 0.01) {
                    continue;
                }

                $oldest ??= $sale->sold_on;
                $days = (int) Carbon::parse($sale->sold_on->toDateString())->diffInDays($today);

                $bucket = match (true) {
                    $days <= 30 => 'b30',
                    $days <= 60 => 'b60',
                    $days <= 90 => 'b90',
                    default => 'over90',
                };

                $buckets[$bucket] += $unpaid;
            }

            return $row + [
                'buckets' => $buckets,
                'oldest_unpaid' => $oldest?->toDateString(),
                'days' => $oldest ? (int) Carbon::parse($oldest->toDateString())->diffInDays($today) : 0,
                'last_paid' => isset($lastPaid[$row['customer']->id]) ? substr((string) $lastPaid[$row['customer']->id], 0, 10) : null,
            ];
        });

        $rows = $all
            ->filter(fn (array $row) => $row['days'] >= self::DUE_AGES[$age])
            ->when($search !== '', fn ($rows) => $rows->filter(function (array $row) use ($search) {
                $customer = $row['customer'];

                return collect([$customer->name, $customer->customer_code, $customer->phone, $customer->remarks])
                    ->contains(fn ($value) => $value && str_contains(mb_strtolower($value), mb_strtolower($search)));
            }))
            ->sortBy([['days', 'desc'], ['balance', 'desc']])
            ->values();

        return [
            'rows' => $rows,
            'age' => $age,
            'search' => $search,
            'summary' => [
                'total' => (float) $all->sum('balance'),
                'count' => $all->count(),
                'shown_total' => (float) $rows->sum('balance'),
                'buckets' => collect(['b30', 'b60', 'b90', 'over90'])
                    ->mapWithKeys(fn (string $bucket) => [$bucket => (float) $all->sum(fn (array $row) => $row['buckets'][$bucket])])
                    ->all(),
            ],
        ];
    }

    /**
     * Day Book: for every day with any activity — sales, money received
     * (by method), rice purchased and expenses, and the day's net cash.
     *
     * @param  array{from: string, to: string}  $filters
     * @return array<string, mixed>
     */
    private function dayBook(array $filters): array
    {
        $between = [$filters['from'].' 00:00:00', $filters['to'].' 23:59:59'];

        $sales = Sale::query()->toBase()->whereBetween('sold_on', $between)
            ->selectRaw('DATE(sold_on) as day, SUM(total_amount) as amount, COUNT(*) as invoices')
            ->groupByRaw('DATE(sold_on)')
            ->get()
            ->keyBy('day');

        $receipts = TraderReceipt::query()->toBase()->whereBetween('received_at', $between)
            ->selectRaw('DATE(received_at) as day, method, SUM(amount) as amount')
            ->groupByRaw('DATE(received_at), method')
            ->get()
            ->groupBy('day');

        $purchases = Purchase::query()->toBase()->whereBetween('purchased_on', $between)
            ->selectRaw('DATE(purchased_on) as day, SUM(total_amount) as amount')
            ->groupByRaw('DATE(purchased_on)')
            ->pluck('amount', 'day');

        $expenses = TraderExpense::query()->toBase()->whereBetween('spent_on', $between)
            ->selectRaw('DATE(spent_on) as day, SUM(amount) as amount')
            ->groupByRaw('DATE(spent_on)')
            ->pluck('amount', 'day');

        $days = $sales->keys()
            ->merge($receipts->keys())
            ->merge($purchases->keys())
            ->merge($expenses->keys())
            ->unique()
            ->sort()
            ->values();

        $rows = $days->map(function (string $day) use ($sales, $receipts, $purchases, $expenses) {
            $byMethod = collect(array_keys(Payment::METHODS))
                ->mapWithKeys(fn (string $method) => [$method => (float) ($receipts[$day] ?? collect())->where('method', $method)->sum('amount')])
                ->all();

            $received = (float) array_sum($byMethod);
            $purchased = (float) ($purchases[$day] ?? 0);
            $spent = (float) ($expenses[$day] ?? 0);

            return [
                'date' => $day,
                'sales' => (float) ($sales[$day]->amount ?? 0),
                'invoices' => (int) ($sales[$day]->invoices ?? 0),
                'received' => $received,
                'by_method' => $byMethod,
                'purchases' => $purchased,
                'expenses' => $spent,
                'net' => round($received - $purchased - $spent, 2),
            ];
        });

        return [
            'rows' => $rows,
            'methods' => Payment::METHODS,
            'summary' => [
                'sales' => (float) $rows->sum('sales'),
                'invoices' => (int) $rows->sum('invoices'),
                'received' => (float) $rows->sum('received'),
                'by_method' => collect(array_keys(Payment::METHODS))
                    ->mapWithKeys(fn (string $method) => [$method => (float) $rows->sum(fn (array $row) => $row['by_method'][$method])])
                    ->all(),
                'purchases' => (float) $rows->sum('purchases'),
                'expenses' => (float) $rows->sum('expenses'),
                'net' => round((float) $rows->sum('net'), 2),
            ],
        ];
    }
}

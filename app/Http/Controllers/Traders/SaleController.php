<?php

namespace App\Http\Controllers\Traders;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ExpenseController;
use App\Http\Requests\Traders\StoreRiceBillRequest;
use App\Models\Customer;
use App\Models\RiceVariety;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\TraderReceipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Sales (SN Traders): rice sold to customers (the shared customer list).
 * Money taken at the sale is a linked receipt; the rest is the customer's
 * credit. The list opens on this month; each invoice prints / downloads.
 */
class SaleController extends Controller
{
    private const PER_PAGE = 20;

    private const REPORT_LIMIT = 3000;

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        return view('traders.sales.index', [
            'sales' => $this->filtered($filters)
                ->with(['customer', 'items.variety', 'receipts'])
                ->latest('sold_on')
                ->latest('id')
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
            'filters' => $filters,
            'summary' => $this->summary($filters),
            'ranges' => ExpenseController::quickRanges(),
        ]);
    }

    public function create(Request $request): View
    {
        $customer = $request->filled('customer') ? Customer::find($request->integer('customer')) : null;

        return view('traders.sales.create', [
            'customers' => Customer::query()->orderBy('name')->get(['id', 'customer_code', 'name', 'phone', 'remarks']),
            'varieties' => RiceVariety::active()->get(),
            'selectedCustomer' => $customer,
            'customerBalance' => $customer?->traderBalance(),
            'stock' => StockController::stockByVariety()->pluck('stock_bags', 'id'),
        ]);
    }

    public function store(StoreRiceBillRequest $request): RedirectResponse
    {
        $received = (float) $request->validated('received_amount');

        $sale = Sale::record(
            $request->safe()->only(['customer_id', 'sold_on', 'notes']),
            $request->lines(),
            $received > 0 ? [
                'amount' => $received,
                'method' => $request->validated('received_method'),
                'reference' => $request->validated('received_reference'),
                'received_at' => now(config('app.business_timezone'))->format('Y-m-d H:i:s'),
            ] : null,
            $request->user(),
        );

        return redirect()
            ->route('traders.sales.show', $sale)
            ->with('success', "Invoice {$sale->invoice_number} saved — ₹".number_format($sale->total_amount, 2).'.');
    }

    public function show(Sale $sale): View
    {
        $sale->load(['customer', 'items.variety', 'receipts', 'recorder']);

        return view('traders.sales.show', [
            'sale' => $sale,
            'customerBalance' => $sale->customer->traderBalance(),
        ]);
    }

    public function pdf(Sale $sale): Response
    {
        $sale->load(['customer', 'items.variety', 'receipts', 'recorder']);

        return Pdf::loadView('pdf.traders.invoice', [
            'sale' => $sale,
            'customerBalance' => $sale->customer->traderBalance(),
            'companyName' => 'Traders',
        ])
            ->setPaper('a5', 'portrait')
            ->download("Invoice-{$sale->invoice_number}.pdf");
    }

    /**
     * Delete an invoice entered by mistake (with any money taken at it).
     */
    public function destroy(Sale $sale): RedirectResponse
    {
        $number = $sale->invoice_number;

        $sale->delete();

        return redirect()->route('traders.sales.index')->with('success', "Invoice {$number} deleted.");
    }

    public function printList(Request $request): View
    {
        return view('traders.sales.print', $this->report($request));
    }

    public function listPdf(Request $request): Response
    {
        $report = $this->report($request);

        return Pdf::loadView('pdf.traders.sales', $report)
            ->setPaper('a4', 'portrait')
            ->download('Sales-'.$report['filters']['from'].'-to-'.$report['filters']['to'].'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function report(Request $request): array
    {
        $filters = $this->filters($request);

        return [
            'companyName' => 'Traders',
            'filters' => $filters,
            'summary' => $this->summary($filters),
            'sales' => $this->filtered($filters)
                ->with(['customer', 'items.variety', 'receipts'])
                ->oldest('sold_on')
                ->oldest('id')
                ->limit(self::REPORT_LIMIT)
                ->get(),
            'limit' => self::REPORT_LIMIT,
        ];
    }

    /**
     * @return array{q: string, from: string, to: string, range: string}
     */
    private function filters(Request $request): array
    {
        [$from, $to, $range] = ExpenseController::dateRange($request, 'month');

        return [
            'q' => trim((string) $request->input('q', '')),
            'from' => $from,
            'to' => $to,
            'range' => $range,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Sale>
     */
    private function filtered(array $filters): Builder
    {
        $search = $filters['q'];

        return Sale::query()
            ->whereBetween('sold_on', [$filters['from'].' 00:00:00', $filters['to'].' 23:59:59'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->search($search));
            }));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{total: float, received: float, count: int, kg: float, bags: int, by_variety: Collection<int, array{name: string, kg: float, bags: int, amount: float}>}
     */
    private function summary(array $filters): array
    {
        $ids = $this->filtered($filters)->select('id');

        $byVariety = SaleItem::query()
            ->whereIn('sale_id', $ids)
            ->join('trader_varieties', 'trader_varieties.id', '=', 'trader_sale_items.variety_id')
            ->selectRaw('trader_varieties.name as name, SUM(trader_sale_items.kg) as kg, SUM(trader_sale_items.bags) as bags, SUM(trader_sale_items.amount) as amount')
            ->groupBy('trader_varieties.name')
            ->orderBy('trader_varieties.name')
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'kg' => (float) $row->kg, 'bags' => (int) $row->bags, 'amount' => (float) $row->amount]);

        return [
            'total' => (float) $this->filtered($filters)->sum('total_amount'),
            'received' => (float) TraderReceipt::whereIn('sale_id', $this->filtered($filters)->select('id'))->sum('amount'),
            'count' => $this->filtered($filters)->count(),
            'kg' => (float) $byVariety->sum('kg'),
            'bags' => (int) $byVariety->sum('bags'),
            'by_variety' => $byVariety,
        ];
    }
}

<?php

namespace App\Http\Controllers\Traders;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ExpenseController;
use App\Http\Requests\Traders\StoreRiceBillRequest;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RiceVariety;
use App\Models\Supplier;
use App\Support\ReportPdf as Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Purchases (SN Traders): rice bought from suppliers. The list opens on
 * this month; each bill has one or more variety lines and prints / downloads.
 */
class PurchaseController extends Controller
{
    private const PER_PAGE = 20;

    private const REPORT_LIMIT = 3000;

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        return view('traders.purchases.index', [
            'purchases' => $this->filtered($filters)
                ->with(['supplier', 'items.variety'])
                ->latest('purchased_on')
                ->latest('id')
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
            'filters' => $filters,
            'summary' => $this->summary($filters),
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'ranges' => ExpenseController::quickRanges(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('traders.purchases.create', [
            'suppliers' => Supplier::orderBy('name')->get(),
            'varieties' => RiceVariety::active()->get(),
            'selectedSupplier' => $request->integer('supplier') ?: null,
        ]);
    }

    public function store(StoreRiceBillRequest $request): RedirectResponse
    {
        $purchase = Purchase::record(
            $request->safe()->only(['supplier_id', 'purchased_on', 'supplier_bill_no', 'method', 'notes']),
            $request->lines(),
            $request->user(),
        );

        return redirect()
            ->route('traders.purchases.show', $purchase)
            ->with('success', "Purchase {$purchase->purchase_number} saved — ₹".number_format($purchase->total_amount, 2).'.');
    }

    public function show(Purchase $purchase): View
    {
        $purchase->load(['supplier', 'items.variety', 'recorder']);

        return view('traders.purchases.show', compact('purchase'));
    }

    public function pdf(Purchase $purchase): Response
    {
        $purchase->load(['supplier', 'items.variety', 'recorder']);

        return Pdf::loadView('pdf.traders.purchase', ['purchase' => $purchase, 'companyName' => 'Traders'])
            ->setPaper('a5', 'portrait')
            ->download("Purchase-{$purchase->purchase_number}.pdf");
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        $number = $purchase->purchase_number;

        $purchase->delete();

        return redirect()->route('traders.purchases.index')->with('success', "Purchase {$number} deleted.");
    }

    public function printList(Request $request): View
    {
        return view('traders.purchases.print', $this->report($request));
    }

    public function listPdf(Request $request): Response
    {
        $report = $this->report($request);

        return Pdf::loadView('pdf.traders.purchases', $report)
            ->setPaper('a4', 'portrait')
            ->download('Purchases-'.$report['filters']['from'].'-to-'.$report['filters']['to'].'.pdf');
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
            'purchases' => $this->filtered($filters)
                ->with(['supplier', 'items.variety'])
                ->oldest('purchased_on')
                ->oldest('id')
                ->limit(self::REPORT_LIMIT)
                ->get(),
            'limit' => self::REPORT_LIMIT,
        ];
    }

    /**
     * @return array{q: string, supplier: ?int, supplier_name: ?string, from: string, to: string, range: string}
     */
    private function filters(Request $request): array
    {
        [$from, $to, $range] = ExpenseController::dateRange($request, 'month');

        $supplierId = $request->integer('supplier') ?: null;

        return [
            'q' => trim((string) $request->input('q', '')),
            'supplier' => $supplierId,
            'supplier_name' => $supplierId ? Supplier::whereKey($supplierId)->value('name') : null,
            'from' => $from,
            'to' => $to,
            'range' => $range,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Purchase>
     */
    private function filtered(array $filters): Builder
    {
        $search = $filters['q'];

        return Purchase::query()
            ->whereBetween('purchased_on', [$filters['from'].' 00:00:00', $filters['to'].' 23:59:59'])
            ->when($filters['supplier'], fn ($query) => $query->where('supplier_id', $filters['supplier']))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('purchase_number', 'like', "%{$search}%")
                    ->orWhere('supplier_bill_no', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', "%{$search}%"));
            }));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{total: float, count: int, kg: float, bags: int, by_variety: Collection<int, array{name: string, kg: float, bags: int, amount: float}>}
     */
    private function summary(array $filters): array
    {
        $ids = $this->filtered($filters)->select('id');

        $byVariety = PurchaseItem::query()
            ->whereIn('purchase_id', $ids)
            ->join('trader_varieties', 'trader_varieties.id', '=', 'trader_purchase_items.variety_id')
            ->selectRaw('trader_varieties.name as name, SUM(trader_purchase_items.kg) as kg, SUM(trader_purchase_items.bags) as bags, SUM(trader_purchase_items.amount) as amount')
            ->groupBy('trader_varieties.name')
            ->orderBy('trader_varieties.name')
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'kg' => (float) $row->kg, 'bags' => (int) $row->bags, 'amount' => (float) $row->amount]);

        return [
            'total' => (float) $this->filtered($filters)->sum('total_amount'),
            'count' => $this->filtered($filters)->count(),
            'kg' => (float) $byVariety->sum('kg'),
            'bags' => (int) $byVariety->sum('bags'),
            'by_variety' => $byVariety,
        ];
    }
}

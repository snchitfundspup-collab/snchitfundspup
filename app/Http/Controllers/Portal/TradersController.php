<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traders\CustomerAccountController;
use App\Http\Controllers\Traders\StockController;
use App\Models\Customer;
use App\Models\RiceVariety;
use App\Models\Sale;
use App\Models\TraderOrder;
use App\Models\TraderReceipt;
use App\Support\ReportPdf as Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * SN Traders for a signed-in customer: the rice on sale (cards) to order
 * from, their orders, and their bills — invoices, receipts and statement.
 */
class TradersController extends Controller
{
    private const MAX_BAGS = 500;

    public function rice(): View
    {
        return view('portal.traders.rice', ['varieties' => self::riceCards()]);
    }

    public function placeOrder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bags' => ['required', 'array'],
            'bags.*' => ['nullable', 'integer', 'min:0', 'max:'.self::MAX_BAGS],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'bags.*.integer' => 'Enter whole bags.',
            'bags.*.max' => 'At most '.self::MAX_BAGS.' bags of one rice.',
        ]);

        $activeIds = RiceVariety::query()->where('is_active', true)->pluck('id')->all();

        $bagsByVariety = collect($validated['bags'])
            ->map(fn ($bags) => (int) $bags)
            ->filter(fn (int $bags, $varietyId) => $bags > 0 && in_array((int) $varietyId, $activeIds, true))
            ->mapWithKeys(fn (int $bags, $varietyId) => [(int) $varietyId => $bags])
            ->all();

        if ($bagsByVariety === []) {
            throw ValidationException::withMessages(['bags' => 'Enter how many bags you want.']);
        }

        $order = TraderOrder::place($this->customer($request), $bagsByVariety, $validated['notes'] ?? null);

        return redirect()
            ->route('portal.orders')
            ->with('success', "Order {$order->order_number} sent. The office will call you to confirm.");
    }

    public function orders(Request $request): View
    {
        return view('portal.traders.orders', [
            'orders' => $this->customer($request)->traderOrders()
                ->with(['items.variety', 'sale'])
                ->latest('id')
                ->paginate(15),
        ]);
    }

    public function cancelOrder(Request $request, TraderOrder $order): RedirectResponse
    {
        $this->ensureOwn($request, $order->customer_id);

        if ($order->isNew()) {
            $order->cancel();
        }

        return redirect()->route('portal.orders')->with('success', "Order {$order->order_number} cancelled.");
    }

    public function bills(Request $request): View
    {
        return view('portal.traders.bills', CustomerAccountController::statement($this->customer($request)));
    }

    public function statementPdf(Request $request): Response
    {
        $customer = $this->customer($request);

        return Pdf::loadView('pdf.traders.account', CustomerAccountController::statement($customer))
            ->setPaper('a4', 'portrait')
            ->download('Account-'.$customer->customer_code.'.pdf');
    }

    public function statementPrint(Request $request): View
    {
        return view('portal.traders.bills-print', CustomerAccountController::statement($this->customer($request)));
    }

    public function invoicePdf(Request $request, Sale $sale): Response
    {
        $this->ensureOwn($request, $sale->customer_id);

        $sale->load(['customer', 'items.variety', 'receipts', 'recorder']);

        return Pdf::loadView('pdf.traders.invoice', [
            'sale' => $sale,
            'customerBalance' => $sale->customer->traderBalance(),
            'companyName' => 'Traders',
        ])
            ->setPaper('a5', 'portrait')
            ->download("Invoice-{$sale->invoice_number}.pdf");
    }

    public function receiptPdf(Request $request, TraderReceipt $receipt): Response
    {
        $this->ensureOwn($request, $receipt->customer_id);

        $receipt->load(['customer', 'sale', 'recorder']);

        return Pdf::loadView('pdf.traders.receipt', [
            'receipt' => $receipt,
            'balanceNow' => $receipt->customer->traderBalance(),
            'companyName' => 'Traders',
        ])
            ->setPaper('a5', 'portrait')
            ->download("Receipt-{$receipt->receipt_number}.pdf");
    }

    /**
     * Active rice varieties with their selling price and whether any bags
     * are in stock (customers see "Available", not the stock count).
     *
     * @return Collection<int, array{variety: RiceVariety, available: bool}>
     */
    public static function riceCards(): Collection
    {
        $stock = StockController::stockByVariety()->pluck('stock_bags', 'id');

        return RiceVariety::active()
            ->get()
            ->map(fn (RiceVariety $variety) => [
                'variety' => $variety,
                'available' => ($stock[$variety->id] ?? 0) > 0,
            ])
            ->sortByDesc('available')
            ->values();
    }

    private function customer(Request $request): Customer
    {
        return $request->user('customer');
    }

    /**
     * Customers only ever see their own bills and orders.
     */
    private function ensureOwn(Request $request, ?int $customerId): void
    {
        abort_unless($customerId === $this->customer($request)->id, 404);
    }
}

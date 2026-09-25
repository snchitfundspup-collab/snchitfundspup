<?php

namespace App\Http\Controllers\Traders;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ExpenseController;
use App\Http\Requests\Traders\StoreTraderReceiptRequest;
use App\Models\Customer;
use App\Models\TraderReceipt;
use App\Support\ReportPdf as Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Customer receipts (SN Traders → Customer Credit): money received from a
 * customer against what they owe, with a printable receipt.
 */
class ReceiptController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): View
    {
        [$from, $to, $range] = ExpenseController::dateRange($request, 'month');
        $search = trim((string) $request->input('q', ''));

        $query = TraderReceipt::query()
            ->whereBetween('received_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('receipt_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->search($search));
            }));

        return view('traders.receipts.index', [
            'receipts' => (clone $query)->with(['customer', 'sale'])->latest('received_at')->latest('id')->paginate(self::PER_PAGE)->withQueryString(),
            'total' => (float) (clone $query)->sum('amount'),
            'count' => (clone $query)->count(),
            'filters' => ['q' => $search, 'from' => $from, 'to' => $to, 'range' => $range],
            'ranges' => ExpenseController::quickRanges(),
        ]);
    }

    public function create(Request $request): View
    {
        $customer = $request->filled('customer') ? Customer::find($request->integer('customer')) : null;

        return view('traders.receipts.create', [
            'customers' => CustomerAccountController::balances()->where('balance', '>', 0)->values(),
            'allCustomers' => Customer::query()->orderBy('name')->get(['id', 'customer_code', 'name', 'phone', 'remarks']),
            'selectedCustomer' => $customer,
            'customerBalance' => $customer?->traderBalance(),
        ]);
    }

    public function store(StoreTraderReceiptRequest $request): RedirectResponse
    {
        $receipt = TraderReceipt::record($request->validated(), $request->user());

        return redirect()
            ->route('traders.receipts.show', $receipt)
            ->with('success', "Receipt {$receipt->receipt_number} saved.");
    }

    public function show(TraderReceipt $receipt): View
    {
        $receipt->load(['customer', 'sale', 'recorder']);

        return view('traders.receipts.show', [
            'receipt' => $receipt,
            'balanceNow' => $receipt->customer->traderBalance(),
        ]);
    }

    public function pdf(TraderReceipt $receipt): Response
    {
        $receipt->load(['customer', 'sale', 'recorder']);

        return Pdf::loadView('pdf.traders.receipt', [
            'receipt' => $receipt,
            'balanceNow' => $receipt->customer->traderBalance(),
            'companyName' => 'Traders',
        ])
            ->setPaper('a5', 'portrait')
            ->download("Receipt-{$receipt->receipt_number}.pdf");
    }

    public function destroy(TraderReceipt $receipt): RedirectResponse
    {
        $number = $receipt->receipt_number;
        $customerId = $receipt->customer_id;

        $receipt->delete();

        return redirect()
            ->route('traders.accounts.show', $customerId)
            ->with('success', "Receipt {$number} cancelled.");
    }
}

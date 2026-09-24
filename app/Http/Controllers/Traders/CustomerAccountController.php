<?php

namespace App\Http\Controllers\Traders;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\TraderReceipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Customer Credit (SN Traders): who owes how much (sales minus money
 * received) and each customer's account statement — every invoice and
 * receipt with a running balance. Printable and downloadable.
 */
class CustomerAccountController extends Controller
{
    private const VIEWS = ['owing', 'advance', 'all'];

    public function index(Request $request): View
    {
        return view('traders.balances', $this->balancesReport($request));
    }

    public function printBalances(Request $request): View
    {
        return view('traders.balances-print', $this->balancesReport($request));
    }

    public function balancesPdf(Request $request): Response
    {
        return Pdf::loadView('pdf.traders.balances', $this->balancesReport($request))
            ->setPaper('a4', 'portrait')
            ->download('Customer-Balances-'.today(config('app.business_timezone'))->toDateString().'.pdf');
    }

    public function show(Customer $customer): View
    {
        return view('traders.account', $this->statement($customer));
    }

    public function pdf(Customer $customer): Response
    {
        return Pdf::loadView('pdf.traders.account', $this->statement($customer))
            ->setPaper('a4', 'portrait')
            ->download('Account-'.$customer->customer_code.'.pdf');
    }

    /**
     * Sales, receipts and balance for every customer who has traded, from
     * two grouped queries (no per-customer queries).
     *
     * @return Collection<int, array{customer: Customer, sales: float, received: float, balance: float, last_sale: ?string}>
     */
    public static function balances(): Collection
    {
        $sales = Sale::query()->toBase()
            ->selectRaw('customer_id, SUM(total_amount) as amount, MAX(sold_on) as last_sale')
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $received = TraderReceipt::query()->toBase()
            ->selectRaw('customer_id, SUM(amount) as amount')
            ->groupBy('customer_id')
            ->pluck('amount', 'customer_id');

        $customerIds = $sales->keys()->merge($received->keys())->unique();

        return Customer::query()
            ->whereIn('id', $customerIds)
            ->get()
            ->map(function (Customer $customer) use ($sales, $received) {
                $sold = (float) ($sales[$customer->id]->amount ?? 0);
                $paid = (float) ($received[$customer->id] ?? 0);

                return [
                    'customer' => $customer,
                    'sales' => $sold,
                    'received' => $paid,
                    'balance' => round($sold - $paid, 2),
                    'last_sale' => isset($sales[$customer->id]) ? substr((string) $sales[$customer->id]->last_sale, 0, 10) : null,
                ];
            })
            ->sortByDesc('balance')
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function balancesReport(Request $request): array
    {
        $view = in_array($request->input('view'), self::VIEWS, true) ? $request->input('view') : 'owing';
        $search = trim((string) $request->input('q', ''));

        $all = self::balances();

        $rows = $all
            ->when($view === 'owing', fn ($rows) => $rows->filter(fn ($row) => $row['balance'] >= 0.01))
            ->when($view === 'advance', fn ($rows) => $rows->filter(fn ($row) => $row['balance'] <= -0.01))
            ->when($search !== '', fn ($rows) => $rows->filter(function ($row) use ($search) {
                $customer = $row['customer'];

                return collect([$customer->name, $customer->customer_code, $customer->phone, $customer->remarks])
                    ->contains(fn ($value) => $value && str_contains(mb_strtolower($value), mb_strtolower($search)));
            }))
            ->values();

        return [
            'companyName' => 'Traders',
            'view' => $view,
            'search' => $search,
            'rows' => $rows,
            'totalOwing' => (float) $all->where('balance', '>', 0)->sum('balance'),
            'owingCount' => $all->where('balance', '>=', 0.01)->count(),
            'totalAdvance' => (float) -$all->where('balance', '<', 0)->sum('balance'),
            'today' => today(config('app.business_timezone')),
        ];
    }

    /**
     * Every invoice (debit) and receipt (credit) in date order with a
     * running balance.
     *
     * @return array<string, mixed>
     */
    private function statement(Customer $customer): array
    {
        $sales = $customer->traderSales()->with('items.variety')->get()->map(fn (Sale $sale) => [
            'date' => $sale->sold_on->toDateString(),
            'sort' => $sale->sold_on->toDateString().' 00:00:00|'.str_pad((string) $sale->id, 10, '0', STR_PAD_LEFT).'|a',
            'type' => 'sale',
            'number' => $sale->invoice_number,
            'details' => $sale->items->map(fn ($item) => $item->variety->name.' '.$item->quantityLabel())->implode(', '),
            'debit' => (float) $sale->total_amount,
            'credit' => 0.0,
            'model' => $sale,
        ]);

        $receipts = $customer->traderReceipts()->with('sale')->get()->map(fn (TraderReceipt $receipt) => [
            'date' => $receipt->received_at->toDateString(),
            /* a receipt taken at a sale sorts right after that invoice */
            'sort' => $receipt->sale
                ? $receipt->sale->sold_on->toDateString().' 00:00:00|'.str_pad((string) $receipt->sale_id, 10, '0', STR_PAD_LEFT).'|b'
                : $receipt->received_at->format('Y-m-d H:i:s').'|'.str_pad((string) $receipt->id, 10, '0', STR_PAD_LEFT).'|c',
            'type' => 'receipt',
            'number' => $receipt->receipt_number,
            'details' => $receipt->methodLabel().($receipt->reference ? ' · '.$receipt->reference : '').($receipt->sale ? ' · at '.$receipt->sale->invoice_number : ''),
            'debit' => 0.0,
            'credit' => (float) $receipt->amount,
            'model' => $receipt,
        ]);

        $balance = 0.0;

        $entries = $sales->concat($receipts)
            ->sortBy('sort')
            ->values()
            ->map(function (array $entry) use (&$balance) {
                $balance = round($balance + $entry['debit'] - $entry['credit'], 2);

                return $entry + ['balance' => $balance];
            });

        return [
            'companyName' => 'Traders',
            'customer' => $customer,
            'entries' => $entries,
            'totalSales' => (float) $sales->sum('debit'),
            'totalReceived' => (float) $receipts->sum('credit'),
            'balance' => $balance,
            'today' => today(config('app.business_timezone')),
        ];
    }
}

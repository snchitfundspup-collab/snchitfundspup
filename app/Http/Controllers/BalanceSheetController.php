<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePartnerSettlementRequest;
use App\Models\Expense;
use App\Models\PartnerSettlement;
use App\Models\TraderExpense;
use App\Models\TraderPartnerSettlement;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Balance Sheet (Management → Expenses): the partners share the business
 * spending equally. For each partner: what they paid, their equal share,
 * settlements given / received, and the balance — then who should pay whom
 * to square up. Settlements are recorded here. Print and PDF. Serves SN
 * Chit Funds and SN Traders (its own tables, routes traders.*).
 */
class BalanceSheetController extends Controller
{
    /**
     * @var class-string<Expense>
     */
    private string $expenseModel = Expense::class;

    /**
     * @var class-string<PartnerSettlement>
     */
    private string $settlementModel = PartnerSettlement::class;

    private string $routePrefix = '';

    /**
     * Work out which business the request is for.
     */
    private function forBusinessOf(Request $request): void
    {
        $isTraders = $request->routeIs('traders.*');

        $this->expenseModel = $isTraders ? TraderExpense::class : Expense::class;
        $this->settlementModel = $isTraders ? TraderPartnerSettlement::class : PartnerSettlement::class;
        $this->routePrefix = $isTraders ? 'traders.' : '';
    }

    public function index(Request $request): View
    {
        $this->forBusinessOf($request);

        return view('expenses.balance', $this->sheet($request));
    }

    /**
     * The balance sheet on its own page, which opens the print dialog.
     */
    public function printSheet(Request $request): View
    {
        $this->forBusinessOf($request);

        return view('expenses.balance-print', $this->sheet($request));
    }

    public function pdf(Request $request): Response
    {
        $this->forBusinessOf($request);

        $sheet = $this->sheet($request);

        return Pdf::loadView('pdf.balance-sheet', $sheet)
            ->setPaper('a4', 'portrait')
            ->download(($this->routePrefix === 'traders.' ? 'Traders-' : '').'Balance-Sheet-'.($sheet['range'] === 'all' ? 'all-time' : $sheet['from'].'-to-'.$sheet['to']).'.pdf');
    }

    /**
     * Record money one partner handed another.
     */
    public function storeSettlement(StorePartnerSettlementRequest $request): RedirectResponse
    {
        $this->forBusinessOf($request);

        $settlement = $this->settlementModel::create([...$request->validated(), 'recorded_by' => $request->user()->id]);

        $settlement->load('fromUser', 'toUser');

        return redirect()
            ->route($this->routePrefix.'expenses.balance')
            ->with('success', "{$settlement->fromUser->name} paid {$settlement->toUser->name} ₹".number_format($settlement->amount).' — recorded.');
    }

    public function destroySettlement(Request $request, string $settlement): RedirectResponse
    {
        $this->forBusinessOf($request);

        $this->settlementModel::findOrFail($settlement)->delete();

        return redirect()
            ->route($this->routePrefix.'expenses.balance')
            ->with('success', 'Settlement deleted.');
    }

    /**
     * Everything the balance sheet shows for the chosen period (all time by
     * default — the running balance between the partners).
     *
     * @return array<string, mixed>
     */
    private function sheet(Request $request): array
    {
        [$from, $to, $range] = ExpenseController::dateRange($request, 'all');

        $partners = User::partners()->get();

        /* whole days, so it matches however the database stores dates */
        $period = [$from.' 00:00:00', $to.' 23:59:59'];

        $expenses = $this->expenseModel::query()->whereBetween('spent_on', $period);

        $paidBy = (clone $expenses)->toBase()
            ->selectRaw('paid_by, SUM(amount) as amount, COUNT(*) as items')
            ->groupBy('paid_by')
            ->get()
            ->keyBy('paid_by');

        $settlements = $this->settlementModel::query()
            ->with(['fromUser', 'toUser'])
            ->whereBetween('settled_on', $period)
            ->latest('settled_on')
            ->latest('id')
            ->get();

        $total = (int) $paidBy->sum('amount');
        $share = $partners->isNotEmpty() ? round($total / $partners->count(), 2) : 0;

        $balances = $partners->map(function (User $partner) use ($paidBy, $settlements, $share) {
            $paid = (int) ($paidBy[$partner->id]->amount ?? 0);
            $given = (int) $settlements->where('from_user_id', $partner->id)->sum('amount');
            $received = (int) $settlements->where('to_user_id', $partner->id)->sum('amount');

            return [
                'partner' => $partner,
                'paid' => $paid,
                'items' => (int) ($paidBy[$partner->id]->items ?? 0),
                'share' => $share,
                'given' => $given,
                'received' => $received,
                /* what they have really put in once settlements are counted */
                'net_put_in' => $paid + $given - $received,
                /* positive: to receive from the others; negative: to pay */
                'balance' => round($paid - $share + $given - $received, 2),
            ];
        });

        return [
            'routePrefix' => $this->routePrefix,
            'companyName' => $this->routePrefix === 'traders.' ? 'Traders' : 'Chit Funds',
            'from' => $from,
            'to' => $to,
            'range' => $range,
            'ranges' => ExpenseController::quickRanges(),
            'partners' => $partners,
            'total' => $total,
            'share' => $share,
            'balances' => $balances,
            'transfers' => $this->transfers($balances),
            'settlements' => $settlements,
        ];
    }

    /**
     * Who pays whom to bring every balance to zero: those who paid less
     * than their share pay those who paid more, largest amounts first.
     *
     * @param  Collection<int, array<string, mixed>>  $balances
     * @return list<array{from: User, to: User, amount: float}>
     */
    private function transfers(Collection $balances): array
    {
        $owe = $balances->filter(fn ($row) => $row['balance'] <= -0.01)
            ->map(fn ($row) => ['partner' => $row['partner'], 'amount' => -$row['balance']])
            ->sortByDesc('amount')
            ->values()
            ->all();

        $receive = $balances->filter(fn ($row) => $row['balance'] >= 0.01)
            ->map(fn ($row) => ['partner' => $row['partner'], 'amount' => $row['balance']])
            ->sortByDesc('amount')
            ->values()
            ->all();

        $transfers = [];
        $i = 0;
        $j = 0;

        while ($i < count($owe) && $j < count($receive)) {
            $amount = round(min($owe[$i]['amount'], $receive[$j]['amount']), 2);

            if ($amount >= 0.01) {
                $transfers[] = ['from' => $owe[$i]['partner'], 'to' => $receive[$j]['partner'], 'amount' => $amount];
            }

            $owe[$i]['amount'] -= $amount;
            $receive[$j]['amount'] -= $amount;

            if ($owe[$i]['amount'] < 0.01) {
                $i++;
            }

            if ($receive[$j]['amount'] < 0.01) {
                $j++;
            }
        }

        return $transfers;
    }
}

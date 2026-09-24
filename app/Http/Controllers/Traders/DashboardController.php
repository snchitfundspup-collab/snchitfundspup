<?php

namespace App\Http\Controllers\Traders;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\TraderReceipt;
use Illuminate\View\View;

/**
 * SN Traders home: today's and this month's sales, money received,
 * purchases, customer credit, stock and the latest invoices.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $today = today(config('app.business_timezone'));

        $day = [$today->toDateString().' 00:00:00', $today->toDateString().' 23:59:59'];
        $month = [$today->copy()->startOfMonth()->toDateString().' 00:00:00', $today->toDateString().' 23:59:59'];

        $balances = CustomerAccountController::balances();
        $stock = StockController::stockByVariety();

        return view('traders.dashboard', [
            'today' => $today,
            'salesToday' => (float) Sale::whereBetween('sold_on', $day)->sum('total_amount'),
            'salesTodayCount' => Sale::whereBetween('sold_on', $day)->count(),
            'salesMonth' => (float) Sale::whereBetween('sold_on', $month)->sum('total_amount'),
            'receivedToday' => (float) TraderReceipt::whereBetween('received_at', $day)->sum('amount'),
            'receivedMonth' => (float) TraderReceipt::whereBetween('received_at', $month)->sum('amount'),
            'purchasesMonth' => (float) Purchase::whereBetween('purchased_on', $month)->sum('total_amount'),
            'creditTotal' => (float) $balances->where('balance', '>', 0)->sum('balance'),
            'creditCount' => $balances->where('balance', '>=', 0.01)->count(),
            'topOwing' => $balances->filter(fn ($row) => $row['balance'] >= 0.01)->take(5)->values(),
            'stock' => $stock,
            'recentSales' => Sale::with('customer')->latest('sold_on')->latest('id')->take(6)->get(),
        ]);
    }
}

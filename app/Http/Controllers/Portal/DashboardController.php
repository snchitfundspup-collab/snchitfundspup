<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\TraderOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A customer's home page: their chit groups first (months done / left and
 * what is due), the groups that are forming, then SN Traders — their rice
 * balance and orders, and the rice to buy. Family members sharing the phone
 * are included, each seat under its own name and ID.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        $seats = ChitController::seatsOf($customer);

        $family = $customer->family();

        return view('portal.dashboard', [
            'customer' => $customer,
            'family' => $family,
            'seats' => $seats,
            'upcomingGroups' => ChitController::upcomingGroups()->take(3),
            'joinRequests' => ChitController::latestRequests($customer),
            'traderBalance' => round($family->sum(fn (Customer $person) => $person->traderBalance()), 2),
            'openOrders' => TraderOrder::query()->whereIn('customer_id', $family->pluck('id'))->open()->count(),
            'rice' => TradersController::riceCards()->take(4),
        ]);
    }
}

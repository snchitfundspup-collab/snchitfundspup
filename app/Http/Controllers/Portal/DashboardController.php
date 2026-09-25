<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A customer's home page: their chit groups first (months done / left and
 * what is due), the groups that are forming, then SN Traders — their rice
 * balance and orders, and the rice to buy.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        $seats = ChitController::seatsOf($customer);

        return view('portal.dashboard', [
            'customer' => $customer,
            'seats' => $seats,
            'upcomingGroups' => ChitController::upcomingGroups()->take(3),
            'joinRequests' => ChitController::latestRequests($customer),
            'traderBalance' => $customer->traderBalance(),
            'openOrders' => $customer->traderOrders()->open()->count(),
            'rice' => TradersController::riceCards()->take(4),
        ]);
    }
}

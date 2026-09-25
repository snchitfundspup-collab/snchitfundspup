<?php

namespace App\Http\Controllers\Traders;

use App\Http\Controllers\Controller;
use App\Models\TraderOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Customer Orders (SN Traders): rice orders customers placed from their own
 * pages. "Make sale" opens New Sale filled in from the order; saving that
 * sale completes the order. Orders can also be cancelled.
 */
class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = array_key_exists((string) $request->input('status'), TraderOrder::STATUSES) ? (string) $request->input('status') : TraderOrder::STATUS_NEW;

        return view('traders.orders.index', [
            'status' => $status,
            'counts' => TraderOrder::query()->toBase()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'orders' => TraderOrder::query()
                ->where('status', $status)
                ->with(['customer', 'items.variety', 'sale'])
                ->when($status === TraderOrder::STATUS_NEW, fn ($query) => $query->oldest('id'), fn ($query) => $query->latest('id'))
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function cancel(TraderOrder $order): RedirectResponse
    {
        if ($order->isNew()) {
            $order->cancel();
        }

        return redirect()->route('traders.orders.index')->with('success', "Order {$order->order_number} cancelled.");
    }
}

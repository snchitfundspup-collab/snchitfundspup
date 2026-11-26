<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use App\Models\FinanceCollection;
use App\Models\FinanceLoan;
use App\Support\AgentFilter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sri Lakshmi Micro Finance: an agent (any staff member but the owner)
 * opens only their own loans (they brought the customer in), receipts
 * (they collected them, or on their loans) and customers' statements (the
 * customer has a loan of theirs). Capital & Cash, Expenses and Profit &
 * Loss are the owner's alone.
 */
class EnsureFinanceAccess
{
    /**
     * Pages only the owner opens.
     *
     * @var list<string>
     */
    private const OWNER_ONLY = ['finance.capital.*', 'finance.expenses.*', 'finance.holidays.*'];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! AgentFilter::heldToOwn($request)) {
            return $next($request);
        }

        $agentId = $request->user()->id;

        abort_if($request->routeIs(...self::OWNER_ONLY) || $request->route('report') === 'profit', 403);

        $loan = $request->route('loan');
        abort_if($loan instanceof FinanceLoan && $loan->agent_id !== $agentId, 404);

        $collection = $request->route('collection');
        abort_if($collection instanceof FinanceCollection && $collection->collected_by !== $agentId && $collection->loan?->agent_id !== $agentId, 404);

        $customer = $request->route('customer');
        abort_if($customer instanceof Customer && ! $customer->financeLoans()->where('agent_id', $agentId)->exists(), 404);

        return $next($request);
    }
}

<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceAgentCash;
use App\Models\FinanceCapital;
use App\Models\FinanceCollection;
use App\Models\FinanceLoan;
use App\Support\AgentFilter;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sri Lakshmi Micro Finance home: money out with customers, what to
 * collect today (overdue and due), collected today / this month, money
 * lent this month, and the latest loans. An agent sees only their own
 * loans and collections, and the money in their own hand.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $today = today(config('app.business_timezone'));

        $day = [$today->toDateString().' 00:00:00', $today->toDateString().' 23:59:59'];
        $month = [$today->copy()->startOfMonth()->toDateString().' 00:00:00', $today->toDateString().' 23:59:59'];

        $agent = AgentFilter::heldToOwn($request) ? $request->user() : null;
        $collections = fn () => FinanceCollection::query()->when($agent, fn ($query) => $query->where('collected_by', $agent->id));

        $running = CollectionController::runningLoans($agent?->id);
        $lentThisMonth = FinanceLoan::query()->whereBetween('loaned_on', $month)->when($agent, fn ($query) => $query->where('agent_id', $agent->id));

        return view('finance.dashboard', [
            'today' => $today,
            'isAgent' => $agent !== null,
            'available' => $agent ? FinanceAgentCash::inHand($agent) : FinanceCapital::position()['available'],
            'outstanding' => (int) $running->sum(fn (array $row) => $row['loan']->balance()),
            'runningCount' => $running->count(),
            'overdueTotal' => (int) $running->sum('standing.overdue'),
            'overdueCount' => $running->where('standing.state', 'overdue')->count(),
            'dueTodayTotal' => (int) $running->sum('standing.due_today'),
            'dueTodayCount' => $running->filter(fn (array $row) => $row['standing']['due_today'] > 0)->count(),
            'collectedToday' => (int) $collections()->whereBetween('collected_at', $day)->sum('amount'),
            'collectedTodayCount' => $collections()->whereBetween('collected_at', $day)->count(),
            'collectedMonth' => (int) $collections()->whereBetween('collected_at', $month)->sum('amount'),
            'lentMonth' => (int) (clone $lentThisMonth)->sum('principal') - (int) (clone $lentThisMonth)->sum('processing_fee') - (int) (clone $lentThisMonth)->sum('gst'),
            'lentMonthCount' => (clone $lentThisMonth)->count(),
            'toCollect' => $running->filter(fn (array $row) => $row['standing']['to_collect'] > 0)->take(8)->values(),
            'recentLoans' => FinanceLoan::query()->when($agent, fn ($query) => $query->where('agent_id', $agent->id))->with('customer')->withSum('collections as collected_total', 'amount')->latest('loaned_on')->latest('id')->take(6)->get(),
        ]);
    }
}

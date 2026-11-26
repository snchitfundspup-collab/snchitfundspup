<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ExpenseController;
use App\Models\FinanceAgentCash;
use App\Models\User;
use App\Support\AgentFilter;
use App\Support\ReportPdf as Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Agent Cash (Sri Lakshmi Micro Finance): the money in each agent's hand
 * and in the treasury. The owner records three things — money taken from
 * the treasury, money given back to it, and money passed from one agent to
 * another — and sees everyone; an agent sees only their own. The Agent
 * Balance Sheet report lists every rupee in and out of one agent's hand
 * for a period (with the loans they gave and the money they collected).
 */
class AgentCashController extends Controller
{
    private const RECENT = 20;

    public function index(Request $request): View
    {
        $isAgent = AgentFilter::heldToOwn($request);

        return view('finance.agent-cash', [
            'isAgent' => $isAgent,
            'overview' => $isAgent ? null : FinanceAgentCash::overview(),
            'myHand' => $isAgent ? FinanceAgentCash::inHand($request->user()) : null,
            'staff' => User::query()->active()->orderBy('name')->get(['id', 'name']),
            'recent' => FinanceAgentCash::query()
                ->with(['agent', 'otherAgent'])
                ->when($isAgent, fn ($query) => $query->where(fn ($query) => $query->where('agent_id', $request->user()->id)->orWhere('other_agent_id', $request->user()->id)))
                ->latest('entry_at')
                ->latest('id')
                ->take(self::RECENT)
                ->get(),
        ]);
    }

    /**
     * Money taken from / given to the treasury, or passed from one agent to
     * another (owner only), saved at the current time.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'amount' => is_string($request->input('amount')) ? str_replace([',', ' ', '₹'], '', $request->input('amount')) : $request->input('amount'),
        ]);

        $validated = $request->validate([
            'type' => ['required', Rule::in(FinanceAgentCash::RECORDED)],
            'agent_id' => ['required', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'other_agent_id' => ['nullable', 'required_if:type,'.FinanceAgentCash::TRANSFER, 'integer', 'different:agent_id', Rule::exists('users', 'id')->where('is_active', true)],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
        ], [
            'agent_id.required' => 'Choose the agent.',
            'other_agent_id.required_if' => 'Choose the agent who receives the money.',
            'other_agent_id.different' => 'The giver and the receiver must be different agents.',
            'amount.integer' => 'Enter the amount in whole rupees.',
            'amount.min' => 'Enter an amount of at least ₹1.',
        ]);

        $agent = User::query()->findOrFail($validated['agent_id']);

        if (in_array($validated['type'], [FinanceAgentCash::TO_TREASURY, FinanceAgentCash::TRANSFER], true)
            && (int) $validated['amount'] > ($inHand = FinanceAgentCash::inHand($agent))) {
            return back()->withInput()->withErrors(['amount' => "{$agent->name} has only ₹".number_format(max(0, $inHand)).' in hand.']);
        }

        FinanceAgentCash::create([
            'type' => $validated['type'],
            'agent_id' => $agent->id,
            'other_agent_id' => $validated['type'] === FinanceAgentCash::TRANSFER ? $validated['other_agent_id'] : null,
            'amount' => (int) $validated['amount'],
            'entry_at' => now(config('app.business_timezone'))->format('Y-m-d H:i:s'),
            'recorded_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('finance.agent-cash.index')
            ->with('success', 'Saved. '.$agent->name.' now has ₹'.number_format(FinanceAgentCash::inHand($agent)).' in hand.');
    }

    /**
     * Remove an entry made by mistake (owner only).
     */
    public function destroy(FinanceAgentCash $entry): RedirectResponse
    {
        $entry->delete();

        return redirect()
            ->route('finance.agent-cash.index')
            ->with('success', 'Entry removed.');
    }

    public function balanceSheet(Request $request): View
    {
        return view('reports.agent-balance-sheet', $this->balanceSheetData($request));
    }

    public function balanceSheetPrint(Request $request): View
    {
        return view('reports.agent-balance-sheet-print', $this->balanceSheetData($request));
    }

    public function balanceSheetPdf(Request $request): Response
    {
        $data = $this->balanceSheetData($request);

        return Pdf::loadView('pdf.agent-balance-sheet', $data)
            ->setPaper('a4', 'portrait')
            ->download('Agent-Balance-Sheet-'.Str::slug($data['agent']?->name ?? 'agent').'-'.$data['filters']['from'].'-to-'.$data['filters']['to'].'.pdf');
    }

    /**
     * One agent's money in and out for a period: what they had at the start,
     * every entry (taken from / given to the treasury, from / to another
     * agent, loans given with the customer, collections with the customer),
     * the totals and what they have at the end.
     *
     * @return array<string, mixed>
     */
    private function balanceSheetData(Request $request): array
    {
        $staff = AgentFilter::heldToOwn($request)
            ? User::query()->whereKey($request->user()->id)->get(['id', 'name'])
            : User::query()->active()->orderBy('name')->get(['id', 'name']);
        $firstWithBook = FinanceAgentCash::query()->orderBy('id')->value('agent_id');
        $agent = $staff->firstWhere('id', $request->integer('agent'))
            ?? $staff->firstWhere('id', AgentFilter::heldToOwn($request) ? $request->user()->id : $firstWithBook)
            ?? $staff->firstWhere('id', $request->user()->id)
            ?? $staff->first();

        [$from, $to, $rangeKey] = ExpenseController::dateRange($request, 'month');

        $ledger = $agent ? FinanceAgentCash::ledger(User::query()->findOrFail($agent->id)) : collect();
        $before = $ledger->filter(fn (array $row) => $row['at']->toDateString() < $from);
        $rows = $ledger->filter(fn (array $row) => $row['at']->toDateString() >= $from && $row['at']->toDateString() <= $to)->values();

        $opening = (int) ($before->last()['balance'] ?? 0);
        $in = (int) $rows->sum('in');
        $out = (int) $rows->sum('out');

        return [
            'companyName' => 'Micro Finance',
            'staff' => $staff,
            'agent' => $agent,
            'filters' => ['q' => '', 'from' => $from, 'to' => $to, 'range' => $rangeKey],
            'ranges' => ExpenseController::quickRanges(),
            'query' => array_filter(['agent' => $agent?->id]) + ($rangeKey !== '' ? ['range' => $rangeKey] : ['from' => $from, 'to' => $to]),
            'rows' => $rows,
            'summary' => [
                'opening' => $opening,
                'in' => $in,
                'out' => $out,
                'closing' => $opening + $in - $out,
            ],
        ];
    }
}

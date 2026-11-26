<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceAgentCash;
use App\Models\User;
use App\Support\AgentFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Agent Cash (Sri Lakshmi Micro Finance): the money in each agent's hand —
 * opening balance, taken from the treasury, lent to customers (in hand),
 * collected, passed between agents and returned to the treasury — and
 * what is left in the treasury. The owner records the entries and sees
 * everyone; an agent sees only their own cash book.
 */
class AgentCashController extends Controller
{
    public function index(Request $request): View
    {
        $isAgent = AgentFilter::heldToOwn($request);
        $overview = $isAgent ? null : FinanceAgentCash::overview();

        $agent = $isAgent
            ? $request->user()
            : (User::query()->active()->find($request->integer('agent')) ?? $overview['agents']->first()['agent'] ?? null);

        $ledger = $agent ? FinanceAgentCash::ledger($agent) : collect();

        return view('finance.agent-cash', [
            'isAgent' => $isAgent,
            'overview' => $overview,
            'agent' => $agent,
            'ledger' => $ledger->reverse()->values(),
            'inHand' => (int) ($ledger->last()['balance'] ?? 0),
            'staff' => User::query()->active()->orderBy('name')->get(['id', 'name']),
            'now' => now(config('app.business_timezone')),
        ]);
    }

    /**
     * Record an opening balance, money taken from / returned to the
     * treasury, or money passed from one agent to another (owner only).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'amount' => is_string($request->input('amount')) ? str_replace([',', ' ', '₹'], '', $request->input('amount')) : $request->input('amount'),
            'entry_at' => is_string($request->input('entry_at')) ? str_replace('T', ' ', $request->input('entry_at')) : $request->input('entry_at'),
        ]);

        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(FinanceAgentCash::TYPES))],
            'agent_id' => ['required', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'other_agent_id' => ['nullable', 'required_if:type,'.FinanceAgentCash::TRANSFER, 'integer', 'different:agent_id', Rule::exists('users', 'id')->where('is_active', true)],
            'amount' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'entry_at' => ['required', 'date', 'before_or_equal:'.now(config('app.business_timezone'))->format('Y-m-d H:i:59')],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'other_agent_id.required_if' => 'Choose the agent who receives the money.',
            'other_agent_id.different' => 'Choose a different agent to give the money to.',
            'amount.integer' => 'Enter the amount in whole rupees.',
            'entry_at.before_or_equal' => 'The date and time cannot be in the future.',
        ]);

        $agent = User::query()->findOrFail($validated['agent_id']);

        if (in_array($validated['type'], [FinanceAgentCash::TO_TREASURY, FinanceAgentCash::TRANSFER], true)
            && (int) $validated['amount'] > ($inHand = FinanceAgentCash::inHand($agent))) {
            return back()->withInput()->withErrors(['amount' => "{$agent->name} has only ₹".number_format(max(0, $inHand)).' in hand.']);
        }

        if ($validated['type'] !== FinanceAgentCash::TRANSFER) {
            $validated['other_agent_id'] = null;
        }

        FinanceAgentCash::create([...$validated, 'recorded_by' => $request->user()->id]);

        return redirect()
            ->route('finance.agent-cash.index', ['agent' => $agent->id])
            ->with('success', 'Saved. '.$agent->name.' now has ₹'.number_format(FinanceAgentCash::inHand($agent)).' in hand.');
    }

    /**
     * Remove an entry made by mistake (owner only).
     */
    public function destroy(FinanceAgentCash $entry): RedirectResponse
    {
        $agentId = $entry->agent_id;
        $entry->delete();

        return redirect()
            ->route('finance.agent-cash.index', ['agent' => $agentId])
            ->with('success', 'Entry removed.');
    }
}

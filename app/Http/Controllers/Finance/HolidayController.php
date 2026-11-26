<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceHoliday;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Holidays (Sri Lakshmi Micro Finance, owner only): dates on which chosen
 * agents' customers pay nothing (e.g. a festival week). Instalments falling
 * on them move later, and every later instalment with them, so no one
 * becomes overdue (FinanceLoan::dueDates()).
 */
class HolidayController extends Controller
{
    public function index(): View
    {
        return view('finance.holidays', [
            'staff' => User::query()->active()->orderBy('name')->get(['id', 'name']),
            'holidays' => FinanceHoliday::query()->with('agent')->latest('starts_on')->latest('id')->get(),
            'today' => today(config('app.business_timezone')),
        ]);
    }

    /**
     * One holiday per chosen agent, for the same dates.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on', 'before_or_equal:'.today(config('app.business_timezone'))->addYear()->toDateString()],
            'agent_ids' => ['required', 'array', 'min:1'],
            'agent_ids.*' => ['integer', Rule::exists('users', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [
            'agent_ids.required' => 'Tick at least one agent.',
            'ends_on.after_or_equal' => 'The last day cannot be before the first day.',
        ]);

        foreach (array_unique($validated['agent_ids']) as $agentId) {
            FinanceHoliday::create([
                'starts_on' => $validated['starts_on'],
                'ends_on' => $validated['ends_on'],
                'agent_id' => $agentId,
                'notes' => $validated['notes'] ?? null,
                'recorded_by' => $request->user()->id,
            ]);
        }

        return redirect()
            ->route('finance.holidays.index')
            ->with('success', 'Holiday saved. Instalments on those days move later for the chosen agents\' customers.');
    }

    public function destroy(FinanceHoliday $holiday): RedirectResponse
    {
        $holiday->delete();

        return redirect()
            ->route('finance.holidays.index')
            ->with('success', 'Holiday removed. The due dates are back as before.');
    }
}

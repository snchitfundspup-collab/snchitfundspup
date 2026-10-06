<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceCapital;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Capital & Cash (Sri Lakshmi Micro Finance): money invested into the
 * business or taken out, and how much is available to lend now.
 */
class CapitalController extends Controller
{
    public function index(): View
    {
        return view('finance.capital', [
            'position' => FinanceCapital::position(),
            'entries' => FinanceCapital::query()->with('recorder')->latest('entry_on')->latest('id')->paginate(20),
            'today' => today(config('app.business_timezone')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['amount' => is_string($request->input('amount')) ? str_replace([',', ' ', '₹'], '', $request->input('amount')) : $request->input('amount')]);

        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(FinanceCapital::TYPES))],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'entry_on' => ['required', 'date', 'before_or_equal:'.today(config('app.business_timezone'))->toDateString()],
            'method' => ['required', Rule::in(array_keys(Payment::METHODS))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'amount.integer' => 'Enter the amount in whole rupees.',
            'entry_on.before_or_equal' => 'The date cannot be in the future.',
        ]);

        if ($validated['type'] === FinanceCapital::WITHDRAW && (int) $validated['amount'] > FinanceCapital::position()['available']) {
            return back()->withInput()->withErrors(['amount' => 'You can take out at most ₹'.number_format(max(0, FinanceCapital::position()['available'])).' — the money available now.']);
        }

        FinanceCapital::create([...$validated, 'recorded_by' => $request->user()?->id]);

        return redirect()
            ->route('finance.capital.index')
            ->with('success', ($validated['type'] === FinanceCapital::INVEST ? 'Capital of ₹' : 'Withdrawal of ₹').number_format((int) $validated['amount']).' saved.');
    }

    public function destroy(FinanceCapital $capital): RedirectResponse
    {
        $capital->delete();

        return redirect()->route('finance.capital.index')->with('success', 'Entry deleted.');
    }
}

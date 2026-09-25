<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\CustomerLoginRequest;
use App\Models\Customer;
use App\Models\UsageLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Customers sign in to their own pages with their phone number. When family
 * members share a phone, they pick whose account to open.
 */
class AuthController extends Controller
{
    /**
     * Session key holding the customers who share the phone just signed in.
     */
    private const CHOICES = 'portal_login_choices';

    public function create(): View
    {
        return view('portal.auth.login');
    }

    public function store(CustomerLoginRequest $request): RedirectResponse
    {
        $customers = $request->customers();

        if ($customers->count() > 1) {
            $request->session()->put(self::CHOICES, $customers->pluck('id')->all());

            return redirect()->route('portal.choose');
        }

        return $this->signIn($request, $customers->first());
    }

    /**
     * Pick whose account to open (phone shared by several customers).
     */
    public function choose(Request $request): View|RedirectResponse
    {
        $ids = $request->session()->get(self::CHOICES, []);

        if ($ids === []) {
            return redirect()->route('portal.login');
        }

        return view('portal.auth.choose', [
            'customers' => Customer::query()->whereKey($ids)->orderBy('name')->get(),
        ]);
    }

    public function chosen(Request $request): RedirectResponse
    {
        $ids = $request->session()->get(self::CHOICES, []);
        $customer = in_array($request->integer('customer'), $ids, true) ? Customer::find($request->integer('customer')) : null;

        if (! $customer) {
            return redirect()->route('portal.login');
        }

        $request->session()->forget(self::CHOICES);

        return $this->signIn($request, $customer);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }

    private function signIn(Request $request, Customer $customer): RedirectResponse
    {
        Auth::guard('customer')->login($customer);

        $request->session()->regenerate();

        $customer->forceFill(['last_login_at' => now()])->save();

        UsageLog::record($request, customer: $customer, event: 'login');

        /* first their own password; then back to the customer page they asked for — never an office page */
        $intended = (string) $request->session()->pull('url.intended', '');

        if ($customer->mustChangePassword()) {
            return redirect()->route('portal.password.edit');
        }

        return redirect()->to(str_starts_with($intended, url('/my')) ? $intended : route('portal.dashboard'));
    }
}

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
 * Customers sign in to their own pages with their phone number. Family
 * members sharing a phone share the password and see all their details
 * together, so the oldest customer ID on the phone is signed in.
 */
class AuthController extends Controller
{
    public function create(): View
    {
        return view('portal.auth.login');
    }

    public function store(CustomerLoginRequest $request): RedirectResponse
    {
        return $this->signIn($request, $request->customers()->first());
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

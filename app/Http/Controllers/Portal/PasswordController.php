<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * A customer changing their own password — required right after signing in
 * with the default password or one the office set.
 */
class PasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('portal.password', ['customer' => $request->user('customer')]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        /* already signed in, so only the new password (twice) — never the default one */
        $request->validate([
            'password' => ['required', 'string', 'confirmed', Password::min(6), 'not_in:'.Customer::DEFAULT_PASSWORD],
        ], [
            'password.not_in' => 'The new password cannot be '.Customer::DEFAULT_PASSWORD.'. Please choose another one.',
        ]);

        $customer->choosePassword($request->string('password')->value());

        return redirect()->route('portal.dashboard')->with('success', 'Your password has been changed. Use it with your phone number next time.');
    }
}

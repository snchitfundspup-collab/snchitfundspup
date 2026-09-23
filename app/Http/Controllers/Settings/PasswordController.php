<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PasswordController extends Controller
{
    /**
     * Show the change password page.
     */
    public function edit(): View
    {
        return view('settings.password');
    }

    /**
     * Save the signed-in admin's new password.
     */
    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->validated('password'),
        ]);

        $request->session()->regenerate();

        return redirect()
            ->route('password.edit')
            ->with('success', 'Your password has been changed.');
    }
}

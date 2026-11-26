<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Staff passwords (owner only, name menu): the owner sets a new password
 * for an agent who forgot theirs. The agent is signed out everywhere and
 * signs in again with the new password.
 */
class StaffPasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('settings.staff-password', [
            'staff' => $this->agents($request),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', Rule::in($this->agents($request)->pluck('id'))],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ], [
            'user_id.required' => 'Choose the agent.',
            'user_id.in' => 'Choose the agent.',
        ]);

        $agent = User::query()->findOrFail($validated['user_id']);

        $agent->forceFill([
            'password' => $validated['password'],
            'remember_token' => null,
        ])->save();

        /* sign them out on every device */
        DB::table('sessions')->where('user_id', $agent->id)->delete();

        return redirect()
            ->route('password.staff')
            ->with('success', "{$agent->name}'s password has been changed.");
    }

    /**
     * Active staff other than the owner signed in.
     *
     * @return Collection<int, User>
     */
    private function agents(Request $request): Collection
    {
        return User::query()->active()->whereKeyNot($request->user()->id)->orderBy('name')->get(['id', 'name', 'username']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Edit Members: add customers to a group's seats, remove and reorder them —
 * before and after the group starts. A forming group may hold more members
 * than planned; Start Group checks for the exact number.
 */
class ChitGroupMemberController extends Controller
{
    /**
     * Search results shown at once on the Edit Members page.
     */
    private const SEARCH_LIMIT = 20;

    /**
     * Edit Members page: current members + customer search.
     */
    public function edit(Request $request, ChitGroup $group): View
    {
        $search = trim((string) $request->input('q', ''));

        $results = $search === ''
            ? collect()
            : Customer::query()
                ->where('is_active', true)
                ->whereDoesntHave('memberships', fn ($query) => $query->where('chit_group_id', $group->id))
                ->search($search)
                ->orderBy('name')
                ->limit(self::SEARCH_LIMIT)
                ->get();

        $members = $group->members()->with('customer')->get();

        return view('groups.members', compact('group', 'members', 'results', 'search'));
    }

    /**
     * Add a customer to the group as a new seat.
     */
    public function store(Request $request, ChitGroup $group): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('is_active', true)],
        ], [
            'customer_id.exists' => 'Only active customers can be added to a group.',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);

        $member = DB::transaction(fn () => $group->members()->create([
            'customer_id' => $customer->id,
            'member_code' => ChitGroupMember::nextCodeFor($group, $customer),
            'position' => (int) $group->members()->max('position') + 1,
        ]));

        return redirect()
            ->route('groups.members.edit', array_filter(['group' => $group, 'q' => $request->input('q')]))
            ->with('success', "Added {$customer->name} as {$member->member_code}.");
    }

    /**
     * Remove a seat from the group.
     */
    public function destroy(Request $request, ChitGroup $group, ChitGroupMember $member): RedirectResponse
    {
        if ($member->payments()->exists()) {
            return redirect()
                ->route('groups.members.edit', array_filter(['group' => $group, 'q' => $request->input('q')]))
                ->withErrors(['member' => "{$member->member_code} has payments recorded, so it cannot be removed. Cancel their receipts first."]);
        }

        if ($member->wonDraw()->exists() || $member->drawEntries()->exists()) {
            return redirect()
                ->route('groups.members.edit', array_filter(['group' => $group, 'q' => $request->input('q')]))
                ->withErrors(['member' => "{$member->member_code} has taken part in a draw, so it cannot be removed."]);
        }

        $member->load('customer');
        $member->delete();

        return redirect()
            ->route('groups.members.edit', array_filter(['group' => $group, 'q' => $request->input('q')]))
            ->with('success', "Removed {$member->customer->name} ({$member->member_code}).");
    }

    /**
     * Save the serial order after members are dragged: member_ids lists
     * every member of the group, first to last.
     */
    public function reorder(Request $request, ChitGroup $group): JsonResponse
    {
        $memberIds = $group->members()->pluck('id')->all();

        $validated = $request->validate([
            'member_ids' => ['required', 'array', 'size:'.count($memberIds)],
            'member_ids.*' => ['required', 'integer', 'distinct', Rule::in($memberIds)],
        ], [
            'member_ids.size' => 'The member list has changed. Please reload the page and try again.',
            'member_ids.*.in' => 'The member list has changed. Please reload the page and try again.',
        ]);

        DB::transaction(function () use ($group, $validated) {
            foreach ($validated['member_ids'] as $index => $memberId) {
                $group->members()->whereKey($memberId)->update(['position' => $index + 1]);
            }
        });

        return response()->json(['message' => 'Order saved.']);
    }
}

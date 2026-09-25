<?php

namespace App\Http\Controllers;

use App\Models\ChitJoinRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Join Requests (Groups): customers who asked, from their own pages, to join
 * a group that is forming. The office adds them to the group or dismisses
 * the request — customers cannot join on their own.
 */
class JoinRequestController extends Controller
{
    /**
     * @var array<string, string>
     */
    public const TABS = [
        ChitJoinRequest::STATUS_PENDING => 'Waiting',
        ChitJoinRequest::STATUS_APPROVED => 'Added',
        ChitJoinRequest::STATUS_DISMISSED => 'Dismissed',
    ];

    public function index(Request $request): View
    {
        $status = array_key_exists((string) $request->input('status'), self::TABS) ? (string) $request->input('status') : ChitJoinRequest::STATUS_PENDING;

        return view('groups.join-requests', [
            'status' => $status,
            'counts' => ChitJoinRequest::query()->toBase()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'requests' => ChitJoinRequest::query()
                ->where('status', $status)
                ->with(['customer', 'chitGroup' => fn ($query) => $query->withCount('members'), 'decider'])
                ->when($status === ChitJoinRequest::STATUS_PENDING, fn ($query) => $query->oldest('id'), fn ($query) => $query->latest('decided_at'))
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function approve(Request $request, ChitJoinRequest $joinRequest): RedirectResponse
    {
        $group = $joinRequest->chitGroup;
        $seatsLeft = max(0, $group->member_count - $group->members()->count());

        $validated = $request->validate([
            'seats' => ['required', 'integer', 'min:1', 'max:'.max(1, min(ChitJoinRequest::MAX_SEATS, $seatsLeft))],
        ], [
            'seats.max' => $seatsLeft === 0 ? "{$group->name} is full." : "Only {$seatsLeft} seat(s) are free in {$group->name}.",
        ]);

        if (! $joinRequest->isPending() || ! $group->isForming() || $seatsLeft === 0) {
            return redirect()
                ->route('groups.requests.index')
                ->withErrors(['request' => ! $group->isForming() ? "{$group->name} has already started, so members cannot be added." : 'This request can no longer be approved.']);
        }

        $added = $joinRequest->approve($request->user(), (int) $validated['seats']);

        return redirect()
            ->route('groups.requests.index')
            ->with('success', "Added {$joinRequest->customer->name} to {$group->name} ({$added} ".($added === 1 ? 'seat' : 'seats').').');
    }

    public function dismiss(Request $request, ChitJoinRequest $joinRequest): RedirectResponse
    {
        $validated = $request->validate(['reply' => ['nullable', 'string', 'max:500']]);

        if ($joinRequest->isPending()) {
            $joinRequest->dismiss($request->user(), $validated['reply'] ?? null);
        }

        return redirect()
            ->route('groups.requests.index')
            ->with('success', "Dismissed {$joinRequest->customer->name}'s request.");
    }
}

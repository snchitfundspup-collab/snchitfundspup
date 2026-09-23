<?php

namespace App\Http\Controllers;

use App\Models\ChitGroup;
use App\Models\Draw;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Monthly draws: the admin picks the interested members, the server picks
 * the winner at random (the wheel then animates to them), and the prize
 * money is later paid out with a voucher the customer signs.
 */
class DrawController extends Controller
{
    private const PER_PAGE = 15;

    /**
     * The list tabs: Draw Details (voucher not printed yet), Pending
     * Payouts, Past Winners (voucher printed) and All.
     */
    private const STATUSES = ['', 'pending', 'past', 'all'];

    /**
     * Draw Details / Pending Payouts / Past Winners.
     */
    public function index(Request $request): View
    {
        $status = in_array($request->input('status'), self::STATUSES, true) ? $request->input('status') : '';
        $search = trim((string) $request->input('q', ''));

        $draws = Draw::query()
            ->with(['chitGroup', 'winner.customer'])
            ->when($status === '', fn ($query) => $query->current())
            ->when($status === 'pending', fn ($query) => $query->whereNull('paid_at'))
            ->when($status === 'past', fn ($query) => $query->pastWinners())
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('voucher_number', 'like', "%{$search}%")
                        ->orWhereHas('chitGroup', fn ($group) => $group->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('winner.customer', fn ($customer) => $customer->search($search))
                        ->orWhereHas('winner', fn ($winner) => $winner->where('member_code', 'like', "%{$search}%"));
                });
            })
            ->latest('drawn_at')
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('draws.index', [
            'draws' => $draws,
            'status' => $status,
            'search' => $search,
            'currentCount' => Draw::current()->count(),
            'pendingCount' => Draw::whereNull('paid_at')->count(),
            'pendingAmount' => (int) Draw::whereNull('paid_at')->sum('withdrawal_amount'),
            'pastCount' => Draw::pastWinners()->count(),
        ]);
    }

    /**
     * Winners Report: every group's winners month by month, printable.
     */
    public function winners(Request $request): View
    {
        return view('draws.winners', $this->winnersReport($request));
    }

    /**
     * Download the Winners Report shown on screen (same group filter).
     */
    public function winnersPdf(Request $request): Response
    {
        $report = $this->winnersReport($request);

        $fileName = 'Winners-'.($report['selectedGroup'] ? Str::slug($report['selectedGroup']->name) : 'all-groups').'.pdf';

        return Pdf::loadView('pdf.winners', $report)
            ->setPaper('a4', 'landscape')
            ->download($fileName);
    }

    /**
     * Groups that have held draws, each with its draws in month order.
     *
     * @return array{groups: Collection<int, ChitGroup>, reportGroups: Collection<int, ChitGroup>, selectedGroup: ?ChitGroup, totalPrize: int, totalPaidOut: int, drawCount: int}
     */
    private function winnersReport(Request $request): array
    {
        $groups = ChitGroup::query()
            ->whereHas('draws')
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedGroup = $groups->firstWhere('id', $request->integer('group'));

        $reportGroups = ChitGroup::query()
            ->whereHas('draws')
            ->when($selectedGroup, fn ($query) => $query->whereKey($selectedGroup->id))
            ->with(['draws.winner.customer'])
            ->orderBy('name')
            ->get();

        $draws = $reportGroups->flatMap->draws;

        return [
            'groups' => $groups,
            'reportGroups' => $reportGroups,
            'selectedGroup' => $selectedGroup,
            'totalPrize' => (int) $draws->sum(fn (Draw $draw) => $draw->prizeAmount()),
            'totalPaidOut' => (int) $draws->filter->isPaidOut()->sum('payout_amount'),
            'drawCount' => $draws->count(),
        ];
    }

    /**
     * The voucher was printed: the draw is settled and moves to Past Winners.
     */
    public function voucherPrinted(Request $request, Draw $draw): JsonResponse|RedirectResponse
    {
        abort_unless($draw->isPaidOut(), 404);

        $draw->markVoucherPrinted();

        if ($request->wantsJson()) {
            return response()->json(['voucher_printed_at' => $draw->voucher_printed_at?->format('d M Y, h:i A')]);
        }

        return redirect()->route('draws.show', $draw);
    }

    /**
     * Run Draw: choose a group, tick the interested members, spin.
     */
    public function create(Request $request): View
    {
        $groups = ChitGroup::query()
            ->where('status', ChitGroup::STATUS_RUNNING)
            ->orderBy('name')
            ->get();

        $group = $groups->firstWhere('id', $request->integer('group'))
            ?? $groups->first(fn (ChitGroup $group) => $group->canDrawNow())
            ?? $groups->first();

        $eligible = $group?->canDrawNow()
            ? $group->membersYetToWin()->with('customer')->get()
            : collect();

        $month = $group?->nextDrawMonth();

        return view('draws.create', [
            'groups' => $groups,
            'group' => $group,
            'month' => $month,
            'canDraw' => (bool) $group?->canDrawNow(),
            'eligible' => $eligible,
            'withdrawal' => $group && $month ? $group->withdrawalForMonth($month) : 0,
            'lastDraw' => $group?->draws()->reorder('month_number', 'desc')->with('winner.customer')->first(),
        ]);
    }

    /**
     * Run the draw: pick the winner at random from the chosen members and
     * save it before the wheel animates (JSON for the wheel, or a redirect).
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'chit_group_id' => ['required', 'integer', Rule::exists('chit_groups', 'id')],
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['required', 'integer', 'distinct'],
        ], [
            'participant_ids.required' => 'Choose at least one interested member for the draw.',
            'participant_ids.min' => 'Choose at least one interested member for the draw.',
        ]);

        $draw = DB::transaction(function () use ($validated, $request) {
            /* lock the group so two clicks cannot draw the same month twice */
            $group = ChitGroup::lockForUpdate()->findOrFail($validated['chit_group_id']);

            if (! $group->canDrawNow()) {
                throw ValidationException::withMessages([
                    'chit_group_id' => 'This group has no draw due right now.',
                ]);
            }

            $eligibleIds = $group->membersYetToWin()->pluck('id')->all();
            $participantIds = array_map('intval', $validated['participant_ids']);

            if (array_diff($participantIds, $eligibleIds) !== []) {
                throw ValidationException::withMessages([
                    'participant_ids' => 'Only members of this group who have not won yet can enter the draw.',
                ]);
            }

            $month = $group->nextDrawMonth();

            $draw = Draw::create([
                'chit_group_id' => $group->id,
                'month_number' => $month,
                'winner_member_id' => collect($participantIds)->random(),
                'withdrawal_amount' => $group->withdrawalForMonth($month),
                'drawn_at' => now(config('app.business_timezone'))->format('Y-m-d H:i:s'),
                'drawn_by' => $request->user()->id,
            ]);

            $draw->participants()->attach($participantIds);

            return $draw;
        });

        $draw->load('winner.customer');

        if ($request->wantsJson()) {
            return response()->json([
                'winner_id' => $draw->winner_member_id,
                'winner_name' => $draw->winner->customer->name,
                'winner_code' => $draw->winner->member_code,
                'url' => route('draws.show', $draw),
            ]);
        }

        return redirect()
            ->route('draws.show', $draw)
            ->with('success', "{$draw->winner->customer->name} ({$draw->winner->member_code}) won the draw.");
    }

    /**
     * Draw result, participants and the payout.
     */
    public function show(Draw $draw): View
    {
        $draw->load(['chitGroup', 'winner.customer', 'participants.customer', 'drawnBy', 'paidBy']);

        return view('draws.show', compact('draw'));
    }

    /**
     * Record that the prize money was handed to the winner: this is the
     * acknowledgement, and it numbers the payout voucher.
     */
    public function payout(Request $request, Draw $draw): RedirectResponse
    {
        if ($draw->isPaidOut()) {
            return redirect()->route('draws.show', $draw)->with('error', 'This prize has already been paid out.');
        }

        $request->merge([
            'payout_amount' => is_string($request->input('payout_amount'))
                ? str_replace([',', ' ', '₹'], '', $request->input('payout_amount'))
                : $request->input('payout_amount'),
            'paid_at' => is_string($request->input('paid_at'))
                ? str_replace('T', ' ', $request->input('paid_at'))
                : $request->input('paid_at'),
        ]);

        $validated = $request->validate([
            'payout_amount' => ['required', 'integer', 'min:1'],
            'payout_method' => ['required', Rule::in(array_keys(Payment::METHODS))],
            'payout_reference' => ['nullable', 'string', 'max:100'],
            'payout_notes' => ['nullable', 'string', 'max:1000'],
            'paid_at' => ['required', 'date', 'before_or_equal:'.now(config('app.business_timezone'))->format('Y-m-d H:i:59')],
        ], [
            'paid_at.before_or_equal' => 'The payout date and time cannot be in the future.',
        ]);

        $draw->update([
            ...$validated,
            'paid_by' => $request->user()->id,
            'voucher_number' => 'PV'.str_pad((string) $draw->id, 6, '0', STR_PAD_LEFT),
        ]);

        return redirect()
            ->route('draws.show', $draw)
            ->with('success', "Prize paid out. Voucher {$draw->voucher_number} is ready to give to the customer.");
    }

    /**
     * Download the payout voucher (A5) for the customer.
     */
    public function voucherPdf(Draw $draw): Response
    {
        abort_unless($draw->isPaidOut(), 404);

        $draw->markVoucherPrinted();

        $draw->load(['chitGroup', 'winner.customer', 'paidBy']);

        return Pdf::loadView('pdf.voucher', compact('draw'))
            ->setPaper('a5', 'portrait')
            ->download("Voucher-{$draw->voucher_number}.pdf");
    }

    /**
     * Cancel a draw entered by mistake (only before the prize is paid).
     */
    public function destroy(Draw $draw): RedirectResponse
    {
        if ($draw->isPaidOut()) {
            return redirect()->route('draws.show', $draw)->with('error', 'A paid-out draw cannot be cancelled.');
        }

        $groupId = $draw->chit_group_id;
        $month = $draw->month_number;

        $draw->delete();

        return redirect()
            ->route('draws.create', ['group' => $groupId])
            ->with('success', "The draw for month {$month} was cancelled. You can run it again.");
    }
}

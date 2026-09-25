<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CustomerStatementController;
use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use App\Models\ChitJoinRequest;
use App\Models\Customer;
use App\Models\Draw;
use App\Models\Payment;
use App\Support\ReportPdf as Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * SN Chit Funds for a signed-in customer: the groups they are in (month by
 * month payments, receipts, prize and withdrawal plan), the statement, and
 * the groups that are forming (upcoming). On an upcoming group they can say
 * they want to join; the office decides (Join Requests).
 */
class ChitController extends Controller
{
    public function groups(Request $request): View
    {
        return view('portal.chit.groups', ['seats' => self::seatsOf($this->customer($request))]);
    }

    public function seat(Request $request, ChitGroupMember $member): View
    {
        $this->ensureOwn($request, $member->customer_id);

        $member->load(['chitGroup.payouts', 'chitGroup.draws', 'allocations', 'payments' => fn ($query) => $query->latest('paid_at'), 'wonDraw']);
        $group = $member->chitGroup;

        $collectable = collect($member->collectableMonths())->keyBy('month');

        $months = collect($member->ledger())->map(fn (array $row) => $row + [
            'state' => match (true) {
                $row['balance'] === 0 => 'paid',
                ($collectable[$row['month']]['status'] ?? null) === 'pending' => 'pending',
                $row['paid'] > 0 => 'partial',
                ($collectable[$row['month']]['status'] ?? null) === 'due' => 'due',
                default => 'upcoming',
            },
        ]);

        return view('portal.chit.seat', [
            'member' => $member,
            'group' => $group,
            'status' => $group->isRunning() ? $member->collectionStatus() : null,
            'months' => $months,
            'monthsPaid' => $months->where('state', 'paid')->count(),
            'monthsPending' => $months->where('state', 'pending')->count(),
            'payments' => $member->payments,
            'wonDraw' => $member->wonDraw,
            'plan' => self::withdrawalPlan($group, $member),
        ]);
    }

    /**
     * The withdrawal plan: each month's date and amount, whether that
     * month's withdrawal is over (drawn, or its date has passed) and whether
     * this member won it.
     *
     * @return Collection<int, array{month: int, date: ?Carbon, withdrawal: int, over: bool, mine: bool}>
     */
    public static function withdrawalPlan(ChitGroup $group, ?ChitGroupMember $member = null): Collection
    {
        $today = today(config('app.business_timezone'))->toDateString();
        $drawsByMonth = $group->relationLoaded('draws') ? $group->draws->keyBy('month_number') : $group->draws()->get()->keyBy('month_number');

        return $group->payouts->map(function ($payout) use ($group, $drawsByMonth, $member, $today) {
            $date = $group->start_date ? $group->dateForMonth($payout->month_number) : null;

            return [
                'month' => $payout->month_number,
                'date' => $date,
                'withdrawal' => $payout->withdrawal_amount,
                'over' => $drawsByMonth->has($payout->month_number) || ($group->isRunning() && $date !== null && $date->toDateString() < $today),
                'mine' => $member !== null && ($drawsByMonth[$payout->month_number] ?? null)?->winner_member_id === $member->id,
            ];
        })->values();
    }

    public function receiptPdf(Request $request, Payment $payment): Response
    {
        $this->ensureOwn($request, $payment->customer_id);

        $payment->load(['customer', 'chitGroup', 'member.allocations', 'allocations', 'recorder']);
        $payment->member->setRelation('chitGroup', $payment->chitGroup);

        return Pdf::loadView('pdf.receipt', [
            'payment' => $payment,
            'balanceNow' => $payment->member->balanceDue(),
            'hasPending' => $payment->member->collectionStatus()['pending'] > 0,
        ])
            ->setPaper('a5', 'portrait')
            ->download("Receipt-{$payment->receipt_number}.pdf");
    }

    public function statementPdf(Request $request): Response
    {
        $customer = $this->customer($request);

        return Pdf::loadView('pdf.customer-statement', [
            'customer' => $customer,
            'seats' => CustomerStatementController::seatsFor($customer),
        ])
            ->setPaper('a4', 'portrait')
            ->download('Statement-'.$customer->customer_code.'-'.Str::slug($customer->name).'.pdf');
    }

    /**
     * The same statement, as a page that opens the print dialog.
     */
    public function statementPrint(Request $request): View
    {
        $customer = $this->customer($request);

        return view('pdf.customer-statement', [
            'layout' => 'layouts.print',
            'customer' => $customer,
            'seats' => CustomerStatementController::seatsFor($customer),
        ]);
    }

    public function upcoming(Request $request): View
    {
        $customer = $this->customer($request);

        return view('portal.chit.upcoming', [
            'groups' => self::upcomingGroups(),
            'joinedGroupIds' => $customer->memberships()->pluck('chit_group_id')->all(),
            'requests' => self::latestRequests($customer),
        ]);
    }

    public function upcomingGroup(Request $request, ChitGroup $group): View
    {
        abort_unless($group->isForming(), 404);

        $group->load(['payouts', 'draws'])->loadCount('members');

        $customer = $this->customer($request);

        return view('portal.chit.upcoming-group', [
            'group' => $group,
            'joined' => $customer->memberships()->where('chit_group_id', $group->id)->exists(),
            'joinRequest' => self::latestRequests($customer)->get($group->id),
            'plan' => self::withdrawalPlan($group),
            'maxSeats' => ChitJoinRequest::MAX_SEATS,
        ]);
    }

    /**
     * "I want to join": a request the office approves or dismisses.
     */
    public function showInterest(Request $request, ChitGroup $group): RedirectResponse
    {
        abort_unless($group->isForming(), 404);

        $customer = $this->customer($request);

        $validated = $request->validate([
            'seats' => ['required', 'integer', 'min:1', 'max:'.ChitJoinRequest::MAX_SEATS],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $alreadyWaiting = ChitJoinRequest::query()->pending()
            ->where('customer_id', $customer->id)
            ->where('chit_group_id', $group->id)
            ->exists();

        if (! $alreadyWaiting) {
            ChitJoinRequest::create([
                'chit_group_id' => $group->id,
                'customer_id' => $customer->id,
                'seats' => (int) $validated['seats'],
                'note' => $validated['note'] ?? null,
                'status' => ChitJoinRequest::STATUS_PENDING,
            ]);
        }

        return redirect()
            ->route('portal.upcoming.show', $group)
            ->with('success', 'Thank you! The office will call you about joining '.$group->name.'.');
    }

    public function withdrawInterest(Request $request, ChitJoinRequest $joinRequest): RedirectResponse
    {
        $this->ensureOwn($request, $joinRequest->customer_id);

        if ($joinRequest->isPending()) {
            $joinRequest->update(['status' => ChitJoinRequest::STATUS_WITHDRAWN]);
        }

        return redirect()
            ->route('portal.upcoming.show', $joinRequest->chit_group_id)
            ->with('success', 'Your request has been withdrawn.');
    }

    /**
     * The customer's latest join request for each group.
     *
     * @return Collection<int, ChitJoinRequest>
     */
    public static function latestRequests(Customer $customer): Collection
    {
        return ChitJoinRequest::query()
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->get()
            ->unique('chit_group_id')
            ->keyBy('chit_group_id');
    }

    /**
     * The customer's seats, newest group first, with where each stands and
     * how many of the group's months are done (their due date has come) or
     * still to come.
     *
     * @return Collection<int, array{member: ChitGroupMember, group: ChitGroup, status: ?array<string, mixed>, total_paid: int, won: ?Draw, months_done: int, months_left: int}>
     */
    public static function seatsOf(Customer $customer): Collection
    {
        return $customer->memberships()
            ->with(['chitGroup', 'allocations', 'wonDraw'])
            ->get()
            ->sortByDesc(fn (ChitGroupMember $member) => [$member->chitGroup->status === ChitGroup::STATUS_RUNNING, $member->chitGroup->start_date?->timestamp])
            ->map(fn (ChitGroupMember $member) => [
                'member' => $member,
                'group' => $member->chitGroup,
                'status' => $member->chitGroup->isRunning() ? $member->collectionStatus() : null,
                'total_paid' => $member->totalPaid(),
                'won' => $member->wonDraw,
                'months_done' => $done = self::monthsDone($member->chitGroup),
                'months_left' => $member->chitGroup->months - $done,
            ])
            ->values();
    }

    /**
     * Group months whose due date has come (all of them once completed).
     */
    public static function monthsDone(ChitGroup $group): int
    {
        return match ($group->status) {
            ChitGroup::STATUS_COMPLETED => $group->months,
            ChitGroup::STATUS_RUNNING => $group->currentMonthNumber(),
            default => 0,
        };
    }

    /**
     * Groups still forming, soonest start first, with seats taken.
     *
     * @return Collection<int, ChitGroup>
     */
    public static function upcomingGroups(): Collection
    {
        return ChitGroup::query()
            ->where('status', ChitGroup::STATUS_FORMING)
            ->withCount('members')
            ->orderBy('start_date')
            ->get();
    }

    private function customer(Request $request): Customer
    {
        return $request->user('customer');
    }

    /**
     * Customers only ever see their own seats and receipts.
     */
    private function ensureOwn(Request $request, ?int $customerId): void
    {
        abort_unless($customerId === $this->customer($request)->id, 404);
    }
}

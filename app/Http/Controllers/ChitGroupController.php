<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChitGroupRequest;
use App\Models\ChitGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ChitGroupController extends Controller
{
    /**
     * Status filters shown as tabs on the group list ('' = all).
     *
     * @var list<string>
     */
    public const STATUS_FILTERS = [
        '',
        ChitGroup::STATUS_FORMING,
        ChitGroup::STATUS_RUNNING,
        ChitGroup::STATUS_COMPLETED,
    ];

    /**
     * Group cards per page — 12 fills the 3-column grid evenly.
     */
    private const PER_PAGE = 12;

    /**
     * All groups, newest first, optionally filtered by status.
     */
    public function index(Request $request): View
    {
        $status = in_array($request->input('status'), self::STATUS_FILTERS, true)
            ? $request->input('status')
            : '';

        $groups = ChitGroup::query()
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->withCount('members')
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $statusCounts = ChitGroup::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('groups.index', compact('groups', 'status', 'statusCounts'));
    }

    /**
     * Add Group form.
     */
    public function create(): View
    {
        return view('groups.form', [
            'group' => new ChitGroup([
                'type' => ChitGroup::TYPE_DRAW,
                'months' => 20,
                'member_count' => 20,
                'start_date' => now()->startOfMonth()->addDays(14),
            ]),
            'schedule' => [],
            'copySources' => $this->copySources(),
        ]);
    }

    /**
     * Save a new group with its withdrawal schedule.
     */
    public function store(StoreChitGroupRequest $request): RedirectResponse
    {
        $group = DB::transaction(function () use ($request) {
            $group = ChitGroup::create([
                ...$request->safe()->except('payouts'),
                'status' => ChitGroup::STATUS_FORMING,
            ]);

            $this->saveSchedule($group, $request->payoutSchedule());

            return $group;
        });

        return redirect()
            ->route('groups.show', $group)
            ->with('success', "Group {$group->name} created.");
    }

    /**
     * View Group: details, schedule and seats.
     */
    public function show(ChitGroup $group): View
    {
        $group->load(['payouts', 'members.customer.memberships.chitGroup', 'members.allocations', 'members.wonDraw']);

        /* members need their group for the due calculation — reuse it */
        $group->members->each->setRelation('chitGroup', $group);

        return view('groups.show', [
            'group' => $group,
            'membersData' => $this->membersDetailData($group),
        ]);
    }

    /**
     * Edit Group form — allowed before and after the group starts.
     */
    public function edit(ChitGroup $group): View
    {
        return view('groups.form', [
            'group' => $group,
            'schedule' => $group->payouts->pluck('withdrawal_amount', 'month_number')->all(),
            'copySources' => $this->copySources($group),
        ]);
    }

    /**
     * Save changes to a group (forming or running), replacing its schedule.
     */
    public function update(StoreChitGroupRequest $request, ChitGroup $group): RedirectResponse
    {
        DB::transaction(function () use ($request, $group) {
            $group->update($request->safe()->except('payouts'));

            $group->payouts()->delete();

            $this->saveSchedule($group, $request->payoutSchedule());
        });

        return redirect()
            ->route('groups.show', $group)
            ->with('success', "Group {$group->name} updated.");
    }

    /**
     * Start Group: forming → running.
     */
    public function start(ChitGroup $group): RedirectResponse
    {
        if (! $group->isForming()) {
            return redirect()
                ->route('groups.show', $group)
                ->with('error', 'This group has already started.');
        }

        if (! $group->hasExactMemberCount()) {
            $memberTotal = $group->members()->count();

            return redirect()
                ->route('groups.show', $group)
                ->with('error', "To start, the group needs exactly {$group->member_count} members. It has {$memberTotal} now.");
        }

        $group->update([
            'status' => ChitGroup::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        return redirect()
            ->route('groups.show', $group)
            ->with('success', "Group {$group->name} has started.");
    }

    /**
     * @param  array<int, int>  $schedule  month_number => withdrawal amount
     */
    private function saveSchedule(ChitGroup $group, array $schedule): void
    {
        $group->payouts()->createMany(
            collect($schedule)
                ->map(fn (int $withdrawalAmount, int $monthNumber) => [
                    'month_number' => $monthNumber,
                    'withdrawal_amount' => $withdrawalAmount,
                ])
                ->values()
                ->all()
        );
    }

    /**
     * Existing groups whose schedule can be copied into the form.
     *
     * @return list<array{id: int, name: string, amount: int, months: int, installment: int, payouts: list<int>}>
     */
    private function copySources(?ChitGroup $except = null): array
    {
        return ChitGroup::query()
            ->with('payouts')
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->latest('id')
            ->get()
            ->map(fn (ChitGroup $source) => [
                'id' => $source->id,
                'name' => $source->name,
                'amount' => $source->amount,
                'months' => $source->months,
                'installment' => $source->installment_amount,
                'payouts' => $source->payouts->pluck('withdrawal_amount')->all(),
            ])
            ->all();
    }

    /**
     * Everything the member Details popup shows, keyed by member id: the
     * customer's full details and every group they hold a seat in.
     *
     * @return array<int, array<string, mixed>>
     */
    private function membersDetailData(ChitGroup $group): array
    {
        return $group->members
            ->mapWithKeys(function ($member) {
                $customer = $member->customer;

                return [$member->id => [
                    'customer_id' => $customer->id,
                    'member_code' => $member->member_code,
                    'customer_code' => $customer->customer_code,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'address' => $customer->address,
                    'remarks' => $customer->remarks,
                    'is_active' => $customer->is_active,
                    'groups' => $customer->memberships
                        ->sortBy('id')
                        ->map(fn ($membership) => [
                            'name' => $membership->chitGroup->name,
                            'member_code' => $membership->member_code,
                            'status' => $membership->chitGroup->status,
                            'url' => route('groups.show', $membership->chitGroup),
                        ])
                        ->values()
                        ->all(),
                ]];
            })
            ->all();
    }
}

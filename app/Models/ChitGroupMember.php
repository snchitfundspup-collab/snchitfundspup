<?php

namespace App\Models;

use Database\Factories\ChitGroupMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ChitGroupMember extends Model
{
    /** @use HasFactory<ChitGroupMemberFactory> */
    use HasFactory;

    protected $fillable = [
        'chit_group_id',
        'customer_id',
        'member_code',
        'position',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ChitGroup, $this>
     */
    public function chitGroup(): BelongsTo
    {
        return $this->belongsTo(ChitGroup::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'chit_group_member_id');
    }

    /**
     * @return HasMany<PaymentAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class, 'chit_group_member_id');
    }

    /**
     * Eager-load allocations as one row per month (the month's total), which
     * is all the dues need — far fewer rows than every payment for lists.
     *
     * @param  HasMany<PaymentAllocation, ChitGroupMember>  $query
     */
    public static function monthTotalsOnly(HasMany $query): void
    {
        $query->select('chit_group_member_id', 'month_number')
            ->selectRaw('SUM(amount) as amount')
            ->groupBy('chit_group_member_id', 'month_number');
    }

    /**
     * The draw this seat won (a seat wins at most once per group).
     *
     * @return HasOne<Draw, $this>
     */
    public function wonDraw(): HasOne
    {
        return $this->hasOne(Draw::class, 'winner_member_id');
    }

    /**
     * Draws this seat was entered into.
     *
     * @return BelongsToMany<Draw, $this>
     */
    public function drawEntries(): BelongsToMany
    {
        return $this->belongsToMany(Draw::class, 'draw_participants');
    }

    /*
    |--------------------------------------------------------------------------
    | Dues
    |--------------------------------------------------------------------------
    | Group months run from the start date (15 Sep – 14 Oct is month 1) and
    | each month falls due on its start date. Months are collected one at a
    | time: the member sees their oldest unpaid month, and the next month
    | only appears once that one is fully paid. A month is "upcoming" until
    | the 1st of its due date's calendar month, "due" from then until its
    | due date (1 – 15 Sep for 15 Sep) and "pending" after it; a month the
    | member has started paying in parts is "due" at once. If several
    | due dates have passed, all those unpaid months are pending together.
    | Month 1 is taken in full only; later months may be paid in parts.
    | Only a running group has anything to collect.
    */

    /**
     * The months that can be collected now, oldest first: every unpaid month
     * whose due date has passed (pending), or else the oldest unpaid month
     * (due, or upcoming before its due window). A later month never shows
     * while an earlier one is unpaid.
     *
     * @return list<array{month: int, balance: int, paid: int, status: string, period: string, due_on: string, full_only: bool}>
     */
    public function collectableMonths(?Carbon $asOf = null): array
    {
        $date = ($asOf ?? now(config('app.business_timezone')))->toDateString();

        $this->syncDuesCache();

        return $this->duesCache['collectable|'.$date] ??= $this->buildCollectableMonths($date);
    }

    /**
     * @return list<array{month: int, balance: int, paid: int, status: string, period: string, due_on: string, full_only: bool}>
     */
    private function buildCollectableMonths(string $date): array
    {
        $group = $this->chitGroup;

        if (! $group->isRunning()) {
            return [];
        }

        $overdueCount = $group->overdueMonthCount(Carbon::parse($date));
        $installment = $group->installment_amount;
        $paid = $this->paidByMonth();

        $months = [];

        foreach (range(1, $group->months) as $monthNumber) {
            $paidAmount = $paid[$monthNumber] ?? 0;
            $balance = max(0, $installment - $paidAmount);

            if ($balance === 0) {
                continue;
            }

            $isOverdue = $monthNumber <= $overdueCount;

            if (! $isOverdue && $months !== []) {
                break;
            }

            $months[] = [
                'month' => $monthNumber,
                'balance' => $balance,
                'paid' => $paidAmount,
                /* a month the member has started paying in parts is due at once */
                'status' => match (true) {
                    $isOverdue => 'pending',
                    /* Y-m-d strings compare in date order */
                    $paidAmount > 0, $date >= $group->dueWindowStart($monthNumber)->toDateString() => 'due',
                    default => 'upcoming',
                },
                'period' => $group->monthPeriodLabel($monthNumber),
                'due_on' => $group->dateForMonth($monthNumber)->format('d M Y'),
                'full_only' => $monthNumber === 1,
            ];

            if (! $isOverdue) {
                break;
            }
        }

        return $months;
    }

    /**
     * Amount paid towards each month: [month_number => rupees].
     *
     * @return array<int, int>
     */
    public function paidByMonth(): array
    {
        $this->syncDuesCache();

        if (! isset($this->duesCache['paid'])) {
            $paid = [];

            foreach ($this->allocations as $allocation) {
                $paid[$allocation->month_number] = ($paid[$allocation->month_number] ?? 0) + (int) $allocation->amount;
            }

            $this->duesCache['paid'] = $paid;
        }

        return $this->duesCache['paid'];
    }

    /**
     * Dues already worked out in this request (the Collect list and the
     * dashboard ask for them several times per member), and the allocations
     * they were worked out from.
     *
     * @var array<string, mixed>
     */
    private array $duesCache = [];

    private ?object $duesFor = null;

    /**
     * Empty the dues cache whenever the allocations are (re)loaded, so it
     * never outlives the payments it was worked out from.
     */
    private function syncDuesCache(): void
    {
        if ($this->duesFor !== $this->allocations) {
            $this->duesFor = $this->allocations;
            $this->duesCache = [];
        }
    }

    public function totalPaid(): int
    {
        return (int) $this->allocations->sum('amount');
    }

    /**
     * What the member owes now: the balance of the months that are due or
     * pending. A month that is still upcoming is not owed yet.
     */
    public function balanceDue(?Carbon $asOf = null): int
    {
        return (int) collect($this->collectableMonths($asOf))
            ->where('status', '!=', 'upcoming')
            ->sum('balance');
    }

    /**
     * Where this seat stands for collection today:
     *   pending  — one or more months are past their due date and unpaid
     *   partial  — the next month is part paid
     *   due      — the next month is in its due window, nothing paid yet
     *   upcoming — the next month's due window has not started yet
     *   clear    — nothing left to collect (every month paid)
     * in_due_window tells whether the next month is due now (the Due tab).
     *
     * @return array{state: string, month: int, months: list<int>, amount_due: int, next_balance: int, pending: int, in_due_window: bool}
     */
    public function collectionStatus(?Carbon $asOf = null): array
    {
        $date = ($asOf ?? now(config('app.business_timezone')))->toDateString();

        $this->syncDuesCache();

        return $this->duesCache['status|'.$date] ??= $this->buildCollectionStatus(Carbon::parse($date));
    }

    /**
     * @return array{state: string, month: int, months: list<int>, amount_due: int, next_balance: int, pending: int, in_due_window: bool}
     */
    private function buildCollectionStatus(Carbon $asOf): array
    {
        $months = collect($this->collectableMonths($asOf));
        $first = $months->first();

        $state = match (true) {
            $first === null => 'clear',
            $first['status'] === 'pending' => 'pending',
            $first['paid'] > 0 => 'partial',
            $first['status'] === 'due' => 'due',
            default => 'upcoming',
        };

        return [
            'state' => $state,
            'in_due_window' => ($first['status'] ?? null) === 'due',
            'month' => $first['month'] ?? 0,
            'months' => $months->pluck('month')->all(),
            'amount_due' => (int) $months->where('status', '!=', 'upcoming')->sum('balance'),
            'next_balance' => (int) ($first['balance'] ?? 0),
            'pending' => (int) $months->where('status', 'pending')->sum('balance'),
        ];
    }

    /**
     * Everything still to pay until the last month of the group.
     */
    public function remainingForGroup(): int
    {
        $group = $this->chitGroup;

        return max(0, $group->months * $group->installment_amount - $this->totalPaid());
    }

    /**
     * First month that is not fully paid, or null when all months are paid.
     */
    public function nextUnpaidMonth(): ?int
    {
        $installment = $this->chitGroup->installment_amount;
        $paid = $this->paidByMonth();

        foreach (range(1, $this->chitGroup->months) as $monthNumber) {
            if (($paid[$monthNumber] ?? 0) < $installment) {
                return $monthNumber;
            }
        }

        return null;
    }

    /**
     * Check an amount collected for one chosen month and return the
     * allocation: [month_number => rupees]. The month must be one that can
     * be collected now, the amount can never be more than that month's
     * balance (nothing extra is taken), and month 1 is taken in full only.
     *
     * @return array<int, int>
     *
     * @throws ValidationException
     */
    public function allocateToMonth(int $amount, int $monthNumber): array
    {
        $month = collect($this->collectableMonths())->firstWhere('month', $monthNumber);

        if ($month === null) {
            throw ValidationException::withMessages([
                'month_number' => "Month {$monthNumber} cannot be collected now. Collect the earlier months first.",
            ]);
        }

        if ($amount > $month['balance']) {
            throw ValidationException::withMessages([
                'amount' => 'Month '.$monthNumber.' only has ₹'.number_format($month['balance']).' left to pay. Enter that much or less.',
            ]);
        }

        if ($month['full_only'] && $amount !== $month['balance']) {
            throw ValidationException::withMessages([
                'amount' => 'Month 1 must be paid in full (₹'.number_format($month['balance']).').',
            ]);
        }

        return [$monthNumber => $amount];
    }

    /**
     * Split an amount over the oldest unfilled months: [month_number => rupees].
     * Used when no month is chosen (e.g. entering past records).
     *
     * @return array<int, int>
     *
     * @throws ValidationException when it is more than the rest of the group
     */
    public function allocate(int $amount): array
    {
        $installment = $this->chitGroup->installment_amount;
        $paid = $this->paidByMonth();

        $allocations = [];
        $left = $amount;

        foreach (range(1, $this->chitGroup->months) as $monthNumber) {
            if ($left <= 0) {
                break;
            }

            $open = $installment - ($paid[$monthNumber] ?? 0);

            if ($open <= 0) {
                continue;
            }

            $allocations[$monthNumber] = min($open, $left);
            $left -= $allocations[$monthNumber];
        }

        if ($left > 0) {
            throw ValidationException::withMessages([
                'amount' => 'This is more than the member still has to pay for the whole group ('.$this->remainingForGroup().' rupees).',
            ]);
        }

        return $allocations;
    }

    /**
     * Month-by-month ledger rows for the member. Status: paid, partial,
     * due (past its due date and unpaid — shown as "Pending") or upcoming.
     *
     * @return list<array{month: int, due_on: Carbon, period: string, installment: int, paid: int, balance: int, status: string}>
     */
    public function ledger(?Carbon $asOf = null): array
    {
        $group = $this->chitGroup;
        $paid = $this->paidByMonth();
        $overdueCount = $group->overdueMonthCount($asOf);

        return collect(range(1, $group->months))
            ->map(function (int $monthNumber) use ($group, $paid, $overdueCount) {
                $paidAmount = $paid[$monthNumber] ?? 0;
                $balance = max(0, $group->installment_amount - $paidAmount);

                $status = match (true) {
                    $balance === 0 => 'paid',
                    $paidAmount > 0 => 'partial',
                    $monthNumber <= $overdueCount => 'due',
                    default => 'upcoming',
                };

                return [
                    'month' => $monthNumber,
                    'due_on' => $group->dateForMonth($monthNumber),
                    'period' => $group->monthPeriodLabel($monthNumber),
                    'installment' => $group->installment_amount,
                    'paid' => $paidAmount,
                    'balance' => $balance,
                    'status' => $status,
                ];
            })
            ->all();
    }

    /**
     * The member code for a customer's next seat in a group: their customer
     * code for the first seat, then CODE-2, CODE-3 … (skipping any code
     * still in use after a seat was removed).
     */
    public static function nextCodeFor(ChitGroup $group, Customer $customer): string
    {
        $takenCodes = $group->members()
            ->where('customer_id', $customer->id)
            ->pluck('member_code')
            ->all();

        $code = $customer->customer_code;
        $seat = 1;

        while (in_array($code, $takenCodes, true)) {
            $seat++;
            $code = $customer->customer_code.'-'.$seat;
        }

        return $code;
    }
}

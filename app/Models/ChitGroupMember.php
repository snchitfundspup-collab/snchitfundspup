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
    | Group months run from the start date (15 Sep – 14 Oct is month 1).
    | A month's installment falls due when that month begins. Only a
    | running group has anything due. Payments fill the oldest month first.
    */

    /**
     * How many installment months have fallen due by the given date —
     * i.e. the group month that date falls in.
     */
    public function dueMonthCount(?Carbon $asOf = null): int
    {
        return $this->chitGroup->currentMonthNumber($asOf);
    }

    /**
     * Amount paid towards each month: [month_number => rupees].
     *
     * @return array<int, int>
     */
    public function paidByMonth(): array
    {
        return $this->allocations
            ->groupBy('month_number')
            ->map(fn ($allocations) => (int) $allocations->sum('amount'))
            ->all();
    }

    public function totalPaid(): int
    {
        return (int) $this->allocations->sum('amount');
    }

    /**
     * Unpaid amount of the months already due (what they owe today).
     */
    public function balanceDue(?Carbon $asOf = null): int
    {
        $dueCount = $this->dueMonthCount($asOf);

        if ($dueCount === 0) {
            return 0;
        }

        $installment = $this->chitGroup->installment_amount;
        $paid = $this->paidByMonth();

        return collect(range(1, $dueCount))
            ->sum(fn (int $monthNumber) => max(0, $installment - ($paid[$monthNumber] ?? 0)));
    }

    /**
     * Where this seat stands for collection today:
     *   pending — an earlier month is still not fully paid
     *   partial — only the current month is open, part of it paid
     *   due     — only the current month is open, nothing paid yet
     *   clear   — nothing to collect up to the current month
     *
     * @return array{state: string, month: int, amount_due: int, pending: int}
     */
    public function collectionStatus(?Carbon $asOf = null): array
    {
        $currentMonth = $this->dueMonthCount($asOf);
        $installment = $this->chitGroup->installment_amount;
        $paid = $this->paidByMonth();

        $pending = $currentMonth > 1
            ? collect(range(1, $currentMonth - 1))->sum(fn (int $monthNumber) => max(0, $installment - ($paid[$monthNumber] ?? 0)))
            : 0;

        $currentPaid = $currentMonth > 0 ? ($paid[$currentMonth] ?? 0) : 0;
        $currentOpen = $currentMonth > 0 ? max(0, $installment - $currentPaid) : 0;

        $state = match (true) {
            $pending > 0 => 'pending',
            $currentOpen > 0 && $currentPaid > 0 => 'partial',
            $currentOpen > 0 => 'due',
            default => 'clear',
        };

        return [
            'state' => $state,
            'month' => $currentMonth,
            'amount_due' => $pending + $currentOpen,
            'pending' => $pending,
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
     * Split an amount over the oldest unfilled months: [month_number => rupees].
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
     * Month-by-month ledger rows for the member.
     *
     * @return list<array{month: int, due_on: Carbon, period: string, installment: int, paid: int, balance: int, status: string}>
     */
    public function ledger(?Carbon $asOf = null): array
    {
        $group = $this->chitGroup;
        $paid = $this->paidByMonth();
        $dueCount = $this->dueMonthCount($asOf);

        return collect(range(1, $group->months))
            ->map(function (int $monthNumber) use ($group, $paid, $dueCount) {
                $paidAmount = $paid[$monthNumber] ?? 0;
                $balance = max(0, $group->installment_amount - $paidAmount);

                $status = match (true) {
                    $balance === 0 => 'paid',
                    $paidAmount > 0 => 'partial',
                    $monthNumber <= $dueCount => 'due',
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
     * Months not yet fully paid, oldest first — the Collect form uses this
     * to fill the amount for "full month(s)".
     *
     * @return list<array{month: int, balance: int, period: string}>
     */
    public function openMonths(): array
    {
        return collect($this->ledger())
            ->filter(fn (array $row) => $row['balance'] > 0)
            ->map(fn (array $row) => [
                'month' => $row['month'],
                'balance' => $row['balance'],
                'period' => $row['period'],
            ])
            ->values()
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

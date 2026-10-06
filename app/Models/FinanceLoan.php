<?php

namespace App\Models;

use Database\Factories\FinanceLoanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A Sri Lakshmi Micro Finance loan. From the loan amount ("principal") a
 * processing fee (1%) and GST on that fee (18%) are cut when the money is
 * given, so the customer receives principal − fee − GST. Interest per annum
 * (26%) for the loan period — the days, or weeks × 7 — is added, and the
 * total ("loan_amount") is repaid in that many daily or weekly instalments,
 * starting on the first due date. The instalment is worked out (rounded up,
 * the last one smaller) and the office may change it. All rates and the
 * number of days / weeks can be changed per loan. Missed instalments show as
 * overdue (no penalty); the loan closes once fully collected.
 */
class FinanceLoan extends Model
{
    /** @use HasFactory<FinanceLoanFactory> */
    use HasFactory;

    public const DAILY = 'daily';

    public const WEEKLY = 'weekly';

    /**
     * @var array<string, string>
     */
    public const FREQUENCIES = [self::DAILY => 'Daily', self::WEEKLY => 'Weekly'];

    public const DEFAULT_PROCESSING_FEE_RATE = 1.0;

    public const DEFAULT_GST_RATE = 18.0;

    public const DEFAULT_INTEREST_RATE = 26.0;

    /**
     * Default number of instalments: 100 days, or 14 weeks (98 days).
     *
     * @var array<string, int>
     */
    public const DEFAULT_INSTALLMENTS = [self::DAILY => 100, self::WEEKLY => 14];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'loan_number',
        'customer_id',
        'loaned_on',
        'principal',
        'processing_fee_rate',
        'processing_fee',
        'gst_rate',
        'gst',
        'interest_rate',
        'interest',
        'term_days',
        'loan_amount',
        'frequency',
        'installment_amount',
        'installments',
        'first_due_on',
        'status',
        'closed_on',
        'notes',
        'recorded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'loaned_on' => 'date',
            'first_due_on' => 'date',
            'closed_on' => 'date',
            'principal' => 'integer',
            'processing_fee_rate' => 'float',
            'processing_fee' => 'integer',
            'gst_rate' => 'float',
            'gst' => 'integer',
            'interest_rate' => 'float',
            'interest' => 'integer',
            'term_days' => 'integer',
            'loan_amount' => 'integer',
            'installment_amount' => 'integer',
            'installments' => 'integer',
        ];
    }

    /**
     * Give a loan and number it (L000001 …), working out the fee, GST,
     * interest, total and (unless the office typed one) the instalment.
     *
     * @param  array{customer_id: int, loaned_on: string, principal: int, processing_fee_rate: float, gst_rate: float, interest_rate: float, frequency: string, installments: int, installment_amount?: ?int, first_due_on: string, notes?: ?string}  $details
     */
    public static function give(array $details, ?User $recordedBy = null): self
    {
        $terms = self::terms(
            (int) $details['principal'],
            (float) $details['processing_fee_rate'],
            (float) $details['gst_rate'],
            (float) $details['interest_rate'],
            $details['frequency'],
            (int) $details['installments'],
            isset($details['installment_amount']) ? (int) $details['installment_amount'] : null,
        );

        $loan = self::create([
            'customer_id' => $details['customer_id'],
            'loaned_on' => $details['loaned_on'],
            'frequency' => $details['frequency'],
            'first_due_on' => $details['first_due_on'],
            'notes' => $details['notes'] ?? null,
            ...$terms,
            'status' => self::STATUS_ACTIVE,
            'recorded_by' => $recordedBy?->id,
        ]);

        $loan->update(['loan_number' => 'L'.str_pad((string) $loan->id, 6, '0', STR_PAD_LEFT)]);

        return $loan;
    }

    /**
     * Work out a loan: fee = principal × fee %, GST = fee × GST %, interest =
     * principal × interest % × days / 365 (weeks count 7 days), total =
     * principal + interest, instalment = total ÷ instalments rounded up
     * (or the office's own amount). Rupees are rounded to whole rupees.
     *
     * @return array{principal: int, processing_fee_rate: float, processing_fee: int, gst_rate: float, gst: int, interest_rate: float, interest: int, term_days: int, loan_amount: int, installments: int, installment_amount: int}
     */
    public static function terms(int $principal, float $feeRate, float $gstRate, float $interestRate, string $frequency, int $installments, ?int $installmentAmount = null): array
    {
        $fee = (int) round($principal * $feeRate / 100);
        $termDays = $frequency === self::WEEKLY ? $installments * 7 : $installments;
        $interest = (int) round($principal * $interestRate / 100 * $termDays / 365);
        $total = $principal + $interest;

        return [
            'principal' => $principal,
            'processing_fee_rate' => $feeRate,
            'processing_fee' => $fee,
            'gst_rate' => $gstRate,
            'gst' => (int) round($fee * $gstRate / 100),
            'interest_rate' => $interestRate,
            'interest' => $interest,
            'term_days' => $termDays,
            'loan_amount' => $total,
            'installments' => $installments,
            'installment_amount' => $installmentAmount ?: self::autoInstallment($total, $installments),
        ];
    }

    /**
     * The total split into equal instalments, rounded up to whole rupees.
     */
    public static function autoInstallment(int $total, int $installments): int
    {
        return $installments > 0 ? (int) ceil($total / $installments) : $total;
    }

    /**
     * An instalment amount works when that many instalments pay the total
     * and none is left empty (the last one may be smaller).
     */
    public static function installmentFits(int $total, int $installments, int $installmentAmount): bool
    {
        return $installmentAmount > 0
            && $installmentAmount * $installments >= $total
            && $installmentAmount * ($installments - 1) < $total;
    }

    /**
     * The first instalment falls due a day (daily) or a week (weekly) after
     * the money is given, unless the office picks another date.
     */
    public static function defaultFirstDue(Carbon $loanedOn, string $frequency): Carbon
    {
        return $frequency === self::WEEKLY ? $loanedOn->copy()->addWeek() : $loanedOn->copy()->addDay();
    }

    /**
     * What the customer received in hand: the loan less fee and GST.
     */
    public function amountGiven(): int
    {
        return $this->principal - $this->processing_fee - $this->gst;
    }

    /**
     * Processing fee and GST cut when the money was given.
     */
    public function charges(): int
    {
        return $this->processing_fee + $this->gst;
    }

    /**
     * The last instalment (smaller when the total does not divide evenly).
     */
    public function lastInstallment(): int
    {
        return $this->loan_amount - ($this->installments - 1) * $this->installment_amount;
    }

    /**
     * The interest inside an amount collected: collections pay loan and
     * interest in the same proportion as the total.
     */
    public function interestIn(int $amount): float
    {
        return $this->loan_amount > 0 ? $amount * $this->interest / $this->loan_amount : 0.0;
    }

    /**
     * "26% a year", "1%" — rates without needless decimals.
     */
    public static function rate(float $rate): string
    {
        return rtrim(rtrim(number_format($rate, 2), '0'), '.').'%';
    }

    /**
     * Collected so far (from withSum "collected_total", the loaded
     * collections, or the database).
     */
    public function collected(): int
    {
        if (array_key_exists('collected_total', $this->attributes)) {
            return (int) $this->attributes['collected_total'];
        }

        return (int) ($this->relationLoaded('collections') ? $this->collections->sum('amount') : $this->collections()->sum('amount'));
    }

    public function balance(): int
    {
        return max(0, $this->loan_amount - $this->collected());
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function frequencyLabel(): string
    {
        return __(self::FREQUENCIES[$this->frequency] ?? ucfirst($this->frequency));
    }

    /**
     * The due date of instalment $number (1 = the first due date).
     */
    public function dueDateFor(int $number): Carbon
    {
        $first = Carbon::parse($this->first_due_on->toDateString());

        return $this->frequency === self::WEEKLY ? $first->addWeeks($number - 1) : $first->addDays($number - 1);
    }

    public function lastDueDate(): Carbon
    {
        return $this->dueDateFor(max(1, $this->installments));
    }

    /**
     * How many instalments have fallen due on or before $date.
     */
    public function installmentsDueBy(Carbon $date): int
    {
        $first = Carbon::parse($this->first_due_on->toDateString());
        $day = Carbon::parse($date->toDateString());

        if ($day->lt($first)) {
            return 0;
        }

        $days = (int) $first->diffInDays($day);
        $count = $this->frequency === self::WEEKLY ? intdiv($days, 7) + 1 : $days + 1;

        return min($this->installments, $count);
    }

    /**
     * What should have been collected by the end of $date.
     */
    public function expectedBy(Carbon $date): int
    {
        return min($this->loan_amount, $this->installmentsDueBy($date) * $this->installment_amount);
    }

    /**
     * Where the loan stands today: overdue (red) — instalments whose date
     * has passed, not yet collected; due (orange) — today's instalment;
     * on track; not started (first due date still ahead); or closed.
     *
     * @return array{state: string, overdue: int, due_today: int, to_collect: int, installments_paid: int, next_due: ?Carbon, overdue_since: ?Carbon, days_overdue: int}
     */
    public function standing(?Carbon $today = null): array
    {
        $today = Carbon::parse(($today ?? today(config('app.business_timezone')))->toDateString());
        $collected = $this->collected();
        $paidInstallments = $this->installment_amount > 0 ? min($this->installments, intdiv($collected, $this->installment_amount)) : 0;

        if ($collected >= $this->loan_amount) {
            return [
                'state' => 'closed', 'overdue' => 0, 'due_today' => 0, 'to_collect' => 0,
                'installments_paid' => $this->installments, 'next_due' => null, 'overdue_since' => null, 'days_overdue' => 0,
            ];
        }

        $overdue = max(0, $this->expectedBy($today->copy()->subDay()) - $collected);
        $dueToday = max(0, $this->expectedBy($today) - $collected) - $overdue;
        $nextDue = $this->dueDateFor($paidInstallments + 1);

        return [
            'state' => match (true) {
                $overdue > 0 => 'overdue',
                $dueToday > 0 => 'due',
                $today->lt(Carbon::parse($this->first_due_on->toDateString())) => 'not_started',
                default => 'on_track',
            },
            'overdue' => $overdue,
            'due_today' => $dueToday,
            'to_collect' => $overdue + $dueToday,
            'installments_paid' => $paidInstallments,
            'next_due' => $nextDue,
            'overdue_since' => $overdue > 0 ? $nextDue : null,
            'days_overdue' => $overdue > 0 ? (int) $nextDue->diffInDays($today) : 0,
        ];
    }

    /**
     * The instalments one by one: number, due date, amount (the last may be
     * smaller), how much of it is paid and the dates that money was
     * collected (collections pay the earliest instalments first, so the
     * collection date can differ from the due date), the balance left after
     * it is paid as planned, and its state — paid, part paid, overdue (red),
     * due today (orange) or upcoming.
     *
     * @return Collection<int, array{number: int, due_on: Carbon, amount: int, paid: int, paid_on: list<Carbon>, balance_after: int, state: string}>
     */
    public function schedule(?Carbon $today = null): Collection
    {
        $today = Carbon::parse(($today ?? today(config('app.business_timezone')))->toDateString());
        $planned = 0;

        /* the money collected, oldest first: [date, amount still to spread] */
        $collections = ($this->relationLoaded('collections') ? $this->collections : $this->collections()->get())
            ->sortBy([['collected_at', 'asc'], ['id', 'asc']])
            ->map(fn (FinanceCollection $collection) => [Carbon::parse($collection->collected_at->toDateString()), (int) $collection->amount])
            ->values()
            ->all();
        $next = 0;

        return collect(range(1, max(1, $this->installments)))->map(function (int $number) use ($today, &$planned, &$collections, &$next) {
            $amount = $number < $this->installments ? $this->installment_amount : $this->lastInstallment();
            $paid = 0;
            $paidOn = [];

            while ($paid < $amount && $next < count($collections)) {
                $take = min($amount - $paid, $collections[$next][1]);
                $paid += $take;
                $collections[$next][1] -= $take;

                if ($take > 0 && ! in_array($collections[$next][0]->toDateString(), array_map(fn (Carbon $date) => $date->toDateString(), $paidOn), true)) {
                    $paidOn[] = $collections[$next][0];
                }

                if ($collections[$next][1] <= 0) {
                    $next++;
                }
            }

            $planned += $amount;
            $dueOn = $this->dueDateFor($number);

            return [
                'number' => $number,
                'due_on' => $dueOn,
                'amount' => $amount,
                'paid' => $paid,
                'paid_on' => $paidOn,
                'balance_after' => max(0, $this->loan_amount - $planned),
                'state' => match (true) {
                    $paid >= $amount => 'paid',
                    $dueOn->lt($today) => 'overdue',
                    $dueOn->equalTo($today) => 'due',
                    $paid > 0 => 'partial',
                    default => 'upcoming',
                },
            ];
        });
    }

    /**
     * Close the loan once fully collected (on the last collection's date);
     * open it again if a collection is cancelled.
     */
    public function refreshStatus(): void
    {
        $collected = (int) $this->collections()->sum('amount');
        unset($this->attributes['collected_total']);

        if ($collected >= $this->loan_amount) {
            $lastCollected = $this->collections()->max('collected_at');

            $this->update([
                'status' => self::STATUS_CLOSED,
                'closed_on' => $lastCollected ? substr((string) $lastCollected, 0, 10) : today(config('app.business_timezone'))->toDateString(),
            ]);

            return;
        }

        $this->update(['status' => self::STATUS_ACTIVE, 'closed_on' => null]);
    }

    /**
     * The amount given, in words, for the acknowledgement.
     */
    public function amountGivenInWords(): string
    {
        return 'Rupees '.Payment::numberInWords($this->amountGiven()).' Only';
    }

    /**
     * @param  Builder<FinanceLoan>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<FinanceCollection, $this>
     */
    public function collections(): HasMany
    {
        return $this->hasMany(FinanceCollection::class)->orderBy('collected_at')->orderBy('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

<?php

namespace App\Models;

use Database\Factories\ChitGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ChitGroup extends Model
{
    /** @use HasFactory<ChitGroupFactory> */
    use HasFactory;

    public const TYPE_DRAW = 'draw';

    public const TYPE_AUCTION = 'auction';

    /**
     * @var list<string>
     */
    public const TYPES = [self::TYPE_DRAW, self::TYPE_AUCTION];

    public const STATUS_FORMING = 'forming';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'name',
        'type',
        'amount',
        'months',
        'installment_amount',
        'member_count',
        'commission_amount',
        'start_date',
        'status',
        'started_at',
        'remarks',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'months' => 'integer',
            'installment_amount' => 'integer',
            'member_count' => 'integer',
            'commission_amount' => 'integer',
            'start_date' => 'date',
            'started_at' => 'datetime',
        ];
    }

    /**
     * Month-by-month withdrawal schedule.
     *
     * @return HasMany<ChitGroupPayout, $this>
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(ChitGroupPayout::class)->orderBy('month_number');
    }

    /**
     * Seats taken in this group, in serial order (set by drag and drop).
     *
     * @return HasMany<ChitGroupMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(ChitGroupMember::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Monthly draws of this group, in month order.
     *
     * @return HasMany<Draw, $this>
     */
    public function draws(): HasMany
    {
        return $this->hasMany(Draw::class)->orderBy('month_number');
    }

    /**
     * The group month the next draw is for (draws go in month order), or
     * null when every month has been drawn.
     */
    public function nextDrawMonth(): ?int
    {
        $next = (int) $this->draws()->max('month_number') + 1;

        return $next <= $this->months ? $next : null;
    }

    /**
     * A draw can run for the next month once that group month has begun.
     */
    public function canDrawNow(): bool
    {
        $next = $this->nextDrawMonth();

        return $this->isRunning() && $next !== null && $next <= $this->currentMonthNumber();
    }

    /**
     * Members who can still enter a draw: everyone who has not won yet.
     *
     * @return HasMany<ChitGroupMember, $this>
     */
    public function membersYetToWin(): HasMany
    {
        return $this->members()->whereDoesntHave('wonDraw');
    }

    /**
     * The withdrawal (prize) amount scheduled for a group month.
     */
    public function withdrawalForMonth(int $monthNumber): int
    {
        return (int) $this->payouts()->where('month_number', $monthNumber)->value('withdrawal_amount');
    }

    /**
     * A group can start only with exactly the planned number of members.
     */
    public function hasExactMemberCount(): bool
    {
        return $this->members()->count() === $this->member_count;
    }

    /**
     * What every member pays each month, as typed in by the admin.
     */
    public function monthlyInstallment(): int
    {
        return $this->installment_amount;
    }

    /*
    |--------------------------------------------------------------------------
    | Group months
    |--------------------------------------------------------------------------
    | Months run from the start date, not the calendar: a group starting on
    | 15 Sep has month 1 = 15 Sep – 14 Oct, month 2 = 15 Oct – 14 Nov, …
    */

    /**
     * First day of a group month (1 = the start date).
     */
    public function dateForMonth(int $monthNumber): Carbon
    {
        return $this->start_date->copy()->addMonthsNoOverflow($monthNumber - 1);
    }

    /**
     * Last day of a group month (the day before the next month starts).
     */
    public function monthEndDate(int $monthNumber): Carbon
    {
        return $this->dateForMonth($monthNumber + 1)->subDay();
    }

    /**
     * "15 Sep – 14 Oct 2026" (or "15 Dec 2026 – 14 Jan 2027" across years).
     */
    public function monthPeriodLabel(int $monthNumber, bool $withYear = true): string
    {
        $start = $this->dateForMonth($monthNumber);
        $end = $this->monthEndDate($monthNumber);

        if (! $withYear) {
            return $start->format('j M').' – '.$end->format('j M');
        }

        return $start->year === $end->year
            ? $start->format('j M').' – '.$end->format('j M Y')
            : $start->format('j M Y').' – '.$end->format('j M Y');
    }

    /**
     * Which group month today falls in (0 before the start date or when
     * the group is not running; never more than the number of months).
     */
    public function currentMonthNumber(?Carbon $asOf = null): int
    {
        if (! $this->isRunning()) {
            return 0;
        }

        $asOf = Carbon::parse(($asOf ?? now(config('app.business_timezone')))->toDateString());

        $current = 0;

        foreach (range(1, $this->months) as $monthNumber) {
            if ($this->dateForMonth($monthNumber)->startOfDay()->gt($asOf)) {
                break;
            }

            $current = $monthNumber;
        }

        return $current;
    }

    /**
     * The last month of the group.
     */
    public function endDate(): Carbon
    {
        return $this->dateForMonth($this->months);
    }

    public function isForming(): bool
    {
        return $this->status === self::STATUS_FORMING;
    }

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }
}

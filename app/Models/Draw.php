<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Draw extends Model
{
    protected $fillable = [
        'chit_group_id',
        'month_number',
        'winner_member_id',
        'withdrawal_amount',
        'drawn_at',
        'drawn_by',
        'voucher_number',
        'payout_amount',
        'payout_method',
        'payout_reference',
        'payout_notes',
        'paid_at',
        'paid_by',
        'voucher_printed_at',
    ];

    /**
     * drawn_at / paid_at are office local date-times, shown as-is.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'month_number' => 'integer',
            'withdrawal_amount' => 'integer',
            'payout_amount' => 'integer',
            'drawn_at' => 'datetime',
            'paid_at' => 'datetime',
            'voucher_printed_at' => 'datetime',
        ];
    }

    public function isPaidOut(): bool
    {
        return $this->paid_at !== null;
    }

    /**
     * Once the payout voucher is printed (or downloaded) the draw is
     * settled and moves from Draw Details to Past Winners.
     */
    public function isVoucherPrinted(): bool
    {
        return $this->voucher_printed_at !== null;
    }

    /**
     * The prize money for display: what was paid, else what is due.
     */
    public function prizeAmount(): int
    {
        return (int) ($this->payout_amount ?? $this->withdrawal_amount);
    }

    /**
     * Record the first time the voucher was printed or downloaded.
     */
    public function markVoucherPrinted(): void
    {
        if ($this->isPaidOut() && ! $this->isVoucherPrinted()) {
            $this->update(['voucher_printed_at' => now(config('app.business_timezone'))->format('Y-m-d H:i:s')]);
        }
    }

    /**
     * Draws still being worked on: voucher not printed yet.
     *
     * @param  Builder<Draw>  $query
     */
    public function scopeCurrent(Builder $query): void
    {
        $query->whereNull('voucher_printed_at');
    }

    /**
     * Settled draws: paid out and voucher printed.
     *
     * @param  Builder<Draw>  $query
     */
    public function scopePastWinners(Builder $query): void
    {
        $query->whereNotNull('voucher_printed_at');
    }

    public function payoutMethodLabel(): string
    {
        return Payment::METHODS[$this->payout_method] ?? ucfirst((string) $this->payout_method);
    }

    /**
     * The payout amount in words for the voucher, Indian style.
     */
    public function payoutInWords(): string
    {
        return (new Payment(['amount' => (int) $this->payout_amount]))->amountInWords();
    }

    /**
     * @return BelongsTo<ChitGroup, $this>
     */
    public function chitGroup(): BelongsTo
    {
        return $this->belongsTo(ChitGroup::class);
    }

    /**
     * @return BelongsTo<ChitGroupMember, $this>
     */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(ChitGroupMember::class, 'winner_member_id');
    }

    /**
     * @return BelongsToMany<ChitGroupMember, $this>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(ChitGroupMember::class, 'draw_participants')
            ->withTimestamps()
            ->orderBy('chit_group_members.position')
            ->orderBy('chit_group_members.id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function drawnBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'drawn_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}

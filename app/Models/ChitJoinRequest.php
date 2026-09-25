<?php

namespace App\Models;

use Database\Factories\ChitJoinRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * A customer's wish to join a chit group that is forming (from their own
 * pages). Only the office decides: approving adds the seats to the group,
 * dismissing closes the request with an optional reply.
 */
class ChitJoinRequest extends Model
{
    /** @use HasFactory<ChitJoinRequestFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DISMISSED = 'dismissed';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const MAX_SEATS = 5;

    protected $fillable = ['chit_group_id', 'customer_id', 'seats', 'note', 'status', 'reply', 'decided_by', 'decided_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'seats' => 'integer',
            'decided_at' => 'datetime',
        ];
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Add the customer to the group (one seat per requested seat, as many
     * as are still free) and close the request.
     *
     * @return int seats added
     */
    public function approve(User $decidedBy, int $seats): int
    {
        return DB::transaction(function () use ($decidedBy, $seats) {
            $group = $this->chitGroup;
            $customer = $this->customer;

            foreach (range(1, $seats) as $ignored) {
                $group->members()->create([
                    'customer_id' => $customer->id,
                    'member_code' => ChitGroupMember::nextCodeFor($group, $customer),
                    'position' => (int) $group->members()->max('position') + 1,
                ]);
            }

            $this->update([
                'status' => self::STATUS_APPROVED,
                'seats' => $seats,
                'decided_by' => $decidedBy->id,
                'decided_at' => now(),
            ]);

            return $seats;
        });
    }

    public function dismiss(User $decidedBy, ?string $reply = null): void
    {
        $this->update([
            'status' => self::STATUS_DISMISSED,
            'reply' => $reply,
            'decided_by' => $decidedBy->id,
            'decided_at' => now(),
        ]);
    }

    /**
     * @param  Builder<ChitJoinRequest>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', self::STATUS_PENDING);
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
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}

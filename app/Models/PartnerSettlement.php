<?php

namespace App\Models;

use Database\Factories\PartnerSettlementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Money one partner handed another to even out the shared spending
 * (the Balance Sheet's "who pays whom").
 */
class PartnerSettlement extends Model
{
    /** @use HasFactory<PartnerSettlementFactory> */
    use HasFactory;

    protected $fillable = [
        'settled_on',
        'from_user_id',
        'to_user_id',
        'amount',
        'method',
        'reference',
        'notes',
        'recorded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settled_on' => 'date',
            'amount' => 'integer',
        ];
    }

    public function methodLabel(): string
    {
        return Payment::METHODS[$this->method] ?? ucfirst($this->method);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}

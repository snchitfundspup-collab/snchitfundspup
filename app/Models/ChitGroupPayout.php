<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChitGroupPayout extends Model
{
    protected $fillable = [
        'chit_group_id',
        'month_number',
        'withdrawal_amount',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'month_number' => 'integer',
            'withdrawal_amount' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ChitGroup, $this>
     */
    public function chitGroup(): BelongsTo
    {
        return $this->belongsTo(ChitGroup::class);
    }
}

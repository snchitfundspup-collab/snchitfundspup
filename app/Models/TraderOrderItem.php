<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One rice variety on a customer's order: bags, at the selling price of the
 * day it was ordered.
 */
class TraderOrderItem extends Model
{
    protected $table = 'trader_order_items';

    protected $fillable = ['order_id', 'variety_id', 'bags', 'rate'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bags' => 'integer',
            'rate' => 'float',
        ];
    }

    /**
     * @return BelongsTo<TraderOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(TraderOrder::class, 'order_id');
    }

    /**
     * @return BelongsTo<RiceVariety, $this>
     */
    public function variety(): BelongsTo
    {
        return $this->belongsTo(RiceVariety::class, 'variety_id');
    }
}

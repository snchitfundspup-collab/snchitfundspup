<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One rice line on a sale invoice.
 */
class SaleItem extends Model
{
    protected $table = 'trader_sale_items';

    protected $fillable = ['sale_id', 'variety_id', 'bags', 'bag_kg', 'loose_kg', 'kg', 'rate', 'rate_per', 'cost_rate', 'amount'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bags' => 'integer',
            'bag_kg' => 'float',
            'loose_kg' => 'float',
            'kg' => 'float',
            'rate' => 'float',
            'cost_rate' => 'float',
            'amount' => 'float',
        ];
    }

    /**
     * Profit on this line: amount less the purchase cost of the bags (null
     * when the variety had no purchase price at the time of sale).
     */
    public function profit(): ?float
    {
        return $this->cost_rate === null ? null : round($this->amount - $this->bags * $this->cost_rate, 2);
    }

    public function quantityLabel(): string
    {
        return RiceVariety::bagsLabel($this->bags);
    }

    /**
     * @return BelongsTo<RiceVariety, $this>
     */
    public function variety(): BelongsTo
    {
        return $this->belongsTo(RiceVariety::class, 'variety_id');
    }

    /**
     * @return BelongsTo<Sale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}

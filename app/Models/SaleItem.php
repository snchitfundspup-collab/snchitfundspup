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

    protected $fillable = ['sale_id', 'variety_id', 'bags', 'bag_kg', 'loose_kg', 'kg', 'rate', 'rate_per', 'amount'];

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
            'amount' => 'float',
        ];
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

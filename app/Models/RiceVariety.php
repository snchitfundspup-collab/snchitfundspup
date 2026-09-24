<?php

namespace App\Models;

use Database\Factories\RiceVarietyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A rice variety SN Traders buys and sells (e.g. Ponni 26 kg bag).
 */
class RiceVariety extends Model
{
    /** @use HasFactory<RiceVarietyFactory> */
    use HasFactory;

    protected $table = 'trader_varieties';

    protected $fillable = ['name', 'bag_kg', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bag_kg' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<RiceVariety>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('name');
    }

    /**
     * Kg and amount of a line: bags × bag weight plus any loose kg, priced
     * per kg, or per bag (loose kg counted as a part bag).
     *
     * @return array{kg: float, amount: float}
     */
    public static function lineTotals(int $bags, float $bagKg, float $looseKg, float $rate, string $ratePer): array
    {
        $kg = round($bags * $bagKg + $looseKg, 2);

        $amount = $ratePer === 'bag'
            ? ($bags + ($bagKg > 0 ? $looseKg / $bagKg : 0)) * $rate
            : $kg * $rate;

        return ['kg' => $kg, 'amount' => round($amount, 2)];
    }

    /**
     * "1 bag" / "12 bags".
     */
    public static function bagsLabel(int $bags): string
    {
        return $bags.' '.(abs($bags) === 1 ? 'bag' : 'bags');
    }

    /**
     * @return HasMany<PurchaseItem, $this>
     */
    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'variety_id');
    }

    /**
     * @return HasMany<SaleItem, $this>
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'variety_id');
    }
}

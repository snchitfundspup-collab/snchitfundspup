<?php

namespace App\Models;

use Database\Factories\PurchaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Rice bought from a supplier: one bill with one or more variety lines.
 */
class Purchase extends Model
{
    /** @use HasFactory<PurchaseFactory> */
    use HasFactory;

    protected $table = 'trader_purchases';

    protected $fillable = ['purchase_number', 'supplier_id', 'purchased_on', 'supplier_bill_no', 'total_amount', 'method', 'notes', 'recorded_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchased_on' => 'date',
            'total_amount' => 'float',
        ];
    }

    /**
     * Save a purchase with its lines and number it (P000001 …). Each
     * variety's purchase price becomes the rate paid on this bill, unless a
     * later-dated bill already set it.
     *
     * @param  array{supplier_id: int, purchased_on: string, supplier_bill_no?: ?string, method: string, notes?: ?string}  $details
     * @param  list<array{variety_id: int, bags: int, bag_kg: float, loose_kg: float, rate: float, rate_per: string}>  $lines
     */
    public static function record(array $details, array $lines, ?User $recordedBy = null): self
    {
        return DB::transaction(function () use ($details, $lines, $recordedBy) {
            $purchase = self::create([...$details, 'recorded_by' => $recordedBy?->id]);

            $total = 0;

            foreach ($lines as $line) {
                $totals = RiceVariety::lineTotals((int) $line['bags'], (float) $line['bag_kg'], (float) $line['loose_kg'], (float) $line['rate'], $line['rate_per']);

                $purchase->items()->create([...$line, ...$totals]);

                if ($line['rate_per'] === 'bag' && ! $purchase->hasNewerPurchaseOf($line['variety_id'])) {
                    RiceVariety::whereKey($line['variety_id'])->update(['purchase_price' => $line['rate']]);
                }

                $total += $totals['amount'];
            }

            $purchase->update([
                'total_amount' => round($total, 2),
                'purchase_number' => 'P'.str_pad((string) $purchase->id, 6, '0', STR_PAD_LEFT),
            ]);

            return $purchase;
        });
    }

    /**
     * Whether another purchase dated after this one has the variety.
     */
    public function hasNewerPurchaseOf(int $varietyId): bool
    {
        return PurchaseItem::query()
            ->where('variety_id', $varietyId)
            ->where('purchase_id', '!=', $this->id)
            ->whereHas('purchase', fn ($query) => $query->where('purchased_on', '>', $this->purchased_on->toDateString().' 23:59:59'))
            ->exists();
    }

    public function methodLabel(): string
    {
        return __(Payment::METHODS[$this->method] ?? ucfirst((string) $this->method));
    }

    /**
     * @return HasMany<PurchaseItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class)->orderBy('id');
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

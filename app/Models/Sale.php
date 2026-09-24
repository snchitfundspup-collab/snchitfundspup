<?php

namespace App\Models;

use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Rice sold to a customer: one invoice with one or more variety lines. Any
 * money taken at the sale is a linked receipt; the rest is credit.
 */
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    protected $table = 'trader_sales';

    protected $fillable = ['invoice_number', 'customer_id', 'sold_on', 'total_amount', 'notes', 'recorded_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sold_on' => 'date',
            'total_amount' => 'float',
        ];
    }

    /**
     * Save a sale with its lines, number the invoice (S000001 …) and record
     * any amount received now as a receipt.
     *
     * @param  array{customer_id: int, sold_on: string, notes?: ?string}  $details
     * @param  list<array{variety_id: int, bags: int, bag_kg: float, loose_kg: float, rate: float, rate_per: string}>  $lines
     * @param  array{amount: float, method: string, reference?: ?string, received_at: string}|null  $receivedNow
     */
    public static function record(array $details, array $lines, ?array $receivedNow = null, ?User $recordedBy = null): self
    {
        return DB::transaction(function () use ($details, $lines, $receivedNow, $recordedBy) {
            $sale = self::create([...$details, 'recorded_by' => $recordedBy?->id]);

            $total = 0;

            foreach ($lines as $line) {
                $totals = RiceVariety::lineTotals((int) $line['bags'], (float) $line['bag_kg'], (float) $line['loose_kg'], (float) $line['rate'], $line['rate_per']);

                $sale->items()->create([...$line, ...$totals]);

                $total += $totals['amount'];
            }

            $sale->update([
                'total_amount' => round($total, 2),
                'invoice_number' => 'S'.str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT),
            ]);

            if ($receivedNow && $receivedNow['amount'] > 0) {
                TraderReceipt::record([
                    ...$receivedNow,
                    'customer_id' => $sale->customer_id,
                    'sale_id' => $sale->id,
                    'notes' => 'At invoice '.$sale->invoice_number,
                ], $recordedBy);
            }

            return $sale;
        });
    }

    public function paidAtSale(): float
    {
        return (float) $this->receipts->sum('amount');
    }

    /**
     * @return HasMany<SaleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class)->orderBy('id');
    }

    /**
     * Money taken at the time of the sale.
     *
     * @return HasMany<TraderReceipt, $this>
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(TraderReceipt::class);
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
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

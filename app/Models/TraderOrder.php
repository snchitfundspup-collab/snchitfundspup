<?php

namespace App\Models;

use Database\Factories\TraderOrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * SN Traders: a rice order a customer places from their own pages (bags of
 * each variety at the selling price of the day). The office turns it into a
 * sale — the invoice is the real bill — or cancels it.
 */
class TraderOrder extends Model
{
    /** @use HasFactory<TraderOrderFactory> */
    use HasFactory;

    public const STATUS_NEW = 'new';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @var array<string, string>
     */
    public const STATUSES = [
        self::STATUS_NEW => 'New',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    protected $table = 'trader_orders';

    protected $fillable = ['order_number', 'customer_id', 'status', 'estimated_total', 'notes', 'sale_id', 'closed_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estimated_total' => 'float',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * Save an order with its lines ([variety id => bags]) and number it
     * (O000001 …). Each line keeps the variety's selling price now.
     *
     * @param  array<int, int>  $bagsByVariety
     */
    public static function place(Customer $customer, array $bagsByVariety, ?string $notes = null): self
    {
        return DB::transaction(function () use ($customer, $bagsByVariety, $notes) {
            $order = self::create(['customer_id' => $customer->id, 'status' => self::STATUS_NEW, 'notes' => $notes]);

            $prices = RiceVariety::query()->whereKey(array_keys($bagsByVariety))->pluck('selling_price', 'id');
            $total = 0.0;

            foreach ($bagsByVariety as $varietyId => $bags) {
                $rate = $prices[$varietyId] ?? null;

                $order->items()->create(['variety_id' => $varietyId, 'bags' => $bags, 'rate' => $rate]);

                $total += $bags * (float) $rate;
            }

            $order->update([
                'estimated_total' => round($total, 2),
                'order_number' => 'O'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
            ]);

            return $order;
        });
    }

    public function isNew(): bool
    {
        return $this->status === self::STATUS_NEW;
    }

    /**
     * The office made the sale for this order.
     */
    public function complete(Sale $sale): void
    {
        $this->update(['status' => self::STATUS_COMPLETED, 'sale_id' => $sale->id, 'closed_at' => now()]);
    }

    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED, 'closed_at' => now()]);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    /**
     * "Ponni 3 bags, Idli Rice 2 bags".
     */
    public function itemsLabel(): string
    {
        return $this->items->map(fn (TraderOrderItem $item) => $item->variety->name.' '.RiceVariety::bagsLabel($item->bags))->implode(', ');
    }

    /**
     * @param  Builder<TraderOrder>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', self::STATUS_NEW);
    }

    /**
     * @return HasMany<TraderOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TraderOrderItem::class, 'order_id')->orderBy('id');
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Sale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}

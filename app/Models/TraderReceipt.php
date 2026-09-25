<?php

namespace App\Models;

use Database\Factories\TraderReceiptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Money SN Traders received from a customer — at a sale or later against
 * their credit.
 */
class TraderReceipt extends Model
{
    /** @use HasFactory<TraderReceiptFactory> */
    use HasFactory;

    protected $table = 'trader_receipts';

    protected $fillable = ['receipt_number', 'customer_id', 'sale_id', 'received_at', 'amount', 'method', 'reference', 'notes', 'recorded_by'];

    /**
     * received_at is office local time, shown as-is.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'amount' => 'float',
        ];
    }

    /**
     * Save a receipt and number it (R000001 …).
     *
     * @param  array{customer_id: int, amount: float, method: string, received_at: string, sale_id?: ?int, reference?: ?string, notes?: ?string}  $details
     */
    public static function record(array $details, ?User $recordedBy = null): self
    {
        $receipt = self::create([...$details, 'recorded_by' => $recordedBy?->id]);

        $receipt->update(['receipt_number' => 'R'.str_pad((string) $receipt->id, 6, '0', STR_PAD_LEFT)]);

        return $receipt;
    }

    public function methodLabel(): string
    {
        return __(Payment::METHODS[$this->method] ?? ucfirst((string) $this->method));
    }

    /**
     * The amount in words for the printed receipt.
     */
    public function amountInWords(): string
    {
        $rupees = (int) floor($this->amount);
        $paise = (int) round(($this->amount - $rupees) * 100);

        return 'Rupees '.Payment::numberInWords($rupees)
            .($paise > 0 ? ' and '.Payment::numberInWords($paise).' Paise' : '')
            .' Only';
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

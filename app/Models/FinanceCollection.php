<?php

namespace App\Models;

use Database\Factories\FinanceCollectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Money collected against a Sri Lakshmi Micro Finance loan, with its own
 * receipt number (C000001 …).
 */
class FinanceCollection extends Model
{
    /** @use HasFactory<FinanceCollectionFactory> */
    use HasFactory;

    protected $fillable = ['receipt_number', 'finance_loan_id', 'customer_id', 'collected_at', 'amount', 'method', 'reference', 'notes', 'recorded_by'];

    /**
     * collected_at is office local time, shown as-is.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'collected_at' => 'datetime',
            'amount' => 'integer',
        ];
    }

    /**
     * Save a collection, number its receipt and close the loan when it is
     * fully paid.
     *
     * @param  array{amount: int, collected_at: string, method: string, reference?: ?string, notes?: ?string}  $details
     */
    public static function record(FinanceLoan $loan, array $details, ?User $recordedBy = null): self
    {
        $collection = self::create([
            ...$details,
            'finance_loan_id' => $loan->id,
            'customer_id' => $loan->customer_id,
            'recorded_by' => $recordedBy?->id,
        ]);

        $collection->update(['receipt_number' => 'C'.str_pad((string) $collection->id, 6, '0', STR_PAD_LEFT)]);

        $loan->refreshStatus();

        return $collection;
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
        return 'Rupees '.Payment::numberInWords($this->amount).' Only';
    }

    /**
     * @return BelongsTo<FinanceLoan, $this>
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(FinanceLoan::class, 'finance_loan_id');
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

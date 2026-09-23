<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * Payment methods: value => label.
     *
     * @var array<string, string>
     */
    public const METHODS = [
        'cash' => 'Cash',
        'upi' => 'UPI',
        'bank' => 'Bank transfer',
        'cheque' => 'Cheque',
    ];

    protected $fillable = [
        'receipt_number',
        'chit_group_member_id',
        'chit_group_id',
        'customer_id',
        'amount',
        'paid_at',
        'method',
        'reference',
        'notes',
        'recorded_by',
    ];

    /**
     * paid_at is the office's local date and time (as typed / defaulted in
     * the business timezone) and is shown as-is.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Record money collected for a member seat. The amount is spread over
     * the oldest unpaid months first and the receipt gets its number
     * (RC000001 …) once saved.
     *
     * @param  array{amount: int, paid_at: string, method: string, reference?: ?string, notes?: ?string}  $details
     */
    public static function record(ChitGroupMember $member, array $details, ?User $recordedBy = null): self
    {
        return DB::transaction(function () use ($member, $details, $recordedBy) {
            $allocations = $member->allocate((int) $details['amount']);

            $payment = self::create([
                'chit_group_member_id' => $member->id,
                'chit_group_id' => $member->chit_group_id,
                'customer_id' => $member->customer_id,
                'amount' => (int) $details['amount'],
                'paid_at' => $details['paid_at'],
                'method' => $details['method'],
                'reference' => $details['reference'] ?? null,
                'notes' => $details['notes'] ?? null,
                'recorded_by' => $recordedBy?->id,
            ]);

            $payment->update(['receipt_number' => 'RC'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT)]);

            foreach ($allocations as $monthNumber => $amount) {
                $payment->allocations()->create([
                    'chit_group_member_id' => $member->id,
                    'month_number' => $monthNumber,
                    'amount' => $amount,
                ]);
            }

            return $payment;
        });
    }

    /**
     * The amount in words, Indian style, for the printed receipt:
     * 125000 → "Rupees One Lakh Twenty Five Thousand Only".
     */
    public function amountInWords(): string
    {
        return 'Rupees '.self::numberToIndianWords($this->amount).' Only';
    }

    private static function numberToIndianWords(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $belowHundred = fn (int $n): string => $n < 20
            ? $ones[$n]
            : trim($tens[intdiv($n, 10)].' '.$ones[$n % 10]);

        $belowThousand = fn (int $n): string => trim(
            ($n >= 100 ? $ones[intdiv($n, 100)].' Hundred ' : '').$belowHundred($n % 100)
        );

        $parts = [];

        foreach ([10000000 => 'Crore', 100000 => 'Lakh', 1000 => 'Thousand'] as $unit => $name) {
            if ($number >= $unit) {
                $count = intdiv($number, $unit);
                $parts[] = ($count >= 100 ? self::numberToIndianWords($count) : $belowHundred($count)).' '.$name;
                $number %= $unit;
            }
        }

        if ($number > 0) {
            $parts[] = $belowThousand($number);
        }

        return implode(' ', $parts);
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? ucfirst($this->method);
    }

    /**
     * "Month 3" or "Months 3–5" — which installments this receipt covers.
     */
    public function monthsCoveredLabel(): string
    {
        $months = $this->allocations->pluck('month_number')->sort()->values();

        if ($months->isEmpty()) {
            return '—';
        }

        return $months->count() === 1
            ? 'Month '.$months->first()
            : 'Months '.$months->first().'–'.$months->last();
    }

    /**
     * @return HasMany<PaymentAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class)->orderBy('month_number');
    }

    /**
     * @return BelongsTo<ChitGroupMember, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(ChitGroupMember::class, 'chit_group_member_id');
    }

    /**
     * @return BelongsTo<ChitGroup, $this>
     */
    public function chitGroup(): BelongsTo
    {
        return $this->belongsTo(ChitGroup::class);
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

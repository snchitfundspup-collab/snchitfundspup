<?php

namespace App\Models;

use Database\Factories\FinanceCapitalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Money put into Sri Lakshmi Micro Finance to lend (invest), or taken back
 * out (withdraw). The cash available to lend is worked out from these, the
 * loans given (only the amount in hand leaves — fee and GST are kept),
 * the collections and the expenses.
 */
class FinanceCapital extends Model
{
    /** @use HasFactory<FinanceCapitalFactory> */
    use HasFactory;

    public const INVEST = 'invest';

    public const WITHDRAW = 'withdraw';

    /**
     * @var array<string, string>
     */
    public const TYPES = [self::INVEST => 'Invest', self::WITHDRAW => 'Withdraw'];

    protected $table = 'finance_capital';

    protected $fillable = ['entry_on', 'type', 'amount', 'method', 'notes', 'recorded_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entry_on' => 'date',
            'amount' => 'integer',
        ];
    }

    /**
     * Cash in the business, up to the end of a date (default: all of it):
     * capital invested − capital withdrawn − money given in hand on loans
     * + money collected − expenses.
     *
     * @return array{invested: int, withdrawn: int, lent: int, collected: int, expenses: int, available: int}
     */
    public static function position(?string $upTo = null): array
    {
        $end = $upTo !== null ? $upTo.' 23:59:59' : null;
        $until = fn ($query, string $column) => $end !== null ? $query->where($column, '<=', $end) : $query;

        $invested = (int) $until(self::query()->where('type', self::INVEST), 'entry_on')->sum('amount');
        $withdrawn = (int) $until(self::query()->where('type', self::WITHDRAW), 'entry_on')->sum('amount');

        $loans = $until(FinanceLoan::query(), 'loaned_on');
        $lent = (int) (clone $loans)->sum('principal') - (int) (clone $loans)->sum('processing_fee') - (int) (clone $loans)->sum('gst');

        $collected = (int) $until(FinanceCollection::query(), 'collected_at')->sum('amount');
        $expenses = (int) $until(FinanceExpense::query(), 'spent_on')->sum('amount');

        return [
            'invested' => $invested,
            'withdrawn' => $withdrawn,
            'lent' => $lent,
            'collected' => $collected,
            'expenses' => $expenses,
            'available' => $invested - $withdrawn - $lent + $collected - $expenses,
        ];
    }

    public function typeLabel(): string
    {
        return __(self::TYPES[$this->type] ?? ucfirst($this->type));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

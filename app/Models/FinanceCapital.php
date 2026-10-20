<?php

namespace App\Models;

use Database\Factories\FinanceCapitalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

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

    /**
     * Where the lent money went and how it came back, per agent (who brought
     * the customers in): loans, loan amount, processing fee and GST kept,
     * money given in hand, money collected back on those loans and what is
     * still to collect. Loans without an agent come last ("No agent").
     *
     * @return Collection<int, array{agent: string, loans: int, principal: int, fee: int, gst: int, in_hand: int, collected: int, to_collect: int}>
     */
    public static function byAgent(): Collection
    {
        $loans = FinanceLoan::query()
            ->toBase()
            ->selectRaw('agent_id, COUNT(*) as loans, SUM(principal) as principal, SUM(processing_fee) as fee, SUM(gst) as gst, SUM(loan_amount) as total')
            ->groupBy('agent_id')
            ->get()
            ->keyBy(fn (object $row) => (string) $row->agent_id);

        $collected = FinanceCollection::query()
            ->toBase()
            ->join('finance_loans', 'finance_loans.id', '=', 'finance_collections.finance_loan_id')
            ->selectRaw('finance_loans.agent_id as agent_id, SUM(finance_collections.amount) as collected')
            ->groupBy('finance_loans.agent_id')
            ->pluck('collected', 'agent_id');

        $names = User::query()->whereKey($loans->pluck('agent_id')->filter())->pluck('name', 'id');

        return $loans
            ->map(function (object $row) use ($collected, $names) {
                $back = (int) ($collected[$row->agent_id ?? ''] ?? 0);

                return [
                    'agent' => $row->agent_id ? ($names[$row->agent_id] ?? '—') : __('No agent'),
                    'loans' => (int) $row->loans,
                    'principal' => (int) $row->principal,
                    'fee' => (int) $row->fee,
                    'gst' => (int) $row->gst,
                    'in_hand' => (int) $row->principal - (int) $row->fee - (int) $row->gst,
                    'collected' => $back,
                    'to_collect' => max(0, (int) $row->total - $back),
                    'sort' => $row->agent_id ? 0 : 1,
                ];
            })
            ->sortBy([['sort', 'asc'], ['agent', 'asc']])
            ->map(fn (array $row) => collect($row)->except('sort')->all())
            ->values();
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

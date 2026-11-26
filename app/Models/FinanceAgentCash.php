<?php

namespace App\Models;

use Database\Factories\FinanceAgentCashFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Money in an agent's hand (Sri Lakshmi Micro Finance). Entries here are
 * the opening balance, money taken from the treasury, money returned to it
 * and money passed from one agent to another; the loans the agent gives
 * (the whole loan out, the processing fee cut from it back in) and the
 * collections they record come from their own tables. An agent's cash book starts at their latest
 * opening balance (or, without one, at their first entry here); loans and
 * collections saved before that do not count.
 */
class FinanceAgentCash extends Model
{
    /** @use HasFactory<FinanceAgentCashFactory> */
    use HasFactory;

    protected $table = 'finance_agent_cash';

    public const OPENING = 'opening';

    public const FROM_TREASURY = 'from_treasury';

    public const TO_TREASURY = 'to_treasury';

    public const TRANSFER = 'transfer';

    /**
     * @var array<string, string>
     */
    public const TYPES = [
        self::FROM_TREASURY => 'Taken from treasury',
        self::TO_TREASURY => 'Given to treasury',
        self::TRANSFER => 'Agent to agent',
        self::OPENING => 'Opening balance',
    ];

    /**
     * What the office records on the Agent Cash page (opening balances are
     * no longer entered — an agent's book starts at their first entry).
     *
     * @var list<string>
     */
    public const RECORDED = [self::FROM_TREASURY, self::TO_TREASURY, self::TRANSFER];

    protected $fillable = ['entry_at', 'type', 'agent_id', 'other_agent_id', 'amount', 'notes', 'recorded_by'];

    /**
     * entry_at is office local time, shown as-is.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entry_at' => 'datetime',
            'amount' => 'integer',
        ];
    }

    /**
     * The agent's latest opening balance, where their cash book starts.
     */
    public static function openingFor(User $agent): ?self
    {
        return self::query()->where('agent_id', $agent->id)->where('type', self::OPENING)->latest('entry_at')->latest('id')->first();
    }

    /**
     * Every movement of the agent's cash since their opening balance, oldest
     * first, each with the balance after it: opening, treasury in / out,
     * transfers, money given in hand on their loans (−) and the money they
     * collected (+).
     *
     * @return Collection<int, array{at: Carbon, kind: string, label: string, detail: string, link: ?string, entry: ?int, in: int, out: int, balance: int}>
     */
    public static function ledger(User $agent): Collection
    {
        $opening = self::openingFor($agent);
        $start = $opening ?? self::query()
            ->where(fn ($query) => $query->where('agent_id', $agent->id)->orWhere('other_agent_id', $agent->id))
            ->oldest('entry_at')->oldest('id')->first();

        if ($start === null) {
            return collect();
        }

        $timezone = config('app.business_timezone');

        /* loans and collections count from the moment they were saved after the start */
        $savedAfter = Carbon::parse($start->entry_at->format('Y-m-d H:i:s'), $timezone)->utc();

        $rows = collect();

        if ($opening) {
            $rows->push(['at' => $opening->entry_at, 'sort' => '0', 'kind' => self::OPENING, 'label' => __('Opening balance'), 'detail' => (string) $opening->notes, 'link' => null, 'entry' => $opening->id, 'in' => $opening->amount, 'out' => 0]);
        }

        self::query()
            ->with(['agent', 'otherAgent'])
            ->where('type', '!=', self::OPENING)
            ->where(fn ($query) => $query->where('agent_id', $agent->id)->orWhere('other_agent_id', $agent->id))
            ->where(fn ($query) => $query->where('entry_at', '>', $start->entry_at)->orWhere(fn ($query) => $query->where('entry_at', $start->entry_at)->where('id', '>=', $start->id)))
            ->get()
            ->each(function (self $entry) use ($rows, $agent) {
                $receiving = $entry->type === self::FROM_TREASURY || ($entry->type === self::TRANSFER && $entry->other_agent_id === $agent->id);

                $rows->push([
                    'at' => $entry->entry_at,
                    'sort' => '1',
                    'kind' => $entry->type,
                    'label' => match (true) {
                        $entry->type === self::TRANSFER && $receiving => __('Received from').' '.$entry->agent?->name,
                        $entry->type === self::TRANSFER => __('Given to').' '.$entry->otherAgent?->name,
                        default => $entry->typeLabel(),
                    },
                    'detail' => (string) $entry->notes,
                    'link' => null,
                    'entry' => $entry->id,
                    'in' => $receiving ? $entry->amount : 0,
                    'out' => $receiving ? 0 : $entry->amount,
                ]);
            });

        FinanceLoan::query()
            ->with('customer')
            ->where('agent_id', $agent->id)
            ->where('created_at', '>', $savedAfter)
            ->get()
            ->each(function (FinanceLoan $loan) use ($rows, $timezone) {
                $customer = $loan->customer?->name.($loan->customer?->remarks ? ' ('.$loan->customer->remarks.')' : '');

                /* the whole loan goes out; the processing fee (and GST) cut from it stays in the agent's hand */
                $rows->push([
                    'at' => $loan->created_at->timezone($timezone),
                    'sort' => '2a',
                    'kind' => 'loan',
                    'label' => __('Loan given').' '.$loan->loan_number,
                    'detail' => $customer,
                    'link' => route('finance.loans.show', $loan),
                    'entry' => null,
                    'in' => 0,
                    'out' => $loan->principal,
                ]);

                if ($loan->charges() > 0) {
                    $rows->push([
                        'at' => $loan->created_at->timezone($timezone),
                        'sort' => '2b',
                        'kind' => 'fee',
                        'label' => __('Processing fee kept').' '.$loan->loan_number,
                        'detail' => $customer,
                        'link' => route('finance.loans.show', $loan),
                        'entry' => null,
                        'in' => $loan->charges(),
                        'out' => 0,
                    ]);
                }
            });

        FinanceCollection::query()
            ->with(['customer', 'loan'])
            ->where('collected_by', $agent->id)
            ->where('created_at', '>', $savedAfter)
            ->get()
            ->each(fn (FinanceCollection $collection) => $rows->push([
                'at' => $collection->created_at->timezone($timezone),
                'sort' => '3',
                'kind' => 'collection',
                'label' => __('Collected').' '.$collection->receipt_number,
                'detail' => $collection->customer?->name.' · '.$collection->loan?->loan_number,
                'link' => route('finance.collections.show', $collection),
                'entry' => null,
                'in' => $collection->amount,
                'out' => 0,
            ]));

        $balance = 0;

        return $rows
            ->sortBy(fn (array $row) => $row['at']->format('Y-m-d H:i:s').$row['sort'])
            ->values()
            ->map(function (array $row) use (&$balance) {
                $balance += $row['in'] - $row['out'];

                return collect($row)->except('sort')->all() + ['balance' => $balance];
            });
    }

    /**
     * Money in the agent's hand now.
     */
    public static function inHand(User $agent): int
    {
        return (int) (self::ledger($agent)->last()['balance'] ?? 0);
    }

    /**
     * Every active staff member with money in hand (or a cash book), and the
     * treasury: the money available to lend that is not with an agent.
     *
     * @return array{agents: Collection<int, array{agent: User, in_hand: int}>, with_agents: int, treasury: int, available: int}
     */
    public static function overview(): array
    {
        $agents = User::query()->active()->orderBy('name')->get()
            ->map(fn (User $agent) => ['agent' => $agent, 'in_hand' => self::inHand($agent), 'has_book' => self::query()->where('agent_id', $agent->id)->orWhere('other_agent_id', $agent->id)->exists()])
            ->filter(fn (array $row) => $row['has_book'] || $row['in_hand'] !== 0)
            ->map(fn (array $row) => collect($row)->except('has_book')->all())
            ->values();

        $available = FinanceCapital::position()['available'];
        $withAgents = (int) $agents->sum('in_hand');

        return [
            'agents' => $agents,
            'with_agents' => $withAgents,
            'treasury' => $available - $withAgents,
            'available' => $available,
        ];
    }

    public function typeLabel(): string
    {
        return __(self::TYPES[$this->type] ?? ucfirst($this->type));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * The agent who received the money (transfers only).
     *
     * @return BelongsTo<User, $this>
     */
    public function otherAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'other_agent_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

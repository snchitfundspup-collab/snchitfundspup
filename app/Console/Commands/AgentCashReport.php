<?php

namespace App\Console\Commands;

use App\Models\FinanceAgentCash;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * How much money an agent must have in hand: everything since their cash
 * book starts — taken from / given to the treasury, from / to other agents,
 * money given in hand on their loans, money they collected — and, with
 * --list, every entry with its running balance.
 *   php artisan app:agent-cash-report ranjith --list
 */
#[Signature('app:agent-cash-report {agent : Username, e.g. ranjith} {--list : Show every entry}')]
#[Description('Show how much money an agent must have in hand, and why')]
class AgentCashReport extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $agent = User::query()->where('username', $this->argument('agent'))->first();

        if (! $agent) {
            $this->error('No staff member with that username.');

            return self::FAILURE;
        }

        $ledger = FinanceAgentCash::ledger($agent);

        if ($ledger->isEmpty()) {
            $this->warn("{$agent->name} has no cash book yet. Record money taken from the treasury first (app:agent-cash-add).");

            return self::SUCCESS;
        }

        $sum = fn (string $kind, string $side) => (int) $ledger->where('kind', $kind)->sum($side);
        $loans = $ledger->where('kind', 'loan');
        $collections = $ledger->where('kind', 'collection');

        $this->info("{$agent->name} — cash book from ".$ledger->first()['at']->format('d M Y, h:i A'));

        $this->table(['', 'Amount'], [
            ['Taken from treasury', '+ '.number_format($sum(FinanceAgentCash::FROM_TREASURY, 'in'))],
            ['Received from other agents', '+ '.number_format($sum(FinanceAgentCash::TRANSFER, 'in'))],
            ['Collected from customers ('.$collections->count().' receipts)', '+ '.number_format($sum('collection', 'in'))],
            ['Processing fee kept from loans', '+ '.number_format($sum('fee', 'in'))],
            ['Loans given to customers ('.$loans->count().' loans)', '− '.number_format($sum('loan', 'out'))],
            ['Given to other agents', '− '.number_format($sum(FinanceAgentCash::TRANSFER, 'out'))],
            ['Given to treasury', '− '.number_format($sum(FinanceAgentCash::TO_TREASURY, 'out'))],
            ['Opening balance (if any)', '+ '.number_format($sum(FinanceAgentCash::OPENING, 'in'))],
            ['MUST HAVE IN HAND NOW', number_format((int) $ledger->last()['balance'])],
        ]);

        if ($this->option('list')) {
            $this->table(['Date & time', 'Entry', 'Details', 'In', 'Out', 'In hand'], $ledger->map(fn (array $row) => [
                $row['at']->format('d M Y H:i'),
                $row['label'],
                $row['detail'],
                $row['in'] ? number_format($row['in']) : '',
                $row['out'] ? number_format($row['out']) : '',
                number_format($row['balance']),
            ])->all());
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\FinanceAgentCash;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Record a past Agent Cash entry with its real date (the page saves at the
 * current time). Used to start an agent's cash book from the day they were
 * first given money, e.g.:
 *   php artisan app:agent-cash-add ranjith from_treasury 22500 2026-10-13
 *   php artisan app:agent-cash-add dinesh transfer 4900 2026-10-20 --to=ranjith
 * An entry dated at midnight counts every loan and collection saved after
 * that day began. Without --force it only shows what it would add.
 */
#[Signature('app:agent-cash-add {agent : Username, e.g. ranjith} {type : from_treasury, to_treasury or transfer} {amount : Rupees} {date : Office date (and time), e.g. 2026-10-13} {--to= : For a transfer: the username receiving the money} {--force : Really save}')]
#[Description('Record a past Agent Cash entry (taken from / given to the treasury, agent to agent) with its date')]
class AgentCashAdd extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $agent = User::query()->where('username', $this->argument('agent'))->first();
        $type = (string) $this->argument('type');
        $amount = (int) str_replace([',', ' ', '₹'], '', (string) $this->argument('amount'));
        $receiver = $this->option('to') ? User::query()->where('username', $this->option('to'))->first() : null;

        if (! $agent || ! in_array($type, FinanceAgentCash::RECORDED, true) || $amount < 1 || ($type === FinanceAgentCash::TRANSFER && (! $receiver || $receiver->is($agent)))) {
            $this->error('Give a username, a type (from_treasury, to_treasury or transfer — with --to=username), an amount and a date.');

            return self::FAILURE;
        }

        $at = Carbon::parse((string) $this->argument('date'), config('app.business_timezone'));

        $this->line(match ($type) {
            FinanceAgentCash::FROM_TREASURY => "{$agent->name} took ₹".number_format($amount).' from the treasury',
            FinanceAgentCash::TO_TREASURY => "{$agent->name} gave ₹".number_format($amount).' to the treasury',
            default => "{$agent->name} gave ₹".number_format($amount)." to {$receiver->name}",
        }.' on '.$at->format('d M Y, h:i A').'.');

        if (! $this->option('force')) {
            $this->warn('Nothing saved. Run again with --force to save it.');

            return self::SUCCESS;
        }

        FinanceAgentCash::create([
            'type' => $type,
            'agent_id' => $agent->id,
            'other_agent_id' => $receiver?->id,
            'amount' => $amount,
            'entry_at' => $at->format('Y-m-d H:i:s'),
        ]);

        $this->info("Saved. {$agent->name} now has ₹".number_format(FinanceAgentCash::inHand($agent)).' in hand'.($receiver ? ", {$receiver->name} ₹".number_format(FinanceAgentCash::inHand($receiver)) : '').'.');

        return self::SUCCESS;
    }
}

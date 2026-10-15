<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Before going live: delete every customer and everything recorded for
 * them — Micro Finance loans and collections, chit seats, payments, draws
 * and join requests, rice sales, receipts and orders, and their usage logs —
 * plus every business's expenses, old partner settlements and Micro Finance
 * capital. Numbering starts again (customer SL2601, loan L000001, receipt
 * C000001…). Staff accounts, chit groups and their withdrawal plans, rice
 * varieties, suppliers and purchases stay.
 */
#[Signature('app:clear-customer-data {--force : Really delete — without it the command only shows what would go}')]
#[Description('Delete all customers and their records (loans, chit seats, payments, rice sales…), expenses and capital, keeping staff and setup')]
class ClearCustomerData extends Command
{
    /**
     * Tables emptied completely, in an order that respects their links.
     *
     * @var list<string>
     */
    public const TABLES = [
        'finance_collections',
        'finance_loans',
        'payment_allocations',
        'payments',
        'draw_participants',
        'draws',
        'chit_join_requests',
        'chit_group_members',
        'trader_receipts',
        'trader_sale_items',
        'trader_sales',
        'trader_order_items',
        'trader_orders',
        'customers',
        'expenses',
        'partner_settlements',
        'trader_expenses',
        'trader_partner_settlements',
        'finance_expenses',
        'finance_partner_settlements',
        'finance_capital',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $counts = collect(self::TABLES)->mapWithKeys(fn (string $table) => [$table => DB::table($table)->count()]);
        $usageLogs = DB::table('usage_logs')->whereNotNull('customer_id')->count();
        $usageDaily = DB::table('usage_daily')->whereNotNull('customer_id')->count();

        $this->table(['Records', 'Count'], $counts->map(fn (int $count, string $table) => [$table, $count])->values()->all());
        $this->line("Customer usage logs: {$usageLogs} · daily counts: {$usageDaily}");

        if (! $this->option('force')) {
            $this->warn('Nothing deleted. Run again with --force to delete all of the above.');

            return self::SUCCESS;
        }

        DB::table('usage_logs')->whereNotNull('customer_id')->delete();
        DB::table('usage_daily')->whereNotNull('customer_id')->delete();

        /* empty the tables and start their numbering again */
        Schema::disableForeignKeyConstraints();

        foreach (self::TABLES as $table) {
            DB::table($table)->truncate();
        }

        Schema::enableForeignKeyConstraints();

        $this->info('All customer data, expenses and capital deleted. Staff accounts, chit groups, rice varieties, suppliers and purchases are kept.');

        return self::SUCCESS;
    }
}

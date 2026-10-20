<?php

namespace App\Console\Commands;

use App\Models\FinanceCollection;
use App\Models\FinanceLoan;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Undo Micro Finance entries typed in by mistake: every loan and every
 * collection (receipt) entered into the app at or after a time (office
 * time, by when it was saved — not the date typed on it). A removed loan
 * takes its collections with it; loans that lose collections are reopened
 * if they were closed. Customers and everything else stay. Without --force
 * it only lists what would go.
 */
#[Signature('app:remove-finance-entries {since : Office date and time, e.g. "2026-10-20 06:00"} {--force : Really delete — without it the command only lists what would go}')]
#[Description('Remove Micro Finance loans and collections entered at or after a time (customers stay)')]
class RemoveFinanceEntriesSince extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $since = Carbon::parse($this->argument('since'), config('app.business_timezone'));
        $sinceUtc = $since->copy()->utc();

        $loans = FinanceLoan::query()->with('customer')->where('created_at', '>=', $sinceUtc)->orderBy('id')->get();
        $collections = FinanceCollection::query()->with(['customer', 'loan'])->where('created_at', '>=', $sinceUtc)
            ->orWhereIn('finance_loan_id', $loans->pluck('id'))
            ->orderBy('id')
            ->get();

        $this->info('Entered at or after '.$since->format('d M Y, h:i A').' (office time):');

        $this->table(['Loan', 'Customer', 'Loan date', 'Amount', 'Entered'], $loans->map(fn (FinanceLoan $loan) => [
            $loan->loan_number,
            $loan->customer?->name.' ('.$loan->customer?->customer_code.')',
            $loan->loaned_on->format('d M Y'),
            number_format($loan->principal),
            $loan->created_at->timezone(config('app.business_timezone'))->format('d M Y h:i A'),
        ])->all());

        $this->table(['Receipt', 'Loan', 'Customer', 'Collected on', 'Amount', 'Entered'], $collections->map(fn (FinanceCollection $collection) => [
            $collection->receipt_number,
            $collection->loan?->loan_number,
            $collection->customer?->name,
            $collection->collected_at->format('d M Y h:i A'),
            number_format($collection->amount),
            $collection->created_at->timezone(config('app.business_timezone'))->format('d M Y h:i A'),
        ])->all());

        $this->line("{$loans->count()} loan(s) and {$collections->count()} collection(s), ₹".number_format($collections->sum('amount')).' collected.');

        if (! $this->option('force')) {
            $this->warn('Nothing deleted. Run again with --force to delete the above.');

            return self::SUCCESS;
        }

        $touchedLoanIds = $collections->pluck('finance_loan_id')->unique()->diff($loans->pluck('id'));

        DB::transaction(function () use ($loans, $collections, $touchedLoanIds) {
            FinanceCollection::query()->whereKey($collections->pluck('id'))->delete();
            FinanceLoan::query()->whereKey($loans->pluck('id'))->delete();

            FinanceLoan::query()->whereKey($touchedLoanIds)->get()->each->refreshStatus();
        });

        $this->resetNumbering();

        $this->info('Deleted. Customers and everything else are unchanged.');

        return self::SUCCESS;
    }

    /**
     * Let the next loan / receipt number follow the last one kept (MySQL).
     */
    private function resetNumbering(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (['finance_loans', 'finance_collections'] as $table) {
            $next = (int) DB::table($table)->max('id') + 1;
            DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = {$next}");
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\FinanceCollection;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Hand Micro Finance collections from one staff member to another (by
 * username): every receipt collected by the first — or entered by them
 * for the second, or with no collector — gets the second as "Collected
 * by" and as the recorder, so the Agent Statement, receipt and passbook
 * signature all show the second. Without --force it only lists them.
 */
#[Signature('app:move-finance-collections {from : Username now on the receipts, e.g. sathiya} {to : Username to put on them, e.g. ranjith} {--force : Really change — without it the command only lists them}')]
#[Description('Move Micro Finance collections (collector and signature) from one staff member to another')]
class MoveFinanceCollections extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $from = User::query()->where('username', $this->argument('from'))->first();
        $to = User::query()->where('username', $this->argument('to'))->first();

        if (! $from || ! $to || $from->is($to)) {
            $this->error('Give two different staff usernames, e.g. sathiya ranjith.');

            return self::FAILURE;
        }

        $query = FinanceCollection::query()->where(fn (Builder $query) => $query
            ->where('collected_by', $from->id)
            ->orWhere(fn (Builder $query) => $query
                ->where('recorded_by', $from->id)
                ->where(fn (Builder $query) => $query->whereNull('collected_by')->orWhere('collected_by', $to->id))));

        $collections = (clone $query)->with(['customer', 'loan'])->orderBy('id')->get();

        $this->table(['Receipt', 'Loan', 'Customer', 'Collected on', 'Amount'], $collections->map(fn (FinanceCollection $collection) => [
            $collection->receipt_number,
            $collection->loan?->loan_number,
            $collection->customer?->name,
            $collection->collected_at->format('d M Y h:i A'),
            number_format($collection->amount),
        ])->all());

        $this->line("{$collections->count()} collection(s), ₹".number_format($collections->sum('amount'))." — from {$from->name} to {$to->name}.");

        if (! $this->option('force')) {
            $this->warn('Nothing changed. Run again with --force to move them.');

            return self::SUCCESS;
        }

        $moved = (clone $query)->update(['collected_by' => $to->id, 'recorded_by' => $to->id]);

        $this->info("Moved {$moved} collection(s) to {$to->name}.");

        return self::SUCCESS;
    }
}

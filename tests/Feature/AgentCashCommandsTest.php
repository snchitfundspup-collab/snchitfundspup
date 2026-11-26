<?php

use App\Models\FinanceAgentCash;
use App\Models\FinanceCollection;
use App\Models\FinanceLoan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/**
 * Ranjith was given ₹22,500 on 13 Oct; on 14 Oct he lent ₹9,800 in hand and
 * on 20 Oct collected ₹1,000 and got ₹4,900 from Dinesh. A loan of his from
 * before 13 Oct does not count.
 */
test('past entries start the cash book on their date; the report shows what the agent must have in hand', function () {
    $ranjith = User::factory()->agent()->create(['name' => 'Ranjith', 'username' => 'ranjith']);
    $dinesh = User::factory()->agent()->create(['name' => 'Dinesh', 'username' => 'dinesh']);

    $this->travelTo(Carbon::parse('2026-10-10 10:00:00', 'Asia/Kolkata'));
    FinanceLoan::factory()->create(['agent_id' => $ranjith->id, 'principal' => 5000, 'processing_fee' => 100, 'gst' => 0, 'loan_amount' => 5600]);

    $this->travelTo(Carbon::parse('2026-10-14 11:00:00', 'Asia/Kolkata'));
    $loan = FinanceLoan::factory()->create(['agent_id' => $ranjith->id, 'principal' => 10000, 'processing_fee' => 200, 'gst' => 0, 'loan_amount' => 11200]);

    $this->travelTo(Carbon::parse('2026-10-20 11:00:00', 'Asia/Kolkata'));
    FinanceCollection::factory()->create(['finance_loan_id' => $loan->id, 'amount' => 1000, 'collected_by' => $ranjith->id]);

    $this->travelTo(Carbon::parse('2026-11-26 12:00:00', 'Asia/Kolkata'));

    $this->artisan('app:agent-cash-add', ['agent' => 'ranjith', 'type' => 'from_treasury', 'amount' => '22,500', 'date' => '2026-10-13'])
        ->expectsOutputToContain('Nothing saved')
        ->assertSuccessful();
    expect(FinanceAgentCash::count())->toBe(0);

    $this->artisan('app:agent-cash-add', ['agent' => 'ranjith', 'type' => 'from_treasury', 'amount' => '22,500', 'date' => '2026-10-13', '--force' => true])->assertSuccessful();
    $this->artisan('app:agent-cash-add', ['agent' => 'dinesh', 'type' => 'transfer', 'amount' => '4900', 'date' => '2026-10-20', '--to' => 'ranjith', '--force' => true])->assertSuccessful();

    /* 22,500 + 4,900 − 9,800 lent in hand + 1,000 collected */
    expect(FinanceAgentCash::inHand($ranjith))->toBe(18600);

    $this->artisan('app:agent-cash-report', ['agent' => 'ranjith', '--list' => true])
        ->expectsOutputToContain('MUST HAVE IN HAND NOW')
        ->assertSuccessful();

    $this->artisan('app:agent-cash-add', ['agent' => 'ranjith', 'type' => 'transfer', 'amount' => '100', 'date' => '2026-10-20'])->assertFailed();
});

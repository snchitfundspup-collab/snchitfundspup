<?php

use App\Http\Controllers\Finance\ReportController;
use App\Http\Middleware\SetLanguage;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\FinanceCapital;
use App\Models\FinanceCollection;
use App\Models\FinanceExpense;
use App\Models\FinanceLoan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/**
 * Today is 5 Oct 2026 (10:00). Narayanan gives Kavitha (identification
 * "Tailor") a ₹10,000 loan on 1 Oct on the standard terms: 1% processing
 * fee (₹100) and 18% GST on it (₹18) are cut, so she receives ₹9,882;
 * 26% a year for 100 days adds ₹712, and she repays ₹10,712 at ₹108 a day
 * from 2 Oct (the last day ₹20).
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-05 10:00:00'));

    $this->admin = User::factory()->create(['name' => 'Narayanan']);
    $this->customer = Customer::factory()->create(['name' => 'Kavitha', 'remarks' => 'Tailor', 'phone' => '9000000011']);

    $this->actingAs($this->admin, 'web');
});

function giveLoan(array $overrides = []): FinanceLoan
{
    test()->post(route('finance.loans.store'), [
        'customer_id' => test()->customer->id,
        'loaned_on' => '2026-10-01',
        'principal' => '10,000',
        'processing_fee_rate' => '1',
        'gst_rate' => '18',
        'interest_rate' => '26',
        'frequency' => 'daily',
        'installments' => '100',
        'installment_amount' => '',
        'first_due_on' => '2026-10-02',
        ...$overrides,
    ])->assertSessionHasNoErrors();

    return FinanceLoan::latest('id')->firstOrFail();
}

test('a loan cuts the fee and GST, adds interest for the period and works out the instalment', function () {
    $loan = giveLoan();

    expect($loan->loan_number)->toBe('L'.str_pad((string) $loan->id, 6, '0', STR_PAD_LEFT))
        ->and($loan->processing_fee)->toBe(100)
        ->and($loan->gst)->toBe(18)
        ->and($loan->amountGiven())->toBe(9882)
        ->and($loan->interest)->toBe(712)
        ->and($loan->term_days)->toBe(100)
        ->and($loan->loan_amount)->toBe(10712)
        ->and($loan->installments)->toBe(100)
        ->and($loan->installment_amount)->toBe(108)
        ->and($loan->lastInstallment())->toBe(20)
        ->and($loan->recorded_by)->toBe($this->admin->id)
        ->and($loan->lastDueDate()->toDateString())->toBe('2027-01-09');

    $this->get(route('finance.loans.show', $loan))
        ->assertOk()
        ->assertSeeText('Kavitha (Tailor)')
        ->assertSeeText('₹9,882')
        ->assertSeeText('₹10,712')
        ->assertSeeText('Record collection');

    $this->get(route('finance.loans.acknowledgement', $loan))
        ->assertOk()
        ->assertSeeText('Sri Lakshmi')
        ->assertSeeText('Amount received in hand')
        ->assertSeeText('₹9,882')
        ->assertSeeText('26% a year, 100 days')
        ->assertSeeText('100 × ₹108')
        ->assertSeeText('last one ₹20');

    $this->get(route('finance.loans.acknowledgement.pdf', $loan))->assertOk()->assertHeader('content-type', 'application/pdf');
});

test('weeks, rates, the number of days and the instalment can all be changed', function () {
    /* weekly: 14 weeks = 98 days of interest */
    $weekly = giveLoan(['frequency' => 'weekly', 'installments' => '14', 'first_due_on' => '2026-10-08']);

    expect($weekly->term_days)->toBe(98)
        ->and($weekly->interest)->toBe(698)
        ->and($weekly->loan_amount)->toBe(10698)
        ->and($weekly->installment_amount)->toBe(765);

    /* the office's own rates */
    $cheaper = giveLoan(['processing_fee_rate' => '2', 'gst_rate' => '0', 'interest_rate' => '24.5']);

    expect($cheaper->processing_fee)->toBe(200)
        ->and($cheaper->gst)->toBe(0)
        ->and($cheaper->amountGiven())->toBe(9800)
        ->and($cheaper->interest)->toBe(671);

    /* the office's own instalment, if it repays the total exactly */
    $this->post(route('finance.loans.store'), [
        'customer_id' => $this->customer->id, 'loaned_on' => '2026-10-01', 'principal' => '10000', 'processing_fee_rate' => '1', 'gst_rate' => '18',
        'interest_rate' => '26', 'frequency' => 'daily', 'installments' => '100', 'installment_amount' => '110', 'first_due_on' => '2026-10-02',
    ])->assertSessionHasErrors('installment_amount');

    $own = giveLoan(['installments' => '98', 'installment_amount' => '110']);

    expect($own->loan_amount)->toBe(10698)
        ->and($own->installment_amount)->toBe(110)
        ->and($own->lastInstallment())->toBe(28);
});

test('missed instalments are overdue (red) and today\'s is due (orange)', function () {
    $loan = giveLoan();
    FinanceCollection::record($loan, ['amount' => 108, 'collected_at' => '2026-10-02 09:00:00', 'method' => 'cash']);

    /* due 2, 3, 4 Oct (₹324) by yesterday, ₹108 paid; 5 Oct due today */
    expect($loan->refresh()->standing())->toMatchArray([
        'state' => 'overdue',
        'overdue' => 216,
        'due_today' => 108,
        'to_collect' => 324,
        'installments_paid' => 1,
        'days_overdue' => 2,
    ])->and($loan->standing()['overdue_since']->toDateString())->toBe('2026-10-03');

    FinanceCollection::record($loan, ['amount' => 216, 'collected_at' => '2026-10-05 09:00:00', 'method' => 'cash']);

    expect($loan->refresh()->standing())->toMatchArray(['state' => 'due', 'overdue' => 0, 'due_today' => 108]);

    $weekly = FinanceLoan::factory()->weekly()->create(['customer_id' => $this->customer->id, 'loaned_on' => '2026-10-01', 'first_due_on' => '2026-10-08']);

    expect($weekly->standing()['state'])->toBe('not_started')
        ->and($weekly->standing(Carbon::parse('2026-10-08'))['due_today'])->toBe(765)
        ->and($weekly->standing(Carbon::parse('2026-10-16'))['overdue'])->toBe(1530);
});

test('a collection never exceeds the balance; full payment closes the loan and cancelling opens it again', function () {
    /* ₹1,000 for 2 days: ₹1 interest, ₹1,001 to repay */
    $loan = giveLoan(['principal' => '1000', 'installments' => '2']);

    expect($loan->loan_amount)->toBe(1001);

    $this->post(route('finance.collections.store', $loan), ['amount' => '1,200', 'collected_at' => '2026-10-05T09:30', 'method' => 'cash'])
        ->assertSessionHasErrors(['amount' => 'The amount cannot be more than the balance of ₹1,001.']);

    $this->post(route('finance.collections.store', $loan), ['amount' => '1001', 'collected_at' => '2026-10-05T09:30', 'method' => 'upi', 'reference' => 'UPI123'])
        ->assertRedirect();

    $collection = FinanceCollection::sole();

    expect($collection->receipt_number)->toBe('C'.str_pad((string) $collection->id, 6, '0', STR_PAD_LEFT))
        ->and($loan->refresh()->isClosed())->toBeTrue()
        ->and($loan->closed_on->toDateString())->toBe('2026-10-05');

    $this->get(route('finance.collections.show', $collection))->assertOk()->assertSeeText('₹1,001')->assertSeeText('Kavitha (Tailor)');
    $this->get(route('finance.collections.pdf', $collection))->assertOk()->assertHeader('content-type', 'application/pdf');

    /* a loan with collections cannot be deleted */
    $this->delete(route('finance.loans.destroy', $loan))->assertSessionHas('error');

    $this->delete(route('finance.collections.destroy', $collection))->assertRedirect(route('finance.loans.show', $loan));

    expect($loan->refresh()->isClosed())->toBeFalse()->and($loan->balance())->toBe(1001);

    $this->delete(route('finance.loans.destroy', $loan))->assertRedirect(route('finance.loans.index'));
    expect(FinanceLoan::count())->toBe(0);
});

test('Collect shows overdue loans first, then due today', function () {
    giveLoan();
    $other = Customer::factory()->create(['name' => 'Ravi']);
    giveLoan(['customer_id' => $other->id, 'loaned_on' => '2026-10-04', 'first_due_on' => '2026-10-05']);

    $this->get(route('finance.collect'))
        ->assertOk()
        ->assertViewHas('tab', 'overdue')
        ->assertSeeText('Kavitha (Tailor)')
        ->assertDontSeeText('Ravi');

    $this->get(route('finance.collect', ['tab' => 'due']))->assertOk()->assertSeeText('Ravi');

    $this->get(route('finance.dashboard'))
        ->assertOk()
        ->assertSeeText('Sri Lakshmi')
        ->assertViewHas('overdueTotal', 324)
        ->assertViewHas('dueTodayTotal', 216)
        ->assertViewHas('dueTodayCount', 2);
});

test('reports show, print and download; profit is the fee plus the interest collected, less expenses', function () {
    $partner = User::factory()->create(['name' => 'Sathiya', 'is_partner' => true]);
    $loan = giveLoan();

    /* ₹1,071 collected holds ₹71 interest (712 / 10,712 of it) */
    FinanceCollection::record($loan, ['amount' => 1071, 'collected_at' => '2026-10-04 09:00:00', 'method' => 'cash']);

    $this->post(route('finance.expenses.store'), [
        'spent_on' => '2026-10-03', 'description' => 'Collection bike petrol', 'amount' => 250, 'paid_by' => $partner->id, 'method' => 'cash',
    ])->assertRedirect();

    expect(FinanceExpense::count())->toBe(1)
        ->and(Expense::count())->toBe(0);

    $this->get(route('finance.reports.show', ['report' => 'profit']))
        ->assertOk()
        ->assertViewHas('summary', fn (array $summary) => $summary['fees'] === 100
            && $summary['interest'] === 71
            && $summary['income'] === 171
            && $summary['gst'] === 18
            && $summary['expenses'] === 250
            && $summary['net'] === -79
            && $summary['lent'] === 9882);

    foreach (array_keys(ReportController::REPORTS) as $report) {
        $this->get(route('finance.reports.show', ['report' => $report]))->assertOk();
        $this->get(route('finance.reports.print', ['report' => $report]))->assertOk()->assertSeeText('Sri Lakshmi');
        $this->get(route('finance.reports.pdf', ['report' => $report]))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    $this->get(route('finance.reports.show', ['report' => 'outstanding']))->assertSeeText('Kavitha (Tailor)')->assertSeeText('₹10,712');

    $this->get(route('finance.reports.customer'))->assertOk()->assertSeeText('Kavitha');
    $this->get(route('finance.accounts.show', $this->customer))->assertOk()->assertSeeText('₹10,000');
    $this->get(route('finance.accounts.print', $this->customer))->assertOk();
    $this->get(route('finance.accounts.pdf', $this->customer))->assertOk()->assertHeader('content-type', 'application/pdf');

    $this->get(route('finance.expenses.balance'))->assertOk()->assertSeeText('Sri Lakshmi');
});

test('the acknowledgement prints in Tamil when Tamil is chosen', function () {
    $loan = giveLoan();

    $this->withUnencryptedCookie(SetLanguage::COOKIE, 'ta')
        ->get(route('finance.loans.acknowledgement', $loan))
        ->assertOk()
        ->assertSeeText('கடன் ஒப்புகைச் சீட்டு')
        ->assertSeeText('கையில் பெற்ற தொகை');

    $this->withUnencryptedCookie(SetLanguage::COOKIE, 'ta')
        ->get(route('finance.loans.acknowledgement.pdf', $loan))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('customers see their own loans with receipts, never other customers loans', function () {
    $loan = giveLoan();
    $collection = FinanceCollection::record($loan, ['amount' => 108, 'collected_at' => '2026-10-02 09:00:00', 'method' => 'cash']);
    $otherLoan = FinanceLoan::factory()->create();

    $this->customer->choosePassword('kavitha99');
    auth('web')->logout();
    $this->actingAs($this->customer->refresh(), 'customer');

    $this->get(route('portal.dashboard'))->assertOk()->assertSeeText('Micro Finance')->assertSeeText($loan->loan_number)->assertSeeText('My Loans');
    $this->get(route('portal.loans'))->assertOk()->assertSeeText($loan->loan_number)->assertDontSeeText($otherLoan->loan_number);
    $this->get(route('portal.loans.show', $loan))->assertOk()->assertSeeText($collection->receipt_number)->assertSeeText('₹10,604');
    $this->get(route('portal.loans.receipt.pdf', $collection))->assertOk()->assertHeader('content-type', 'application/pdf');

    $this->get(route('portal.loans.show', $otherLoan))->assertNotFound();
});

test('customers without a loan see the loan plans, not My Loans in the menu', function () {
    $this->customer->choosePassword('kavitha99');
    auth('web')->logout();

    $this->actingAs($this->customer->refresh(), 'customer')
        ->get(route('portal.dashboard'))
        ->assertOk()
        ->assertSeeText('You have no running loan')
        ->assertSeeText('Daily loan')
        ->assertSeeText('Weekly loan')
        ->assertDontSee('href="'.route('portal.loans').'"', false);
});

test('customers see only Sri Lakshmi Micro Finance while chit funds and rice are paused', function () {
    giveLoan();
    $this->customer->choosePassword('kavitha99');
    auth('web')->logout();
    $this->actingAs($this->customer->refresh(), 'customer');

    $this->get(route('portal.dashboard'))
        ->assertOk()
        ->assertSeeText('Sri Lakshmi')
        ->assertSeeText('Small Loans · Easy Repayment')
        ->assertSeeText('My Loans')
        ->assertDontSeeText('My Groups')
        ->assertDontSeeText('Upcoming Groups')
        ->assertDontSeeText('Rice & Order')
        ->assertDontSee('Chit Funds · Traders');

    foreach (['portal.groups', 'portal.upcoming', 'portal.rice', 'portal.orders', 'portal.bills'] as $paused) {
        $this->get(route($paused))->assertRedirect(route('portal.dashboard'));
    }

    auth('customer')->logout();
    $this->get(route('portal.login'))->assertOk()->assertSeeText('Micro Finance')->assertSeeText('See your loans day by day');
});

test('the loan plans show what a customer receives and repays, without an APR', function () {
    $this->customer->choosePassword('kavitha99');
    auth('web')->logout();

    $this->actingAs($this->customer->refresh(), 'customer')
        ->get(route('portal.loan-plans'))
        ->assertOk()
        ->assertSeeText('₹9,882')
        ->assertSeeText('₹10,712')
        ->assertSeeText('₹108')
        ->assertSeeText('₹765')
        ->assertDontSeeText('APR')
        ->assertSee('id="loanCalculator"', false);
});

test('customers see their loan day by day — paid and left — and can print or download the passbook', function () {
    $loan = giveLoan();
    FinanceCollection::record($loan, ['amount' => 270, 'collected_at' => '2026-10-04 09:00:00', 'method' => 'cash']);
    $this->customer->choosePassword('kavitha99');
    auth('web')->logout();
    $this->actingAs($this->customer->refresh(), 'customer');

    /* ₹270 pays days 1–2 (2, 3 Oct) and ₹54 of day 3 (4 Oct, now overdue); day 4 (5 Oct) is due today */
    $schedule = $loan->refresh()->schedule();

    expect($schedule->take(5)->pluck('state')->all())->toBe(['paid', 'paid', 'overdue', 'due', 'upcoming'])
        ->and($schedule[2]['paid'])->toBe(54)
        ->and($schedule[0]['paid_on'][0]->toDateString())->toBe('2026-10-04')
        ->and($schedule[3]['paid_on'])->toBe([])
        ->and($schedule[0]['balance_after'])->toBe(10604)
        ->and($schedule->last()['amount'])->toBe(20)
        ->and($schedule->last()['balance_after'])->toBe(0);

    $this->get(route('portal.loans.show', $loan))
        ->assertOk()
        ->assertSeeText('Day by day')
        ->assertSeeText('Collected on')
        ->assertSeeText('04 Oct')
        ->assertSeeText('Left to pay')
        ->assertSeeText('₹10,442');

    $this->get(route('portal.loans.passbook', $loan))->assertOk()->assertSeeText('Part B — Collection Passbook');
    $this->get(route('portal.loans.passbook.pdf', $loan))->assertOk()->assertHeader('content-type', 'application/pdf');

    $other = FinanceLoan::factory()->create();
    $this->get(route('portal.loans.passbook', $other))->assertNotFound();
});

test('the Key Fact Statement shows the costs, without an APR, with the passbook grid', function () {
    $loan = giveLoan();

    $this->get(route('finance.loans.kfs', $loan))
        ->assertOk()
        ->assertSeeText('Part A — Key Fact Statement')
        ->assertSeeText('Sanctioned loan amount')
        ->assertSeeText('Net amount disbursed to the borrower')
        ->assertSeeText('₹9,882')
        ->assertDontSeeText('Annual Percentage Rate')
        ->assertSeeText('Coimbatore')
        ->assertSeeText('No collateral or security is taken for this loan.')
        ->assertSeeText('9842510159')
        ->assertDontSeeText('credit information companies')
        ->assertSeeText('Part B — Collection Passbook');

    config(['app.finance_kfs.credit_bureaus' => 'CIBIL, Equifax', 'app.finance_kfs.grievance_officer' => 'R. Priya']);

    $this->get(route('finance.loans.kfs', $loan))
        ->assertSeeText('credit information companies: CIBIL, Equifax')
        ->assertSeeText('R. Priya');

    $this->get(route('finance.loans.kfs.pdf', $loan))->assertOk()->assertHeader('content-type', 'application/pdf');
});

test('on its own domain, staff land on Micro Finance and customers see its name', function () {
    /* with chit funds and rice open to customers, only the finance domain carries the Sri Lakshmi name */
    config(['app.finance_domain' => 'srilakshmifinance.com', 'app.portal_sections' => ['chit', 'traders', 'finance']]);

    $this->get('http://srilakshmifinance.com/admin')->assertRedirect('http://srilakshmifinance.com/finance');

    auth('web')->logout();

    $this->get('http://srilakshmifinance.com/')->assertOk()->assertSeeText('Micro Finance')->assertSeeText('See your loans day by day');
    $this->get('http://snchitfunds.com/')->assertOk()->assertSeeText('See your chit groups');
});

test('Micro Finance pages and the customer pages use the Sri Lakshmi tab icon; SN pages keep theirs', function () {
    $this->get(route('finance.dashboard'))->assertSee('favicon-sl.ico', false);
    $this->get(route('dashboard'))->assertSee('favicon.ico', false)->assertDontSee('favicon-sl.ico', false);

    auth('web')->logout();
    $this->get(route('portal.login'))->assertSee('favicon-sl.ico', false)->assertSee('apple-touch-icon-sl.png', false);
});

test('in Micro Finance the customer list shows running loans instead of chit groups', function () {
    giveLoan();
    Customer::factory()->create(['name' => 'Ravi']);

    $this->get(route('finance.dashboard'));

    $this->get(route('customers.index'))
        ->assertOk()
        ->assertViewHas('inFinance', true)
        ->assertSeeText('Manage your customers and their loans')
        ->assertSeeText('1 loan')
        ->assertSeeText('₹10,712')
        ->assertSee(route('finance.accounts.show', $this->customer), false)
        ->assertSee(route('finance.loans.create', ['customer' => Customer::where('name', 'Ravi')->value('id')]), false)
        ->assertDontSeeText('View Groups');

    $this->get(route('dashboard'));

    $this->get(route('customers.index'))->assertViewHas('inFinance', false)->assertSeeText('View Groups');
});

test('staff land on the Micro Finance dashboard after signing in', function () {
    auth()->logout();
    $staff = User::factory()->create(['username' => 'sathiya', 'password' => 'secret123']);

    $this->post(route('login.store'), ['username' => 'sathiya', 'password' => 'secret123'])
        ->assertRedirect(route('finance.dashboard'));

    $this->get('/admin')->assertRedirect('/finance');
    $this->get(route('dashboard'))->assertOk()->assertSee(route('finance.dashboard'), false);
});

test('capital invested less money lent, plus collections, less expenses is available to lend', function () {
    $this->post(route('finance.capital.store'), ['type' => 'invest', 'amount' => '5,00,000', 'entry_on' => '2026-10-01', 'method' => 'cash'])
        ->assertRedirect(route('finance.capital.index'));

    $loan = giveLoan();
    FinanceCollection::record($loan, ['amount' => 324, 'collected_at' => '2026-10-04 09:00:00', 'method' => 'cash']);
    FinanceExpense::create(['spent_on' => '2026-10-03', 'description' => 'Petrol', 'amount' => 200, 'paid_by' => $this->admin->id, 'method' => 'cash']);

    /* 5,00,000 − 9,882 in hand + 324 collected − 200 expenses */
    expect(FinanceCapital::position()['available'])->toBe(490242);

    $this->get(route('finance.capital.index'))->assertOk()->assertSeeText('Available to lend')->assertSeeText('₹4,90,242');
    $this->get(route('finance.loans.create'))->assertOk()->assertSee('data-available="490242"', false);
    $this->get(route('finance.dashboard'))->assertOk()->assertViewHas('available', 490242);

    /* cannot take out more than is available */
    $this->post(route('finance.capital.store'), ['type' => 'withdraw', 'amount' => '600000', 'entry_on' => '2026-10-05', 'method' => 'cash'])
        ->assertSessionHasErrors('amount');
    $this->post(route('finance.capital.store'), ['type' => 'withdraw', 'amount' => '90242', 'entry_on' => '2026-10-05', 'method' => 'cash']);

    expect(FinanceCapital::position()['available'])->toBe(400000);
});

test('Micro Finance pages need a staff sign-in', function () {
    auth()->logout();

    $this->get(route('finance.dashboard'))->assertRedirect(route('login'));
});

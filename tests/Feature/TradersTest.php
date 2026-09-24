<?php

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\RiceVariety;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\TraderExpense;
use App\Models\TraderReceipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 9, 24)->setTime(10, 0));

    $this->admin = User::factory()->create(['name' => 'Narayanan']);
    $this->actingAs($this->admin);

    $this->ponni = RiceVariety::factory()->create(['name' => 'Ponni', 'bag_kg' => 26]);
    $this->basmati = RiceVariety::factory()->create(['name' => 'Basmati', 'bag_kg' => 25]);
    $this->mill = Supplier::factory()->create(['name' => 'Sri Murugan Rice Mill']);
    $this->customer = Customer::factory()->create(['name' => 'Lakshmi Stores', 'phone' => '9000022222', 'remarks' => 'Grocery']);
});

/**
 * A purchase of 10 bags of Ponni at ₹1,330 a bag and 2 bags of Basmati at
 * ₹2,250 a bag.
 *
 * @return array<string, mixed>
 */
function purchaseInput(array $overrides = []): array
{
    return array_merge([
        'supplier_id' => test()->mill->id,
        'purchased_on' => '2026-09-20',
        'supplier_bill_no' => 'SM-441',
        'method' => 'bank',
        'lines' => [
            ['variety_id' => test()->ponni->id, 'bags' => '10', 'rate' => '1,330'],
            ['variety_id' => test()->basmati->id, 'bags' => '2', 'rate' => '2,250'],
            ['variety_id' => '', 'bags' => '', 'rate' => ''],
        ],
    ], $overrides);
}

/**
 * A sale of 3 bags of Ponni at ₹1,430 a bag to Lakshmi Stores.
 *
 * @return array<string, mixed>
 */
function saleInput(array $overrides = []): array
{
    return array_merge([
        'customer_id' => test()->customer->id,
        'sold_on' => '2026-09-24',
        'lines' => [
            ['variety_id' => test()->ponni->id, 'bags' => '3', 'rate' => '1,430'],
        ],
        'received_amount' => '2,000',
        'received_method' => 'cash',
    ], $overrides);
}

test('guests cannot open SN Traders', function () {
    auth()->logout();

    $this->get(route('traders.dashboard'))->assertRedirect(route('login'));
    $this->get(route('traders.sales.index'))->assertRedirect(route('login'));
});

test('each dashboard switches to the other business and the menu follows', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('traders.dashboard'))
        ->assertSee('<span>SN</span> Chit Funds', false)
        ->assertSee('id="groupsSubmenu"', false);

    $this->get(route('traders.dashboard'))
        ->assertOk()
        ->assertSee('<span>SN</span> Traders', false)
        ->assertSee('id="tradersSalesSubmenu"', false)
        ->assertSee('id="tradersExpensesSubmenu"', false)
        ->assertDontSee('id="groupsSubmenu"', false)
        ->assertSee(route('dashboard'));

    /* shared pages (customers) keep the business last opened */
    $this->get(route('customers.index'))->assertSee('<span>SN</span> Traders', false);

    $this->get(route('dashboard'));
    $this->get(route('customers.index'))->assertSee('<span>SN</span> Chit Funds', false);
});

test('rice varieties and suppliers can be added, edited and protected once used', function () {
    $this->post(route('traders.varieties.store'), ['name' => 'Idli Rice', 'bag_kg' => '25'])->assertRedirect(route('traders.varieties.index'));
    $this->post(route('traders.varieties.store'), ['name' => 'Idli Rice', 'bag_kg' => '25'])->assertSessionHasErrors('name');

    $idli = RiceVariety::where('name', 'Idli Rice')->sole();

    $this->put(route('traders.varieties.update', $idli), ['name' => 'Idli Rice', 'bag_kg' => '26', 'is_active' => '1']);
    expect((float) $idli->refresh()->bag_kg)->toBe(26.0);

    $this->post(route('traders.suppliers.store'), ['name' => 'Kaveri Traders', 'phone' => '9876501234', 'place' => 'Salem'])->assertRedirect(route('traders.suppliers.index'));
    expect(Supplier::where('name', 'Kaveri Traders')->exists())->toBeTrue();

    $this->post(route('traders.purchases.store'), purchaseInput());

    $this->delete(route('traders.varieties.destroy', $this->ponni))->assertSessionHas('error');
    $this->delete(route('traders.suppliers.destroy', $this->mill))->assertSessionHas('error');
    $this->delete(route('traders.varieties.destroy', $idli))->assertSessionHas('success');

    expect(RiceVariety::whereKey($this->ponni->id)->exists())->toBeTrue()
        ->and(RiceVariety::whereKey($idli->id)->exists())->toBeFalse();
});

test('a purchase is priced per bag and weighed at the variety bag size', function () {
    $this->post(route('traders.purchases.store'), purchaseInput())
        ->assertRedirect(route('traders.purchases.show', Purchase::sole()));

    $purchase = Purchase::with('items')->sole();
    [$ponni, $basmati] = $purchase->items;

    /* 10 bags × ₹1,330 = ₹13,300 (260 kg); 2 bags × ₹2,250 = ₹4,500 (50 kg) */
    expect($purchase->purchase_number)->toBe('P000001')
        ->and($purchase->items)->toHaveCount(2)
        ->and($ponni->kg)->toBe(260.0)
        ->and($ponni->rate_per)->toBe('bag')
        ->and($ponni->amount)->toBe(13300.0)
        ->and($basmati->kg)->toBe(50.0)
        ->and($basmati->amount)->toBe(4500.0)
        ->and($purchase->total_amount)->toBe(17800.0)
        ->and($purchase->recorded_by)->toBe($this->admin->id);

    $this->get(route('traders.purchases.show', $purchase))
        ->assertOk()
        ->assertSeeText('Sri Murugan Rice Mill')
        ->assertSeeTextInOrder(['Ponni', '10', '₹1,330', '₹13,300'])
        ->assertDontSeeText('kg')
        ->assertSeeText('₹17,800');

    $this->get(route('traders.purchases.pdf', $purchase))->assertOk()->assertHeader('content-type', 'application/pdf');
});

test('bad bills are rejected', function (array $overrides, string $field) {
    $this->post(route('traders.purchases.store'), purchaseInput($overrides))->assertSessionHasErrors($field);

    expect(Purchase::count())->toBe(0);
})->with([
    'no lines' => [['lines' => [['variety_id' => '', 'bags' => '', 'rate' => '']]], 'lines'],
    'no bags' => [['lines' => [['variety_id' => 1, 'bags' => '0', 'rate' => '1300']]], 'lines.0.bags'],
    'part bag' => [['lines' => [['variety_id' => 1, 'bags' => '2.5', 'rate' => '1300']]], 'lines.0.bags'],
    'no rate' => [['lines' => [['variety_id' => 1, 'bags' => '2', 'rate' => '']]], 'lines.0.rate'],
    'future date' => [['purchased_on' => '2026-09-25'], 'purchased_on'],
    'no supplier' => [['supplier_id' => ''], 'supplier_id'],
]);

test('a sale takes money now, puts the rest on credit and brings stock down', function () {
    $this->post(route('traders.purchases.store'), purchaseInput());

    $this->post(route('traders.sales.store'), saleInput())->assertRedirect(route('traders.sales.show', Sale::sole()));

    $sale = Sale::with(['items', 'receipts'])->sole();

    /* 3 bags × ₹1,430 = ₹4,290; ₹2,000 now → ₹2,290 on credit */
    expect($sale->invoice_number)->toBe('S000001')
        ->and($sale->total_amount)->toBe(4290.0)
        ->and($sale->receipts)->toHaveCount(1)
        ->and($sale->receipts->first()->amount)->toBe(2000.0)
        ->and($sale->receipts->first()->receipt_number)->toBe('R000001')
        ->and($this->customer->traderBalance())->toBe(2290.0);

    $this->get(route('traders.sales.show', $sale))
        ->assertOk()
        ->assertSeeTextInOrder(['Invoice', 'S000001', 'Lakshmi Stores', 'Ponni', '3', '₹1,430', '₹4,290', 'Received now', '₹2,000', 'On credit', '₹2,290']);

    $stock = $this->get(route('traders.stock'))->viewData('rows')->keyBy('name');

    expect($stock['Ponni']['stock_bags'])->toBe(7)
        ->and($stock['Basmati']['stock_bags'])->toBe(2);

    $this->get(route('traders.sales.pdf', $sale))->assertOk()->assertHeader('content-type', 'application/pdf');
});

test('a sale cannot take more money now than the invoice total', function () {
    $this->post(route('traders.sales.store'), saleInput(['received_amount' => '5,000']))
        ->assertSessionHasErrors('received_amount');

    expect(Sale::count())->toBe(0);
});

test('receiving a payment lowers the balance and cancelling it puts it back', function () {
    $this->post(route('traders.sales.store'), saleInput(['received_amount' => '0']));

    $this->get(route('traders.receipts.create', ['customer' => $this->customer->id]))
        ->assertOk()
        ->assertSee('value="4290.00"', false);

    $this->post(route('traders.receipts.store'), [
        'customer_id' => $this->customer->id,
        'amount' => '1,500',
        'received_at' => '2026-09-24T15:00',
        'method' => 'upi',
        'reference' => 'UPI-77',
    ])->assertRedirect(route('traders.receipts.show', TraderReceipt::sole()));

    expect($this->customer->traderBalance())->toBe(2790.0);

    $receipt = TraderReceipt::sole();

    $this->get(route('traders.receipts.show', $receipt))
        ->assertOk()
        ->assertSeeText('Rupees One Thousand Five Hundred Only')
        ->assertSeeText('₹2,790');

    $this->get(route('traders.receipts.pdf', $receipt))->assertOk();

    $this->delete(route('traders.receipts.destroy', $receipt))->assertRedirect(route('traders.accounts.show', $this->customer));

    expect($this->customer->traderBalance())->toBe(4290.0);
});

test('customer balances list who owes and the account shows a running balance', function () {
    $other = Customer::factory()->create(['name' => 'Paid Up Priya']);

    $this->post(route('traders.sales.store'), saleInput(['sold_on' => '2026-09-20', 'received_amount' => '1,000']));
    $this->post(route('traders.sales.store'), saleInput(['customer_id' => $other->id, 'received_amount' => '4,290']));
    $this->post(route('traders.receipts.store'), ['customer_id' => $this->customer->id, 'amount' => '500', 'received_at' => '2026-09-22T10:00', 'method' => 'cash']);

    $this->get(route('traders.balances.index'))
        ->assertOk()
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('customer.name')->all() === ['Lakshmi Stores'] && $rows[0]['balance'] === 2790.0)
        ->assertViewHas('totalOwing', 2790.0)
        ->assertDontSee('Paid Up Priya');

    $this->get(route('traders.balances.index', ['view' => 'all']))->assertSee('Paid Up Priya');

    $this->get(route('traders.accounts.show', $this->customer))
        ->assertOk()
        ->assertViewHas('entries', fn ($entries) => $entries->pluck('balance')->all() === [4290.0, 3290.0, 2790.0])
        ->assertSeeTextInOrder(['Invoice S000001', '₹4,290', 'Receipt R000001', '₹1,000', 'Receipt', '₹500', '₹2,790']);

    $this->get(route('traders.accounts.pdf', $this->customer))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->get(route('traders.balances.print'))->assertOk()->assertSee('window.print()', false);
    $this->get(route('traders.balances.pdf'))->assertOk();
});

test('the sales and purchase lists open on this month with totals, print and pdf', function () {
    $this->post(route('traders.purchases.store'), purchaseInput());
    $this->post(route('traders.purchases.store'), purchaseInput(['purchased_on' => '2026-08-10']));
    $this->post(route('traders.sales.store'), saleInput());

    $this->get(route('traders.purchases.index'))
        ->assertOk()
        ->assertViewHas('purchases', fn ($purchases) => $purchases->count() === 1)
        ->assertViewHas('summary', fn ($summary) => $summary['total'] === 17800.0 && $summary['bags'] === 12);

    $this->get(route('traders.purchases.index', ['range' => 'all', 'supplier' => $this->mill->id]))
        ->assertViewHas('purchases', fn ($purchases) => $purchases->count() === 2);

    $this->get(route('traders.sales.index'))
        ->assertOk()
        ->assertViewHas('summary', fn ($summary) => $summary['total'] === 4290.0 && $summary['received'] === 2000.0)
        ->assertSeeText('₹2,290 on credit');

    $this->get(route('traders.sales.index', ['q' => 'Lakshmi']))->assertViewHas('sales', fn ($sales) => $sales->count() === 1);

    $this->get(route('traders.purchases.print', ['range' => 'month']))->assertOk()->assertSeeText('Sri Murugan Rice Mill');
    $this->get(route('traders.purchases.list-pdf', ['range' => 'month']))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->get(route('traders.sales.print', ['range' => 'month']))->assertOk()->assertSeeText('S000001');
    $this->get(route('traders.sales.list-pdf', ['range' => 'month']))->assertOk();
    $this->get(route('traders.stock.print'))->assertOk()->assertSeeText('Ponni');
    $this->get(route('traders.stock.pdf'))->assertOk();
});

test('deleting a sale removes its money taken at the sale', function () {
    $this->post(route('traders.sales.store'), saleInput());

    $this->delete(route('traders.sales.destroy', Sale::sole()))->assertRedirect(route('traders.sales.index'));

    expect(Sale::count())->toBe(0)
        ->and(TraderReceipt::count())->toBe(0)
        ->and($this->customer->traderBalance())->toBe(0.0);
});

test('traders expenses and balance sheet are kept apart from chit fund expenses', function () {
    $sathiya = User::factory()->create(['name' => 'Sathiya']);

    $this->get(route('traders.expenses.create'))->assertOk()->assertSee(route('traders.expenses.store'));

    $this->post(route('traders.expenses.store'), [
        'spent_on' => '2026-09-20',
        'description' => 'Godown rent',
        'amount' => '8,000',
        'paid_by' => $sathiya->id,
        'method' => 'cash',
    ])->assertRedirect(route('traders.expenses.index', ['range' => 'month']));

    expect(TraderExpense::count())->toBe(1)
        ->and(Expense::count())->toBe(0);

    $this->get(route('expenses.index'))->assertDontSee('Godown rent');
    $this->get(route('traders.expenses.index'))->assertSee('Godown rent');

    $balance = $this->get(route('traders.expenses.balance'))->assertOk();

    expect($balance->viewData('total'))->toBe(8000)
        ->and($balance->viewData('transfers')[0]['from']->is($this->admin))->toBeTrue();

    expect($this->get(route('expenses.balance'))->viewData('total'))->toBe(0);

    $expense = TraderExpense::sole();

    $this->get(route('traders.expenses.edit', $expense))->assertOk()->assertSee('Godown rent');
    $this->delete(route('traders.expenses.destroy', $expense))->assertRedirect(route('traders.expenses.index'));

    expect(TraderExpense::count())->toBe(0);

    $this->get(route('traders.expenses.balance.pdf'))->assertOk();
    expect($this->get(route('traders.expenses.balance.pdf'))->headers->get('content-disposition'))->toContain('Traders-Balance-Sheet');
});

test('the traders dashboard shows sales, credit and stock', function () {
    $this->post(route('traders.purchases.store'), purchaseInput());
    $this->post(route('traders.sales.store'), saleInput());

    $this->get(route('traders.dashboard'))
        ->assertOk()
        ->assertViewHas('salesToday', 4290.0)
        ->assertViewHas('receivedToday', 2000.0)
        ->assertViewHas('purchasesMonth', 17800.0)
        ->assertViewHas('creditTotal', 2290.0)
        ->assertSeeTextInOrder(['Who owes the most', 'Lakshmi Stores', '₹2,290'])
        ->assertSeeText('7 bags');
});

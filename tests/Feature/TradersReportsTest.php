<?php

use App\Models\Customer;
use App\Models\Purchase;
use App\Models\RiceVariety;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\TraderExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A bag line for Purchase::record / Sale::record.
 *
 * @return array{variety_id: int, bags: int, bag_kg: float, loose_kg: float, rate: float, rate_per: string}
 */
function bagLine(RiceVariety $variety, int $bags, float $rate): array
{
    return ['variety_id' => $variety->id, 'bags' => $bags, 'bag_kg' => 26.0, 'loose_kg' => 0.0, 'rate' => $rate, 'rate_per' => 'bag'];
}

/**
 * Today is 24 Sep 2026.
 *
 * Purchases: 1 Sep — Ponni 10 bags @ ₹1,300, Basmati 4 bags @ ₹2,000;
 * 20 Aug (entered later) — Ponni 2 bags @ ₹1,200.
 * Sales: 1 Jun — Ravi, Basmati 1 @ ₹2,300, unpaid; 1 Jul — Lakshmi, Ponni
 * 1 @ ₹1,450; 10 Sep — Lakshmi, Ponni 5 @ ₹1,450, ₹2,250 paid; 24 Sep —
 * Kumar, Basmati 1 @ ₹2,300, paid by UPI.
 * Expense: 15 Sep — ₹1,000.
 */
beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 9, 24)->setTime(10, 0));

    $this->admin = User::factory()->create(['name' => 'Narayanan', 'is_partner' => true]);
    User::factory()->create(['name' => 'Sathiya', 'is_partner' => true]);
    $this->actingAs($this->admin);

    $this->ponni = RiceVariety::factory()->create(['name' => 'Ponni', 'bag_kg' => 26, 'selling_price' => 1450]);
    $this->basmati = RiceVariety::factory()->create(['name' => 'Basmati', 'bag_kg' => 26, 'selling_price' => 2300]);
    $mill = Supplier::factory()->create(['name' => 'Sri Murugan Rice Mill']);

    $this->lakshmi = Customer::factory()->create(['name' => 'Lakshmi Stores', 'remarks' => 'Grocery']);
    $this->ravi = Customer::factory()->create(['name' => 'Ravi Mess']);
    $this->kumar = Customer::factory()->create(['name' => 'Kumar Hotel']);

    Purchase::record(['supplier_id' => $mill->id, 'purchased_on' => '2026-09-01', 'method' => 'bank'], [
        bagLine($this->ponni, 10, 1300),
        bagLine($this->basmati, 4, 2000),
    ], $this->admin);

    Purchase::record(['supplier_id' => $mill->id, 'purchased_on' => '2026-08-20', 'method' => 'cash'], [
        bagLine($this->ponni, 2, 1200),
    ], $this->admin);

    Sale::record(['customer_id' => $this->ravi->id, 'sold_on' => '2026-06-01'], [bagLine($this->basmati, 1, 2300)], null, $this->admin);
    Sale::record(['customer_id' => $this->lakshmi->id, 'sold_on' => '2026-07-01'], [bagLine($this->ponni, 1, 1450)], null, $this->admin);
    Sale::record(['customer_id' => $this->lakshmi->id, 'sold_on' => '2026-09-10'], [bagLine($this->ponni, 5, 1450)], [
        'amount' => 2250, 'method' => 'cash', 'received_at' => '2026-09-10 11:00:00',
    ], $this->admin);
    Sale::record(['customer_id' => $this->kumar->id, 'sold_on' => '2026-09-24'], [bagLine($this->basmati, 1, 2300)], [
        'amount' => 2300, 'method' => 'upi', 'received_at' => '2026-09-24 09:30:00',
    ], $this->admin);

    TraderExpense::create(['spent_on' => '2026-09-15', 'description' => 'Lorry hire', 'amount' => 1000, 'paid_by' => $this->admin->id, 'method' => 'cash']);
});

test('a purchase sets the purchase price unless a later bill already did, and sales keep it as their cost', function () {
    expect($this->ponni->refresh()->purchase_price)->toBe(1300.0)
        ->and($this->basmati->refresh()->purchase_price)->toBe(2000.0)
        ->and($this->ponni->profitPerBag())->toBe(150.0);

    $line = Sale::whereDate('sold_on', '2026-09-10')->sole()->items->sole();

    /* 5 bags × (₹1,450 − ₹1,300) */
    expect($line->cost_rate)->toBe(1300.0)
        ->and($line->profit())->toBe(750.0);

    $this->get(route('traders.sales.show', $line->sale_id))
        ->assertOk()
        ->assertSeeTextInOrder(['Profit on this sale', '₹750']);

    /* the bill forms fill the selling / purchase price */
    $this->get(route('traders.sales.create'))->assertSee('data-price="1450"', false)->assertSee('data-cost="1300"', false);
    $this->get(route('traders.purchases.create'))->assertSee('data-price="1300"', false);
});

test('varieties keep their purchase and selling prices', function () {
    $this->put(route('traders.varieties.update', $this->ponni), [
        'name' => 'Ponni', 'bag_kg' => '26', 'purchase_price' => '1350', 'selling_price' => '1500', 'is_active' => '1',
    ])->assertRedirect(route('traders.varieties.index'));

    expect($this->ponni->refresh()->purchase_price)->toBe(1350.0)
        ->and($this->ponni->selling_price)->toBe(1500.0);

    $this->get(route('traders.varieties.index'))
        ->assertOk()
        ->assertSeeInOrder(['Ponni', '₹150', 'value="1350"', 'value="1500"'], false);

    $this->put(route('traders.varieties.update', $this->ponni), ['name' => 'Ponni', 'bag_kg' => '26', 'selling_price' => '-5'])
        ->assertSessionHasErrors('selling_price');
});

test('rice sales ranks the rice by bags sold with pace, stock and top customers', function () {
    $response = $this->get(route('traders.reports.show', 'rice-sales'))->assertOk();

    $rows = $response->viewData('rows');

    /* September: Ponni 5 bags, Basmati 1 bag over 24 days */
    expect($rows->pluck('name')->all())->toBe(['Ponni', 'Basmati'])
        ->and($rows[0]['bags'])->toBe(5)
        ->and($rows[0]['share'])->toBe(83.3)
        ->and($rows[0]['stock_bags'])->toBe(6)
        ->and($rows[0]['days_left'])->toBe(28)
        ->and($rows[0]['speed'])->toBe('fast')
        ->and($rows[1]['speed'])->toBe('slow')
        ->and($response->viewData('summary')['amount'])->toBe(9550.0)
        ->and($response->viewData('topCustomers')->first()['customer']->is($this->lakshmi))->toBeTrue();

    $response->assertSeeText('Grocery');
});

test('profit and loss takes the cost of the rice sold and expenses, and splits the profit', function () {
    $response = $this->get(route('traders.reports.show', 'profit'))->assertOk();

    $summary = $response->viewData('summary');

    /* sales ₹9,550 − cost (5 × 1,300 + 1 × 2,000) ₹8,500 = ₹1,050; − ₹1,000 expenses = ₹50 */
    expect($summary['sales'])->toBe(9550.0)
        ->and($summary['cost'])->toBe(8500.0)
        ->and($summary['gross'])->toBe(1050.0)
        ->and($summary['expenses'])->toBe(1000.0)
        ->and($summary['net'])->toBe(50.0)
        ->and($summary['purchases'])->toBe(21000.0)
        ->and($response->viewData('partnerShares')->pluck('share')->all())->toBe([25.0, 25.0]);

    $response->assertSeeTextInOrder(['Net profit', '₹50', 'Share of Narayanan', '₹25']);
});

test('profit falls back to the average purchase cost for sales saved without one', function () {
    Sale::query()->each(fn (Sale $sale) => $sale->items()->update(['cost_rate' => null]));

    /* Ponni average: (13,000 + 2,400) / 12 bags = ₹1,283.33 */
    $rows = $this->get(route('traders.reports.show', 'profit'))->viewData('rows')->keyBy('name');

    expect($rows['Ponni']['cost_rate'])->toBe(1283.33)
        ->and($rows['Basmati']['cost_rate'])->toBe(2000.0);
});

test('customer dues age the unpaid invoices, oldest first', function () {
    $response = $this->get(route('traders.reports.show', 'dues'))->assertOk();

    $rows = $response->viewData('rows')->keyBy(fn ($row) => $row['customer']->name);

    /* Lakshmi paid ₹2,250: the July invoice (₹1,450) is cleared, ₹6,450 of 10 Sep is left */
    expect($rows->keys()->all())->toBe(['Ravi Mess', 'Lakshmi Stores'])
        ->and($rows['Lakshmi Stores']['balance'])->toBe(6450.0)
        ->and($rows['Lakshmi Stores']['oldest_unpaid'])->toBe('2026-09-10')
        ->and($rows['Lakshmi Stores']['days'])->toBe(14)
        ->and($rows['Lakshmi Stores']['buckets']['b30'])->toBe(6450.0)
        ->and($rows['Ravi Mess']['days'])->toBe(115)
        ->and($rows['Ravi Mess']['buckets']['over90'])->toBe(2300.0)
        ->and($response->viewData('summary')['total'])->toBe(8750.0);

    $this->get(route('traders.reports.show', ['report' => 'dues', 'age' => '90']))
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && $rows->first()['customer']->is($this->ravi));

    $this->get(route('traders.reports.show', ['report' => 'dues', 'q' => 'grocery']))
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && $rows->first()['customer']->is($this->lakshmi));
});

test('the day book lists each day with money in and out', function () {
    $response = $this->get(route('traders.reports.show', 'day-book'))->assertOk();

    $rows = $response->viewData('rows')->keyBy('date');

    expect($rows->keys()->all())->toBe(['2026-09-01', '2026-09-10', '2026-09-15', '2026-09-24'])
        ->and($rows['2026-09-01']['purchases'])->toBe(21000.0)
        ->and($rows['2026-09-10']['by_method']['cash'])->toBe(2250.0)
        ->and($rows['2026-09-15']['expenses'])->toBe(1000.0)
        ->and($rows['2026-09-24']['by_method']['upi'])->toBe(2300.0)
        ->and($response->viewData('summary')['net'])->toBe(-17450.0);

    $this->get(route('traders.reports.show', ['report' => 'day-book', 'from' => '2026-09-10', 'to' => '2026-09-10']))
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1);
});

test('every report prints and downloads', function (string $report) {
    $this->get(route('traders.reports.print', $report))->assertOk()->assertSee('window.print()', false);

    $response = $this->get(route('traders.reports.pdf', $report))->assertOk();

    expect($response->headers->get('content-type'))->toBe('application/pdf');
})->with(['rice-sales', 'profit', 'dues', 'day-book']);

test('the customer statement finder lists customers who bought rice', function () {
    $other = Customer::factory()->create(['name' => 'Never Bought']);

    $this->get(route('traders.reports.customer'))
        ->assertOk()
        ->assertSeeText('Kumar Hotel')
        ->assertSee(route('traders.accounts.show', $this->lakshmi))
        ->assertDontSeeText($other->name);

    $this->get(route('traders.reports.customer', ['q' => 'ravi']))
        ->assertSeeText('Ravi Mess')
        ->assertDontSeeText('Kumar Hotel');
});

test('the reports menu is in SN Traders and unknown reports are not found', function () {
    $this->get(route('traders.reports.show', 'profit'))
        ->assertSee('id="tradersReportsSubmenu"', false)
        ->assertSee(route('traders.reports.show', 'day-book'));

    $this->get('/traders/reports/unknown')->assertNotFound();
});

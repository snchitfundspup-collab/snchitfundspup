<?php

use App\Models\Expense;
use App\Models\PartnerSettlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 9, 24)->setTime(10, 0));

    $this->narayanan = User::factory()->create(['name' => 'Narayanan']);
    $this->sathiya = User::factory()->create(['name' => 'Sathiya']);

    $this->actingAs($this->narayanan);
});

/**
 * @return array<string, mixed>
 */
function expenseInput(User $paidBy, array $overrides = []): array
{
    return array_merge([
        'spent_on' => '2026-09-20',
        'description' => 'September office rent',
        'amount' => '12,000',
        'paid_by' => $paidBy->id,
        'paid_to' => 'Ravi Buildings',
        'method' => 'upi',
        'reference' => 'BILL-77',
        'notes' => 'Paid early',
    ], $overrides);
}

test('guests cannot open expenses or the balance sheet', function () {
    auth()->logout();

    $this->get(route('expenses.index'))->assertRedirect(route('login'));
    $this->get(route('expenses.balance'))->assertRedirect(route('login'));
});

test('an expense is saved with who paid, the bill number and who recorded it', function () {
    $this->get(route('expenses.create'))
        ->assertOk()
        ->assertSee('Narayanan')
        ->assertSee('Sathiya')
        ->assertSee('Bill / reference no.');

    $this->post(route('expenses.store'), expenseInput($this->sathiya))
        ->assertRedirect(route('expenses.index', ['range' => 'month']));

    expect(Expense::sole())
        ->amount->toBe(12000)
        ->paid_by->toBe($this->sathiya->id)
        ->reference->toBe('BILL-77')
        ->paid_to->toBe('Ravi Buildings')
        ->recorded_by->toBe($this->narayanan->id)
        ->spent_on->format('Y-m-d')->toBe('2026-09-20');
});

test('save and add another returns to a fresh form', function () {
    $this->post(route('expenses.store'), expenseInput($this->narayanan, ['add_another' => '1']))
        ->assertRedirect(route('expenses.create'));
});

test('invalid expenses are rejected', function (array $overrides, string $field) {
    $this->post(route('expenses.store'), expenseInput($this->narayanan, $overrides))
        ->assertSessionHasErrors($field);

    expect(Expense::count())->toBe(0);
})->with([
    'future date' => [['spent_on' => '2026-09-25'], 'spent_on'],
    'zero amount' => [['amount' => '0'], 'amount'],
    'no description' => [['description' => ''], 'description'],
    'unknown method' => [['method' => 'barter'], 'method'],
]);

test('only a partner can be the one who paid', function () {
    $staff = User::factory()->create(['name' => 'Office Staff', 'is_partner' => false]);

    $this->post(route('expenses.store'), expenseInput($staff))->assertSessionHasErrors('paid_by');

    $this->get(route('expenses.create'))->assertDontSee('Office Staff');
});

test('the expense list opens on this month with totals by partner', function () {
    Expense::factory()->create(['paid_by' => $this->narayanan->id, 'amount' => 12000, 'spent_on' => '2026-09-05']);
    Expense::factory()->create(['paid_by' => $this->sathiya->id, 'amount' => 800, 'spent_on' => '2026-09-18']);
    Expense::factory()->create(['paid_by' => $this->sathiya->id, 'amount' => 5000, 'spent_on' => '2026-08-05']);

    $this->get(route('expenses.index'))
        ->assertOk()
        ->assertViewHas('expenses', fn ($expenses) => $expenses->pluck('amount')->all() === [800, 12000])
        ->assertViewHas('summary', fn ($summary) => $summary['total'] === 12800
            && $summary['by_partner']->pluck('amount', 'name')->all() === ['Narayanan' => 12000, 'Sathiya' => 800]);

    $amounts = fn (array $query) => $this->get(route('expenses.index', $query))->viewData('expenses')->pluck('amount')->sort()->values()->all();

    expect($amounts(['range' => 'last_month']))->toBe([5000])
        ->and($amounts(['range' => 'all']))->toBe([800, 5000, 12000])
        ->and($amounts(['range' => 'all', 'partner' => $this->sathiya->id]))->toBe([800, 5000]);
});

test('expenses can be searched by what for, paid to and bill number', function () {
    Expense::factory()->create(['paid_by' => $this->narayanan->id, 'description' => 'Printer ink', 'paid_to' => 'Sri Stationers', 'reference' => 'INV-501']);
    Expense::factory()->create(['paid_by' => $this->narayanan->id, 'description' => 'Auto fare', 'paid_to' => null, 'reference' => null]);

    foreach (['ink', 'stationers', 'INV-501'] as $search) {
        $this->get(route('expenses.index', ['q' => $search]))
            ->assertViewHas('expenses', fn ($expenses) => $expenses->pluck('description')->all() === ['Printer ink']);
    }
});

test('an expense can be edited and deleted', function () {
    $expense = Expense::factory()->create(['paid_by' => $this->narayanan->id, 'amount' => 500, 'spent_on' => '2026-09-10']);

    $this->get(route('expenses.edit', $expense))->assertOk()->assertSee('Delete this expense');

    $this->put(route('expenses.update', $expense), expenseInput($this->sathiya, ['amount' => '750', 'spent_on' => '2026-09-10']))
        ->assertRedirect(route('expenses.index', ['from' => '2026-09-10', 'to' => '2026-09-10']));

    expect($expense->refresh())->amount->toBe(750)->paid_by->toBe($this->sathiya->id);

    $this->delete(route('expenses.destroy', $expense))->assertRedirect(route('expenses.index'));

    expect(Expense::count())->toBe(0);
});

test('the expense list prints and downloads as a pdf', function () {
    Expense::factory()->create(['paid_by' => $this->narayanan->id, 'amount' => 12000, 'description' => 'September rent', 'reference' => 'BILL-9', 'spent_on' => '2026-09-05']);

    $this->get(route('expenses.print', ['range' => 'month']))
        ->assertOk()
        ->assertSeeTextInOrder(['01 Sep 2026 – 24 Sep 2026', 'Total spent', '₹12,000', 'September rent', 'BILL-9', 'Narayanan', 'Total (1)'])
        ->assertSee('window.print()', false);

    $response = $this->get(route('expenses.pdf', ['range' => 'month']))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))->toContain('Expenses-2026-09-01-to-2026-09-24.pdf');
});

test('the balance sheet shares spending equally and says who pays whom', function () {
    Expense::factory()->create(['paid_by' => $this->narayanan->id, 'amount' => 3000, 'spent_on' => '2026-09-01']);
    Expense::factory()->create(['paid_by' => $this->sathiya->id, 'amount' => 1000, 'spent_on' => '2026-08-01']);

    $response = $this->get(route('expenses.balance'))->assertOk();

    $balances = $response->viewData('balances')->keyBy(fn ($row) => $row['partner']->name);

    expect($response->viewData('total'))->toBe(4000)
        ->and($response->viewData('share'))->toEqual(2000)
        ->and($balances['Narayanan']['balance'])->toEqual(1000)
        ->and($balances['Sathiya']['balance'])->toEqual(-1000)
        ->and($response->viewData('transfers'))->toHaveCount(1)
        ->and($response->viewData('transfers')[0]['from']->is($this->sathiya))->toBeTrue()
        ->and($response->viewData('transfers')[0]['amount'])->toEqual(1000);

    $response->assertSeeTextInOrder(['Sathiya', 'pays', 'Narayanan', '₹1,000', 'Record this payment']);
});

test('recording a settlement squares the partners up', function () {
    Expense::factory()->create(['paid_by' => $this->narayanan->id, 'amount' => 3000]);
    Expense::factory()->create(['paid_by' => $this->sathiya->id, 'amount' => 1000]);

    $this->get(route('expenses.balance', ['settle_from' => $this->sathiya->id, 'settle_to' => $this->narayanan->id, 'settle_amount' => 1000]))
        ->assertSee('value="1,000"', false);

    $this->post(route('expenses.settlements.store'), [
        'settled_on' => '2026-09-24',
        'from_user_id' => $this->sathiya->id,
        'to_user_id' => $this->narayanan->id,
        'amount' => '1,000',
        'method' => 'cash',
    ])->assertRedirect(route('expenses.balance'));

    $response = $this->get(route('expenses.balance'));

    expect($response->viewData('transfers'))->toBe([])
        ->and($response->viewData('balances')->pluck('balance')->all())->toEqual([0, 0])
        ->and($response->viewData('balances')->pluck('net_put_in')->all())->toBe([2000, 2000]);

    $response->assertSee('All square');

    $this->delete(route('expenses.settlements.destroy', PartnerSettlement::sole()))->assertRedirect(route('expenses.balance'));

    expect($this->get(route('expenses.balance'))->viewData('transfers'))->toHaveCount(1);
});

test('a settlement needs two different partners', function () {
    $this->post(route('expenses.settlements.store'), [
        'settled_on' => '2026-09-24',
        'from_user_id' => $this->sathiya->id,
        'to_user_id' => $this->sathiya->id,
        'amount' => '500',
        'method' => 'cash',
    ])->assertSessionHasErrors('to_user_id');
});

test('an odd total is split to the paisa', function () {
    Expense::factory()->create(['paid_by' => $this->narayanan->id, 'amount' => 1001]);

    $response = $this->get(route('expenses.balance'));

    expect($response->viewData('share'))->toEqual(500.5)
        ->and($response->viewData('transfers')[0]['amount'])->toEqual(500.5);

    $response->assertSee('₹500.50');
});

test('staff logins who are not partners do not share the spending', function () {
    User::factory()->create(['name' => 'Office Staff', 'is_partner' => false]);
    Expense::factory()->create(['paid_by' => $this->narayanan->id, 'amount' => 4000]);

    expect($this->get(route('expenses.balance'))->viewData('share'))->toEqual(2000);
});

test('the balance sheet prints and downloads as a pdf', function () {
    Expense::factory()->create(['paid_by' => $this->narayanan->id, 'amount' => 3000]);

    $this->get(route('expenses.balance.print'))
        ->assertOk()
        ->assertSeeTextInOrder(['All time', '₹3,000', 'Narayanan', '₹1,500 to receive', 'Sathiya', '₹1,500 to pay', 'Who pays whom', 'Sathiya pays Narayanan']);

    $response = $this->get(route('expenses.balance.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))->toContain('Balance-Sheet-all-time.pdf');
});

test('the management menu has the expenses section', function () {
    $this->get(route('dashboard'))
        ->assertSee('id="expensesSubmenu"', false)
        ->assertSee(route('expenses.create'))
        ->assertSee(route('expenses.index'))
        ->assertSee(route('expenses.balance'));
});

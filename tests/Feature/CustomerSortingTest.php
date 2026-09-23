<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    Customer::factory()->create(['customer_code' => 'SN9999', 'name' => 'Bala', 'is_active' => true]);
    Customer::factory()->create(['customer_code' => 'SN10000', 'name' => 'Arun', 'is_active' => false]);
    Customer::factory()->create(['customer_code' => 'SN2601', 'name' => 'Chitra', 'is_active' => true]);
});

test('customers are sorted by the chosen option', function (?string $sort, array $expectedOrder) {
    $this->get(route('customers.index', array_filter(['sort' => $sort])))
        ->assertOk()
        ->assertSeeInOrder($expectedOrder);
})->with([
    'default is newest first' => [null, ['Chitra', 'Arun', 'Bala']],
    'oldest first' => ['oldest', ['Bala', 'Arun', 'Chitra']],
    'name A to Z' => ['name_asc', ['Arun', 'Bala', 'Chitra']],
    'name Z to A' => ['name_desc', ['Chitra', 'Bala', 'Arun']],
    'customer id ascending' => ['code_asc', ['SN2601', 'SN9999', 'SN10000']],
    'customer id descending' => ['code_desc', ['SN10000', 'SN9999', 'SN2601']],
    'active first' => ['active_first', ['Bala', 'Chitra', 'Arun']],
    'inactive first' => ['inactive_first', ['Arun', 'Bala', 'Chitra']],
    'unknown value falls back to newest' => ['drop table', ['Chitra', 'Arun', 'Bala']],
]);

test('sorting works together with search', function () {
    Customer::factory()->create(['name' => 'Kumar B']);
    Customer::factory()->create(['name' => 'Kumar A']);

    $this->get(route('customers.index', ['search' => 'Kumar', 'sort' => 'name_asc']))
        ->assertOk()
        ->assertSeeInOrder(['Kumar A', 'Kumar B'])
        ->assertDontSee('Chitra');
});

test('the chosen sort is kept in the pagination links', function () {
    Customer::factory()->count(10)->create();

    $this->get(route('customers.index', ['sort' => 'name_asc']))
        ->assertOk()
        ->assertSee('sort=name_asc&amp;page=2', false);
});

test('the dropdown shows the current sort and headings toggle direction', function () {
    $response = $this->get(route('customers.index', ['sort' => 'name_asc']))
        ->assertOk()
        ->assertSee('aria-sort="ascending"', false)
        ->assertSee('data-sort="name_desc"', false);

    expect($response->getContent())->toMatch('/value="name_asc"[^>]*selected/');
});

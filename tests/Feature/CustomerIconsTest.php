<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['name' => 'Narayanan']));
});

test('each customer row shows a 3D initial thumbnail', function () {
    $customer = Customer::factory()->create(['name' => 'kumar raja']);

    $tone = ['orange', 'blue', 'purple'][$customer->id % 3];

    $this->get(route('customers.index'))
        ->assertOk()
        ->assertSee('customer-avatar avatar-3d icon-3d icon-3d-'.$tone, false)
        ->assertSeeInOrder(['customer-avatar', '>K</span>'], false);
});

test('the header shows the admin initial as a 3D avatar', function () {
    $this->get(route('customers.index'))
        ->assertOk()
        ->assertSee('admin-avatar icon-3d icon-3d-orange', false)
        ->assertSee('class="svg-icon theme-sun"', false);
});

test('pages render svg icons instead of text symbols', function (string $routeName) {
    $response = $this->get(route($routeName))->assertOk();

    foreach (['♙', '⚿', '⌂', '▤', '◆', '☎', '✉', '◉'] as $symbol) {
        $response->assertDontSee($symbol, false);
    }

    $response->assertSee('<svg', false);
})->with(['customers.index', 'customers.create', 'password.edit']);

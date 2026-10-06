<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Lakshmi (SN2601, "Teacher") uses 98765 43210 with her own password.
 */
beforeEach(function () {
    $this->actingAs(User::factory()->create(), 'web');

    $this->lakshmi = Customer::factory()->create(['customer_code' => 'SN2601', 'name' => 'Lakshmi', 'phone' => '+91 98765 43210', 'remarks' => 'Teacher']);
    $this->lakshmi->choosePassword('lakshmi123');
});

test('the same name with the same phone number cannot get a second customer ID', function () {
    $this->post(route('customers.store'), ['name' => '  lakshmi ', 'phone' => '9876543210'])
        ->assertSessionHasErrors(['phone' => 'Lakshmi (SN2601 · Teacher) already has this phone number. One person keeps one customer ID — use SN2601.']);

    expect(Customer::count())->toBe(1);
});

test('new customer IDs start with SL and carry on from the highest number', function () {
    Customer::factory()->create(['customer_code' => 'SN2605']);

    $this->post(route('customers.store'), ['name' => 'Meena', 'phone' => '9000000077'])->assertRedirect(route('customers.create'));
    $this->post(route('customers.store'), ['name' => 'Ravi', 'phone' => '9000000078'])->assertRedirect(route('customers.create'));

    expect(Customer::where('name', 'Meena')->value('customer_code'))->toBe('SL2606')
        ->and(Customer::where('name', 'Ravi')->value('customer_code'))->toBe('SL2607');

    $this->get(route('customers.index', ['sort' => 'code_asc']))
        ->assertSeeInOrder(['SN2601', 'SN2605', 'SL2606', 'SL2607']);
});

test('a different name can share the phone number and takes its password', function () {
    $this->post(route('customers.store'), ['name' => 'Meena', 'phone' => '98765-43210'])
        ->assertRedirect(route('customers.create'))
        ->assertSessionHas('success', fn (string $message) => str_contains($message, 'also used by Lakshmi (SN2601)'));

    $meena = Customer::where('name', 'Meena')->sole();

    expect($meena->passwordMatches('lakshmi123'))->toBeTrue()
        ->and($meena->mustChangePassword())->toBeFalse();
});

test('editing cannot turn a customer into a duplicate, but an older duplicate can still be edited', function () {
    $kumar = Customer::factory()->create(['name' => 'Kumar', 'phone' => '9000000002']);

    $this->putJson(route('customers.update', $kumar), ['name' => 'Lakshmi', 'phone' => '9876543210', 'is_active' => true])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('phone');

    /* saved before the rule: same name and phone, only the address changes */
    $old = Customer::factory()->create(['name' => 'Lakshmi', 'phone' => '9876543210']);

    $this->putJson(route('customers.update', $old), ['name' => 'Lakshmi', 'phone' => '9876543210', 'address' => 'Main Road', 'is_active' => true])
        ->assertOk();
});

test('Add Customer warns who already uses a phone number', function () {
    $this->getJson(route('customers.phone-check', ['phone' => '98765 43210', 'name' => 'Meena']))
        ->assertOk()
        ->assertJsonPath('customers.0.code', 'SN2601')
        ->assertJsonPath('customers.0.remarks', 'Teacher')
        ->assertJsonPath('customers.0.same_name', false);

    $this->getJson(route('customers.phone-check', ['phone' => '9876543210', 'name' => 'LAKSHMI']))
        ->assertJsonPath('customers.0.same_name', true);

    $this->getJson(route('customers.phone-check', ['phone' => '9000000009']))->assertJsonCount(0, 'customers');

    $this->get(route('customers.create'))->assertOk()->assertSee('id="phoneWarning"', false);
});

<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the login page renders for guests', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Username')
        ->assertSee('Password');
});

test('guests are redirected to login from admin pages', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with(['customers.index', 'customers.create']);

test('guests cannot create customers', function () {
    $this->post(route('customers.store'), ['name' => 'Kumar', 'phone' => '9876543210'])
        ->assertRedirect(route('login'));

    $this->assertDatabaseCount('customers', 0);
});

test('an admin can sign in with username and password', function () {
    $admin = User::factory()->create(['username' => 'sathiya']);

    $this->post(route('login.store'), [
        'username' => 'sathiya',
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($admin);
});

test('an admin is sent back to the page they originally requested', function () {
    User::factory()->create(['username' => 'sathiya']);

    $this->get(route('customers.create'));

    $this->post(route('login.store'), [
        'username' => 'sathiya',
        'password' => 'password',
    ])->assertRedirect(route('customers.create'));
});

test('a wrong password is rejected', function () {
    User::factory()->create(['username' => 'sathiya']);

    $this->from(route('login'))
        ->post(route('login.store'), [
            'username' => 'sathiya',
            'password' => 'wrong-password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('login is locked after five failed attempts', function () {
    User::factory()->create(['username' => 'sathiya']);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['username' => 'sathiya', 'password' => 'wrong']);
    }

    $this->post(route('login.store'), ['username' => 'sathiya', 'password' => 'password'])
        ->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('signed-in admins are redirected away from the login page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('login'))
        ->assertRedirect();
});

test('signed-in admins see their name in the header', function () {
    $this->actingAs(User::factory()->create(['name' => 'Narayanan']))
        ->get(route('customers.index'))
        ->assertOk()
        ->assertSee('Narayanan');
});

test('the header name menu links to change password and logout', function () {
    $this->actingAs(User::factory()->create(['name' => 'Narayanan']))
        ->get(route('customers.index'))
        ->assertOk()
        ->assertSee('id="adminMenu"', false)
        ->assertSee(route('password.edit'))
        ->assertSee('action="'.route('logout').'"', false);
});

test('an admin can log out', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('the admin seeder creates Narayanan and Sathiya once', function () {
    $this->seed(AdminUserSeeder::class);
    $this->seed(AdminUserSeeder::class);

    expect(User::pluck('username')->sort()->values()->all())
        ->toBe(['narayanan', 'sathiya']);
});

<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('guests cannot open the change password page', function () {
    $this->get(route('password.edit'))->assertRedirect(route('login'));
});

test('an admin can open the change password page', function () {
    $this->actingAs(User::factory()->create(['username' => 'sathiya']))
        ->get(route('password.edit'))
        ->assertOk()
        ->assertSee('Change Password')
        ->assertSee('sathiya');
});

test('an admin can change their password and sign in with it', function () {
    $admin = User::factory()->create(['username' => 'sathiya']);

    $this->actingAs($admin)
        ->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'NewSecret123',
            'password_confirmation' => 'NewSecret123',
        ])
        ->assertRedirect(route('password.edit'))
        ->assertSessionHas('success');

    expect(Hash::check('NewSecret123', $admin->fresh()->password))->toBeTrue();

    $this->post(route('logout'));

    $this->post(route('login.store'), ['username' => 'sathiya', 'password' => 'NewSecret123'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($admin);
});

test('the password is not changed when the input is invalid', function (array $input, string $errorField) {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->from(route('password.edit'))
        ->put(route('password.update'), $input)
        ->assertRedirect(route('password.edit'))
        ->assertSessionHasErrors($errorField);

    expect(Hash::check('password', $admin->fresh()->password))->toBeTrue();
})->with([
    'wrong current password' => [
        ['current_password' => 'not-my-password', 'password' => 'NewSecret123', 'password_confirmation' => 'NewSecret123'],
        'current_password',
    ],
    'confirmation does not match' => [
        ['current_password' => 'password', 'password' => 'NewSecret123', 'password_confirmation' => 'Different123'],
        'password',
    ],
    'too short' => [
        ['current_password' => 'password', 'password' => 'short', 'password_confirmation' => 'short'],
        'password',
    ],
    'same as current password' => [
        ['current_password' => 'password', 'password' => 'password', 'password_confirmation' => 'password'],
        'password',
    ],
]);

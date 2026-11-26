<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->sathiya = User::factory()->create(['name' => 'Sathiya', 'username' => 'sathiya']);
    $this->dinesh = User::factory()->agent()->create(['name' => 'Dinesh', 'username' => 'dinesh', 'password' => 'old-password-1']);
});

test('the owner sets a new password for an agent from the name menu', function () {
    $this->actingAs($this->sathiya, 'web');

    $this->get(route('finance.dashboard'))->assertSee(route('password.staff'), false);
    $this->get(route('password.staff'))->assertOk()->assertSeeText('Dinesh (dinesh)')->assertDontSeeText('Sathiya (sathiya)');

    $this->put(route('password.staff.update'), ['user_id' => $this->dinesh->id, 'password' => 'new-password-1', 'password_confirmation' => 'other'])
        ->assertSessionHasErrors('password');
    $this->put(route('password.staff.update'), ['user_id' => $this->sathiya->id, 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1'])
        ->assertSessionHasErrors('user_id');

    $this->put(route('password.staff.update'), ['user_id' => $this->dinesh->id, 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1'])
        ->assertRedirect(route('password.staff'));

    expect(Hash::check('new-password-1', $this->dinesh->refresh()->password))->toBeTrue();

    auth('web')->logout();
    $this->post(route('login.store'), ['username' => 'dinesh', 'password' => 'new-password-1'])->assertRedirect();
    $this->assertAuthenticatedAs($this->dinesh, 'web');
});

test('an agent cannot change other staff passwords', function () {
    $this->actingAs($this->dinesh, 'web');

    $this->get(route('finance.dashboard'))->assertDontSee(route('password.staff'), false);
    $this->get(route('password.staff'))->assertForbidden();
    $this->put(route('password.staff.update'), ['user_id' => $this->sathiya->id, 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1'])->assertForbidden();
});

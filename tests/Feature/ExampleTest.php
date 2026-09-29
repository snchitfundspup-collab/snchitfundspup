<?php

test('the home page is the customer login', function () {
    $this->get('/')
        ->assertOk()
        ->assertSeeText('Phone number');
});

test('staff pages send guests to the staff login', function () {
    $this->get('/admin')->assertRedirect(route('login'));
});

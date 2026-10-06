<?php

test('the home page is the customer login', function () {
    $this->get('/')
        ->assertOk()
        ->assertSeeText('Phone number');
});

test('staff go to the Micro Finance dashboard; guests are sent to the staff login', function () {
    $this->get('/admin')->assertRedirect('/finance');
    $this->get('/finance')->assertRedirect(route('login'));
});

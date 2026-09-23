<?php

test('the home page sends guests to the login page', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});

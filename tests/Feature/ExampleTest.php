<?php

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertSee('Fast, reliable device repair')
        ->assertSee('Join Walk-In Queue')
        ->assertSee('Cash payment')
        ->assertSee('Log in')
        ->assertSee('Register')
        ->assertSee(route('login'), escape: false)
        ->assertSee(route('register'), escape: false);
});

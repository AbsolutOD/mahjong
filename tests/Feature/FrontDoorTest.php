<?php

test('the front door opens onto the decoder, because that is the whole product', function () {
    $this->get(route('home'))->assertRedirect(route('card'));
});

test('the public shell offers no way in, because there is nothing to sign in to', function () {
    $this->get(route('card'))
        ->assertOk()
        ->assertDontSee(route('login'))
        ->assertDontSee(route('password.request'));
});

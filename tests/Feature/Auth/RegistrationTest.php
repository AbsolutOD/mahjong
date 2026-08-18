<?php

use App\Models\User;
use Laravel\Fortify\Features;

/**
 * Registration is closed, so these assert the door is shut rather than that it
 * opens. TileTutor v1 is anonymous, and nothing in production can send the mail
 * that signing up, verifying an address or accepting an invitation depends on.
 *
 * It is closed in config/fortify.php rather than behind an environment flag, so
 * what the public site runs is what these tests run.
 */
test('registration is not one of the features the application offers', function () {
    expect(Features::enabled(Features::registration()))->toBeFalse();
});

test('there is no registration screen to reach', function () {
    $this->get('/register')->assertNotFound();
});

test('an account cannot be made by posting to where registration used to be', function () {
    $this->post('/register', [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    expect(User::query()->count())->toBe(0);
});

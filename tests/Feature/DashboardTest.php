<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
});

/** Why the dashboard borrows the front door's ways in is told in dashboard.blade.php. */
test('the dashboard leads into every phase', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk()
        ->assertSee(route('card'))
        ->assertSee(route('matcher'))
        ->assertSee(route('practice'));

    expect(substr_count($response->getContent(), 'Open it'))->toBe(3);
});

/** The signed-in shell offers the same three phases as the front door's navbar. */
test('the app shell links every phase and none of the starter kit', function (string $layout) {
    $user = User::factory()->create();
    $this->actingAs($user);
    $this->get(route('dashboard'));

    $view = $this->blade("<x-layouts::app.{$layout}>body</x-layouts::app.{$layout}>");

    $view->assertSee(route('card'))
        ->assertSee(route('matcher'))
        ->assertSee(route('practice'))
        ->assertDontSee('livewire-starter-kit')
        ->assertDontSee('docs/starter-kits');
})->with(['sidebar', 'header']);

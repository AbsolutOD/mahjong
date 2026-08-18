<?php

use App\Data\Tiles\Tile;
use App\Data\Tiles\TileFace;
use App\Enums\FaceTone;

test('the reference page shows every face in the set', function () {
    $response = $this->get(route('tiles'));

    $response->assertOk();

    foreach (Tile::all() as $tile) {
        $response->assertSee(TileFace::of($tile)->name);
    }
});

test('the card slots start unbound, so the page opens grey', function () {
    $response = $this->get(route('tiles'));

    $response->assertOk()
        ->assertSee('X in suit A')
        ->assertSee(FaceTone::Unbound->ink());
});

test('assigning the suits binds the card slots to real tiles', function () {
    $response = $this->get(route('tiles', ['assign' => 1]));

    $response->assertOk()
        ->assertSee('X in Dots')
        ->assertSee('7 Dots')
        ->assertDontSee('X in suit A');
});

/**
 * The reference is a workbench for drawing tiles, not a page for players — and
 * `optimize` caches routes, so it has to stay in the route table to be cached.
 * Production hides it at the door instead.
 */
test('the reference is not served on the site the public reaches', function () {
    app()->detectEnvironment(fn (): string => 'production');

    $this->get(route('tiles'))->assertNotFound();
});

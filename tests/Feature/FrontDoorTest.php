<?php

use App\Data\Tiles\Tile;
use App\Data\Tiles\TileFace;
use App\Models\Card;

/**
 * The front door — the landing page at `/` and the shell around it (issue #32).
 *
 * Anonymous v1 has no dashboard behind a login, so `/` is the whole front door:
 * it is the only place the product gets to say what it is.
 */
/**
 * A sentinel name rather than `config('app.name')`, which would compare the page
 * against the very value it renders and pass whatever that value was. This bites
 * only if nothing in the document — title, chrome or copy — reads the name from
 * config; the hero alone is not covered.
 */
test('the front door is a page, and takes the product name from config', function () {
    config()->set('app.name', 'Front Door Fixture');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Front Door Fixture');
});

test('the front door leads into the decoder', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('card'));
});

/**
 * The decoder is one of three planned phases, and the other two have nowhere
 * else to be announced — anonymous v1 has no dashboard to announce them from.
 */
test('the front door names the phases still to come, and says they are not built', function () {
    $response = $this->get(route('home'))
        ->assertOk()
        ->assertSee('Hand Matcher')
        ->assertSee('Practice & Quiz')
        ->assertSee('Not built yet');

    /** Announced, but with nowhere to go: only the built phase offers a way in. */
    expect(substr_count($response->getContent(), 'Open it'))->toBe(1);
});

/**
 * The guard #32 was written around. Caching *"there is no card"* left the site
 * blank once already (#30), and a front door that goes dark when the card is
 * missing is that defect in a new costume — so the tiles here are fixed ones,
 * and nothing on this page may come from the database.
 */
test('the front door stands up with no card seeded', function () {
    expect(Card::count())->toBe(0);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee(TileFace::of(Tile::joker())->name)
        ->assertSee('Open the Line Decoder');
});

/**
 * The header carries live destinations only. Two permanently unclickable items
 * would read as broken, so the phases still to come are announced on the
 * landing page instead and the header grows a link the day one ships.
 */
test('the header carries only the phases that exist', function () {
    $this->get(route('card'))
        ->assertOk()
        ->assertSee('Line Decoder')
        ->assertDontSee('Hand Matcher')
        ->assertDontSee('Practice & Quiz');
});

test('the header marks the page you are on, and only that page', function () {
    $this->get(route('card'))
        ->assertOk()
        ->assertSee('aria-current="page"', escape: false);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('aria-current="page"', escape: false);
});

test('the public shell offers no way in, because there is nothing to sign in to', function (string $route) {
    $this->get(route($route))
        ->assertOk()
        ->assertDontSee(route('login'))
        ->assertDontSee(route('password.request'));
})->with(['home', 'card']);

<?php

use App\Data\Tiles\Tile;
use App\Data\Tiles\TileFace;
use App\Mahjong\Slug;
use App\Models\Card;
use App\Models\User;

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

test('the front door leads into the phases that are built', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('card'))
        ->assertSee(route('matcher'))
        ->assertSee(route('practice'));
});

/**
 * The phases still to come had nowhere else to be announced — anonymous v1 has
 * no dashboard to announce them from — and each turned from an announcement
 * into a way in as it shipped. With the practice suite built (issue #18), none
 * is left to announce.
 */
test('the front door offers a way into every phase, and announces none as unbuilt', function () {
    $response = $this->get(route('home'))
        ->assertOk()
        ->assertSee('Hand Matcher')
        ->assertSee('Practice & Quiz')
        ->assertDontSee('Not built yet');

    expect(substr_count($response->getContent(), 'Open it'))->toBe(3);
});

/**
 * The footer is the page's one claim about the card's size, and it cannot read
 * the card to check itself — nothing on this page may come from the database.
 */
test('the front door counts the practice card\'s hands correctly', function () {
    $card = json_decode(file_get_contents(database_path('cards/practice-card.json')), associative: true);

    $hands = array_sum(array_map(fn (array $section): int => count($section['hands']), $card['categories']));

    $this->get(route('home'))
        ->assertOk()
        ->assertSee("{$hands} original hands");
});

/**
 * The hero's sample line is fixed, like everything else here, so it is held to
 * the practice card the way the footer's count is: by replaying the import's
 * slugs over the card file. Editing that hand off the card fails this, rather
 * than leaving the front door linking to a line the decoder no longer has.
 */
test('the front door\'s sample line is on the practice card, and opens it in the decoder', function () {
    $card = json_decode(file_get_contents(database_path('cards/practice-card.json')), associative: true);
    $slugs = new Slug;
    $link = null;

    foreach ($card['categories'] as $section) {
        $category = $slugs->forCategory($section['name']);

        foreach ($section['hands'] as $hand) {
            $hand['slug'] = $slugs->forLine($hand['line'], $hand['concealed']);

            if (preg_replace('/\([A-Z]\)/', '', $hand['line']) === 'FFFF 2026 222 666') {
                $link = route('card', ['category' => $category, 'hand' => $hand['slug']]);
            }
        }
    }

    expect($link)->not->toBeNull();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('FFFF 2026 222 666')
        ->assertSee($link);
});

/**
 * Phase 2 shipped in two halves. The blurb lost its Charleston clause while only
 * the matcher existed, and regains it now the assistant is built (issue #17), so
 * no half of a built phase is promised before it exists — or left unannounced.
 */
test('the front door promises the whole of the hand matcher, Charleston included', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('which to pass in the Charleston');
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
 * The header carries live destinations only, growing a link the day a phase
 * ships; all three now have.
 */
test('the header carries every phase that exists', function () {
    $this->get(route('card'))
        ->assertOk()
        ->assertSee(route('card'))
        ->assertSee(route('matcher'))
        ->assertSee(route('practice'));
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
})->with(['home', 'card', 'matcher', 'practice']);

/**
 * The phases render in the public shell for everyone, so a signed-in learner
 * who opens one needs the header to lead back to their account (issues #51, #54).
 */
test('the public shell leads a signed-in user back to their account', function (string $route) {
    $this->actingAs(User::factory()->create());

    $this->get(route($route))
        ->assertOk()
        ->assertSee(route('dashboard'))
        ->assertSee(route('profile.edit'))
        ->assertSee(route('logout'));
})->with(['home', 'card', 'matcher', 'practice']);

test('the public shell shows a guest no account', function (string $route) {
    $this->get(route($route))
        ->assertOk()
        ->assertDontSee(route('logout'))
        ->assertDontSee(route('profile.edit'));
})->with(['home', 'card', 'matcher', 'practice']);

<?php

use App\Data\HandStructure;
use App\Data\Tiles\Tile;
use App\Enums\Suit;
use App\Models\Card;
use App\Models\Category;
use App\Models\Hand;
use Database\Seeders\CardSeeder;
use Livewire\Livewire;

/**
 * Build a card with two lines a rack can be measured against.
 *
 * The near line asks for a different number in each of its two suits, so a rack
 * of one suit has exactly one best binding rather than a tie; the far line is
 * two suits of sevens, which nothing in these tests ever holds.
 */
function matcherCard(): Card
{
    $card = Card::factory()->create(['name' => 'Practice Card']);
    $category = Category::factory()->for($card)->create(['name' => 'Flowers', 'sort_order' => 1]);

    Hand::factory()->for($card)->for($category)->create([
        'slug' => 'ffff-5555a-222222b',
        'sort_order' => 1,
        'points' => 25,
        'concealed' => false,
        'structure' => HandStructure::fromArray([
            'variables' => ['A' => ['kind' => 'suit'], 'B' => ['kind' => 'suit']],
            'groups' => [
                array_fill(0, 4, ['t' => 'flower']),
                array_fill(0, 4, ['t' => 'num', 'suit' => 'A', 'n' => 5]),
                array_fill(0, 6, ['t' => 'num', 'suit' => 'B', 'n' => 2]),
            ],
        ]),
    ]);

    Hand::factory()->for($card)->for($category)->create([
        'slug' => 'seven-sevens',
        'sort_order' => 2,
        'points' => 50,
        'concealed' => true,
        'structure' => HandStructure::fromArray([
            'variables' => ['A' => ['kind' => 'suit'], 'B' => ['kind' => 'suit']],
            'groups' => [
                array_fill(0, 7, ['t' => 'num', 'suit' => 'A', 'n' => 7]),
                array_fill(0, 7, ['t' => 'num', 'suit' => 'B', 'n' => 7]),
            ],
        ]),
    ]);

    return $card;
}

test('the matcher is public, because learning the card needs no account', function () {
    matcherCard();

    $this->get(route('matcher'))->assertOk();
});

test('an empty rack still ranks the whole card, a hand away from everything', function () {
    matcherCard();

    Livewire::test('pages::matcher.hand-matcher')
        ->assertSee('14 away')
        ->assertSee('FFFF 5555(A) 222222(B)')
        ->assertSee('7777777(A) 7777777(B)');
});

test('tapping a tile racks it, and the rack lives in the url', function () {
    matcherCard();

    Livewire::test('pages::matcher.hand-matcher')
        ->call('addTile', 'flower')
        ->call('addTile', 'flower')
        ->assertSet('rackCodes', 'flower,flower')
        ->assertSee('12 away');
});

test('tapping a racked tile takes one copy back off', function () {
    matcherCard();

    Livewire::test('pages::matcher.hand-matcher')
        ->call('addTile', 'flower')
        ->call('addTile', 'flower')
        ->call('removeTile', 'flower')
        ->assertSet('rackCodes', 'flower');
});

test('the rack sweeps clean', function () {
    matcherCard();

    Livewire::test('pages::matcher.hand-matcher')
        ->call('addTile', 'joker')
        ->call('clearRack')
        ->assertSet('rackCodes', '');
});

test('a rack read from the url settles on the tiles it can actually hold', function () {
    matcherCard();

    Livewire::withUrlParams(['rack' => 'dots-2,not-a-tile,dots-2,dots-2,dots-2,dots-2'])
        ->test('pages::matcher.hand-matcher')
        ->assertSet('rackCodes', 'dots-2,dots-2,dots-2,dots-2');
});

test('the page will not rack a tile the game could not supply', function () {
    matcherCard();

    Livewire::test('pages::matcher.hand-matcher')
        ->call('addTile', 'dots-2')
        ->call('addTile', 'dots-2')
        ->call('addTile', 'dots-2')
        ->call('addTile', 'dots-2')
        ->call('addTile', 'dots-2')
        ->call('addTile', 'not-a-tile')
        ->assertSet('rackCodes', 'dots-2,dots-2,dots-2,dots-2');
});

test('racking tiles moves the closest line to the top', function () {
    matcherCard();

    $component = Livewire::test('pages::matcher.hand-matcher');

    foreach (array_fill(0, 4, 'flower') as $code) {
        $component->call('addTile', $code);
    }

    $ranked = $component->instance()->matches;

    expect($ranked[0]->hand->slug)->toBe('ffff-5555a-222222b')
        ->and($ranked[0]->tilesAway())->toBe(10)
        ->and($ranked[1]->tilesAway())->toBe(14);
});

/**
 * The panel follows the best fit until a line is picked, because the thing
 * worth linking to here is the rack rather than any one line.
 */
test('the breakdown follows the top of the ranking until a line is picked', function () {
    matcherCard();

    Livewire::test('pages::matcher.hand-matcher')
        ->assertSet('handSlug', null)
        ->call('selectHand', 'seven-sevens')
        ->assertSet('handSlug', 'seven-sevens')
        ->assertSee('Concealed — no exposures');
});

test('a link to a line the card no longer prints falls back instead of showing a different line', function () {
    matcherCard();

    Livewire::withUrlParams(['hand' => 'a-line-this-card-does-not-print'])
        ->test('pages::matcher.hand-matcher')
        ->assertSee('FFFF 5555(A) 222222(B)');
});

test('the breakdown reads the line out and marks what the rack does about each slot', function () {
    matcherCard();

    $component = Livewire::test('pages::matcher.hand-matcher');

    foreach (['flower', 'flower', 'dots-2', 'joker'] as $code) {
        $component->call('addTile', $code);
    }

    $component
        ->assertSee('10 tiles away')
        ->assertSee('Kong of flowers')
        ->assertSee('You hold this tile')
        ->assertSee('A joker would cover this tile')
        ->assertSee('You are still short of this tile')
        ->assertSee('Still to find');
});

/**
 * The binding is named rather than assumed silently — the same lesson the
 * decoder's "try it in real suits" teaches.
 */
test('the breakdown names the binding it measured the line at', function () {
    matcherCard();

    $component = Livewire::test('pages::matcher.hand-matcher');

    foreach (array_fill(0, 4, 'craks-5') as $code) {
        $component->call('addTile', $code);
    }

    $component->assertSee('A = Craks');
});

test('a rack holding a whole line says so', function () {
    matcherCard();

    $component = Livewire::test('pages::matcher.hand-matcher');

    foreach ([...array_fill(0, 4, 'flower'), ...array_fill(0, 4, 'dots-5'), ...array_fill(0, 4, 'bams-2')] as $code) {
        $component->call('addTile', $code);
    }

    /** Only four twos of bams exist, so the sextet's last two must be jokers. */
    $component
        ->call('addTile', 'joker')
        ->call('addTile', 'joker')
        ->assertSee('You have this hand');
});

test('the matcher says what to do when no card is seeded', function () {
    Livewire::test('pages::matcher.hand-matcher')
        ->assertSee('No card is loaded');
});

/**
 * The performance claim #15 rests on, held to the real card rather than a
 * fixture: exhaustive enumeration over all fifty lines, every time a tile lands.
 */
test('the real card ranks every one of its lines against a rack', function () {
    $this->seed(CardSeeder::class);

    $component = Livewire::test('pages::matcher.hand-matcher');

    foreach (['flower', 'flower', 'dots-2', 'dots-2', 'bams-5', 'joker'] as $code) {
        $component->call('addTile', $code);
    }

    $ranked = $component->instance()->matches;

    expect($ranked)->toHaveCount(50)
        ->and($ranked[0]->tilesAway())->toBeLessThanOrEqual($ranked[49]->tilesAway());

    foreach ($ranked as $match) {
        expect($match->tilesAway())->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(14);
    }
});

test('a tile the set does not have is not on the palette', function () {
    matcherCard();

    Livewire::test('pages::matcher.hand-matcher')
        ->assertSeeHtml("addTile('".Tile::number(Suit::Dots, 9)->code()."')")
        ->assertSeeHtml("addTile('joker')")
        ->assertDontSeeHtml("addTile('zero')");
});

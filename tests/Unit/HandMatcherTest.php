<?php

use App\Data\HandStructure;
use App\Data\Matching\HandMatch;
use App\Data\Matching\MatchedSlot;
use App\Data\Matching\Rack;
use App\Data\Tiles\Tile;
use App\Enums\Suit;
use App\Mahjong\HandMatcher;
use App\Models\Card;
use App\Models\Category;
use App\Models\Hand;

/**
 * A line the matcher can measure, built without touching the database.
 *
 * @param  list<list<array<string, mixed>>>  $groups
 * @param  array<string, array<string, mixed>>  $variables
 */
function matchable(array $groups, array $variables = ['A' => ['kind' => 'suit'], 'B' => ['kind' => 'suit']], int $points = 25): Hand
{
    return new Hand([
        'slug' => 'line-'.count($groups),
        'points' => $points,
        'concealed' => false,
        'structure' => HandStructure::fromArray(['variables' => $variables, 'groups' => $groups]),
    ]);
}

/**
 * A pair and a kong of the same tile, so the pair has no jokers to fall back on.
 *
 * "2222(A)" cannot be drawn in full once "22(A)" has taken two of the four, so
 * this is the line that says where the real tiles have to go.
 */
function pairAndKongHand(int $points = 25): Hand
{
    return matchable([
        [['t' => 'num', 'suit' => 'A', 'n' => 2], ['t' => 'num', 'suit' => 'A', 'n' => 2]],
        array_fill(0, 4, ['t' => 'num', 'suit' => 'A', 'n' => 2]),
        array_fill(0, 4, ['t' => 'num', 'suit' => 'B', 'n' => 5]),
        array_fill(0, 4, ['t' => 'flower']),
    ], points: $points);
}

/**
 * Get the state of every slot of a match, group by group.
 *
 * @return list<list<string>>
 */
function slotStates(HandMatch $match): array
{
    return array_map(
        fn (array $slots): array => array_map(fn (MatchedSlot $slot): string => $slot->state->value, $slots),
        $match->coverage->groups,
    );
}

test('an empty rack is a whole hand away from everything', function () {
    $match = (new HandMatcher)->match(pairAndKongHand(), Rack::empty());

    expect($match->tilesAway())->toBe(14)
        ->and($match->isComplete())->toBeFalse()
        ->and($match->coverage->jokersUsed)->toBe(0)
        ->and($match->coverage->missing())->toHaveCount(14);
});

test('a rack holding the whole line is nothing away from it', function () {
    $hand = matchable([
        array_fill(0, 4, ['t' => 'flower']),
        array_fill(0, 4, ['t' => 'num', 'suit' => 'A', 'n' => 2]),
        array_fill(0, 4, ['t' => 'num', 'suit' => 'A', 'n' => 3]),
        [['t' => 'num', 'suit' => 'A', 'n' => 5], ['t' => 'num', 'suit' => 'A', 'n' => 5]],
    ]);

    $rack = Rack::of([
        ...array_fill(0, 4, Tile::flower()),
        ...array_fill(0, 4, Tile::number(Suit::Bams, 2)),
        ...array_fill(0, 4, Tile::number(Suit::Bams, 3)),
        ...array_fill(0, 2, Tile::number(Suit::Bams, 5)),
    ]);

    $match = (new HandMatcher)->match($hand, $rack);

    expect($match->tilesAway())->toBe(0)
        ->and($match->isComplete())->toBeTrue()
        ->and($match->instantiation->suits->for('A'))->toBe(Suit::Bams)
        ->and($match->coverage->missing())->toBe([]);
});

/**
 * Jokers stand in only inside a grouping of three or more identical tiles, so a
 * pair is never covered by one however many are on the rack (issue #6).
 */
test('jokers fill the groups that take them and no others', function () {
    $match = (new HandMatcher)->match(pairAndKongHand(), Rack::of(array_fill(0, 4, Tile::joker())));

    expect(slotStates($match))->toBe([
        ['missing', 'missing'],
        ['joker', 'joker', 'joker', 'joker'],
        ['missing', 'missing', 'missing', 'missing'],
        ['missing', 'missing', 'missing', 'missing'],
    ])->and($match->tilesAway())->toBe(10);
});

/**
 * The reason coverage is laid out in two passes: spending the two real 2s on
 * the kong, which four jokers could have filled by themselves, would leave the
 * pair empty and the rack two tiles further away for no reason.
 */
test('real tiles go where no joker could, before anywhere a joker could', function () {
    $rack = Rack::of([
        Tile::number(Suit::Dots, 2),
        Tile::number(Suit::Dots, 2),
        ...array_fill(0, 4, Tile::joker()),
    ]);

    $match = (new HandMatcher)->match(pairAndKongHand(), $rack);

    expect($match->instantiation->suits->for('A'))->toBe(Suit::Dots)
        ->and(slotStates($match))->toBe([
            ['held', 'held'],
            ['joker', 'joker', 'joker', 'joker'],
            ['missing', 'missing', 'missing', 'missing'],
            ['missing', 'missing', 'missing', 'missing'],
        ])
        ->and($match->tilesAway())->toBe(8)
        ->and($match->coverage->jokersUsed)->toBe(4);
});

test('a joker with nowhere to go counts for nothing', function () {
    $hand = matchable([
        array_fill(0, 7, ['t' => 'num', 'suit' => 'A', 'n' => ['var' => 'X']]),
        [
            ['t' => 'num', 'suit' => 'B', 'n' => 1], ['t' => 'num', 'suit' => 'B', 'n' => 2],
            ['t' => 'num', 'suit' => 'B', 'n' => 3], ['t' => 'num', 'suit' => 'B', 'n' => 4],
            ['t' => 'num', 'suit' => 'B', 'n' => 5], ['t' => 'num', 'suit' => 'B', 'n' => 6],
            ['t' => 'num', 'suit' => 'B', 'n' => 7],
        ],
    ], ['A' => ['kind' => 'suit'], 'B' => ['kind' => 'suit'], 'X' => ['kind' => 'number']]);

    $match = (new HandMatcher)->match($hand, Rack::of(array_fill(0, 8, Tile::joker())));

    /** Seven slots take jokers; the eighth joker has nowhere legal to sit. */
    expect($match->coverage->jokersUsed)->toBe(7)
        ->and($match->tilesAway())->toBe(7);
});

test('a line is measured at its best binding, and says which one that is', function () {
    $rack = Rack::of([
        ...array_fill(0, 4, Tile::number(Suit::Craks, 2)),
        ...array_fill(0, 3, Tile::number(Suit::Dots, 5)),
    ]);

    $match = (new HandMatcher)->match(pairAndKongHand(), $rack);

    expect($match->instantiation->bindings)->toBe(['A' => 'Craks', 'B' => 'Dots'])
        ->and($match->tilesAway())->toBe(7);
});

test('the tiles still needed are the ones no rack tile and no joker reached', function () {
    $rack = Rack::of([...array_fill(0, 4, Tile::flower()), Tile::number(Suit::Dots, 2)]);

    $match = (new HandMatcher)->match(pairAndKongHand(), $rack);

    $missing = array_map(fn (Tile $tile): string => $tile->code(), $match->coverage->missing());

    expect($missing)->toBe([
        'dots-2',
        'dots-2', 'dots-2', 'dots-2', 'dots-2',
        'bams-5', 'bams-5', 'bams-5', 'bams-5',
    ]);
});

/**
 * Build a card whose lines are in a known printing order.
 *
 * @param  list<Hand>  $hands
 */
function cardOf(array $hands): Card
{
    $card = new Card(['name' => 'Ranking Fixture']);
    $category = new Category(['name' => 'All of it']);

    $category->setRelation('hands', collect($hands));
    $card->setRelation('categories', collect([$category]));

    return $card;
}

test('the closest line comes first, and distance is the only thing that ranks', function () {
    $near = matchable([
        array_fill(0, 4, ['t' => 'flower']),
        array_fill(0, 4, ['t' => 'num', 'suit' => 'A', 'n' => 2]),
        array_fill(0, 6, ['t' => 'num', 'suit' => 'B', 'n' => 5]),
    ], points: 25);

    $far = matchable([
        array_fill(0, 7, ['t' => 'num', 'suit' => 'A', 'n' => 1]),
        array_fill(0, 7, ['t' => 'num', 'suit' => 'B', 'n' => 9]),
    ], points: 50);

    $ranked = (new HandMatcher)->rank(cardOf([$far, $near]), Rack::of(array_fill(0, 4, Tile::flower())));

    expect($ranked[0]->hand)->toBe($near)
        ->and($ranked[0]->tilesAway())->toBe(10)
        ->and($ranked[1]->tilesAway())->toBe(14);
});

test('lines the same distance away break on points, and then on card order', function () {
    $first = pairAndKongHand(points: 25);
    $richer = pairAndKongHand(points: 40);
    $last = pairAndKongHand(points: 25);

    $ranked = (new HandMatcher)->rank(cardOf([$first, $richer, $last]), Rack::empty());

    expect(array_map(fn (HandMatch $match): Hand => $match->hand, $ranked))->toBe([$richer, $first, $last]);
});

test('every line on the card gets a row, so the list never hides one', function () {
    $card = cardOf([pairAndKongHand(), pairAndKongHand(), pairAndKongHand()]);

    expect((new HandMatcher)->rank($card, Rack::of([Tile::joker()])))->toHaveCount(3);
});

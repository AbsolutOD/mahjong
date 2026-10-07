<?php

use App\Data\Charleston\CharlestonAdvice;
use App\Data\Charleston\TileAdvice;
use App\Data\HandStructure;
use App\Data\Matching\Rack;
use App\Data\Tiles\Tile;
use App\Enums\Suit;
use App\Mahjong\CharlestonAdvisor;
use App\Mahjong\HandMatcher;
use App\Models\Card;
use App\Models\Category;
use App\Models\Hand;

/**
 * A line of fourteen built from groups of one repeated spec, off the database.
 *
 * @param  list<array{0: int, 1: array<string, mixed>}>  $groups  each a count and the spec repeated
 */
function charlestonLine(string $slug, array $groups, int $points = 25): Hand
{
    return new Hand([
        'slug' => $slug,
        'points' => $points,
        'concealed' => false,
        'structure' => HandStructure::fromArray([
            'variables' => ['A' => ['kind' => 'suit'], 'B' => ['kind' => 'suit']],
            'groups' => array_map(fn (array $group): array => array_fill(0, $group[0], $group[1]), $groups),
        ]),
    ]);
}

/**
 * The fixture card, one section so lines are named "Fixture 1" to "Fixture 4".
 *
 * Fixture 1 wants one 7 of suit B; Fixtures 2 and 3 want flowers and fives;
 * Fixture 4 wants nothing any rack here ever holds but its own nines.
 */
function charlestonCard(): Card
{
    $hands = [
        charlestonLine('one-seven', [
            [4, ['t' => 'flower']],
            [4, ['t' => 'num', 'suit' => 'A', 'n' => 5]],
            [5, ['t' => 'num', 'suit' => 'A', 'n' => 6]],
            [1, ['t' => 'num', 'suit' => 'B', 'n' => 7]],
        ]),
        charlestonLine('flowers-and-fives', [
            [4, ['t' => 'flower']],
            [4, ['t' => 'num', 'suit' => 'A', 'n' => 5]],
            [6, ['t' => 'num', 'suit' => 'B', 'n' => 2]],
        ]),
        charlestonLine('more-fives', [
            [2, ['t' => 'flower']],
            [4, ['t' => 'num', 'suit' => 'A', 'n' => 5]],
            [4, ['t' => 'num', 'suit' => 'B', 'n' => 5]],
            [4, ['t' => 'num', 'suit' => 'A', 'n' => 3]],
        ]),
        charlestonLine('nines', [
            [7, ['t' => 'num', 'suit' => 'A', 'n' => 9]],
            [7, ['t' => 'num', 'suit' => 'B', 'n' => 9]],
        ]),
    ];

    $card = new Card(['name' => 'Charleston Fixture']);
    $category = new Category(['name' => 'Fixture']);

    $category->setRelation('hands', collect($hands));
    $card->setRelation('categories', collect([$category]));

    return $card;
}

/**
 * Rank the fixture card against the given tile codes and advise on the rack.
 *
 * @param  list<string>  $codes
 * @param  list<string>  $pins
 */
function adviseOn(array $codes, array $pins = []): CharlestonAdvice
{
    $rack = Rack::fromCodes($codes);
    $ranked = (new HandMatcher)->rank(charlestonCard(), $rack);

    return (new CharlestonAdvisor)->advise($ranked, $rack, $pins);
}

/**
 * Find the advice for one copy of a face on the rack.
 */
function adviceFor(CharlestonAdvice $advice, string $code, int $copy = 1): TileAdvice
{
    foreach ($advice->tiles as $tile) {
        if ($tile->tile->code() === $code && $tile->copy === $copy) {
            return $tile;
        }
    }

    throw new RuntimeException("No copy {$copy} of [{$code}] is on the rack.");
}

/**
 * @return list<string>
 */
function wantedByNames(TileAdvice $tile): array
{
    return array_map(fn ($match): string => $match->name, $tile->wantedBy);
}

test('a ranked line is named by its section and its place in it', function () {
    $ranked = (new HandMatcher)->rank(charlestonCard(), Rack::empty());

    expect(array_map(fn ($match): string => $match->name, $ranked))
        ->toBe(['Fixture 1', 'Fixture 2', 'Fixture 3', 'Fixture 4']);
});

test('the leading hands are the top three of the ranking until the learner pins some', function () {
    $advice = adviseOn(['flower', 'flower', 'dots-5']);

    expect(array_map(fn ($match): string => $match->hand->slug, $advice->leading))
        ->toHaveCount(CharlestonAdvisor::DEFAULT_LEADING)
        ->not->toContain('nines')
        ->and($advice->pinned)->toBeFalse();
});

test('pins replace the default leading hands, and a pin the card does not print is ignored', function () {
    $advice = adviseOn(['flower', 'dots-9'], ['nines', 'a-line-nobody-printed']);

    expect(array_map(fn ($match): string => $match->hand->slug, $advice->leading))->toBe(['nines'])
        ->and($advice->pinned)->toBeTrue();
});

test('pins that name nothing on the card fall back to the top of the ranking', function () {
    $advice = adviseOn(['flower'], ['a-line-nobody-printed']);

    expect($advice->leading)->toHaveCount(CharlestonAdvisor::DEFAULT_LEADING)
        ->and($advice->pinned)->toBeFalse();
});

test('a tile is wanted by the leading hands that place it', function () {
    $advice = adviseOn(['flower', 'dots-5', 'dots-9']);

    expect(wantedByNames(adviceFor($advice, 'flower')))->toBe(['Fixture 1', 'Fixture 2', 'Fixture 3'])
        ->and(adviceFor($advice, 'dots-9')->wantedBy)->toBe([]);
});

/**
 * The lesson for beginners who hoard duplicates: Fixture 1 places one 7 of its
 * second suit, so the second 7 is spare however much the first is wanted.
 */
test('wanted is judged per copy, so a surplus duplicate is spare', function () {
    $advice = adviseOn(['dots-5', 'dots-6', 'bams-7', 'bams-7'], ['one-seven']);

    expect(wantedByNames(adviceFor($advice, 'bams-7', 1)))->toBe(['Fixture 1'])
        ->and(adviceFor($advice, 'bams-7', 2)->wantedBy)->toBe([])
        ->and(adviceFor($advice, 'bams-7', 2)->isSpareCopy())->toBeTrue()
        ->and(adviceFor($advice, 'bams-7', 2)->marker())->toBe('spare')
        ->and(adviceFor($advice, 'bams-7', 2)->reason())
        ->toBe('Your second 7 Bams is spare — your leading hands use only one, and no line on the card would.');
});

test('jokers never enter the passing order, and say so', function () {
    $advice = adviseOn(['joker', 'joker', 'dots-9']);

    expect(array_map(fn (TileAdvice $tile): string => $tile->tile->code(), $advice->passingOrder()))->toBe(['dots-9'])
        ->and(adviceFor($advice, 'joker')->marker())->toBe('never passed')
        ->and(adviceFor($advice, 'joker')->reason())->toBe('Jokers are never passed — keep it, whatever you are chasing.');
});

/**
 * The 1 Craks is wanted by nothing and the 9 Dots only by the line that is not
 * leading, so both go before the 3 Craks, which one leading hand places. The
 * fives and flowers tie on both counts, so set order settles them — fives first.
 */
test('the order passes what the fewest leading hands want, then what the fewest lines want', function () {
    $advice = adviseOn(['flower', 'flower', 'dots-5', 'dots-5', 'dots-9', 'craks-3', 'craks-1']);

    expect(array_map(fn (TileAdvice $tile): string => $tile->tile->code(), $advice->passingOrder()))
        ->toBe(['craks-1', 'dots-9', 'craks-3', 'dots-5', 'dots-5', 'flower', 'flower'])
        ->and(adviceFor($advice, 'craks-3')->wantedBy)->toHaveCount(1);
});

test('a tile no leading hand wants says how many other lines would still use it', function (array $codes, string $code, string $reason) {
    expect(adviceFor(adviseOn($codes), $code)->reason())->toBe($reason);
})->with([
    'no line at all' => [['flower', 'craks-1'], 'craks-1', 'None of your leading hands uses it, and no line on the card would.'],
    'one other line' => [['flower', 'flower', 'dots-5', 'dots-9'], 'dots-9', 'None of your leading hands uses it; 1 other line on the card would.'],
]);

test('a tile no leading hand wants says how many lines would use it, counted', function () {
    $advice = adviseOn(['dots-9'], ['one-seven']);

    expect(adviceFor($advice, 'dots-9')->reason())
        ->toBe('None of your leading hands uses it; 1 other line on the card would.');

    $advice = adviseOn(['dots-5'], ['nines']);

    expect(adviceFor($advice, 'dots-5')->reason())
        ->toBe('None of your leading hands uses it; 3 other lines on the card would.');
});

test('a wanted tile names the hands that want it and what passing it costs them', function () {
    $advice = adviseOn(['dots-5', 'dots-6', 'bams-7', 'craks-3']);

    expect(adviceFor($advice, 'bams-7')->reason())
        ->toBe('Wanted only by Fixture 1 — passing it puts that hand one tile further away.')
        ->and(adviceFor($advice, 'bams-7')->marker())->toBe('1 hand');

    $advice = adviseOn(['flower', 'dots-5']);

    expect(adviceFor($advice, 'dots-5')->reason())
        ->toBe('Wanted by Fixture 1, Fixture 2 and Fixture 3 — passing it puts each of them one tile further away.')
        ->and(adviceFor($advice, 'dots-5')->marker())->toBe('3 hands');

    $advice = adviseOn(['flower', 'dots-5'], ['flowers-and-fives', 'more-fives']);

    expect(adviceFor($advice, 'dots-5')->reason())
        ->toBe('Wanted by Fixture 2 and Fixture 3 — passing it puts both of them one tile further away.');
});

test('the rack size decides the call to action', function (int $size, ?string $call, int $named) {
    $codes = array_slice([
        ...array_fill(0, 4, 'flower'), ...array_fill(0, 4, 'dots-5'),
        'craks-1', 'craks-2', 'craks-3', 'craks-4', 'craks-6', 'craks-8',
    ], 0, $size);

    $advice = adviseOn($codes);

    expect($advice->call())->toBe($call)
        ->and($advice->recommended())->toHaveCount($named);
})->with([
    'still building' => [12, null, 0],
    'a Charleston rack' => [13, 'Pass these 3', 3],
    'a turn' => [14, 'Discard this 1', 1],
]);

test('what is recommended is the head of the passing order', function () {
    $advice = adviseOn([
        ...array_fill(0, 4, 'flower'), ...array_fill(0, 4, 'dots-5'),
        'bams-2', 'bams-2', 'craks-1', 'craks-8', 'craks-9',
    ]);

    expect($advice->recommended())->toBe(array_slice($advice->passingOrder(), 0, 3))
        ->and(array_map(fn (TileAdvice $tile): string => $tile->tile->code(), $advice->recommended()))
        ->toBe(['craks-1', 'craks-8', 'craks-9']);
});

test('a rack that already is a hand is not told to break it', function () {
    $advice = adviseOn([...array_fill(0, 4, 'dots-9'), ...array_fill(0, 4, 'bams-9'), ...array_fill(0, 6, 'joker')]);

    expect($advice->holdsHand)->toBeTrue()
        ->and($advice->call())->toBeNull()
        ->and($advice->recommended())->toBe([]);
});

test('a leading hand is recognised by its line', function () {
    $ranked = (new HandMatcher)->rank(charlestonCard(), Rack::of([Tile::number(Suit::Dots, 9)]));
    $advice = (new CharlestonAdvisor)->advise($ranked, Rack::of([Tile::number(Suit::Dots, 9)]), ['nines']);

    expect($advice->isLeading($ranked[0]))->toBeTrue()
        ->and(array_filter($ranked, fn ($match): bool => $advice->isLeading($match)))->toHaveCount(1);
});

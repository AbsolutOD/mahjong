<?php

use App\Data\TileGroup;
use App\Data\Tiles\DragonTile;
use App\Data\Tiles\FlowerTile;
use App\Data\Tiles\NumberTile;
use App\Data\Tiles\NumberValue;
use App\Data\Tiles\WindTile;
use App\Data\Tiles\ZeroTile;
use App\Enums\Wind;

/**
 * Build a group of the given size from repeats of one number tile.
 */
function likeNumbers(int $size, int $number = 2, string $suit = 'A'): TileGroup
{
    return new TileGroup(array_fill(0, $size, new NumberTile($suit, NumberValue::literal($number))));
}

/**
 * Build the NEWS group of four wind singles.
 */
function news(): TileGroup
{
    return new TileGroup([
        new WindTile(Wind::North),
        new WindTile(Wind::East),
        new WindTile(Wind::West),
        new WindTile(Wind::South),
    ]);
}

test('a group is as big as the number of tiles in it', function (int $size) {
    expect(likeNumbers($size)->size())->toBe($size);
})->with([1, 2, 3, 4, 5, 6]);

test('a group of three or more identical tiles takes jokers', function (int $size) {
    expect(likeNumbers($size)->acceptsJokers())->toBeTrue();
})->with([
    'pung' => 3,
    'kong' => 4,
    'quint' => 5,
    'sextet' => 6,
]);

test('singles and pairs never take jokers', function (int $size) {
    expect(likeNumbers($size)->acceptsJokers())->toBeFalse();
})->with([
    'single' => 1,
    'pair' => 2,
]);

test('NEWS takes no jokers even though it is four tiles', function () {
    expect(news()->acceptsJokers())->toBeFalse();
});

test('a year group takes no jokers because its digits differ', function () {
    $group = new TileGroup([
        new NumberTile('A', NumberValue::literal(2)),
        new ZeroTile,
        new NumberTile('A', NumberValue::literal(2)),
        new NumberTile('A', NumberValue::literal(6)),
    ]);

    expect($group->acceptsJokers())->toBeFalse();
});

test('a run of singles takes no jokers', function () {
    $group = new TileGroup([
        new NumberTile('A', NumberValue::variable('X')),
        new NumberTile('A', NumberValue::variable('X', 1)),
        new NumberTile('A', NumberValue::variable('X', 2)),
    ]);

    expect($group->acceptsJokers())->toBeFalse();
});

test('a group takes its suit variable from its suited tiles', function () {
    expect(likeNumbers(4, suit: 'B')->suitVariable())->toBe('B');
});

test('a dragon group carries the suit its dragons bind to', function () {
    $group = new TileGroup(array_fill(0, 3, new DragonTile('C')));

    expect($group->suitVariable())->toBe('C');
});

test('a year group takes its suit from its digits, ignoring the zero', function () {
    $group = new TileGroup([
        new NumberTile('A', NumberValue::literal(2)),
        new ZeroTile,
        new NumberTile('A', NumberValue::literal(2)),
        new NumberTile('A', NumberValue::literal(6)),
    ]);

    expect($group->suitVariable())->toBe('A');
});

test('groups of suitless tiles bind to no suit', function (TileGroup $group) {
    expect($group->suitVariable())->toBeNull();
})->with([
    'flowers' => fn () => new TileGroup([new FlowerTile, new FlowerTile]),
    'NEWS' => fn () => news(),
    'zeros' => fn () => new TileGroup([new ZeroTile]),
]);

test('a group whose suited tiles disagree on their suit is rejected', function () {
    new TileGroup([
        new NumberTile('A', NumberValue::literal(2)),
        new NumberTile('B', NumberValue::literal(2)),
    ]);
})->throws(InvalidArgumentException::class, 'A group may only use one suit variable, found [A, B].');

test('an empty group is rejected', function () {
    new TileGroup([]);
})->throws(InvalidArgumentException::class, 'A group must hold at least one tile.');

test('a group round-trips through its authoring array', function () {
    $tiles = [
        ['t' => 'num', 'suit' => 'A', 'n' => 2],
        ['t' => 'num', 'suit' => 'A', 'n' => 2],
    ];

    expect(TileGroup::fromArray($tiles)->toArray())->toBe($tiles);
});

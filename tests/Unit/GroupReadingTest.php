<?php

use App\Data\Decoding\GroupReading;
use App\Data\TileGroup;
use App\Data\Tiles\DragonTile;
use App\Data\Tiles\FlowerTile;
use App\Data\Tiles\NumberTile;
use App\Data\Tiles\NumberValue;
use App\Data\Tiles\SuitAssignment;
use App\Data\Tiles\TileSpec;
use App\Data\Tiles\WindTile;
use App\Data\Tiles\ZeroTile;
use App\Enums\Suit;
use App\Enums\Wind;

/**
 * Read a group with nothing bound, the way the card prints it.
 *
 * @param  list<TileSpec>  $tiles
 */
function read(array $tiles, ?SuitAssignment $assignment = null): GroupReading
{
    return GroupReading::for(new TileGroup($tiles), $assignment ?? SuitAssignment::none());
}

/**
 * Build a group of identical suited number tiles.
 *
 * @return list<NumberTile>
 */
function repeated(int $count, string $suit, NumberValue $number): array
{
    return array_fill(0, $count, new NumberTile($suit, $number));
}

test('a group of identical tiles is named by the vocabulary for its size', function (int $size, string $label) {
    expect(read(repeated($size, 'A', NumberValue::literal(3)))->label)->toBe($label);
})->with([
    [1, 'Single'],
    [2, 'Pair'],
    [3, 'Pung'],
    [4, 'Kong'],
    [5, 'Quint'],
    [6, 'Sextet'],
]);

test('a run reads as consecutive numbers, never as a kong', function () {
    $reading = read([
        new NumberTile('A', NumberValue::variable('X')),
        new NumberTile('A', NumberValue::variable('X', 1)),
        new NumberTile('A', NumberValue::variable('X', 2)),
    ]);

    expect($reading->label)->toBe('Run of 3')
        ->and($reading->sentence)->toBe('3 consecutive numbers in suit A, reading X Y Z');
});

test('a kong of one open number names the letter it stands on', function () {
    $reading = read(repeated(4, 'A', NumberValue::variable('X')));

    expect($reading->label)->toBe('Kong')
        ->and($reading->sentence)->toBe('Kong of any one number — call it X — in suit A');
});

test('a kong offset along a run says how far above its variable it sits', function () {
    $reading = read(repeated(4, 'B', NumberValue::variable('X', 1)));

    expect($reading->sentence)->toBe('Kong of Y, 1 higher than X, in suit B');
});

test('a group of printed digits reads as that number', function () {
    $reading = read(repeated(3, 'A', NumberValue::literal(7)));

    expect($reading->sentence)->toBe('Pung of 7s in suit A');
});

test('the year group reads as its digits, with the soap called out', function () {
    $reading = read([
        new NumberTile('A', NumberValue::literal(2)),
        new ZeroTile,
        new NumberTile('A', NumberValue::literal(2)),
        new NumberTile('A', NumberValue::literal(6)),
    ]);

    expect($reading->label)->toBe('4 tiles')
        ->and($reading->sentence)->toBe('the number 2026 in suit A, with a soap standing in for the zero');
});

test('the four winds are named as such rather than counted', function () {
    $reading = read([
        new WindTile(Wind::North),
        new WindTile(Wind::East),
        new WindTile(Wind::West),
        new WindTile(Wind::South),
    ]);

    expect($reading->label)->toBe('The four winds')
        ->and($reading->sentence)->toBe('the four winds, N E W S');
});

test('flowers read as flowers and take no suit', function () {
    $reading = read([new FlowerTile, new FlowerTile]);

    expect($reading->label)->toBe('Pair')
        ->and($reading->sentence)->toBe('Pair of flowers')
        ->and($reading->suitVariable)->toBeNull();
});

test('an unbound dragon group names the suit variable it waits on', function () {
    $reading = read([new DragonTile('C'), new DragonTile('C'), new DragonTile('C')]);

    expect($reading->sentence)->toBe('Pung of dragons in suit C');
});

test('a bound dragon group names the dragon that suit resolves to', function (Suit $suit, string $dragons) {
    $reading = read(
        [new DragonTile('C'), new DragonTile('C'), new DragonTile('C')],
        SuitAssignment::of(['C' => $suit]),
    );

    expect($reading->sentence)->toBe("Pung of {$dragons}");
})->with([
    [Suit::Craks, 'red dragons'],
    [Suit::Bams, 'green dragons'],
    [Suit::Dots, 'soaps'],
]);

test('binding a suit rewrites the prose in that suit', function () {
    $reading = read(
        repeated(4, 'A', NumberValue::variable('X')),
        SuitAssignment::of(['A' => Suit::Bams]),
    );

    expect($reading->sentence)->toBe('Kong of any one number — call it X — in Bams');
});

test('a group carries a face per tile, resolved as far as the assignment allows', function () {
    $reading = read(
        repeated(3, 'A', NumberValue::literal(7)),
        SuitAssignment::of(['A' => Suit::Dots]),
    );

    expect($reading->faces)->toHaveCount(3)
        ->and($reading->faces[0]->isResolved())->toBeTrue()
        ->and($reading->faces[0]->index)->toBe('7D');

    expect(read(repeated(3, 'A', NumberValue::literal(7)))->faces[0]->isResolved())->toBeFalse();
});

test('joker eligibility comes from the rules rather than the sentence', function () {
    expect(read(repeated(3, 'A', NumberValue::literal(7)))->acceptsJokers)->toBeTrue()
        ->and(read(repeated(2, 'A', NumberValue::literal(7)))->acceptsJokers)->toBeFalse()
        ->and(read([
            new WindTile(Wind::North),
            new WindTile(Wind::East),
            new WindTile(Wind::West),
            new WindTile(Wind::South),
        ])->acceptsJokers)->toBeFalse();
});

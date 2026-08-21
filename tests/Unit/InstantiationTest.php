<?php

use App\Data\HandStructure;
use App\Data\Matching\Instantiation;
use App\Data\Tiles\Tile;
use App\Enums\Dragon;
use App\Enums\Suit;

/**
 * Build a structure from the same authoring array the card is written in.
 *
 * @param  array<string, mixed>  $data
 */
function structure(array $data): HandStructure
{
    return HandStructure::fromArray($data);
}

/**
 * Get the codes of every tile a settled hand asks for.
 *
 * @return list<string>
 */
function codesOf(Instantiation $instantiation): array
{
    return array_map(fn (Tile $tile): string => $tile->code(), $instantiation->tiles());
}

test('a hand with three suit letters settles six ways, one per permutation', function () {
    $instantiations = Instantiation::forStructure(structure([
        'variables' => ['A' => ['kind' => 'suit'], 'B' => ['kind' => 'suit'], 'C' => ['kind' => 'suit']],
        'groups' => [
            array_fill(0, 4, ['t' => 'num', 'suit' => 'A', 'n' => 2]),
            array_fill(0, 4, ['t' => 'num', 'suit' => 'B', 'n' => 2]),
            array_fill(0, 4, ['t' => 'dragon', 'suit' => 'C']),
            [['t' => 'flower'], ['t' => 'flower']],
        ],
    ]));

    expect($instantiations)->toHaveCount(6);
});

/**
 * The card's colours mean the groups take *different* suits, which is the rule
 * SuitAssignment refuses to break — so an assignment that breaks it is never
 * a hand to be ranked in the first place.
 */
test('no settled hand puts two letters on one suit', function () {
    $instantiations = Instantiation::forStructure(structure([
        'variables' => ['A' => ['kind' => 'suit'], 'B' => ['kind' => 'suit']],
        'groups' => [
            array_fill(0, 7, ['t' => 'num', 'suit' => 'A', 'n' => 1]),
            array_fill(0, 7, ['t' => 'num', 'suit' => 'B', 'n' => 9]),
        ],
    ]));

    foreach ($instantiations as $instantiation) {
        expect($instantiation->suits->for('A'))->not->toBe($instantiation->suits->for('B'));
    }

    expect($instantiations)->toHaveCount(6);
});

test('a number letter settles once per value its domain allows', function () {
    $instantiations = Instantiation::forStructure(structure([
        'variables' => ['A' => ['kind' => 'suit'], 'X' => ['kind' => 'number', 'domain' => 'even']],
        'groups' => [
            array_fill(0, 7, ['t' => 'num', 'suit' => 'A', 'n' => ['var' => 'X']]),
            array_fill(0, 7, ['t' => 'flower']),
        ],
    ]));

    /** Three suits, and the even numbers 2, 4, 6 and 8. */
    expect($instantiations)->toHaveCount(12)
        ->and(array_unique(array_map(fn (Instantiation $i): int => $i->numbers['X'], $instantiations)))
        ->toEqualCanonicalizing([2, 4, 6, 8]);
});

/**
 * A run that steps off the end of a suit is not an error but an assignment the
 * hand cannot be played on, so it is dropped rather than ranked.
 */
test('a run that would step past nine is not a hand at all', function () {
    $instantiations = Instantiation::forStructure(structure([
        'variables' => ['A' => ['kind' => 'suit'], 'X' => ['kind' => 'number']],
        'groups' => [
            array_fill(0, 4, ['t' => 'num', 'suit' => 'A', 'n' => ['var' => 'X']]),
            array_fill(0, 4, ['t' => 'num', 'suit' => 'A', 'n' => ['var' => 'X', 'off' => 1]]),
            array_fill(0, 6, ['t' => 'num', 'suit' => 'A', 'n' => ['var' => 'X', 'off' => 2]]),
        ],
    ]));

    /** X may be 1 through 7 in each of the three suits; 8 and 9 run off the end. */
    expect($instantiations)->toHaveCount(21)
        ->and(max(array_map(fn (Instantiation $i): int => $i->numbers['X'], $instantiations)))->toBe(7);
});

/**
 * The same guard PracticeCardTest holds over the card, applied to one settled
 * hand: a line the set cannot supply must never be ranked as reachable.
 */
test('a hand the tile set cannot supply settles no way at all', function () {
    $instantiations = Instantiation::forStructure(structure([
        'variables' => ['A' => ['kind' => 'suit']],
        'groups' => [
            array_fill(0, 4, ['t' => 'flower']),
            ...array_fill(0, 5, array_fill(0, 2, ['t' => 'num', 'suit' => 'A', 'n' => 2])),
        ],
    ]));

    expect($instantiations)->toBe([]);
});

/**
 * A quint asks for five copies of a tile that exists four times, which is legal
 * precisely because the fifth may be a joker.
 */
test('a group that overruns the set is kept when jokers may fill it', function () {
    $instantiations = Instantiation::forStructure(structure([
        'variables' => ['A' => ['kind' => 'suit']],
        'groups' => [
            array_fill(0, 5, ['t' => 'num', 'suit' => 'A', 'n' => 3]),
            array_fill(0, 5, ['t' => 'num', 'suit' => 'A', 'n' => 4]),
            array_fill(0, 4, ['t' => 'flower']),
        ],
    ]));

    expect($instantiations)->toHaveCount(3);
});

test('every slot settles on a tile you could pick up', function () {
    $instantiations = Instantiation::forStructure(structure([
        'variables' => ['A' => ['kind' => 'suit'], 'B' => ['kind' => 'suit']],
        'constraints' => [['distinct' => ['A', 'B']]],
        'groups' => [
            [['t' => 'flower'], ['t' => 'flower']],
            [
                ['t' => 'num', 'suit' => 'A', 'n' => 2],
                ['t' => 'zero'],
                ['t' => 'num', 'suit' => 'A', 'n' => 2],
                ['t' => 'num', 'suit' => 'A', 'n' => 6],
            ],
            array_fill(0, 4, ['t' => 'dragon', 'suit' => 'B']),
            [['t' => 'wind', 'w' => 'N'], ['t' => 'wind', 'w' => 'E'], ['t' => 'wind', 'w' => 'W'], ['t' => 'wind', 'w' => 'S']],
        ],
    ]));

    $settled = collect($instantiations)->firstWhere(
        fn (Instantiation $i): bool => $i->suits->for('A') === Suit::Craks && $i->suits->for('B') === Suit::Bams,
    );

    expect(codesOf($settled))->toBe([
        'flower', 'flower',
        'craks-2', 'dragon-'.Dragon::White->value, 'craks-2', 'craks-6',
        ...array_fill(0, 4, 'dragon-'.Dragon::Green->value),
        'wind-north', 'wind-east', 'wind-west', 'wind-south',
    ]);
});

test('a settled hand reads its own bindings out, in the order the hand declares them', function () {
    $instantiations = Instantiation::forStructure(structure([
        'variables' => ['A' => ['kind' => 'suit'], 'X' => ['kind' => 'number', 'domain' => [4]]],
        'groups' => [
            array_fill(0, 7, ['t' => 'num', 'suit' => 'A', 'n' => ['var' => 'X']]),
            array_fill(0, 7, ['t' => 'flower']),
        ],
    ]));

    expect($instantiations[0]->bindings)->toBe(['A' => 'Dots', 'X' => '4']);
});

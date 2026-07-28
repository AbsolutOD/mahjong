<?php

use App\Data\HandStructure;
use App\Mahjong\LineRenderer;

/**
 * Build the authoring array for a hand from its groups.
 *
 * @param  list<list<array<string, mixed>>>  $groups
 * @param  array<string, array<string, mixed>>  $variables
 * @return array<string, mixed>
 */
function hand(array $groups, array $variables): array
{
    return ['variables' => $variables, 'constraints' => [], 'groups' => $groups];
}

/**
 * Repeat a tile spec into a group of the given size.
 *
 * @param  array<string, mixed>  $tile
 * @return list<array<string, mixed>>
 */
function repeatTile(array $tile, int $size): array
{
    return array_fill(0, $size, $tile);
}

/**
 * Declare the given names as suit variables.
 *
 * @return array<string, array<string, mixed>>
 */
function suits(string ...$names): array
{
    return array_map(fn (): array => ['kind' => 'suit'], array_flip($names));
}

test('a hand renders as the line the card prints', function (array $data, string $line) {
    expect((new LineRenderer)->render(HandStructure::fromArray($data)))->toBe($line);
})->with([
    'suited groups carry their suit variable' => [
        fn () => hand([
            repeatTile(['t' => 'flower'], 2),
            repeatTile(['t' => 'num', 'suit' => 'A', 'n' => 2], 4),
            repeatTile(['t' => 'num', 'suit' => 'A', 'n' => 4], 4),
            repeatTile(['t' => 'dragon', 'suit' => 'B'], 4),
        ], suits('A', 'B')),
        'FF 2222(A) 4444(A) DDDD(B)',
    ],

    'a like-numbers hand prints its number variable' => [
        fn () => hand([
            repeatTile(['t' => 'flower'], 4),
            repeatTile(['t' => 'num', 'suit' => 'A', 'n' => ['var' => 'X']], 4),
            repeatTile(['t' => 'num', 'suit' => 'B', 'n' => ['var' => 'X']], 3),
            repeatTile(['t' => 'num', 'suit' => 'C', 'n' => ['var' => 'X']], 3),
        ], suits('A', 'B', 'C') + ['X' => ['kind' => 'number']]),
        'FFFF XXXX(A) XXX(B) XXX(C)',
    ],

    'a consecutive run prints as advancing letters' => [
        fn () => hand([
            repeatTile(['t' => 'flower'], 2),
            repeatTile(['t' => 'num', 'suit' => 'A', 'n' => ['var' => 'X']], 4),
            repeatTile(['t' => 'num', 'suit' => 'A', 'n' => ['var' => 'X', 'off' => 1]], 4),
            repeatTile(['t' => 'num', 'suit' => 'B', 'n' => ['var' => 'X', 'off' => 2]], 4),
        ], suits('A', 'B') + ['X' => ['kind' => 'number']]),
        'FF XXXX(A) YYYY(A) ZZZZ(B)',
    ],

    'winds take no suit suffix' => [
        fn () => hand([
            [['t' => 'wind', 'w' => 'N'], ['t' => 'wind', 'w' => 'E'], ['t' => 'wind', 'w' => 'W'], ['t' => 'wind', 'w' => 'S']],
            repeatTile(['t' => 'dragon', 'suit' => 'A'], 4),
            repeatTile(['t' => 'dragon', 'suit' => 'B'], 4),
            repeatTile(['t' => 'wind', 'w' => 'N'], 2),
        ], suits('A', 'B')),
        'NEWS DDDD(A) DDDD(B) NN',
    ],

    'a year group prints its zero and still carries the suit of its digits' => [
        fn () => hand([
            repeatTile(['t' => 'flower'], 2),
            [['t' => 'num', 'suit' => 'A', 'n' => 2], ['t' => 'zero'], ['t' => 'num', 'suit' => 'A', 'n' => 2], ['t' => 'num', 'suit' => 'A', 'n' => 6]],
            [['t' => 'num', 'suit' => 'B', 'n' => 2], ['t' => 'zero'], ['t' => 'num', 'suit' => 'B', 'n' => 2], ['t' => 'num', 'suit' => 'B', 'n' => 6]],
            repeatTile(['t' => 'dragon', 'suit' => 'A'], 4),
        ], suits('A', 'B')),
        'FF 2026(A) 2026(B) DDDD(A)',
    ],

    'an all-suitless hand prints no suffixes at all' => [
        fn () => hand([
            repeatTile(['t' => 'flower'], 6),
            [['t' => 'wind', 'w' => 'N'], ['t' => 'wind', 'w' => 'E'], ['t' => 'wind', 'w' => 'W'], ['t' => 'wind', 'w' => 'S']],
            repeatTile(['t' => 'wind', 'w' => 'S'], 4),
        ], []),
        'FFFFFF NEWS SSSS',
    ],
]);

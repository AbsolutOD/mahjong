<?php

use App\Data\HandStructure;

/**
 * The authoring array for "FF 2222(A) 4444(A) DDDD(B)".
 *
 * @return array<string, mixed>
 */
function evensHand(): array
{
    return [
        'variables' => ['A' => ['kind' => 'suit'], 'B' => ['kind' => 'suit']],
        'constraints' => [['distinct' => ['A', 'B']]],
        'groups' => [
            [['t' => 'flower'], ['t' => 'flower']],
            array_fill(0, 4, ['t' => 'num', 'suit' => 'A', 'n' => 2]),
            array_fill(0, 4, ['t' => 'num', 'suit' => 'A', 'n' => 4]),
            array_fill(0, 4, ['t' => 'dragon', 'suit' => 'B']),
        ],
    ];
}

/**
 * The authoring array for "FF 11(A) 22(A) 33(B) 44(B) 55(C) 66(C)" — 14 tiles.
 *
 * @return array<string, mixed>
 */
function singlesAndPairsHand(): array
{
    return [
        'variables' => ['A' => ['kind' => 'suit'], 'B' => ['kind' => 'suit'], 'C' => ['kind' => 'suit']],
        'constraints' => [['distinct' => ['A', 'B', 'C']]],
        'groups' => [
            [['t' => 'flower'], ['t' => 'flower']],
            array_fill(0, 2, ['t' => 'num', 'suit' => 'A', 'n' => 1]),
            array_fill(0, 2, ['t' => 'num', 'suit' => 'A', 'n' => 2]),
            array_fill(0, 2, ['t' => 'num', 'suit' => 'B', 'n' => 3]),
            array_fill(0, 2, ['t' => 'num', 'suit' => 'B', 'n' => 4]),
            array_fill(0, 2, ['t' => 'num', 'suit' => 'C', 'n' => 5]),
            array_fill(0, 2, ['t' => 'num', 'suit' => 'C', 'n' => 6]),
        ],
    ];
}

test('a hand is built from its authoring array', function () {
    $structure = HandStructure::fromArray(evensHand());

    expect($structure->groups)->toHaveCount(4)
        ->and($structure->groups[0]->size())->toBe(2)
        ->and($structure->variables)->toHaveKeys(['A', 'B'])
        ->and($structure->constraints)->toHaveCount(1);
});

test('every hand holds exactly fourteen tiles', function () {
    expect(HandStructure::fromArray(evensHand())->tileCount())->toBe(14);
});

test('a hand that does not total fourteen tiles is rejected', function () {
    $hand = evensHand();
    array_pop($hand['groups']);

    HandStructure::fromArray($hand);
})->throws(InvalidArgumentException::class, 'A hand must hold exactly 14 tiles, found 10.');

test('a tile standing on an undeclared variable is rejected', function () {
    $hand = evensHand();
    $hand['groups'][3] = array_fill(0, 4, ['t' => 'dragon', 'suit' => 'Z']);

    HandStructure::fromArray($hand);
})->throws(InvalidArgumentException::class, 'Tiles reference undeclared variable [Z].');

test('a constraint naming an undeclared variable is rejected', function () {
    $hand = evensHand();
    $hand['constraints'] = [['distinct' => ['A', 'Q']]];

    HandStructure::fromArray($hand);
})->throws(InvalidArgumentException::class, 'Constraint references undeclared variable [Q].');

test('a run whose letters collide with another declared variable is rejected', function () {
    $hand = evensHand();
    $hand['variables'] = ['A' => ['kind' => 'suit'], 'B' => ['kind' => 'suit'], 'X' => ['kind' => 'number'], 'Y' => ['kind' => 'number']];
    $hand['constraints'] = [];
    $hand['groups'][1] = array_fill(0, 4, ['t' => 'num', 'suit' => 'A', 'n' => ['var' => 'X', 'off' => 1]]);

    HandStructure::fromArray($hand);
})->throws(InvalidArgumentException::class, 'Run [X+1] renders as [Y], which the hand also declares.');

test('a hand counts the distinct suits it uses', function (array $hand, int $suitCount) {
    expect(HandStructure::fromArray($hand)->suitCount())->toBe($suitCount);
})->with([
    'two suits' => [evensHand(), 2],
    'three suits' => [singlesAndPairsHand(), 3],
]);

test('a hand knows whether it holds a pair', function () {
    expect(HandStructure::fromArray(evensHand())->hasPair())->toBeTrue();
});

test('a hand of only kongs holds no pair', function () {
    $hand = evensHand();
    $hand['groups'][0] = array_fill(0, 2, ['t' => 'flower']);
    $hand['groups'] = [
        array_fill(0, 4, ['t' => 'num', 'suit' => 'A', 'n' => 2]),
        array_fill(0, 4, ['t' => 'num', 'suit' => 'A', 'n' => 4]),
        array_fill(0, 3, ['t' => 'dragon', 'suit' => 'B']),
        array_fill(0, 3, ['t' => 'num', 'suit' => 'B', 'n' => 6]),
    ];

    expect(HandStructure::fromArray($hand)->hasPair())->toBeFalse();
});

test('a hand knows whether it uses flowers', function () {
    $withoutFlowers = evensHand();
    $withoutFlowers['groups'][0] = array_fill(0, 2, ['t' => 'num', 'suit' => 'A', 'n' => 6]);

    expect(HandStructure::fromArray(evensHand())->usesFlowers())->toBeTrue()
        ->and(HandStructure::fromArray($withoutFlowers)->usesFlowers())->toBeFalse();
});

test('the most jokers a hand can hold is the size of its joker-eligible groups', function () {
    expect(HandStructure::fromArray(evensHand())->maxJokers())->toBe(12);
});

test('a hand of singles and pairs can hold no jokers at all', function () {
    expect(HandStructure::fromArray(singlesAndPairsHand())->maxJokers())->toBe(0);
});

test('a hand round-trips through its authoring array', function (array $hand) {
    expect(HandStructure::fromArray($hand)->toArray())->toBe($hand);
})->with([
    'evens' => [evensHand()],
    'singles and pairs' => [singlesAndPairsHand()],
]);

test('a distinct constraint holds only when its variables take different values', function (array $assignment, bool $satisfied) {
    $structure = HandStructure::fromArray(evensHand());

    expect($structure->constraints[0]->isSatisfiedBy($assignment))->toBe($satisfied);
})->with([
    'two different suits' => [['A' => 'bams', 'B' => 'craks'], true],
    'the same suit twice' => [['A' => 'bams', 'B' => 'bams'], false],
]);

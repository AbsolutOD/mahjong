<?php

use App\Data\HandVariable;
use App\Enums\NumberDomain;
use App\Enums\VariableKind;

test('a suit variable stands for any one suit', function () {
    $variable = HandVariable::fromArray(['kind' => 'suit']);

    expect($variable->kind)->toBe(VariableKind::Suit)
        ->and($variable->domain)->toBeNull();
});

test('a number variable declared without a domain admits every number', function () {
    $variable = HandVariable::fromArray(['kind' => 'number']);

    expect($variable->domain)->toBe(NumberDomain::Any)
        ->and(array_filter(range(1, 9), $variable->allows(...)))->toBe(range(1, 9));
});

test('a number variable admits only the numbers its domain allows', function (string|array $domain, array $allowed) {
    $variable = HandVariable::fromArray(['kind' => 'number', 'domain' => $domain]);

    expect(array_values(array_filter(range(1, 9), $variable->allows(...))))->toBe($allowed);
})->with([
    'any' => ['any', [1, 2, 3, 4, 5, 6, 7, 8, 9]],
    'odd' => ['odd', [1, 3, 5, 7, 9]],
    'even' => ['even', [2, 4, 6, 8]],
    'an explicit list' => [[3, 6, 9], [3, 6, 9]],
]);

test('asking a suit variable which numbers it allows is a mistake', function () {
    HandVariable::fromArray(['kind' => 'suit'])->allows(3);
})->throws(LogicException::class, 'Suit variable has no number domain.');

test('a variable round-trips through its authoring array', function (array $data) {
    expect(HandVariable::fromArray($data)->toArray())->toBe($data);
})->with([
    'suit' => [['kind' => 'suit']],
    'any number' => [['kind' => 'number', 'domain' => 'any']],
    'odd numbers' => [['kind' => 'number', 'domain' => 'odd']],
    'an explicit list' => [['kind' => 'number', 'domain' => [3, 6, 9]]],
]);

test('an unknown variable kind is rejected', function () {
    HandVariable::fromArray(['kind' => 'colour']);
})->throws(InvalidArgumentException::class, 'Unknown variable kind [colour].');

test('an unknown number domain is rejected', function () {
    HandVariable::fromArray(['kind' => 'number', 'domain' => 'prime']);
})->throws(InvalidArgumentException::class, 'Unknown number domain [prime].');

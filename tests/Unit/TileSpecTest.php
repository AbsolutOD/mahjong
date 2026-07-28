<?php

use App\Data\Tiles\DragonTile;
use App\Data\Tiles\FlowerTile;
use App\Data\Tiles\NumberTile;
use App\Data\Tiles\NumberValue;
use App\Data\Tiles\TileSpec;
use App\Data\Tiles\WindTile;
use App\Data\Tiles\ZeroTile;
use App\Enums\Wind;

test('a literal number tile renders as its digit and carries its suit variable', function () {
    $tile = new NumberTile('A', NumberValue::literal(2));

    expect($tile->symbol())->toBe('2')
        ->and($tile->suitVariable())->toBe('A');
});

test('a number tile with no offset renders as its declared letter', function () {
    $tile = new NumberTile('A', NumberValue::variable('X'));

    expect($tile->symbol())->toBe('X');
});

test('an offset number tile renders as its letter advanced by the offset', function (int $offset, string $symbol) {
    $tile = new NumberTile('A', NumberValue::variable('X', $offset));

    expect($tile->symbol())->toBe($symbol);
})->with([
    'the run starts at the declared letter' => [0, 'X'],
    'one past X is Y' => [1, 'Y'],
    'two past X is Z' => [2, 'Z'],
]);

test('an offset that runs off the end of the alphabet is rejected', function () {
    NumberValue::variable('Z', 1)->symbol();
})->throws(InvalidArgumentException::class);

test('a dragon renders as D and binds to its group\'s suit variable', function () {
    $tile = new DragonTile('B');

    expect($tile->symbol())->toBe('D')
        ->and($tile->suitVariable())->toBe('B');
});

test('suitless tiles render their own symbol and bind to no suit', function (TileSpec $tile, string $symbol) {
    expect($tile->symbol())->toBe($symbol)
        ->and($tile->suitVariable())->toBeNull();
})->with([
    'north wind' => [new WindTile(Wind::North), 'N'],
    'south wind' => [new WindTile(Wind::South), 'S'],
    'flower' => [new FlowerTile, 'F'],
    'zero' => [new ZeroTile, '0'],
]);

test('a tile spec is built from its authoring array', function (array $data, TileSpec $expected) {
    expect(TileSpec::fromArray($data))->toEqual($expected);
})->with([
    'literal number' => [['t' => 'num', 'suit' => 'A', 'n' => 4], new NumberTile('A', NumberValue::literal(4))],
    'number variable' => [['t' => 'num', 'suit' => 'A', 'n' => ['var' => 'X']], new NumberTile('A', NumberValue::variable('X'))],
    'offset number variable' => [['t' => 'num', 'suit' => 'B', 'n' => ['var' => 'X', 'off' => 2]], new NumberTile('B', NumberValue::variable('X', 2))],
    'dragon' => [['t' => 'dragon', 'suit' => 'A'], new DragonTile('A')],
    'wind' => [['t' => 'wind', 'w' => 'E'], new WindTile(Wind::East)],
    'flower' => [['t' => 'flower'], new FlowerTile],
    'zero' => [['t' => 'zero'], new ZeroTile],
]);

test('a tile spec round-trips back to its authoring array', function (array $data) {
    expect(TileSpec::fromArray($data)->toArray())->toBe($data);
})->with([
    'literal number' => [['t' => 'num', 'suit' => 'A', 'n' => 4]],
    'number variable' => [['t' => 'num', 'suit' => 'A', 'n' => ['var' => 'X']]],
    'offset number variable' => [['t' => 'num', 'suit' => 'B', 'n' => ['var' => 'X', 'off' => 2]]],
    'dragon' => [['t' => 'dragon', 'suit' => 'A']],
    'wind' => [['t' => 'wind', 'w' => 'E']],
    'flower' => [['t' => 'flower']],
    'zero' => [['t' => 'zero']],
]);

test('an unknown wind symbol is rejected', function () {
    Wind::fromSymbol('Q');
})->throws(ValueError::class, '[Q] is not a valid wind symbol.');

test('an unknown tile type is rejected', function () {
    TileSpec::fromArray(['t' => 'pizza']);
})->throws(InvalidArgumentException::class, 'Unknown tile type [pizza].');

test('tiles are identical only when their type, suit variable and value all agree', function (TileSpec $one, TileSpec $other, bool $identical) {
    expect($one->isIdenticalTo($other))->toBe($identical);
})->with([
    'same literal number in the same suit' => [new NumberTile('A', NumberValue::literal(2)), new NumberTile('A', NumberValue::literal(2)), true],
    'same number in different suits' => [new NumberTile('A', NumberValue::literal(2)), new NumberTile('B', NumberValue::literal(2)), false],
    'different numbers in the same suit' => [new NumberTile('A', NumberValue::literal(2)), new NumberTile('A', NumberValue::literal(3)), false],
    'the same point in a run' => [new NumberTile('A', NumberValue::variable('X', 1)), new NumberTile('A', NumberValue::variable('X', 1)), true],
    'different points in a run' => [new NumberTile('A', NumberValue::variable('X')), new NumberTile('A', NumberValue::variable('X', 1)), false],
    'two flowers' => [new FlowerTile, new FlowerTile, true],
    'a flower and a zero' => [new FlowerTile, new ZeroTile, false],
    'two north winds' => [new WindTile(Wind::North), new WindTile(Wind::North), true],
    'two different winds' => [new WindTile(Wind::North), new WindTile(Wind::East), false],
    'dragons of the same suit' => [new DragonTile('A'), new DragonTile('A'), true],
    'dragons of different suits' => [new DragonTile('A'), new DragonTile('B'), false],
]);

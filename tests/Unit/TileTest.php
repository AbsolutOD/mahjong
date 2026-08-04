<?php

use App\Data\Tiles\Tile;
use App\Enums\Dragon;
use App\Enums\Suit;
use App\Enums\Wind;
use App\Mahjong\AmericanMahjong;

test('the set has one face per distinct tile, copies aside', function () {
    expect(Tile::all())->toHaveCount(36);
});

test('every face in the set is distinct', function () {
    $codes = array_map(fn (Tile $tile): string => $tile->code(), Tile::all());

    expect(array_unique($codes))->toHaveCount(count($codes));
});

test('the set covers exactly the American tile inventory', function () {
    $codes = array_map(fn (Tile $tile): string => $tile->code(), Tile::all());
    sort($codes);

    $inventory = array_keys(AmericanMahjong::tileInventory());
    sort($inventory);

    expect($codes)->toBe($inventory);
});

test('a number tile knows its suit and number', function () {
    $tile = Tile::number(Suit::Dots, 7);

    expect($tile->suit)->toBe(Suit::Dots)
        ->and($tile->number)->toBe(7)
        ->and($tile->code())->toBe('dots-7');
});

test('a number outside one to nine is rejected', function (int $number) {
    Tile::number(Suit::Bams, $number);
})->with([0, 10, -1])->throws(InvalidArgumentException::class);

test('honour tiles carry their own identity and no suit', function (Tile $tile, string $code) {
    expect($tile->code())->toBe($code)
        ->and($tile->suit)->toBeNull()
        ->and($tile->number)->toBeNull();
})->with([
    'east wind' => [fn () => Tile::wind(Wind::East), 'wind-east'],
    'red dragon' => [fn () => Tile::dragon(Dragon::Red), 'dragon-red'],
    'soap' => [fn () => Tile::dragon(Dragon::White), 'dragon-white'],
    'flower' => [fn () => Tile::flower(), 'flower'],
    'joker' => [fn () => Tile::joker(), 'joker'],
]);

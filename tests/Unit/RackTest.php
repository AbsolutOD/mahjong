<?php

use App\Data\Matching\Rack;
use App\Data\Tiles\Tile;
use App\Enums\Dragon;
use App\Enums\Suit;
use App\Enums\Wind;

test('a rack starts empty and takes tiles one at a time', function () {
    $rack = Rack::empty()
        ->add(Tile::number(Suit::Bams, 3))
        ->add(Tile::joker());

    expect($rack->size())->toBe(2)
        ->and($rack->isEmpty())->toBeFalse()
        ->and($rack->isFull())->toBeFalse();
});

test('a rack reads in set order however the tiles were clicked', function () {
    $rack = Rack::of([
        Tile::joker(),
        Tile::wind(Wind::East),
        Tile::number(Suit::Craks, 2),
        Tile::number(Suit::Dots, 9),
    ]);

    expect($rack->codes())->toBe(['dots-9', 'craks-2', 'wind-east', 'joker']);
});

test('a rack refuses a fifteenth tile, because a hand is fourteen', function () {
    $rack = Rack::of(array_fill(0, 4, Tile::number(Suit::Dots, 1)));
    $rack = $rack->add(Tile::flower())->add(Tile::flower())->add(Tile::flower())->add(Tile::flower());
    $rack = $rack->add(Tile::joker())->add(Tile::joker())->add(Tile::joker())->add(Tile::joker());
    $rack = $rack->add(Tile::wind(Wind::North))->add(Tile::wind(Wind::South));

    expect($rack->isFull())->toBeTrue()
        ->and($rack->canHold(Tile::flower()))->toBeFalse()
        ->and(fn () => $rack->add(Tile::flower()))->toThrow(InvalidArgumentException::class);
});

test('a rack refuses more copies of a tile than the set holds', function (Tile $tile, int $copies) {
    $rack = Rack::of(array_fill(0, $copies, $tile));

    expect($rack->countOf($tile))->toBe($copies)
        ->and($rack->canHold($tile))->toBeFalse()
        ->and(fn () => $rack->add($tile))->toThrow(InvalidArgumentException::class);
})->with([
    'a fifth two of dots' => [fn () => Tile::number(Suit::Dots, 2), 4],
    'a fifth soap' => [fn () => Tile::dragon(Dragon::White), 4],
    'a ninth flower' => [fn () => Tile::flower(), 8],
    'a ninth joker' => [fn () => Tile::joker(), 8],
]);

test('removing takes one copy, and shrugs at a tile that is not there', function () {
    $rack = Rack::of([Tile::flower(), Tile::flower(), Tile::joker()]);

    expect($rack->remove(Tile::flower())->codes())->toBe(['flower', 'joker'])
        ->and($rack->remove(Tile::number(Suit::Bams, 1))->codes())->toBe(['flower', 'flower', 'joker']);
});

/**
 * A rack arrives from the query string, where anything at all may be typed, so
 * it settles on the rack it can make rather than five-hundreding the page.
 */
test('a rack read from a url drops what it cannot hold', function () {
    $rack = Rack::fromCodes(['dots-1', 'not-a-tile', 'dots-99', 'flower', 'joker']);

    expect($rack->codes())->toBe(['dots-1', 'flower', 'joker']);
});

test('a rack read from a url drops copies past what the set holds', function () {
    $rack = Rack::fromCodes(array_fill(0, 6, 'craks-7'));

    expect($rack->size())->toBe(4);
});

test('a rack read from a url drops tiles past a full hand', function () {
    $rack = Rack::fromCodes(array_fill(0, 20, 'joker'));

    expect($rack->size())->toBe(8);
});

test('a rack survives a round trip through its codes', function () {
    $rack = Rack::of([Tile::number(Suit::Bams, 5), Tile::dragon(Dragon::Red), Tile::flower()]);

    expect(Rack::fromCodes($rack->codes())->codes())->toBe($rack->codes());
});

test('a rack counts itself by tile code', function () {
    $rack = Rack::of([Tile::flower(), Tile::flower(), Tile::joker()]);

    expect($rack->counts())->toBe(['flower' => 2, 'joker' => 1]);
});

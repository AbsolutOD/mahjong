<?php

use App\Enums\Dragon;
use App\Enums\Suit;
use App\Mahjong\AmericanMahjong;

test('each suit binds to its matching dragon', function (Suit $suit, Dragon $dragon) {
    expect(AmericanMahjong::dragonForSuit($suit))->toBe($dragon);
})->with([
    'craks take the red dragon' => [Suit::Craks, Dragon::Red],
    'bams take the green dragon' => [Suit::Bams, Dragon::Green],
    'dots take the soap' => [Suit::Dots, Dragon::White],
]);

test('the tile inventory totals 152 tiles', function () {
    expect(array_sum(AmericanMahjong::tileInventory()))->toBe(152);
});

test('the tile inventory holds four copies of every natural tile', function () {
    $inventory = AmericanMahjong::tileInventory();

    expect($inventory['craks-1'])->toBe(4)
        ->and($inventory['dots-9'])->toBe(4)
        ->and($inventory['wind-north'])->toBe(4)
        ->and($inventory['dragon-white'])->toBe(4);
});

test('flowers and jokers each have eight copies', function () {
    $inventory = AmericanMahjong::tileInventory();

    expect($inventory['flower'])->toBe(8)
        ->and($inventory['joker'])->toBe(8);
});

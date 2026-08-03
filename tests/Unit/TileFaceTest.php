<?php

use App\Data\Tiles\DragonTile;
use App\Data\Tiles\FlowerTile;
use App\Data\Tiles\NumberTile;
use App\Data\Tiles\NumberValue;
use App\Data\Tiles\SuitAssignment;
use App\Data\Tiles\Tile;
use App\Data\Tiles\TileFace;
use App\Data\Tiles\TileSpec;
use App\Data\Tiles\WindTile;
use App\Data\Tiles\ZeroTile;
use App\Enums\Dragon;
use App\Enums\FaceTone;
use App\Enums\Suit;
use App\Enums\Wind;

test('a physical tile resolves to a face carrying that tile', function () {
    $face = TileFace::of(Tile::number(Suit::Dots, 7));

    expect($face->tile)->toEqual(Tile::number(Suit::Dots, 7))
        ->and($face->isResolved())->toBeTrue()
        ->and($face->tone)->toBe(FaceTone::Dots)
        ->and($face->index)->toBe('7D')
        ->and($face->name)->toBe('7 Dots');
});

test('every face in the physical set is resolved, toned and indexed', function () {
    foreach (Tile::all() as $tile) {
        $face = TileFace::of($tile);

        expect($face->isResolved())->toBeTrue()
            ->and($face->tone)->not->toBe(FaceTone::Unbound)
            ->and($face->index)->not->toBe('')
            ->and($face->name)->not->toBe('');
    }
});

test('each physical face carries a distinct corner index', function () {
    $indexes = array_map(fn (Tile $tile): string => TileFace::of($tile)->index, Tile::all());

    expect(array_unique($indexes))->toHaveCount(36);
});

test('honour faces name themselves in the word band', function (Tile $tile, string $index, ?string $word) {
    $face = TileFace::of($tile);

    expect($face->index)->toBe($index)
        ->and($face->word())->toBe($word);
})->with([
    'east wind' => [fn () => Tile::wind(Wind::East), 'E', 'EAST'],
    'north wind' => [fn () => Tile::wind(Wind::North), 'N', 'NORTH'],
    'red dragon' => [fn () => Tile::dragon(Dragon::Red), 'RD', 'RED'],
    'green dragon' => [fn () => Tile::dragon(Dragon::Green), 'GD', 'GREEN'],
    'soap' => [fn () => Tile::dragon(Dragon::White), 'SO', 'SOAP'],
    'flower' => [fn () => Tile::flower(), 'FL', 'FLOWER'],
    'joker' => [fn () => Tile::joker(), 'JK', 'JOKER'],
    'a number tile leaves the band to its artwork' => [fn () => Tile::number(Suit::Craks, 3), '3C', null],
]);

/*
|--------------------------------------------------------------------------
| Rule: never colour alone (issue #8)
|--------------------------------------------------------------------------
*/

test('the red and green dragons differ by more than hue', function () {
    $red = TileFace::of(Tile::dragon(Dragon::Red));
    $green = TileFace::of(Tile::dragon(Dragon::Green));

    expect($red->glyph())->not->toBe($green->glyph())
        ->and($red->word())->not->toBe($green->word())
        ->and($red->index)->not->toBe($green->index);
});

test('no two faces in the set are told apart by tone alone', function () {
    $signals = [];

    foreach (Tile::all() as $tile) {
        $face = TileFace::of($tile);
        $signals[] = $face->index.'|'.$face->word().'|'.$face->glyph().'|'.$face->tile->number;
    }

    expect(array_unique($signals))->toHaveCount(36);
});

/*
|--------------------------------------------------------------------------
| Rule: grey means unbound (issue #8)
|--------------------------------------------------------------------------
*/

test('a slot on an unassigned suit variable has no colour and no artwork', function (TileSpec $spec, string $index, string $well) {
    $face = TileFace::for($spec, SuitAssignment::none());

    expect($face->tone)->toBe(FaceTone::Unbound)
        ->and($face->isResolved())->toBeFalse()
        ->and($face->tile)->toBeNull()
        ->and($face->well)->toBe($well)
        ->and($face->index)->toBe($index);
})->with([
    'a number variable in suit A' => [fn () => new NumberTile('A', NumberValue::variable('X')), 'XA', 'X'],
    'a run position in suit A' => [fn () => new NumberTile('A', NumberValue::variable('X', 2)), 'ZA', 'Z'],
    'a printed number in suit A' => [fn () => new NumberTile('A', NumberValue::literal(1)), '1A', '1'],
    'a dragon matching suit B' => [fn () => new DragonTile('B'), 'DB', 'D'],
]);

test('an unbound slot names the variable it is waiting on', function () {
    $face = TileFace::for(new NumberTile('A', NumberValue::variable('X')), SuitAssignment::none());

    expect($face->name)->toBe('X in suit A');
});

test('binding another variable leaves this slot grey', function () {
    $face = TileFace::for(
        new NumberTile('B', NumberValue::literal(5)),
        SuitAssignment::of(['A' => Suit::Dots]),
    );

    expect($face->tone)->toBe(FaceTone::Unbound)
        ->and($face->isResolved())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| The three states a card slot renders in
|--------------------------------------------------------------------------
*/

test('a half-resolved slot takes the suit colour but still draws no artwork', function () {
    $face = TileFace::for(
        new NumberTile('A', NumberValue::variable('X')),
        SuitAssignment::of(['A' => Suit::Bams]),
    );

    expect($face->tone)->toBe(FaceTone::Bams)
        ->and($face->isResolved())->toBeFalse()
        ->and($face->tile)->toBeNull()
        ->and($face->well)->toBe('X')
        ->and($face->index)->toBe('XB')
        ->and($face->name)->toBe('X in Bams');
});

test('a fully resolved slot is indistinguishable from the physical tile', function () {
    $face = TileFace::for(
        new NumberTile('A', NumberValue::literal(7)),
        SuitAssignment::of(['A' => Suit::Dots]),
    );

    expect($face)->toEqual(TileFace::of(Tile::number(Suit::Dots, 7)));
});

test('a run position resolves to the tile its offset lands on', function () {
    $face = TileFace::for(
        new NumberTile('A', NumberValue::variable('X', 2)),
        SuitAssignment::of(['A' => Suit::Craks]),
    );

    expect($face->tone)->toBe(FaceTone::Craks)
        ->and($face->isResolved())->toBeFalse()
        ->and($face->well)->toBe('Z');
});

test('a dragon resolves to the dragon its assigned suit binds to', function (Suit $suit, Dragon $dragon) {
    $face = TileFace::for(new DragonTile('A'), SuitAssignment::of(['A' => $suit]));

    expect($face->tile)->toEqual(Tile::dragon($dragon))
        ->and($face->isResolved())->toBeTrue();
})->with([
    'craks take the red dragon' => [Suit::Craks, Dragon::Red],
    'bams take the green dragon' => [Suit::Bams, Dragon::Green],
    'dots take the soap' => [Suit::Dots, Dragon::White],
]);

test('suitless specs resolve whatever is assigned', function (TileSpec $spec, string $index, string $name) {
    $face = TileFace::for($spec, SuitAssignment::none());

    expect($face->isResolved())->toBeTrue()
        ->and($face->tone)->not->toBe(FaceTone::Unbound)
        ->and($face->index)->toBe($index)
        ->and($face->name)->toBe($name);
})->with([
    'a wind' => [fn () => new WindTile(Wind::South), 'S', 'South Wind'],
    'a flower' => [fn () => new FlowerTile, 'FL', 'Flower'],
]);

test('a zero draws the soap but indexes as the numeral the card prints', function () {
    $face = TileFace::for(new ZeroTile, SuitAssignment::none());

    expect($face->tile)->toEqual(Tile::dragon(Dragon::White))
        ->and($face->tone)->toBe(FaceTone::Soap)
        ->and($face->index)->toBe('0')
        ->and($face->word())->toBe('SOAP')
        ->and($face->name)->toBe('Soap, standing in as zero');
});

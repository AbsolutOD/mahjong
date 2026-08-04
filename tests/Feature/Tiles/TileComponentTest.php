<?php

use App\Data\Tiles\DragonTile;
use App\Data\Tiles\NumberTile;
use App\Data\Tiles\NumberValue;
use App\Data\Tiles\SuitAssignment;
use App\Data\Tiles\Tile;
use App\Data\Tiles\TileFace;
use App\Enums\Dragon;
use App\Enums\FaceTone;
use App\Enums\Suit;
use App\Enums\Wind;

/**
 * Render a tile face and hand back its markup.
 */
function renderFace(TileFace $face, string $size = 'md'): string
{
    return view('components.tile', ['face' => $face, 'size' => $size])->render();
}

/**
 * Count how many times a marker attribute appears in some markup.
 */
function countMarkers(string $markup, string $marker): int
{
    return substr_count($markup, "data-{$marker}");
}

test('every face in the physical set renders', function () {
    foreach (Tile::all() as $tile) {
        $face = TileFace::of($tile);
        $markup = renderFace($face);

        expect($markup)
            ->toContain('<svg')
            ->toContain($face->index)
            ->toContain($face->name)
            ->and(countMarkers($markup, 'artwork'))->toBe(1);
    }
});

test('a face is announced by name rather than by its artwork', function () {
    $markup = renderFace(TileFace::of(Tile::dragon(Dragon::Green)));

    expect($markup)
        ->toContain('role="img"')
        ->toContain('aria-label="Green Dragon"')
        ->toContain('<title>Green Dragon</title>');
});

/*
|--------------------------------------------------------------------------
| Rule: grey means unbound (issue #8)
|--------------------------------------------------------------------------
*/

test('an unbound slot draws the grey ink and no artwork at all', function () {
    $markup = renderFace(TileFace::for(new NumberTile('A', NumberValue::variable('X')), SuitAssignment::none()));

    expect(countMarkers($markup, 'artwork'))->toBe(0)
        ->and(countMarkers($markup, 'well'))->toBe(1)
        ->and($markup)->toContain(FaceTone::Unbound->ink())
        ->and($markup)->not->toContain(FaceTone::Dots->ink())
        ->and($markup)->not->toContain(FaceTone::Bams->ink())
        ->and($markup)->not->toContain(FaceTone::Craks->ink());
});

test('an unbound dragon draws no artwork either', function () {
    $markup = renderFace(TileFace::for(new DragonTile('B'), SuitAssignment::none()));

    expect(countMarkers($markup, 'artwork'))->toBe(0)
        ->and($markup)->toContain('DB')
        ->and($markup)->toContain(FaceTone::Unbound->ink());
});

test('a half-resolved slot takes the suit ink but still draws no artwork', function () {
    $markup = renderFace(TileFace::for(
        new NumberTile('A', NumberValue::variable('X')),
        SuitAssignment::of(['A' => Suit::Bams]),
    ));

    expect(countMarkers($markup, 'artwork'))->toBe(0)
        ->and(countMarkers($markup, 'well'))->toBe(1)
        ->and($markup)->toContain(FaceTone::Bams->ink())
        ->and($markup)->not->toContain(FaceTone::Unbound->ink())
        ->and($markup)->toContain('>X<');
});

test('a resolved slot drops the well and draws its artwork', function () {
    $markup = renderFace(TileFace::for(
        new NumberTile('A', NumberValue::literal(7)),
        SuitAssignment::of(['A' => Suit::Dots]),
    ));

    expect(countMarkers($markup, 'well'))->toBe(0)
        ->and(countMarkers($markup, 'artwork'))->toBe(1)
        ->and($markup)->toContain(FaceTone::Dots->ink());
});

/*
|--------------------------------------------------------------------------
| Rule: never colour alone (issue #8)
|--------------------------------------------------------------------------
*/

test('the red and green dragons differ in markup beyond their ink, at every size', function (string $size) {
    $red = renderFace(TileFace::of(Tile::dragon(Dragon::Red)), $size);
    $green = renderFace(TileFace::of(Tile::dragon(Dragon::Green)), $size);

    expect($red)->toContain('中')->toContain('RED')->toContain('RD')
        ->and($green)->toContain('發')->toContain('GREEN')->toContain('GD');

    $redWithoutInk = str_replace(FaceTone::Red->ink(), '', $red);
    $greenWithoutInk = str_replace(FaceTone::Green->ink(), '', $green);

    expect($redWithoutInk)->not->toBe($greenWithoutInk);
})->with(['sm', 'md', 'lg']);

test('every honour keeps its word band at the smallest size', function (Tile $tile, string $word) {
    expect(renderFace(TileFace::of($tile), 'sm'))->toContain($word);
})->with([
    'east' => [fn () => Tile::wind(Wind::East), 'EAST'],
    'red dragon' => [fn () => Tile::dragon(Dragon::Red), 'RED'],
    'green dragon' => [fn () => Tile::dragon(Dragon::Green), 'GREEN'],
    'soap' => [fn () => Tile::dragon(Dragon::White), 'SOAP'],
    'flower' => [fn () => Tile::flower(), 'FLOWER'],
    'joker' => [fn () => Tile::joker(), 'JOKER'],
]);

/*
|--------------------------------------------------------------------------
| The artwork itself
|--------------------------------------------------------------------------
*/

test('a dots tile draws one mark per number', function (int $number) {
    $markup = renderFace(TileFace::of(Tile::number(Suit::Dots, $number)));

    expect(countMarkers($markup, 'pip'))->toBe($number);
})->with(range(1, 9));

test('a bams tile draws one mark per number', function (int $number) {
    $markup = renderFace(TileFace::of(Tile::number(Suit::Bams, $number)));

    expect(countMarkers($markup, 'pip'))->toBe($number);
})->with(range(1, 9));

test('a craks tile draws its numeral over the character rather than pips', function () {
    $markup = renderFace(TileFace::of(Tile::number(Suit::Craks, 4)));

    expect(countMarkers($markup, 'pip'))->toBe(0)
        ->and($markup)->toContain('萬')
        ->toContain('>4<');
});

/*
|--------------------------------------------------------------------------
| Sizes
|--------------------------------------------------------------------------
*/

test('each size renders its own box width', function (string $size, string $width) {
    expect(renderFace(TileFace::of(Tile::flower()), $size))->toContain($width);
})->with([
    'the line list' => ['sm', 'w-8'],
    'the breakdown' => ['md', 'w-12'],
    'the focus deck' => ['lg', 'w-20'],
]);

test('an unknown size falls back to the breakdown size', function () {
    expect(renderFace(TileFace::of(Tile::flower()), 'enormous'))->toContain('w-12');
});

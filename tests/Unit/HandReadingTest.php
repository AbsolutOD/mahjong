<?php

use App\Data\Decoding\HandReading;
use App\Data\Decoding\RuleTag;
use App\Data\HandStructure;
use App\Data\HandVariable;
use App\Data\TileGroup;
use App\Data\Tiles\DragonTile;
use App\Data\Tiles\FlowerTile;
use App\Data\Tiles\NumberTile;
use App\Data\Tiles\NumberValue;
use App\Data\Tiles\SuitAssignment;
use App\Data\Tiles\WindTile;
use App\Enums\NumberDomain;
use App\Enums\Suit;
use App\Enums\VariableKind;
use App\Enums\Wind;

/**
 * Build a hand of three kongs — two suited, one of dragons — and a pair of flowers.
 *
 * Fourteen tiles across three suits, with the pair last, so a single fixture
 * exercises every tag the panel can print.
 */
function sampleStructure(): HandStructure
{
    return new HandStructure(
        groups: [
            new TileGroup(array_fill(0, 4, new NumberTile('A', NumberValue::literal(1)))),
            new TileGroup(array_fill(0, 4, new NumberTile('B', NumberValue::literal(1)))),
            new TileGroup(array_fill(0, 4, new DragonTile('C'))),
            new TileGroup([new FlowerTile, new FlowerTile]),
        ],
        variables: [
            'A' => new HandVariable(VariableKind::Suit),
            'B' => new HandVariable(VariableKind::Suit),
            'C' => new HandVariable(VariableKind::Suit),
        ],
    );
}

/**
 * Get a reading's tag labels.
 *
 * @return list<string>
 */
function tagLabels(HandReading $reading): array
{
    return array_map(fn (RuleTag $tag): string => $tag->label, $reading->tags);
}

test('a hand reads as one group per line of the card', function () {
    $reading = HandReading::for(sampleStructure(), SuitAssignment::none(), concealed: false);

    expect($reading->groups)->toHaveCount(4)
        ->and($reading->groups[0]->label)->toBe('Kong')
        ->and($reading->groups[3]->label)->toBe('Pair');
});

test('the summary strings the group readings into one sentence', function () {
    $reading = HandReading::for(sampleStructure(), SuitAssignment::none(), concealed: false);

    expect($reading->summary)->toBe(
        'Kong of 1s in suit A, kong of 1s in suit B, kong of dragons in suit C, and pair of flowers.'
    );
});

test('a one-group hand needs no conjunction', function () {
    $structure = new HandStructure(
        groups: [new TileGroup(array_fill(0, 14, new NumberTile('A', NumberValue::literal(5))))],
        variables: ['A' => new HandVariable(VariableKind::Suit)],
    );

    expect(HandReading::for($structure, SuitAssignment::none(), concealed: false)->summary)
        ->toBe('14 tiles of 5s in suit A.');
});

test('the concealed tag says what the flag actually costs', function () {
    expect(tagLabels(HandReading::for(sampleStructure(), SuitAssignment::none(), concealed: true)))
        ->toContain('Concealed — no exposures')
        ->and(tagLabels(HandReading::for(sampleStructure(), SuitAssignment::none(), concealed: false)))
        ->toContain('May be exposed');
});

test('the suit tag counts the suits and says they must differ', function () {
    expect(tagLabels(HandReading::for(sampleStructure(), SuitAssignment::none(), concealed: false)))
        ->toContain('3 suits, all different');
});

test('a single-suit hand says suit rather than suits', function () {
    $structure = new HandStructure(
        groups: [
            new TileGroup(array_fill(0, 6, new NumberTile('A', NumberValue::literal(2)))),
            new TileGroup(array_fill(0, 6, new NumberTile('A', NumberValue::literal(4)))),
            new TileGroup([new FlowerTile, new FlowerTile]),
        ],
        variables: ['A' => new HandVariable(VariableKind::Suit)],
    );

    expect(tagLabels(HandReading::for($structure, SuitAssignment::none(), concealed: false)))
        ->toContain('1 suit');
});

test('the joker tag counts the tiles jokers could actually cover', function () {
    expect(tagLabels(HandReading::for(sampleStructure(), SuitAssignment::none(), concealed: false)))
        ->toContain('Jokers OK — up to 12');
});

test('a hand with no eligible group says so outright', function () {
    $structure = new HandStructure(
        groups: [
            new TileGroup([
                new WindTile(Wind::North),
                new WindTile(Wind::East),
                new WindTile(Wind::West),
                new WindTile(Wind::South),
            ]),
            new TileGroup(array_map(
                fn (int $offset): NumberTile => new NumberTile('A', NumberValue::variable('T', $offset)),
                range(0, 4),
            )),
            new TileGroup(array_map(
                fn (int $offset): NumberTile => new NumberTile('B', NumberValue::variable('T', $offset)),
                range(0, 4),
            )),
        ],
        variables: [
            'A' => new HandVariable(VariableKind::Suit),
            'B' => new HandVariable(VariableKind::Suit),
            'T' => new HandVariable(VariableKind::Number, NumberDomain::Any),
        ],
    );

    expect(tagLabels(HandReading::for($structure, SuitAssignment::none(), concealed: false)))
        ->toContain('No jokers anywhere');
});

test('the pair and flower tags appear only when the hand has them', function () {
    $withPair = tagLabels(HandReading::for(sampleStructure(), SuitAssignment::none(), concealed: false));

    expect($withPair)
        ->toContain('Contains a pair — no jokers there')
        ->and($withPair)->toContain('Flowers are interchangeable');

    $structure = new HandStructure(
        groups: [
            new TileGroup(array_fill(0, 7, new NumberTile('A', NumberValue::literal(1)))),
            new TileGroup(array_fill(0, 7, new NumberTile('B', NumberValue::literal(1)))),
        ],
        variables: [
            'A' => new HandVariable(VariableKind::Suit),
            'B' => new HandVariable(VariableKind::Suit),
        ],
    );

    $without = tagLabels(HandReading::for($structure, SuitAssignment::none(), concealed: false));

    expect($without)
        ->not->toContain('Contains a pair — no jokers there')
        ->and($without)->not->toContain('Flowers are interchangeable');
});

test('binding suits rewrites the whole summary', function () {
    $reading = HandReading::for(
        sampleStructure(),
        SuitAssignment::of(['A' => Suit::Dots, 'B' => Suit::Bams, 'C' => Suit::Craks]),
        concealed: false,
    );

    expect($reading->summary)->toBe(
        'Kong of 1s in Dots, kong of 1s in Bams, kong of red dragons, and pair of flowers.'
    );
});

test('the suit variables a hand waits on are the ones it can bind', function () {
    $reading = HandReading::for(sampleStructure(), SuitAssignment::none(), concealed: false);

    expect($reading->suitVariables)->toBe(['A', 'B', 'C']);
});

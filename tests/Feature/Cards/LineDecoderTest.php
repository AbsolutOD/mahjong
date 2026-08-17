<?php

use App\Data\HandStructure;
use App\Data\HandVariable;
use App\Data\TileGroup;
use App\Data\Tiles\DragonTile;
use App\Data\Tiles\FlowerTile;
use App\Data\Tiles\NumberTile;
use App\Data\Tiles\NumberValue;
use App\Enums\Suit;
use App\Enums\VariableKind;
use App\Models\Card;
use App\Models\Category;
use App\Models\Hand;
use Livewire\Livewire;

/**
 * Build a card with two categories, so switching between them is testable.
 */
function decoderCard(): Card
{
    $card = Card::factory()->create(['name' => 'Practice Card']);

    $year = Category::factory()->for($card)->create(['name' => 'Year', 'sort_order' => 1]);
    $evens = Category::factory()->for($card)->create(['name' => 'Evens', 'sort_order' => 2]);

    Hand::factory()->for($card)->for($year)->create([
        'sort_order' => 1,
        'points' => 25,
        'concealed' => false,
        'structure' => threeKongHand(),
    ]);

    Hand::factory()->for($card)->for($evens)->create([
        'sort_order' => 2,
        'points' => 30,
        'concealed' => true,
        'structure' => threeKongHand(),
    ]);

    return $card;
}

/**
 * Build a hand of two suited kongs, a kong of dragons and a pair of flowers.
 */
function threeKongHand(): HandStructure
{
    return new HandStructure(
        groups: [
            new TileGroup(array_fill(0, 4, new NumberTile('A', NumberValue::literal(2)))),
            new TileGroup(array_fill(0, 4, new NumberTile('B', NumberValue::literal(2)))),
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

test('the decoder is public, because learning the card needs no account', function () {
    decoderCard();

    $this->get(route('card'))->assertOk();
});

test('the decoder lists the card categories and the selected one is hands', function () {
    decoderCard();

    Livewire::test('pages::card.line-decoder')
        ->assertSee('Year')
        ->assertSee('Evens')
        ->assertSee('2222(A) 2222(B) DDDD(C) FF');
});

test('the first category and its first hand are selected on arrival', function () {
    $card = decoderCard();

    Livewire::test('pages::card.line-decoder')
        ->assertSet('categorySlug', $card->categories->first()->slug)
        ->assertSet('handSlug', $card->categories->first()->hands->first()->slug);
});

test('selection lives in the url so a line can be linked to', function () {
    $card = decoderCard();
    $evens = $card->categories->last();

    Livewire::withUrlParams([
        'category' => $evens->slug,
        'hand' => $evens->hands->first()->slug,
    ])
        ->test('pages::card.line-decoder')
        ->assertSet('categorySlug', $evens->slug)
        ->assertSee('Concealed — no exposures');
});

test('a link to a line the card no longer prints falls back instead of showing a different line', function () {
    $card = decoderCard();
    $year = $card->categories->first();

    Livewire::withUrlParams([
        'category' => $year->slug,
        'hand' => 'a-line-this-card-does-not-print',
    ])
        ->test('pages::card.line-decoder')
        ->assertSet('handSlug', $year->hands->first()->slug);
});

test('choosing a category moves the selection to its first hand', function () {
    $card = decoderCard();
    $evens = $card->categories->last();

    Livewire::test('pages::card.line-decoder')
        ->call('selectCategory', $evens->slug)
        ->assertSet('categorySlug', $evens->slug)
        ->assertSet('handSlug', $evens->hands->first()->slug);
});

test('a hand stays selected when clicked again, because the panel is the state', function () {
    $card = decoderCard();
    $hand = $card->categories->first()->hands->first();

    Livewire::test('pages::card.line-decoder')
        ->call('selectHand', $hand->slug)
        ->assertSet('handSlug', $hand->slug);
});

test('the breakdown panel explains the selected hand in plain english', function () {
    decoderCard();

    Livewire::test('pages::card.line-decoder')
        ->assertSee('Kong of 2s in suit A')
        ->assertSee('Kong of dragons in suit C')
        ->assertSee('Pair of flowers')
        ->assertSee('Jokers OK — up to 12')
        ->assertSee('3 suits, all different');
});

test('binding a variable to a suit rewrites the tiles and the prose', function () {
    decoderCard();

    Livewire::test('pages::card.line-decoder')
        ->assertSee('Kong of dragons in suit C')
        ->call('assign', 'C', Suit::Bams->value)
        ->assertSet('suits.C', Suit::Bams->value)
        ->assertSee('Kong of green dragons')
        ->assertDontSee('Kong of dragons in suit C');
});

test('binding the suit a neighbour holds moves it rather than duplicating it', function () {
    decoderCard();

    Livewire::test('pages::card.line-decoder')
        ->call('assign', 'A', Suit::Dots->value)
        ->call('assign', 'B', Suit::Dots->value)
        ->assertSet('suits.B', Suit::Dots->value)
        ->assertSet('suits.A', null)
        ->assertSee('Kong of 2s in Dots')
        ->assertSee('Kong of 2s in suit A');
});

test('binding the suit a variable already holds releases it', function () {
    decoderCard();

    Livewire::test('pages::card.line-decoder')
        ->call('assign', 'A', Suit::Dots->value)
        ->call('assign', 'A', Suit::Dots->value)
        ->assertSet('suits.A', null)
        ->assertSee('Kong of 2s in suit A');
});

test('the suits reset together', function () {
    decoderCard();

    Livewire::test('pages::card.line-decoder')
        ->call('assign', 'A', Suit::Dots->value)
        ->call('assign', 'B', Suit::Bams->value)
        ->call('clearSuits')
        ->assertSet('suits', [])
        ->assertSee('Kong of 2s in suit A');
});

test('the decoder says what to do when no card is seeded', function () {
    Livewire::test('pages::card.line-decoder')
        ->assertSee('No card is loaded');
});

test('the markup clicks through by slug, so no row id reaches the page', function () {
    $year = decoderCard()->categories->first();

    Livewire::test('pages::card.line-decoder')
        ->assertSeeHtml("selectCategory('{$year->slug}')")
        ->assertSeeHtml("selectHand('{$year->hands->first()->slug}')");
});

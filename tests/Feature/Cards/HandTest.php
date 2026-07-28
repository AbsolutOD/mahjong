<?php

use App\Data\HandStructure;
use App\Enums\Variant;
use App\Models\Card;
use App\Models\Category;
use App\Models\Hand;

test('a card is stored with its variant and year', function () {
    $card = Card::factory()->create(['name' => 'Practice Card', 'year' => 2026]);

    expect($card->variant)->toBe(Variant::American)
        ->and($card->year)->toBe(2026)
        ->and($card->published_at)->not->toBeNull();
});

test('a card holds its categories in card order', function () {
    $card = Card::factory()->create();

    Category::factory()->for($card)->create(['name' => 'Winds and Dragons', 'sort_order' => 2]);
    Category::factory()->for($card)->create(['name' => 'Year', 'sort_order' => 1]);

    expect($card->categories->pluck('name')->all())->toBe(['Year', 'Winds and Dragons']);
});

test('a category belongs to one card, so two cards may name a section alike', function () {
    $firstCategory = Category::factory()->create(['name' => 'Consecutive Run']);
    $secondCategory = Category::factory()->create(['name' => 'Consecutive Run']);

    expect($firstCategory->card->isNot($secondCategory->card))->toBeTrue();
});

test('a hand belongs to a card and a category', function () {
    $category = Category::factory()->create();
    $hand = Hand::factory()->for($category)->for($category->card)->create();

    expect($hand->category->is($category))->toBeTrue()
        ->and($hand->card->is($category->card))->toBeTrue();
});

test('a card holds its hands in card order', function () {
    $card = Card::factory()->create();
    $category = Category::factory()->for($card)->create();

    Hand::factory()->for($card)->for($category)->create(['sort_order' => 2, 'points' => 30]);
    Hand::factory()->for($card)->for($category)->create(['sort_order' => 1, 'points' => 25]);

    expect($card->hands->pluck('points')->all())->toBe([25, 30]);
});

test('a hand structure round-trips through the database as a value object', function () {
    $hand = Hand::factory()->create();

    $structure = $hand->fresh()->structure;

    expect($structure)->toBeInstanceOf(HandStructure::class)
        ->and($structure->tileCount())->toBe(14)
        ->and($structure->toArray())->toBe($hand->structure->toArray());
});

test('a hand derives its card facts from the stored structure rather than columns', function () {
    $hand = Hand::factory()->create()->fresh();

    expect($hand->structure->suitCount())->toBe(2)
        ->and($hand->structure->maxJokers())->toBe(12)
        ->and($hand->structure->usesFlowers())->toBeTrue();
});

test('a concealed hand is stored as a boolean', function () {
    $hand = Hand::factory()->concealed()->create();

    expect($hand->fresh()->concealed)->toBeTrue()
        ->and(Hand::factory()->create()->fresh()->concealed)->toBeFalse();
});

test('deleting a card takes its categories and hands with it', function () {
    $card = Card::factory()->create();
    $category = Category::factory()->for($card)->create();
    Hand::factory()->for($card)->for($category)->create();

    $card->delete();

    expect(Category::count())->toBe(0)
        ->and(Hand::count())->toBe(0);
});

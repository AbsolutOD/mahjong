<?php

use App\Actions\Cards\LoadCurrentCard;
use App\Data\HandStructure;
use App\Models\Card;
use App\Models\Category;
use App\Models\Hand;
use Illuminate\Support\Facades\DB;

/**
 * Build a card of one section and two lines, stamped with the year it is for.
 */
function cardOfYear(int $year): Card
{
    $card = Card::factory()->create(['name' => "Card of {$year}", 'year' => $year]);

    $category = Category::factory()->for($card)->create([
        'name' => 'Evens',
        'slug' => 'evens',
        'sort_order' => 1,
    ]);

    Hand::factory()->for($card)->for($category)->count(2)->sequence(
        ['slug' => 'first', 'sort_order' => 1],
        ['slug' => 'second', 'sort_order' => 2],
    )->create();

    return $card;
}

test('the card comes back whole, with its sections and lines in printed order', function () {
    cardOfYear(2026);

    $card = app(LoadCurrentCard::class)->handle();

    expect($card?->name)->toBe('Card of 2026')
        ->and($card->categories->pluck('slug')->all())->toBe(['evens'])
        ->and($card->categories->first()->hands->pluck('slug')->all())->toBe(['first', 'second']);
});

test('the newest card is the one taught, because a new year replaces the last', function () {
    cardOfYear(2025);
    cardOfYear(2026);

    expect(app(LoadCurrentCard::class)->handle()?->name)->toBe('Card of 2026');
});

test('an unseeded database reads as no card at all, rather than failing', function () {
    expect(app(LoadCurrentCard::class)->handle())->toBeNull();
});

test('the card is queried once and served from the cache after that', function () {
    cardOfYear(2026);

    app(LoadCurrentCard::class)->handle();

    DB::enableQueryLog();

    $card = app(LoadCurrentCard::class)->handle();

    expect(DB::getQueryLog())->toBeEmpty()
        ->and($card?->categories->first()->hands)->toHaveCount(2);
});

/**
 * Laravel will not unserialize objects out of the cache, so the rows are stored
 * flat. What comes back still has to be a hand, tiles and all.
 */
test('lines come back out of the cache as hands, not as raw rows', function () {
    cardOfYear(2026);

    app(LoadCurrentCard::class)->handle();

    $hand = app(LoadCurrentCard::class)->handle()?->categories->first()->hands->first();

    expect($hand)->toBeInstanceOf(Hand::class)
        ->and($hand->structure)->toBeInstanceOf(HandStructure::class)
        ->and($hand->structure->tileCount())->toBe(HandStructure::HAND_SIZE)
        ->and($hand->concealed)->toBeFalse()
        ->and($hand->exists)->toBeTrue();
});

test('clearing the cache is what publishes a reseeded card', function () {
    cardOfYear(2026);

    app(LoadCurrentCard::class)->handle();

    cardOfYear(2027);

    expect(app(LoadCurrentCard::class)->handle()?->name)->toBe('Card of 2026');

    $this->artisan('cache:clear')->assertSuccessful();

    expect(app(LoadCurrentCard::class)->handle()?->name)->toBe('Card of 2027');
});

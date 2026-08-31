<?php

use App\Actions\Cards\LoadCurrentCard;
use App\Mahjong\LineRenderer;
use App\Models\Card;
use App\Models\Hand;
use Database\Seeders\CardSeeder;

test('every authored card file seeds every hand it holds', function () {
    $authored = collect(glob(database_path('cards/*.json')))
        ->map(fn (string $path): array => json_decode(file_get_contents($path), associative: true));

    $this->seed(CardSeeder::class);

    $authoredHands = $authored
        ->flatMap(fn (array $card): array => $card['categories'])
        ->sum(fn (array $category): int => count($category['hands']));

    expect($authored)->not->toBeEmpty()
        ->and(Card::count())->toBe($authored->count())
        ->and(Hand::count())->toBe($authoredHands);
});

test('every seeded hand renders back to a line the card could print', function () {
    $this->seed(CardSeeder::class);

    $renderer = new LineRenderer;

    Hand::each(function (Hand $hand) use ($renderer) {
        expect($renderer->render($hand->structure))->not->toBeEmpty();
    });
});

test('the practice card seeds its categories in card order', function () {
    $this->seed(CardSeeder::class);

    $card = Card::firstOrFail();

    expect($card->categories->pluck('sort_order')->all())
        ->toBe(range(1, $card->categories->count()));
});

test('seeding twice leaves one copy of each card', function () {
    $this->seed(CardSeeder::class);
    $handCount = Hand::count();

    $this->seed(CardSeeder::class);

    expect(Card::count())->toBe(1)
        ->and(Hand::count())->toBe($handCount);
});

/**
 * The card is cached forever, so something has to drop it when the card
 * changes. Leaving that to the release command's separate `cache:clear` lets
 * the two drift: a seed run any other way — by hand in a console, which is how
 * production was first seeded — would publish nothing.
 */
test('seeding publishes the card it imports, without waiting for a separate cache clear', function () {
    Card::factory()->create(['name' => 'Last season', 'year' => 2025]);

    expect(app(LoadCurrentCard::class)->handle()?->name)->toBe('Last season');

    $this->seed(CardSeeder::class);

    expect(app(LoadCurrentCard::class)->handle()?->name)->toBe('TileTutor Practice Card');
});

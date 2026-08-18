<?php

namespace App\Actions\Cards;

use App\Models\Card;
use App\Models\Category;
use App\Models\Hand;
use Illuminate\Support\Facades\Cache;

/**
 * The card the app is teaching, read once and kept in the cache.
 *
 * A card only changes when a deploy reseeds it, so querying it on every page
 * view spends three round trips to learn nothing new. It is cached without a
 * TTL instead, which makes deploying the invalidation: the release command
 * clears the cache after it reseeds.
 *
 * Only plain arrays reach the cache. Laravel refuses by default to unserialize
 * objects stored in the cache — see `serializable_classes` in config/cache.php
 * — so the rows are kept as the database handed them over and made models
 * again on the way out.
 *
 * @phpstan-type CardRows array{
 *     card: array<string, mixed>|null,
 *     categories: list<array{
 *         category: array<string, mixed>,
 *         hands: list<array<string, mixed>>,
 *     }>,
 * }
 */
class LoadCurrentCard
{
    public const string CACHE_KEY = 'cards.current';

    /**
     * Get the newest card, with its sections and lines already loaded.
     */
    public function handle(): ?Card
    {
        $rows = Cache::rememberForever(self::CACHE_KEY, $this->read(...));

        if ($rows['card'] === null) {
            return null;
        }

        $card = (new Card)->newFromBuilder($rows['card']);

        $card->setRelation('categories', collect($rows['categories'])->map(
            function (array $row): Category {
                $category = (new Category)->newFromBuilder($row['category']);
                $category->setRelation('hands', Hand::hydrate($row['hands']));

                return $category;
            },
        ));

        return $card;
    }

    /**
     * Read the whole card out of the database, in the order it prints.
     *
     * @return CardRows
     */
    private function read(): array
    {
        $card = Card::query()->with('categories.hands')->latest('year')->first();

        if ($card === null) {
            return ['card' => null, 'categories' => []];
        }

        return [
            'card' => $card->getAttributes(),
            'categories' => array_map(
                fn (Category $category): array => [
                    'category' => $category->getAttributes(),
                    'hands' => array_map(
                        fn (Hand $hand): array => $hand->getAttributes(),
                        array_values($category->hands->all()),
                    ),
                ],
                array_values($card->categories->all()),
            ),
        ];
    }
}

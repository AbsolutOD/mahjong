<?php

namespace App\Actions\Cards;

use App\Data\HandStructure;
use App\Enums\Variant;
use App\Exceptions\SeedMismatch;
use App\Mahjong\LineRenderer;
use App\Mahjong\Slug;
use App\Models\Card;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

/**
 * Hydrates the database from a card's authoring file.
 *
 * The authoring file is the human-editable source; the app only ever reads the
 * database, so every card — built-in or imported later — arrives through here.
 *
 * @phpstan-type AuthoredHand array{line: string, points: int, concealed?: bool, variables: array<string, array{kind: string, domain?: string|list<int>}>, constraints?: list<array<string, mixed>>, groups: list<list<array<string, mixed>>>}
 * @phpstan-type AuthoredCategory array{name: string, hands: list<AuthoredHand>}
 */
class ImportCard
{
    public function __construct(private LineRenderer $renderer) {}

    /**
     * Import a card, replacing any card already published for that variant and year.
     *
     * @param  array{name: string, variant: string, year: int, categories: list<AuthoredCategory>}  $data
     */
    public function handle(array $data): Card
    {
        return DB::transaction(function () use ($data): Card {
            Card::query()
                ->where('variant', $data['variant'])
                ->where('year', $data['year'])
                ->where('name', $data['name'])
                ->delete();

            $card = Card::create([
                'name' => $data['name'],
                'variant' => Variant::from($data['variant']),
                'year' => $data['year'],
                'published_at' => now(),
            ]);

            $slugs = new Slug;

            foreach ($data['categories'] as $categoryOrder => $authoredCategory) {
                $this->importCategory($card, $authoredCategory, $categoryOrder + 1, $slugs);
            }

            return $card->load(['categories', 'hands']);
        });
    }

    /**
     * Import one category of the card and every hand printed under it.
     *
     * @param  AuthoredCategory  $data
     */
    private function importCategory(Card $card, array $data, int $sortOrder, Slug $slugs): void
    {
        $category = $card->categories()->create([
            'name' => $data['name'],
            'slug' => $slugs->forCategory($data['name']),
            'sort_order' => $sortOrder,
        ]);

        foreach ($data['hands'] as $handOrder => $authoredHand) {
            $this->importHand($card, $category, $authoredHand, $handOrder + 1, $slugs);
        }
    }

    /**
     * Import one hand, checking it renders to the line written beside it.
     *
     * @param  AuthoredHand  $data
     */
    private function importHand(Card $card, Category $category, array $data, int $sortOrder, Slug $slugs): void
    {
        $structure = HandStructure::fromArray($data);
        $rendered = $this->renderer->render($structure);

        throw_unless(
            $rendered === $data['line'],
            SeedMismatch::forHand($category->name, $data['line'], $rendered),
        );

        $concealed = $data['concealed'] ?? false;

        $card->hands()->create([
            'category_id' => $category->id,
            'slug' => $slugs->forLine($rendered, $concealed),
            'sort_order' => $sortOrder,
            'points' => $data['points'],
            'concealed' => $concealed,
            'structure' => $structure,
        ]);
    }
}

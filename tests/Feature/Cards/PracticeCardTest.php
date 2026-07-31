<?php

use App\Data\HandStructure;
use App\Data\Tiles\DragonTile;
use App\Data\Tiles\FlowerTile;
use App\Data\Tiles\NumberTile;
use App\Data\Tiles\TileSpec;
use App\Data\Tiles\WindTile;
use App\Data\Tiles\ZeroTile;
use App\Enums\Dragon;
use App\Enums\Suit;
use App\Enums\VariableKind;
use App\Mahjong\AmericanMahjong;
use App\Mahjong\LineRenderer;
use App\Models\Card;
use App\Models\Hand;
use Database\Seeders\CardSeeder;

/**
 * Works out whether a hand could actually be assembled from the tiles the game
 * has, which is the one thing the schema's own checks cannot tell you.
 *
 * A hand can hold fourteen tiles, name only declared variables and render to
 * the line beside it, and still be unbuildable — `2222(A) 2222(A)` asks for
 * eight copies of a tile that exists four times. A hand counts as assemblable
 * when at least one assignment of its variables works, because the card offers
 * the player that choice.
 */
final class TileDemand
{
    /**
     * Determine whether some assignment of the hand's variables can be built.
     */
    public static function isAssemblable(HandStructure $structure): bool
    {
        foreach (self::assignments($structure) as $assignment) {
            if (self::fits($structure, $assignment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get every assignment of the hand's variables its constraints allow.
     *
     * @return list<array<string, Suit|int>>
     */
    private static function assignments(HandStructure $structure): array
    {
        $assignments = [[]];

        foreach ($structure->variables as $name => $variable) {
            $values = $variable->kind === VariableKind::Suit
                ? Suit::cases()
                : array_values(array_filter(range(1, 9), $variable->allows(...)));

            $expanded = [];

            foreach ($assignments as $partial) {
                foreach ($values as $value) {
                    $expanded[] = $partial + [$name => $value];
                }
            }

            $assignments = $expanded;
        }

        return array_values(array_filter(
            $assignments,
            fn (array $assignment): bool => collect($structure->constraints)
                ->every(fn ($constraint): bool => $constraint->isSatisfiedBy($assignment)),
        ));
    }

    /**
     * Determine whether one concrete assignment fits inside the tile set.
     *
     * Groups that take no jokers are drawn first, since they have no other way
     * to be filled; the rest then take real tiles wherever any are left, which
     * is what makes the leftover joker count the smallest the hand could need.
     *
     * @param  array<string, Suit|int>  $assignment
     */
    private static function fits(HandStructure $structure, array $assignment): bool
    {
        $inventory = AmericanMahjong::tileInventory();
        $jokersAvailable = $inventory['joker'];
        $jokerEligible = [];

        foreach ($structure->groups as $group) {
            $codes = [];

            foreach ($group->tiles as $tile) {
                $code = self::code($tile, $assignment);

                if ($code === null) {
                    return false;
                }

                $codes[] = $code;
            }

            if ($group->acceptsJokers()) {
                $jokerEligible[] = $codes;

                continue;
            }

            foreach ($codes as $code) {
                if (--$inventory[$code] < 0) {
                    return false;
                }
            }
        }

        $jokersNeeded = 0;

        foreach ($jokerEligible as $codes) {
            foreach ($codes as $code) {
                if ($inventory[$code] > 0) {
                    $inventory[$code]--;

                    continue;
                }

                $jokersNeeded++;
            }
        }

        return $jokersNeeded <= $jokersAvailable;
    }

    /**
     * Get the inventory code for the physical tile this spec resolves to.
     *
     * Returns null when a run point falls off either end of a suit, which is
     * not an error but simply an assignment the hand cannot be played on.
     *
     * @param  array<string, Suit|int>  $assignment
     */
    private static function code(TileSpec $tile, array $assignment): ?string
    {
        return match (true) {
            $tile instanceof FlowerTile => 'flower',
            $tile instanceof ZeroTile => 'dragon-'.Dragon::White->value,
            $tile instanceof WindTile => 'wind-'.$tile->wind->value,
            $tile instanceof DragonTile => 'dragon-'.AmericanMahjong::dragonForSuit($assignment[$tile->suit])->value,
            $tile instanceof NumberTile => self::numberCode($tile, $assignment),
        };
    }

    /**
     * Get the inventory code for a suited number tile under this assignment.
     *
     * @param  array<string, Suit|int>  $assignment
     */
    private static function numberCode(NumberTile $tile, array $assignment): ?string
    {
        $number = $tile->number->isVariable()
            ? $assignment[$tile->number->variable] + $tile->number->offset
            : $tile->number->literal;

        return $number >= 1 && $number <= 9
            ? $assignment[$tile->suit]->value.'-'.$number
            : null;
    }
}

test('the practice card ships nine sections, each holding hands', function () {
    $this->seed(CardSeeder::class);

    $card = Card::with('categories.hands')->firstOrFail();

    expect($card->name)->toBe('TileTutor Practice Card')
        ->and($card->categories)->toHaveCount(9)
        ->and($card->hands)->toHaveCount(50);

    $card->categories->each(function ($category) {
        expect($category->hands)->not->toBeEmpty();
    });
});

test('every hand can be assembled from the 152-tile set', function () {
    $this->seed(CardSeeder::class);

    $renderer = new LineRenderer;

    Hand::each(function (Hand $hand) use ($renderer) {
        expect(TileDemand::isAssemblable($hand->structure))
            ->toBeTrue('No assignment of ['.$renderer->render($hand->structure).'] fits the tile set.');
    });
});

test('no two hands print the same line', function () {
    $this->seed(CardSeeder::class);

    $renderer = new LineRenderer;

    $lines = Hand::all()->map(fn (Hand $hand): string => $renderer->render($hand->structure));

    expect($lines->duplicates()->all())->toBe([]);
});

test('every number variable stays on the card across its whole domain', function () {
    $this->seed(CardSeeder::class);

    Hand::each(function (Hand $hand) {
        foreach ($hand->structure->tiles() as $tile) {
            if (! $tile instanceof NumberTile || ! $tile->number->isVariable()) {
                continue;
            }

            $variable = $hand->structure->variables[$tile->number->variable];
            $allowed = array_values(array_filter(range(1, 9), $variable->allows(...)));

            expect($allowed)->not->toBeEmpty();

            foreach ($allowed as $value) {
                expect($value + $tile->number->offset)->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(9);
            }
        }
    });
});

test('the check rejects hands the tile set cannot supply', function (array $groups) {
    $structure = HandStructure::fromArray([
        'variables' => ['A' => ['kind' => 'suit']],
        'groups' => $groups,
    ]);

    expect(TileDemand::isAssemblable($structure))->toBeFalse();
})->with([
    'ten copies of a tile that exists four times, in pairs no joker may fill' => [[
        [['t' => 'flower'], ['t' => 'flower'], ['t' => 'flower'], ['t' => 'flower']],
        ...array_fill(0, 5, [
            ['t' => 'num', 'suit' => 'A', 'n' => 2],
            ['t' => 'num', 'suit' => 'A', 'n' => 2],
        ]),
    ]],
    'three kongs of one tile, needing more jokers than the set holds' => [[
        ...array_fill(0, 3, array_fill(0, 4, ['t' => 'num', 'suit' => 'A', 'n' => 1])),
        [['t' => 'num', 'suit' => 'A', 'n' => 1], ['t' => 'num', 'suit' => 'A', 'n' => 1]],
    ]],
]);

test('singles and pairs hands are concealed and take no jokers', function () {
    $this->seed(CardSeeder::class);

    $hands = Hand::query()
        ->whereRelation('category', 'name', 'Singles and Pairs')
        ->get();

    expect($hands)->not->toBeEmpty();

    $hands->each(function (Hand $hand) {
        expect($hand->concealed)->toBeTrue()
            ->and($hand->structure->maxJokers())->toBe(0);
    });
});

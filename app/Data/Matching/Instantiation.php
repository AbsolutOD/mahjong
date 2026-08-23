<?php

namespace App\Data\Matching;

use App\Data\HandStructure;
use App\Data\TileGroup;
use App\Data\Tiles\DragonTile;
use App\Data\Tiles\NumberTile;
use App\Data\Tiles\SuitAssignment;
use App\Data\Tiles\Tile;
use App\Data\Tiles\TileSpec;
use App\Data\Tiles\WindTile;
use App\Data\Tiles\ZeroTile;
use App\Enums\Dragon;
use App\Enums\Suit;
use App\Enums\VariableKind;
use App\Mahjong\AmericanMahjong;

/**
 * One card line with every variable settled — a hand you could actually build.
 *
 * A {@see HandStructure} describes a family of hands: `FFFF XXXX(A) XXXX(B)`
 * is a different fourteen tiles for every suit pairing and every value of X.
 * An instantiation is one member of that family, so its tiles are physical
 * {@see Tile}s rather than card slots, and matching a rack against it is
 * multiset arithmetic.
 *
 * The whole family is small enough to enumerate exhaustively — 818 members
 * across the practice card, worst hand 54 — which is why the matcher needs no
 * heuristic and no cache (issue #15).
 */
readonly class Instantiation
{
    /**
     * @param  array<string, int>  $numbers  the value each number variable took
     * @param  list<list<Tile>>  $groups  concrete tiles, parallel to the structure's groups
     * @param  list<bool>  $acceptsJokers  whether each group may be filled with jokers
     * @param  array<string, string>  $bindings  every variable read out, in the order the hand declares them
     */
    private function __construct(
        public SuitAssignment $suits,
        public array $numbers,
        public array $groups,
        public array $acceptsJokers,
        public array $bindings,
    ) {
        //
    }

    /**
     * Get every hand the given card line could turn into.
     *
     * Members that fall outside the game are dropped rather than ranked: a run
     * that steps off the end of a suit, or a hand that would need more copies
     * of a tile than the set holds, is not a hand the player could ever reach.
     *
     * @return list<self>
     */
    public static function forStructure(HandStructure $structure): array
    {
        $instantiations = [];

        foreach (self::assignments($structure) as $assignment) {
            $instantiation = self::resolve($structure, $assignment);

            if ($instantiation !== null && $instantiation->isSupplyFeasible()) {
                $instantiations[] = $instantiation;
            }
        }

        return $instantiations;
    }

    /**
     * Get every tile the hand asks for, in card order.
     *
     * @return list<Tile>
     */
    public function tiles(): array
    {
        return array_merge(...$this->groups);
    }

    /**
     * Determine whether the 152-tile set could supply this hand.
     *
     * Groups no joker may fill are drawn first, since they have no other way to
     * be filled; what is left over then decides how many jokers the hand would
     * need, which is the fewest it could possibly need.
     */
    public function isSupplyFeasible(): bool
    {
        $inventory = AmericanMahjong::tileInventory();
        $jokersAvailable = $inventory['joker'];

        foreach ($this->groups as $index => $tiles) {
            if ($this->acceptsJokers[$index]) {
                continue;
            }

            foreach ($tiles as $tile) {
                if (--$inventory[$tile->code()] < 0) {
                    return false;
                }
            }
        }

        $jokersNeeded = 0;

        foreach ($this->groups as $index => $tiles) {
            if (! $this->acceptsJokers[$index]) {
                continue;
            }

            foreach ($tiles as $tile) {
                if ($inventory[$tile->code()] > 0) {
                    $inventory[$tile->code()]--;

                    continue;
                }

                $jokersNeeded++;
            }
        }

        return $jokersNeeded <= $jokersAvailable;
    }

    /**
     * Get every assignment of the hand's variables the game allows.
     *
     * Suit variables are enumerated injectively rather than freely: the card's
     * colours mean the groups take *different* suits, which is why
     * {@see SuitAssignment} refuses two variables on one suit at all.
     *
     * @return list<VariableAssignment>
     */
    private static function assignments(HandStructure $structure): array
    {
        $assignments = [VariableAssignment::none()];

        foreach ($structure->variables as $name => $variable) {
            $values = $variable->kind === VariableKind::Suit
                ? Suit::cases()
                : array_values(array_filter(range(Tile::MINIMUM_NUMBER, Tile::MAXIMUM_NUMBER), $variable->allows(...)));

            $expanded = [];

            foreach ($assignments as $partial) {
                foreach ($values as $value) {
                    if ($value instanceof Suit && $partial->holds($value)) {
                        continue;
                    }

                    $expanded[] = $partial->with($name, $value);
                }
            }

            $assignments = $expanded;
        }

        return array_values(array_filter(
            $assignments,
            fn (VariableAssignment $assignment): bool => array_all(
                $structure->constraints,
                fn ($constraint): bool => $constraint->isSatisfiedBy($assignment->values),
            ),
        ));
    }

    /**
     * Settle every slot of the hand under one assignment.
     */
    private static function resolve(HandStructure $structure, VariableAssignment $assignment): ?self
    {
        $groups = [];

        foreach ($structure->groups as $group) {
            $tiles = [];

            foreach ($group->tiles as $spec) {
                $tile = self::tile($spec, $assignment);

                if ($tile === null) {
                    return null;
                }

                $tiles[] = $tile;
            }

            $groups[] = $tiles;
        }

        return new self(
            SuitAssignment::of($assignment->suits),
            $assignment->numbers,
            $groups,
            array_map(fn (TileGroup $group): bool => $group->acceptsJokers(), $structure->groups),
            $assignment->bindings(),
        );
    }

    /**
     * Get the physical tile a slot settles on, or null if it settles outside the game.
     *
     * A run point can be pushed off either end of a suit — X+2 with X assigned
     * 8 asks for a 10 — which is not an error but an assignment the hand simply
     * cannot be played on.
     */
    private static function tile(TileSpec $spec, VariableAssignment $assignment): ?Tile
    {
        return match (true) {
            $spec instanceof ZeroTile => Tile::dragon(Dragon::White),
            $spec instanceof WindTile => Tile::wind($spec->wind),
            $spec instanceof DragonTile => Tile::dragon(AmericanMahjong::dragonForSuit($assignment->suit($spec->suit))),
            $spec instanceof NumberTile => self::numberTile($spec, $assignment),
            default => Tile::flower(),
        };
    }

    /**
     * Get the physical tile a suited number slot settles on.
     */
    private static function numberTile(NumberTile $spec, VariableAssignment $assignment): ?Tile
    {
        $number = $spec->number->isVariable()
            ? $assignment->number($spec->number->variable) + $spec->number->offset
            : $spec->number->literal;

        return $number >= Tile::MINIMUM_NUMBER && $number <= Tile::MAXIMUM_NUMBER
            ? Tile::number($assignment->suit($spec->suit), $number)
            : null;
    }
}

<?php

namespace App\Data\Decoding;

use App\Data\TileGroup;
use App\Data\Tiles\DragonTile;
use App\Data\Tiles\FlowerTile;
use App\Data\Tiles\NumberTile;
use App\Data\Tiles\SuitAssignment;
use App\Data\Tiles\TileFace;
use App\Data\Tiles\TileSpec;
use App\Data\Tiles\WindTile;
use App\Data\Tiles\ZeroTile;
use App\Enums\Dragon;
use App\Enums\Suit;
use App\Mahjong\AmericanMahjong;

/**
 * One group of a card line, read aloud the way a teacher would say it.
 *
 * Nothing here is authored per hand: the label, the sentence and the faces are
 * all derived from the group's tiles under a suit assignment, so a group that
 * changes shape cannot keep a description that stopped being true.
 */
readonly class GroupReading
{
    /**
     * @param  list<TileFace>  $faces  one face per tile, resolved as far as the assignment allows
     */
    private function __construct(
        public string $label,
        public array $faces,
        public string $sentence,
        public bool $acceptsJokers,
        public ?string $suitVariable,
    ) {
        //
    }

    /**
     * Read a group under the given suit assignment.
     */
    public static function for(TileGroup $group, SuitAssignment $assignment): self
    {
        return new self(
            self::label($group),
            array_map(fn (TileSpec $tile): TileFace => TileFace::for($tile, $assignment), $group->tiles),
            self::sentence($group, $assignment),
            $group->acceptsJokers(),
            $group->suitVariable(),
        );
    }

    /**
     * Determine whether a group's tiles step up one number at a time.
     *
     * Every tile must stand on the same variable and sit one higher than the
     * one before it — otherwise a group of two open numbers would read as a
     * run merely because neither number was printed.
     */
    private static function isRun(TileGroup $group): bool
    {
        $first = $group->tiles[0];

        if ($group->size() < 2 || ! $first instanceof NumberTile || ! $first->number->isVariable()) {
            return false;
        }

        foreach ($group->tiles as $position => $tile) {
            if (! $tile instanceof NumberTile || ! $tile->number->isVariable()) {
                return false;
            }

            if ($tile->number->variable !== $first->number->variable) {
                return false;
            }

            if ($tile->number->offset !== $first->number->offset + $position) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the heading a group prints above its tiles.
     *
     * Pung, kong, quint and sextet all mean *identical* tiles, so a run of four
     * is never a kong however much it looks like one on the card.
     */
    private static function label(TileGroup $group): string
    {
        if ($group->isIdentical()) {
            return AmericanMahjong::groupName($group->size());
        }

        if (self::isRun($group)) {
            return 'Run of '.$group->size();
        }

        if (self::isEvery($group, WindTile::class)) {
            return 'The four winds';
        }

        return $group->size().' tiles';
    }

    /**
     * Get the plain-English reading of a group.
     */
    private static function sentence(TileGroup $group, SuitAssignment $assignment): string
    {
        return $group->isIdentical()
            ? self::identicalSentence($group, $assignment)
            : self::mixedSentence($group, $assignment);
    }

    /**
     * Describe a group whose tiles all say the same thing.
     */
    private static function identicalSentence(TileGroup $group, SuitAssignment $assignment): string
    {
        $label = self::label($group);
        $tile = $group->tiles[0];
        $suit = self::suitPhrase($group->suitVariable(), $assignment);

        return match (true) {
            $tile instanceof FlowerTile => "{$label} of flowers",
            $tile instanceof ZeroTile => "{$label} of soaps standing in for zero",
            $tile instanceof WindTile => "{$label} of {$tile->wind->label()} winds",
            $tile instanceof DragonTile => self::dragonSentence($label, $group->suitVariable(), $assignment),
            $tile instanceof NumberTile && $tile->number->isVariable() => $tile->number->offset === 0
                ? "{$label} of any one number — call it {$tile->symbol()} — {$suit}"
                : "{$label} of {$tile->symbol()}, {$tile->number->offset} higher than {$tile->number->variable}, {$suit}",
            default => "{$label} of {$tile->symbol()}s {$suit}",
        };
    }

    /**
     * Describe a group whose tiles differ from one another.
     */
    private static function mixedSentence(TileGroup $group, SuitAssignment $assignment): string
    {
        $symbols = array_map(fn (TileSpec $tile): string => $tile->symbol(), $group->tiles);
        $suit = self::suitPhrase($group->suitVariable(), $assignment);

        if (self::isRun($group)) {
            return $group->size().' consecutive numbers '.$suit.', reading '.implode(' ', $symbols);
        }

        if (self::isEvery($group, WindTile::class)) {
            return 'the four winds, '.implode(' ', $symbols);
        }

        // Printed digits read as the number they spell — 2 0 2 6 is the year 2026.
        $printed = self::isPrintedNumber($group);
        $soap = $printed && self::isAny($group, ZeroTile::class)
            ? ', with a soap standing in for the zero'
            : '';

        return $printed
            ? 'the number '.implode('', $symbols).' '.$suit.$soap
            : implode(' ', $symbols).' '.$suit;
    }

    /**
     * Describe a group of dragons, which take their colour from their suit.
     */
    private static function dragonSentence(string $label, string $variable, SuitAssignment $assignment): string
    {
        $suit = $assignment->for($variable);

        if ($suit === null) {
            return "{$label} of dragons in suit {$variable}";
        }

        $dragon = AmericanMahjong::dragonForSuit($suit);

        $name = $dragon === Dragon::White
            ? 'soaps'
            : strtolower($dragon->label()).'s';

        return "{$label} of {$name}";
    }

    /**
     * Get the phrase naming the suit a group binds to.
     *
     * An unbound variable is named by its letter, because "in suit A" is what
     * the card actually says — the suit is the player's to choose.
     */
    private static function suitPhrase(?string $variable, SuitAssignment $assignment): string
    {
        if ($variable === null) {
            return '';
        }

        $suit = $assignment->for($variable);

        return $suit instanceof Suit
            ? 'in '.$suit->label()
            : "in suit {$variable}";
    }

    /**
     * Determine whether the group spells a number the card prints in full.
     */
    private static function isPrintedNumber(TileGroup $group): bool
    {
        foreach ($group->tiles as $tile) {
            $isPrintedDigit = $tile instanceof NumberTile && ! $tile->number->isVariable();

            if (! $isPrintedDigit && ! $tile instanceof ZeroTile) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether every tile in the group is of the given spec class.
     *
     * @param  class-string<TileSpec>  $spec
     */
    private static function isEvery(TileGroup $group, string $spec): bool
    {
        foreach ($group->tiles as $tile) {
            if (! $tile instanceof $spec) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether any tile in the group is of the given spec class.
     *
     * @param  class-string<TileSpec>  $spec
     */
    private static function isAny(TileGroup $group, string $spec): bool
    {
        foreach ($group->tiles as $tile) {
            if ($tile instanceof $spec) {
                return true;
            }
        }

        return false;
    }
}

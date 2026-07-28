<?php

namespace App\Mahjong;

use App\Data\TileGroup;
use App\Enums\Dragon;
use App\Enums\Suit;
use App\Enums\Wind;

/**
 * The rules that are specific to American Mah Jongg.
 *
 * Everything variant-dependent lives here — joker eligibility, the suit-to-dragon
 * binding, and the 152-tile inventory — so a second variant only needs a sibling
 * class rather than changes threaded through the schema.
 */
class AmericanMahjong
{
    /**
     * The number of identical tiles a group needs before jokers may substitute.
     */
    public const int MINIMUM_JOKER_GROUP_SIZE = 3;

    /**
     * Get the dragon bound to the given suit.
     */
    public static function dragonForSuit(Suit $suit): Dragon
    {
        return match ($suit) {
            Suit::Craks => Dragon::Red,
            Suit::Bams => Dragon::Green,
            Suit::Dots => Dragon::White,
        };
    }

    /**
     * Determine whether jokers may stand in for tiles in the given group.
     *
     * A joker substitutes only inside a grouping of three or more *identical*
     * tiles, so singles, pairs, NEWS and year runs are all ineligible — length
     * alone never earns a group its jokers.
     */
    public static function acceptsJokers(TileGroup $group): bool
    {
        return $group->size() >= self::MINIMUM_JOKER_GROUP_SIZE && $group->isIdentical();
    }

    /**
     * Get every tile in the American set, keyed by tile code, with its copy count.
     *
     * @return array<string, int>
     */
    public static function tileInventory(): array
    {
        $inventory = [];

        foreach (Suit::cases() as $suit) {
            foreach (range(1, 9) as $number) {
                $inventory["{$suit->value}-{$number}"] = 4;
            }
        }

        foreach (Wind::cases() as $wind) {
            $inventory["wind-{$wind->value}"] = 4;
        }

        foreach (Dragon::cases() as $dragon) {
            $inventory["dragon-{$dragon->value}"] = 4;
        }

        $inventory['flower'] = 8;
        $inventory['joker'] = 8;

        return $inventory;
    }
}

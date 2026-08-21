<?php

namespace App\Data\Matching;

use App\Data\HandStructure;
use App\Data\Tiles\Tile;
use App\Mahjong\AmericanMahjong;
use InvalidArgumentException;

/**
 * The tiles in front of the player.
 *
 * A rack is accepted at any size from nothing to a full hand, because the
 * matcher ranks continuously as tiles arrive — the tile-by-tile loop is where
 * the teaching happens (issue #15). What it will not accept is a rack the game
 * cannot produce: fifteen tiles, or a fifth copy of a tile that exists four
 * times.
 *
 * Tiles are kept in set order rather than the order they were clicked, so the
 * same hand always reads the same way and always makes the same link.
 */
readonly class Rack
{
    /**
     * @param  list<Tile>  $tiles
     */
    private function __construct(public array $tiles)
    {
        //
    }

    /**
     * Build a rack with nothing on it.
     */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * Build a rack from tiles, refusing any the game could not supply.
     *
     * @param  list<Tile>  $tiles
     */
    public static function of(array $tiles): self
    {
        $rack = self::empty();

        foreach ($tiles as $tile) {
            $rack = $rack->add($tile);
        }

        return $rack;
    }

    /**
     * Build a rack from tile codes, dropping anything it cannot hold.
     *
     * This is the one entry point fed by a URL, so it forgives rather than
     * throws: a hand-edited link should settle on the rack it can make, not
     * five-hundred the page.
     *
     * @param  list<string>  $codes
     */
    public static function fromCodes(array $codes): self
    {
        $rack = self::empty();

        foreach ($codes as $code) {
            $tile = Tile::tryFromCode($code);

            if ($tile !== null && $rack->canHold($tile)) {
                $rack = $rack->add($tile);
            }
        }

        return $rack;
    }

    /**
     * Get how many tiles are on the rack.
     */
    public function size(): int
    {
        return count($this->tiles);
    }

    /**
     * Determine whether the rack holds a full hand already.
     */
    public function isFull(): bool
    {
        return $this->size() >= HandStructure::HAND_SIZE;
    }

    /**
     * Determine whether the rack is empty.
     */
    public function isEmpty(): bool
    {
        return $this->tiles === [];
    }

    /**
     * Determine whether one more of the given tile could be racked.
     */
    public function canHold(Tile $tile): bool
    {
        return ! $this->isFull()
            && $this->countOf($tile) < AmericanMahjong::tileInventory()[$tile->code()];
    }

    /**
     * Get how many copies of the given tile are on the rack.
     */
    public function countOf(Tile $tile): int
    {
        return $this->counts()[$tile->code()] ?? 0;
    }

    /**
     * Get the rack as a count per tile code.
     *
     * @return array<string, int>
     */
    public function counts(): array
    {
        $counts = [];

        foreach ($this->tiles as $tile) {
            $counts[$tile->code()] = ($counts[$tile->code()] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * Get the rack as tile codes, which is how it travels in a url.
     *
     * @return list<string>
     */
    public function codes(): array
    {
        return array_map(fn (Tile $tile): string => $tile->code(), $this->tiles);
    }

    /**
     * Rack one more tile.
     */
    public function add(Tile $tile): self
    {
        throw_unless(
            $this->canHold($tile),
            new InvalidArgumentException(
                "A rack cannot hold another [{$tile->code()}]; a hand is "
                .HandStructure::HAND_SIZE.' tiles and the set has only so many copies.'
            ),
        );

        return new self(self::inSetOrder([...$this->tiles, $tile]));
    }

    /**
     * Unrack one copy of the given tile, if it is there at all.
     */
    public function remove(Tile $tile): self
    {
        $tiles = $this->tiles;

        foreach ($tiles as $position => $racked) {
            if ($racked->code() === $tile->code()) {
                unset($tiles[$position]);

                return new self(array_values($tiles));
            }
        }

        return $this;
    }

    /**
     * Put tiles in the order a set is laid out, so a rack always reads the same.
     *
     * @param  list<Tile>  $tiles
     * @return list<Tile>
     */
    private static function inSetOrder(array $tiles): array
    {
        $order = [];

        foreach (Tile::all() as $position => $tile) {
            $order[$tile->code()] = $position;
        }

        usort($tiles, fn (Tile $a, Tile $b): int => $order[$a->code()] <=> $order[$b->code()]);

        return $tiles;
    }
}

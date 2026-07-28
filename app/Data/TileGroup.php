<?php

namespace App\Data;

use App\Data\Tiles\TileSpec;
use App\Mahjong\AmericanMahjong;
use InvalidArgumentException;

/**
 * One group of a card line — a flat, ordered list of tile specs.
 *
 * Pung, kong, quint and sextet are vocabulary for a group's size rather than
 * kinds of group, and a run is simply a group whose specs differ. Every fact
 * the card prints about a group is derived from these tiles.
 */
readonly class TileGroup
{
    /** @var list<TileSpec> */
    public array $tiles;

    /**
     * @param  list<TileSpec>  $tiles
     */
    public function __construct(array $tiles)
    {
        throw_if($tiles === [], new InvalidArgumentException('A group must hold at least one tile.'));

        $suits = array_values(array_unique(array_filter(
            array_map(fn (TileSpec $tile): ?string => $tile->suitVariable(), $tiles)
        )));
        sort($suits);

        throw_if(
            count($suits) > 1,
            new InvalidArgumentException(
                'A group may only use one suit variable, found ['.implode(', ', $suits).'].'
            ),
        );

        $this->tiles = $tiles;
    }

    /**
     * Get how many tiles the group holds.
     */
    public function size(): int
    {
        return count($this->tiles);
    }

    /**
     * Determine whether every tile in the group describes the same tile.
     */
    public function isIdentical(): bool
    {
        foreach ($this->tiles as $tile) {
            if (! $tile->isIdenticalTo($this->tiles[0])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether jokers may stand in for tiles in this group.
     */
    public function acceptsJokers(): bool
    {
        return AmericanMahjong::acceptsJokers($this);
    }

    /**
     * Get the suit variable every suited tile in the group binds to.
     */
    public function suitVariable(): ?string
    {
        foreach ($this->tiles as $tile) {
            if ($tile->suitVariable() !== null) {
                return $tile->suitVariable();
            }
        }

        return null;
    }

    /**
     * Get every variable name the group's tiles reference.
     *
     * @return list<string>
     */
    public function variableNames(): array
    {
        $names = [];

        foreach ($this->tiles as $tile) {
            if ($tile->suitVariable() !== null) {
                $names[] = $tile->suitVariable();
            }

            if ($tile instanceof Tiles\NumberTile && $tile->number->isVariable()) {
                $names[] = $tile->number->variable;
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Build a group from its authoring array of tile specs.
     *
     * @param  list<array<string, mixed>>  $tiles
     */
    public static function fromArray(array $tiles): self
    {
        return new self(array_map(TileSpec::fromArray(...), $tiles));
    }

    /**
     * Get the authoring array for this group.
     *
     * @return list<array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(fn (TileSpec $tile): array => $tile->toArray(), $this->tiles);
    }
}

<?php

namespace App\Data\Tiles;

use App\Enums\TileType;
use App\Enums\Wind;
use InvalidArgumentException;

/**
 * A single tile position inside a group, as the card describes it.
 *
 * A spec is a description, not a tile: it may still be waiting on a suit or
 * number variable that only a concrete assignment resolves.
 */
abstract readonly class TileSpec
{
    /**
     * Get the symbol the card prints for this tile.
     */
    abstract public function symbol(): string;

    /**
     * Get the authoring array this spec was built from.
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    /**
     * Get the suit variable this tile binds to, if it is suited at all.
     */
    public function suitVariable(): ?string
    {
        return null;
    }

    /**
     * Determine whether this tile and the given one describe the same tile.
     *
     * This is the test joker eligibility rests on: a group takes jokers only
     * when its tiles are identical, so "identical" has to mean identical after
     * variable assignment, not merely the same shape.
     */
    public function isIdenticalTo(self $other): bool
    {
        return $this::class === $other::class
            && $this->toArray() === $other->toArray();
    }

    /**
     * Build a tile spec from its authoring array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $type = TileType::tryFrom($data['t'] ?? '')
            ?? throw new InvalidArgumentException("Unknown tile type [{$data['t']}].");

        return match ($type) {
            TileType::Number => new NumberTile($data['suit'], NumberValue::fromValue($data['n'])),
            TileType::Dragon => new DragonTile($data['suit']),
            TileType::Wind => new WindTile(Wind::fromSymbol($data['w'])),
            TileType::Flower => new FlowerTile,
            TileType::Zero => new ZeroTile,
        };
    }
}

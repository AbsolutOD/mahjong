<?php

namespace App\Data\Tiles;

use App\Enums\TileType;

/**
 * A flower. All eight flowers are interchangeable, so there is only one spec.
 */
readonly class FlowerTile extends TileSpec
{
    /**
     * Get the symbol the card prints for this tile.
     */
    public function symbol(): string
    {
        return 'F';
    }

    /**
     * Get the authoring array for this tile.
     *
     * @return array{t: string}
     */
    public function toArray(): array
    {
        return ['t' => TileType::Flower->value];
    }
}

<?php

namespace App\Data\Tiles;

use App\Enums\TileType;
use App\Enums\Wind;

/**
 * One of the four winds. Winds carry no suit.
 */
readonly class WindTile extends TileSpec
{
    public function __construct(
        public Wind $wind,
    ) {
        //
    }

    /**
     * Get the symbol the card prints for this tile.
     */
    public function symbol(): string
    {
        return $this->wind->symbol();
    }

    /**
     * Get the authoring array for this tile.
     *
     * @return array{t: string, w: string}
     */
    public function toArray(): array
    {
        return [
            't' => TileType::Wind->value,
            'w' => $this->wind->symbol(),
        ];
    }
}

<?php

namespace App\Data\Tiles;

use App\Enums\TileType;

/**
 * A dragon, which takes its colour from the suit its group is assigned.
 */
readonly class DragonTile extends TileSpec
{
    public function __construct(
        public string $suit,
    ) {
        //
    }

    /**
     * Get the symbol the card prints for this tile.
     */
    public function symbol(): string
    {
        return 'D';
    }

    /**
     * Get the suit variable this dragon binds to.
     */
    public function suitVariable(): string
    {
        return $this->suit;
    }

    /**
     * Get the authoring array for this tile.
     *
     * @return array{t: string, suit: string}
     */
    public function toArray(): array
    {
        return [
            't' => TileType::Dragon->value,
            'suit' => $this->suit,
        ];
    }
}

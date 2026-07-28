<?php

namespace App\Data\Tiles;

use App\Enums\TileType;

/**
 * The soap standing in as the numeral zero, as year hands print it.
 *
 * It resolves to the same physical tile as the white dragon but is a separate
 * spec: a zero never joins a consecutive run and never binds to a suit.
 */
readonly class ZeroTile extends TileSpec
{
    /**
     * Get the symbol the card prints for this tile.
     */
    public function symbol(): string
    {
        return '0';
    }

    /**
     * Get the authoring array for this tile.
     *
     * @return array{t: string}
     */
    public function toArray(): array
    {
        return ['t' => TileType::Zero->value];
    }
}

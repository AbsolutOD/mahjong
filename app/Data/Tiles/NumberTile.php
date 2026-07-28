<?php

namespace App\Data\Tiles;

use App\Enums\TileType;

/**
 * A suited number tile — 1 through 9 in dots, bams or craks.
 */
readonly class NumberTile extends TileSpec
{
    public function __construct(
        public string $suit,
        public NumberValue $number,
    ) {
        //
    }

    /**
     * Get the symbol the card prints for this tile.
     */
    public function symbol(): string
    {
        return $this->number->symbol();
    }

    /**
     * Get the suit variable this tile binds to.
     */
    public function suitVariable(): string
    {
        return $this->suit;
    }

    /**
     * Get the authoring array for this tile.
     *
     * @return array{t: string, suit: string, n: int|array{var: string, off?: int}}
     */
    public function toArray(): array
    {
        return [
            't' => TileType::Number->value,
            'suit' => $this->suit,
            'n' => $this->number->toValue(),
        ];
    }
}

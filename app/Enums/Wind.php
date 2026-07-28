<?php

namespace App\Enums;

use ValueError;

enum Wind: string
{
    case North = 'north';
    case East = 'east';
    case West = 'west';
    case South = 'south';

    /**
     * Get the single-letter symbol the card prints for this wind.
     */
    public function symbol(): string
    {
        return match ($this) {
            self::North => 'N',
            self::East => 'E',
            self::West => 'W',
            self::South => 'S',
        };
    }

    /**
     * Get the wind printed as the given card symbol.
     */
    public static function fromSymbol(string $symbol): self
    {
        return match (strtoupper($symbol)) {
            'N' => self::North,
            'E' => self::East,
            'W' => self::West,
            'S' => self::South,
            default => throw new ValueError("[{$symbol}] is not a valid wind symbol."),
        };
    }
}

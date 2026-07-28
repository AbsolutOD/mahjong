<?php

namespace App\Enums;

enum Dragon: string
{
    case Red = 'red';
    case Green = 'green';
    case White = 'white';

    /**
     * Get the display label for the dragon.
     *
     * The white dragon is universally called the soap at an American table.
     */
    public function label(): string
    {
        return match ($this) {
            self::Red => 'Red Dragon',
            self::Green => 'Green Dragon',
            self::White => 'Soap',
        };
    }
}

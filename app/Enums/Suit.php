<?php

namespace App\Enums;

enum Suit: string
{
    case Dots = 'dots';
    case Bams = 'bams';
    case Craks = 'craks';

    /**
     * Get the display label for the suit.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }
}

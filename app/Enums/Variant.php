<?php

namespace App\Enums;

enum Variant: string
{
    case American = 'american';

    /**
     * Get the display label for the variant.
     */
    public function label(): string
    {
        return match ($this) {
            self::American => 'American Mah Jongg',
        };
    }
}

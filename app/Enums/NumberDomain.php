<?php

namespace App\Enums;

enum NumberDomain: string
{
    case Any = 'any';
    case Odd = 'odd';
    case Even = 'even';

    /**
     * Determine whether the given number falls inside this domain.
     */
    public function allows(int $number): bool
    {
        if ($number < 1 || $number > 9) {
            return false;
        }

        return match ($this) {
            self::Any => true,
            self::Odd => $number % 2 === 1,
            self::Even => $number % 2 === 0,
        };
    }
}

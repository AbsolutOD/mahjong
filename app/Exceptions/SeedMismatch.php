<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * An authored hand's structure does not render to the line written beside it.
 *
 * The line is an assertion the author writes, not a stored column: catching the
 * disagreement here is the whole point, since the realistic authoring mistake
 * is a structure that quietly says something other than the line intended.
 */
class SeedMismatch extends RuntimeException
{
    /**
     * Build the exception for a hand that renders differently than authored.
     */
    public static function forHand(string $category, string $authored, string $rendered): self
    {
        return new self("Hand [{$authored}] in category [{$category}] renders as [{$rendered}].");
    }
}

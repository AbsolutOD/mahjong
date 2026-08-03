<?php

namespace App\Data\Tiles;

use App\Enums\Suit;
use InvalidArgumentException;

/**
 * A partial binding of a hand's suit variables to real suits.
 *
 * The card's colours mean the groups use *different* suits, never fixed ones,
 * so an assignment is what turns "any number in suit A" into a tile you could
 * pick up. It is deliberately partial: a variable with no binding stays
 * unbound, and an unbound variable is what the tile face draws as grey.
 */
readonly class SuitAssignment
{
    /**
     * @param  array<string, Suit>  $suits
     */
    private function __construct(public array $suits)
    {
        //
    }

    /**
     * Build an assignment with nothing bound.
     */
    public static function none(): self
    {
        return new self([]);
    }

    /**
     * Build an assignment from a map of variable names to suits.
     *
     * @param  array<string, Suit>  $suits
     */
    public static function of(array $suits): self
    {
        $assignment = self::none();

        foreach ($suits as $variable => $suit) {
            $assignment = $assignment->bind($variable, $suit);
        }

        return $assignment;
    }

    /**
     * Bind a suit variable, returning a new assignment.
     *
     * Two variables may not share a suit: the card's colours say the groups are
     * in different suits, so letting A and B both mean dots would render a hand
     * the card cannot describe.
     */
    public function bind(string $variable, Suit $suit): self
    {
        foreach ($this->suits as $bound => $existing) {
            throw_if(
                $existing === $suit && $bound !== $variable,
                new InvalidArgumentException(
                    "Suit [{$suit->value}] is already bound to variable [{$bound}]; card suits must stay distinct."
                ),
            );
        }

        return new self([...$this->suits, $variable => $suit]);
    }

    /**
     * Get the suit bound to the given variable, or null while it is unbound.
     */
    public function for(string $variable): ?Suit
    {
        return $this->suits[$variable] ?? null;
    }

    /**
     * Determine whether any variable has been bound at all.
     */
    public function isEmpty(): bool
    {
        return $this->suits === [];
    }
}

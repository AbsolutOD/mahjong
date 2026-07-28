<?php

namespace App\Data\Tiles;

use InvalidArgumentException;

/**
 * The number a number tile carries: either a printed digit or a point in a run.
 *
 * A run point is stored as its declared variable plus an offset, and prints as
 * that variable's letter advanced by the offset — a hand declaring X renders a
 * consecutive run as X, Y, Z.
 */
readonly class NumberValue
{
    private function __construct(
        public ?int $literal,
        public ?string $variable,
        public int $offset,
    ) {
        //
    }

    /**
     * Build a number that is printed on the card as a fixed digit.
     */
    public static function literal(int $number): self
    {
        return new self($number, null, 0);
    }

    /**
     * Build a number standing on a declared variable, optionally offset along a run.
     */
    public static function variable(string $name, int $offset = 0): self
    {
        return new self(null, $name, $offset);
    }

    /**
     * Build a number from its authoring value — a digit, or a var/off pair.
     *
     * @param  int|array{var: string, off?: int}  $value
     */
    public static function fromValue(int|array $value): self
    {
        return is_int($value)
            ? self::literal($value)
            : self::variable($value['var'], $value['off'] ?? 0);
    }

    /**
     * Determine whether this number stands on a variable rather than a digit.
     */
    public function isVariable(): bool
    {
        return $this->variable !== null;
    }

    /**
     * Get the symbol the card prints for this number.
     */
    public function symbol(): string
    {
        if (! $this->isVariable()) {
            return (string) $this->literal;
        }

        $letter = chr(ord($this->variable) + $this->offset);

        throw_unless(
            preg_match('/^[A-Z]$/', $letter) === 1,
            new InvalidArgumentException(
                "Offset [{$this->offset}] runs past the end of the alphabet from variable [{$this->variable}]."
            ),
        );

        return $letter;
    }

    /**
     * Get the authoring value for this number.
     *
     * @return int|array{var: string, off?: int}
     */
    public function toValue(): int|array
    {
        if (! $this->isVariable()) {
            return $this->literal;
        }

        return $this->offset === 0
            ? ['var' => $this->variable]
            : ['var' => $this->variable, 'off' => $this->offset];
    }
}

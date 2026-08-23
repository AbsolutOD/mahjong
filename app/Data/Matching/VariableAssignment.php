<?php

namespace App\Data\Matching;

use App\Enums\Suit;

/**
 * One settled value for every variable a hand declares.
 *
 * A hand's letters mean two different things — a suit or a number — and mixing
 * them into one map is what makes a slot's resolution guesswork. They are kept
 * apart here and only put back together for the constraints, which restrict
 * variables without caring which kind they are.
 */
readonly class VariableAssignment
{
    /**
     * @param  array<string, Suit>  $suits
     * @param  array<string, int>  $numbers
     * @param  array<string, Suit|int>  $values  every variable, in the order the hand declares it
     */
    private function __construct(
        public array $suits,
        public array $numbers,
        public array $values,
    ) {
        //
    }

    /**
     * Build an assignment with nothing settled.
     */
    public static function none(): self
    {
        return new self([], [], []);
    }

    /**
     * Settle one more variable, returning a new assignment.
     */
    public function with(string $name, Suit|int $value): self
    {
        return $value instanceof Suit
            ? new self([...$this->suits, $name => $value], $this->numbers, [...$this->values, $name => $value])
            : new self($this->suits, [...$this->numbers, $name => $value], [...$this->values, $name => $value]);
    }

    /**
     * Get the suit a variable settled on.
     */
    public function suit(string $name): Suit
    {
        return $this->suits[$name];
    }

    /**
     * Get the number a variable settled on.
     */
    public function number(string $name): int
    {
        return $this->numbers[$name];
    }

    /**
     * Determine whether some variable already holds the given suit.
     */
    public function holds(Suit $suit): bool
    {
        return in_array($suit, $this->suits, strict: true);
    }

    /**
     * Read every binding out, in the order the hand declares its variables.
     *
     * @return array<string, string>
     */
    public function bindings(): array
    {
        return array_map(
            fn (Suit|int $value): string => $value instanceof Suit ? $value->label() : (string) $value,
            $this->values,
        );
    }
}

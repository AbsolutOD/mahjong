<?php

namespace App\Data\Constraints;

use InvalidArgumentException;

/**
 * A restriction on how a hand's variables may be assigned together.
 */
abstract readonly class Constraint
{
    /**
     * Determine whether the given variable assignment satisfies this constraint.
     *
     * @param  array<string, mixed>  $assignment
     */
    abstract public function isSatisfiedBy(array $assignment): bool;

    /**
     * Get the variable names this constraint restricts.
     *
     * @return list<string>
     */
    abstract public function variableNames(): array;

    /**
     * Get the authoring array for this constraint.
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    /**
     * Build a constraint from its authoring array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return match (array_key_first($data)) {
            'distinct' => new DistinctConstraint(array_values($data['distinct'])),
            default => throw new InvalidArgumentException(
                'Unknown constraint ['.array_key_first($data).'].'
            ),
        };
    }
}

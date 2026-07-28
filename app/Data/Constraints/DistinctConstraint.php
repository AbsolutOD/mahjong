<?php

namespace App\Data\Constraints;

/**
 * The named variables must all be assigned different values.
 *
 * This is what the card's colours mean: two colours on a line say "any two
 * suits, but not the same one twice".
 */
readonly class DistinctConstraint extends Constraint
{
    /**
     * @param  list<string>  $variables
     */
    public function __construct(
        public array $variables,
    ) {
        //
    }

    /**
     * Determine whether the given variable assignment satisfies this constraint.
     *
     * @param  array<string, mixed>  $assignment
     */
    public function isSatisfiedBy(array $assignment): bool
    {
        $values = array_map(fn (string $name): mixed => $assignment[$name] ?? null, $this->variables);

        return count(array_unique($values, SORT_REGULAR)) === count($values);
    }

    /**
     * Get the variable names this constraint restricts.
     *
     * @return list<string>
     */
    public function variableNames(): array
    {
        return $this->variables;
    }

    /**
     * Get the authoring array for this constraint.
     *
     * @return array{distinct: list<string>}
     */
    public function toArray(): array
    {
        return ['distinct' => $this->variables];
    }
}

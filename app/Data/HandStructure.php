<?php

namespace App\Data;

use App\Data\Constraints\Constraint;
use App\Data\Tiles\FlowerTile;
use App\Data\Tiles\NumberTile;
use App\Data\Tiles\TileSpec;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

/**
 * A whole card line: its groups, the variables they stand on, and the
 * constraints tying those variables together.
 *
 * Everything a card prints about a hand beyond this — how many suits it uses,
 * whether it holds a pair, how many jokers it can take — is derived here rather
 * than stored, so it can never drift from the structure it describes.
 */
readonly class HandStructure implements Castable
{
    /**
     * The number of tiles in a complete American Mah Jongg hand.
     */
    public const int HAND_SIZE = 14;

    /**
     * @param  list<TileGroup>  $groups
     * @param  array<string, HandVariable>  $variables
     * @param  list<Constraint>  $constraints
     */
    public function __construct(
        public array $groups,
        public array $variables,
        public array $constraints = [],
    ) {
        $this->validateHandSize();
        $this->validateVariablesAreDeclared();
        $this->validateRunsDoNotCollide();
    }

    /**
     * Get how many tiles the hand holds in total.
     */
    public function tileCount(): int
    {
        return array_sum(array_map(fn (TileGroup $group): int => $group->size(), $this->groups));
    }

    /**
     * Get the suit variables the hand's groups actually use.
     *
     * @return list<string>
     */
    public function suitVariables(): array
    {
        $suits = array_filter(array_map(fn (TileGroup $group): ?string => $group->suitVariable(), $this->groups));

        return array_values(array_unique($suits));
    }

    /**
     * Get how many distinct suits the hand calls for.
     */
    public function suitCount(): int
    {
        return count($this->suitVariables());
    }

    /**
     * Determine whether any group in the hand is a pair.
     */
    public function hasPair(): bool
    {
        foreach ($this->groups as $group) {
            if ($group->size() === 2) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the hand calls for flowers.
     */
    public function usesFlowers(): bool
    {
        foreach ($this->tiles() as $tile) {
            if ($tile instanceof FlowerTile) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the most jokers the hand could hold, across every eligible group.
     */
    public function maxJokers(): int
    {
        $eligible = array_filter($this->groups, fn (TileGroup $group): bool => $group->acceptsJokers());

        return array_sum(array_map(fn (TileGroup $group): int => $group->size(), $eligible));
    }

    /**
     * Get every tile in the hand, in card order.
     *
     * @return list<TileSpec>
     */
    public function tiles(): array
    {
        return array_merge(...array_map(fn (TileGroup $group): array => $group->tiles, $this->groups));
    }

    /**
     * Build a hand structure from its authoring array.
     *
     * @param  array{variables: array<string, array{kind: string, domain?: string|list<int>}>, constraints?: list<array<string, mixed>>, groups: list<list<array<string, mixed>>>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            array_map(TileGroup::fromArray(...), $data['groups']),
            array_map(HandVariable::fromArray(...), $data['variables']),
            array_map(Constraint::fromArray(...), $data['constraints'] ?? []),
        );
    }

    /**
     * Get the authoring array for this hand structure.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'variables' => array_map(fn (HandVariable $variable): array => $variable->toArray(), $this->variables),
            'constraints' => array_map(fn (Constraint $constraint): array => $constraint->toArray(), $this->constraints),
            'groups' => array_map(fn (TileGroup $group): array => $group->toArray(), $this->groups),
        ];
    }

    /**
     * Get the caster that stores this structure in a JSON column.
     *
     * Structures are validated on the way in as well as on the way out, so a
     * hand that would not survive a round trip never reaches the database.
     *
     * @param  array<int, string>  $arguments
     * @return CastsAttributes<HandStructure, HandStructure|array<string, mixed>>
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class implements CastsAttributes
        {
            /**
             * Cast the stored JSON to a hand structure.
             *
             * @param  array<string, mixed>  $attributes
             */
            public function get($model, string $key, $value, array $attributes): ?HandStructure
            {
                return $value === null
                    ? null
                    : HandStructure::fromArray(json_decode($value, associative: true));
            }

            /**
             * Prepare a hand structure for storage.
             *
             * @param  array<string, mixed>  $attributes
             * @return array<string, string>
             */
            public function set($model, string $key, $value, array $attributes): array
            {
                $structure = $value instanceof HandStructure ? $value : HandStructure::fromArray($value);

                return [$key => json_encode($structure->toArray(), flags: JSON_THROW_ON_ERROR)];
            }
        };
    }

    /**
     * Ensure the hand holds a full complement of tiles.
     */
    private function validateHandSize(): void
    {
        throw_unless(
            $this->tileCount() === self::HAND_SIZE,
            new InvalidArgumentException(
                'A hand must hold exactly '.self::HAND_SIZE.' tiles, found '.$this->tileCount().'.'
            ),
        );
    }

    /**
     * Ensure every variable the tiles and constraints name has been declared.
     */
    private function validateVariablesAreDeclared(): void
    {
        foreach ($this->groups as $group) {
            foreach ($group->variableNames() as $name) {
                throw_unless(
                    isset($this->variables[$name]),
                    new InvalidArgumentException("Tiles reference undeclared variable [{$name}].")
                );
            }
        }

        foreach ($this->constraints as $constraint) {
            foreach ($constraint->variableNames() as $name) {
                throw_unless(
                    isset($this->variables[$name]),
                    new InvalidArgumentException("Constraint references undeclared variable [{$name}].")
                );
            }
        }
    }

    /**
     * Ensure no run's rendered letters collide with another declared variable.
     *
     * A run prints as its declared letter advanced by the offset — X, Y, Z — so
     * a hand that also declared Y would render two different tiles the same way.
     */
    private function validateRunsDoNotCollide(): void
    {
        foreach ($this->tiles() as $tile) {
            if (! $tile instanceof NumberTile || ! $tile->number->isVariable() || $tile->number->offset === 0) {
                continue;
            }

            $rendered = $tile->symbol();

            throw_if(
                $rendered !== $tile->number->variable && isset($this->variables[$rendered]),
                new InvalidArgumentException(
                    "Run [{$tile->number->variable}+{$tile->number->offset}] renders as [{$rendered}], which the hand also declares."
                ),
            );
        }
    }
}

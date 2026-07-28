<?php

namespace App\Data;

use App\Enums\NumberDomain;
use App\Enums\VariableKind;
use InvalidArgumentException;
use LogicException;

/**
 * A variable a hand declares and its tiles then stand on.
 *
 * Variables are declared rather than inferred from the tiles that use them,
 * because a number variable's domain — any, odd, even, or an explicit list —
 * is a restriction no scan of the tiles could reveal.
 */
readonly class HandVariable
{
    /**
     * @param  NumberDomain|list<int>|null  $domain
     */
    public function __construct(
        public VariableKind $kind,
        public NumberDomain|array|null $domain = null,
    ) {
        //
    }

    /**
     * Determine whether this variable may be assigned the given number.
     */
    public function allows(int $number): bool
    {
        throw_if($this->domain === null, new LogicException('Suit variable has no number domain.'));

        return is_array($this->domain)
            ? in_array($number, $this->domain, strict: true)
            : $this->domain->allows($number);
    }

    /**
     * Build a variable from its authoring array.
     *
     * @param  array{kind: string, domain?: string|list<int>}  $data
     */
    public static function fromArray(array $data): self
    {
        $kind = VariableKind::tryFrom($data['kind'])
            ?? throw new InvalidArgumentException("Unknown variable kind [{$data['kind']}].");

        if ($kind === VariableKind::Suit) {
            return new self($kind);
        }

        $domain = $data['domain'] ?? NumberDomain::Any->value;

        return new self($kind, is_array($domain)
            ? $domain
            : NumberDomain::tryFrom($domain) ?? throw new InvalidArgumentException("Unknown number domain [{$domain}].")
        );
    }

    /**
     * Get the authoring array for this variable.
     *
     * @return array{kind: string, domain?: string|list<int>}
     */
    public function toArray(): array
    {
        if ($this->kind === VariableKind::Suit) {
            return ['kind' => $this->kind->value];
        }

        return [
            'kind' => $this->kind->value,
            'domain' => is_array($this->domain) ? $this->domain : $this->domain->value,
        ];
    }
}

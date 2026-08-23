<?php

namespace App\Enums;

/**
 * What the rack does about one slot of a hand.
 *
 * A slot is covered two ways and uncovered one, and the three are kept apart
 * because a joker is not the same thing as the tile it stands in for: a hand
 * held together by jokers is still legal, but it is not the hand you own.
 */
enum SlotState: string
{
    case Held = 'held';
    case Joker = 'joker';
    case Missing = 'missing';

    /**
     * Determine whether the rack fills this slot at all.
     */
    public function isCovered(): bool
    {
        return $this !== self::Missing;
    }
}

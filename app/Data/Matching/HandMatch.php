<?php

namespace App\Data\Matching;

use App\Models\Hand;

/**
 * One card line measured against the rack, at the best hand it could become.
 *
 * The ranking currency is tiles away and nothing else (issue #15): a learner
 * has to be able to say why one line beats another, and "it needs fewer tiles"
 * is checkable at the table where a blended score is not. The accepted cost is
 * that this reports *closeness*, never achievability — a concealed hand two
 * away outranks an open hand three away.
 */
readonly class HandMatch
{
    public function __construct(
        public Hand $hand,
        public Instantiation $instantiation,
        public Coverage $coverage,
        public int $cardOrder,
    ) {
        //
    }

    /**
     * Get how many tiles the rack is short of this line.
     */
    public function tilesAway(): int
    {
        return $this->coverage->tilesAway();
    }

    /**
     * Determine whether the rack already holds this line.
     */
    public function isComplete(): bool
    {
        return $this->tilesAway() === 0;
    }
}

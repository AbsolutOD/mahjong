<?php

namespace App\Data\Matching;

use App\Data\Tiles\Tile;
use App\Enums\SlotState;

/**
 * One slot of a settled hand, and what the rack does about it.
 *
 * The tile is the one the hand actually asks for, whatever fills it — a slot a
 * joker covers still names the tile the joker is standing in for, because that
 * is the tile the player is being taught to see.
 */
readonly class MatchedSlot
{
    public function __construct(
        public Tile $tile,
        public SlotState $state,
    ) {
        //
    }
}

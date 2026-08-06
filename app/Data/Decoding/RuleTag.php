<?php

namespace App\Data\Decoding;

use App\Enums\TagTone;

/**
 * One rule the panel prints about a hand, derived from its structure.
 *
 * A tag is never authored per hand: every one is read off the tiles, so a card
 * line that changes shape cannot keep a tag that stopped being true.
 */
readonly class RuleTag
{
    public function __construct(
        public string $label,
        public TagTone $tone,
    ) {
        //
    }
}

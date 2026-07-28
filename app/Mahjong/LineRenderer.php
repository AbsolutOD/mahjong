<?php

namespace App\Mahjong;

use App\Data\HandStructure;
use App\Data\TileGroup;
use App\Data\Tiles\TileSpec;

/**
 * Renders a hand structure back into the shorthand a card prints.
 *
 * The card itself uses colour to say which groups share a suit; a monochrome
 * skeleton would let A/A/B and A/B/B print identically, so each suited group
 * carries its suit variable inline instead.
 */
class LineRenderer
{
    /**
     * Render a hand as its card line.
     */
    public function render(HandStructure $structure): string
    {
        return implode(' ', array_map($this->renderGroup(...), $structure->groups));
    }

    /**
     * Render one group as its symbols plus the suit it binds to.
     */
    private function renderGroup(TileGroup $group): string
    {
        $symbols = implode('', array_map(fn (TileSpec $tile): string => $tile->symbol(), $group->tiles));

        return $group->suitVariable() === null
            ? $symbols
            : "{$symbols}({$group->suitVariable()})";
    }
}

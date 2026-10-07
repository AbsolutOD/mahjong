<?php

namespace App\Data\Charleston;

use App\Data\Matching\HandMatch;
use App\Data\Tiles\Tile;
use App\Data\Tiles\TileFace;
use App\Enums\TileType;

/**
 * What the Charleston advice says about one copy of a tile on the rack.
 *
 * Advice is per copy rather than per face (issue #17): holding two 7 Bams where
 * the only hand that wants one needs just the one, the second is spare. Every
 * word the page prints about the copy comes from here, so the reasons are
 * unit-tested rather than eyeballed — the decoder's rule, carried over.
 */
readonly class TileAdvice
{
    /**
     * @param  int  $copy  which copy of the face this is, counting from one
     * @param  list<HandMatch>  $wantedBy  the leading hands that place this copy
     * @param  int  $linesWanting  how many lines on the whole card place this copy
     * @param  int  $mostPlacedByLeading  the most copies of this face any leading hand places
     */
    public function __construct(
        public Tile $tile,
        public int $copy,
        public array $wantedBy,
        public int $linesWanting,
        public int $mostPlacedByLeading,
    ) {
        //
    }

    /**
     * Determine whether this copy may be passed at all. Jokers never are.
     */
    public function isPassable(): bool
    {
        return $this->tile->type !== TileType::Joker;
    }

    /**
     * Determine whether a leading hand places an earlier copy but not this one.
     */
    public function isSpareCopy(): bool
    {
        return $this->wantedBy === [] && $this->mostPlacedByLeading > 0;
    }

    /**
     * Get the word printed under the tile on the rack — never colour alone (#8).
     */
    public function marker(): string
    {
        $wanting = count($this->wantedBy);

        return match (true) {
            ! $this->isPassable() => 'never passed',
            $this->isSpareCopy() => 'spare',
            $wanting === 0 => 'unwanted',
            $wanting === 1 => '1 hand',
            default => "{$wanting} hands",
        };
    }

    /**
     * Get the marker as a screen reader should hear it.
     */
    public function spokenMarker(): string
    {
        $wanting = count($this->wantedBy);

        return match (true) {
            ! $this->isPassable() => 'Jokers are never passed',
            $wanting === 0 => 'None of your leading hands wants this tile',
            $wanting === 1 => 'Wanted by 1 of your leading hands',
            default => "Wanted by {$wanting} of your leading hands",
        };
    }

    /**
     * Get the sentence saying why this copy sits where it does in the order.
     *
     * Only the first two keys of the order are ever given as reasons; the third,
     * tile order, exists for determinism and explains nothing.
     */
    public function reason(): string
    {
        if (! $this->isPassable()) {
            return 'Jokers are never passed — keep it, whatever you are chasing.';
        }

        if ($this->wantedBy !== []) {
            return $this->costOfPassing();
        }

        $lead = $this->isSpareCopy()
            ? "Your {$this->ordinal($this->copy)} {$this->name()} is spare — your leading hands use only {$this->cardinal($this->mostPlacedByLeading)}"
            : 'None of your leading hands uses it';

        return match ($this->linesWanting) {
            0 => "{$lead}, and no line on the card would.",
            1 => "{$lead}; 1 other line on the card would.",
            default => "{$lead}; {$this->linesWanting} other lines on the card would.",
        };
    }

    /**
     * Name the leading hands that want this copy, and what passing it costs them.
     */
    private function costOfPassing(): string
    {
        $names = array_map(fn (HandMatch $match): string => $match->name, $this->wantedBy);

        if (count($names) === 1) {
            return "Wanted only by {$names[0]} — passing it puts that hand one tile further away.";
        }

        $last = array_pop($names);
        $listed = implode(', ', $names)." and {$last}";

        return count($this->wantedBy) === 2
            ? "Wanted by {$listed} — passing it puts both of them one tile further away."
            : "Wanted by {$listed} — passing it puts each of them one tile further away.";
    }

    private function name(): string
    {
        return TileFace::of($this->tile)->name;
    }

    private function ordinal(int $number): string
    {
        return ['first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth'][$number - 1] ?? "#{$number}";
    }

    private function cardinal(int $number): string
    {
        return ['one', 'two', 'three', 'four', 'five', 'six', 'seven'][$number - 1] ?? (string) $number;
    }
}

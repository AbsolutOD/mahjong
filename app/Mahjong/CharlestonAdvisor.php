<?php

namespace App\Mahjong;

use App\Data\Charleston\CharlestonAdvice;
use App\Data\Charleston\TileAdvice;
use App\Data\Matching\HandMatch;
use App\Data\Matching\Rack;

/**
 * Reads a ranked card for what the rack can afford to let go of (issue #17).
 *
 * A rack tile is *wanted by* a hand when that hand's best-binding coverage
 * places it — judged per copy, and at the very binding the hand's row names,
 * so advice and row can never disagree. Advice is judged against the leading
 * hands: the ones the learner pinned, or the top of the ranking.
 *
 * Marginal distance — take each tile away and re-rank — was rejected for the
 * same reason #15 refused a blended score: its explanation is a computed number,
 * where "none of your three closest hands uses the 7 Bam" is checkable at the
 * table.
 */
class CharlestonAdvisor
{
    /**
     * How many of the ranking's top lines lead when the learner has pinned none.
     */
    public const int DEFAULT_LEADING = 3;

    /**
     * Advise on a rack, given the card already ranked against it.
     *
     * Pins name lines by slug. A pin the card does not print is ignored, and if
     * none survive the advice falls back to the top of the ranking.
     *
     * @param  list<HandMatch>  $ranked  every line, closest first, as {@see HandMatcher::rank()} returns them
     * @param  list<string>  $pins
     */
    public function advise(array $ranked, Rack $rack, array $pins = []): CharlestonAdvice
    {
        $pinned = array_values(array_filter(
            $ranked,
            fn (HandMatch $match): bool => in_array($match->hand->slug, $pins, true),
        ));

        $leading = $pinned !== [] ? $pinned : array_slice($ranked, 0, self::DEFAULT_LEADING);

        $placedByLeading = array_map(fn (HandMatch $match): array => $match->coverage->placed(), $leading);
        $placedByCard = array_map(fn (HandMatch $match): array => $match->coverage->placed(), $ranked);

        $tiles = [];
        $seen = [];

        foreach ($rack->tiles as $tile) {
            $code = $tile->code();
            $copy = $seen[$code] = ($seen[$code] ?? 0) + 1;

            $wantedBy = [];

            foreach ($leading as $index => $match) {
                if (($placedByLeading[$index][$code] ?? 0) >= $copy) {
                    $wantedBy[] = $match;
                }
            }

            $tiles[] = new TileAdvice(
                $tile,
                $copy,
                $wantedBy,
                count(array_filter($placedByCard, fn (array $placed): bool => ($placed[$code] ?? 0) >= $copy)),
                max([0, ...array_map(fn (array $placed): int => $placed[$code] ?? 0, $placedByLeading)]),
            );
        }

        return new CharlestonAdvice(
            $tiles,
            $leading,
            $pinned !== [],
            ($ranked[0] ?? null)?->isComplete() ?? false,
        );
    }
}

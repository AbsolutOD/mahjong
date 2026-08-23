<?php

namespace App\Mahjong;

use App\Data\Matching\Coverage;
use App\Data\Matching\HandMatch;
use App\Data\Matching\Instantiation;
use App\Data\Matching\Rack;
use App\Models\Card;
use App\Models\Hand;

/**
 * Ranks the whole card against a rack, closest line first.
 *
 * Every hand is measured at its *best* binding rather than once per binding:
 * `FF 2026(A) 2026(B) DDDD(C)` binds six ways, and showing all six would bury
 * the card's variety under three hundred near-duplicate rows. The binding it
 * settled on is reported with the row, because that is the same lesson the
 * decoder's "try it in real suits" teaches — the card's colours mean suits that
 * must differ, not fixed suits (issue #15).
 *
 * Enumeration is exhaustive and uncached. The whole card is 818 settled hands,
 * so exactness costs less than any scheme for avoiding it would.
 */
class HandMatcher
{
    /**
     * Rank every line on the card against the rack.
     *
     * Ties break on points and then on card order, so the rule stays as
     * checkable as the key — *same distance, worth more* — and the order stays
     * total, which is what keeps the list from jittering between renders.
     *
     * @return list<HandMatch>
     */
    public function rank(Card $card, Rack $rack): array
    {
        $matches = [];
        $order = 0;

        foreach ($card->categories as $category) {
            foreach ($category->hands as $hand) {
                $match = $this->match($hand, $rack, $order++);

                if ($match !== null) {
                    $matches[] = $match;
                }
            }
        }

        usort($matches, fn (HandMatch $a, HandMatch $b): int => [
            $a->tilesAway(), -$a->hand->points, $a->cardOrder,
        ] <=> [
            $b->tilesAway(), -$b->hand->points, $b->cardOrder,
        ]);

        return $matches;
    }

    /**
     * Measure one line against the rack, at the hand it best could become.
     *
     * Where several settled hands tie, the first enumerated wins, which binds
     * the letters in card order — A to dots, B to bams, C to craks — rather
     * than asserting a binding the rack gave no reason for.
     *
     * Returns null for a line the game cannot supply under any assignment; the
     * practice card holds none, but nothing in the schema forbids one.
     */
    public function match(Hand $hand, Rack $rack, int $cardOrder = 0): ?HandMatch
    {
        $best = null;

        foreach (Instantiation::forStructure($hand->structure) as $instantiation) {
            $coverage = Coverage::of($instantiation, $rack);

            if ($best === null || $coverage->covered > $best->coverage->covered) {
                $best = new HandMatch($hand, $instantiation, $coverage, $cardOrder);
            }
        }

        return $best;
    }
}

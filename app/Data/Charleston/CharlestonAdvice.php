<?php

namespace App\Data\Charleston;

use App\Data\HandStructure;
use App\Data\Matching\HandMatch;

/**
 * The Charleston advice for one rack — which tiles to let go of, and why.
 *
 * Stateless by design (issue #17): it knows nothing of which pass this is or
 * what came across, only the rack and the hands it is leading towards. After a
 * pass the learner edits the rack and asks again.
 */
readonly class CharlestonAdvice
{
    /**
     * How many tiles each Charleston pass moves.
     */
    public const int PASS_SIZE = 3;

    /**
     * @param  list<TileAdvice>  $tiles  one per copy on the rack, in rack order
     * @param  list<HandMatch>  $leading  the hands the advice is judged against
     * @param  bool  $pinned  whether the learner chose the leading hands, rather than the ranking
     * @param  bool  $holdsHand  whether the rack is already a complete line
     */
    public function __construct(
        public array $tiles,
        public array $leading,
        public bool $pinned,
        public bool $holdsHand,
    ) {
        //
    }

    /**
     * Get every passable copy, the first to let go of first.
     *
     * Fewest leading hands wanting it, then fewest lines on the card, then the
     * rack's own set order — which the copies already arrive in, so a stable
     * sort supplies the last key. Jokers never enter the order.
     *
     * @return list<TileAdvice>
     */
    public function passingOrder(): array
    {
        $order = array_values(array_filter($this->tiles, fn (TileAdvice $tile): bool => $tile->isPassable()));

        usort($order, fn (TileAdvice $a, TileAdvice $b): int => [
            count($a->wantedBy), $a->linesWanting,
        ] <=> [
            count($b->wantedBy), $b->linesWanting,
        ]);

        return $order;
    }

    /**
     * Get the call to action this rack size asks for, or null if it asks none.
     *
     * Thirteen tiles is a Charleston rack; fourteen is a turn, so the same order
     * answers which one to discard. Below thirteen the rack is still being
     * built, and a rack that already is a hand should be declared, not broken.
     */
    public function call(): ?string
    {
        return match (true) {
            $this->holdsHand => null,
            count($this->tiles) === HandStructure::HAND_SIZE - 1 => 'Pass these '.self::PASS_SIZE,
            count($this->tiles) === HandStructure::HAND_SIZE => 'Discard this 1',
            default => null,
        };
    }

    /**
     * Get the copies the call to action names.
     *
     * @return list<TileAdvice>
     */
    public function recommended(): array
    {
        return match ($this->call()) {
            null => [],
            'Discard this 1' => array_slice($this->passingOrder(), 0, 1),
            default => array_slice($this->passingOrder(), 0, self::PASS_SIZE),
        };
    }

    /**
     * Determine whether the given line is one the advice is judged against.
     */
    public function isLeading(HandMatch $match): bool
    {
        foreach ($this->leading as $leading) {
            if ($leading->hand->slug === $match->hand->slug) {
                return true;
            }
        }

        return false;
    }
}

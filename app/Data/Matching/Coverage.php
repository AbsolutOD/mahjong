<?php

namespace App\Data\Matching;

use App\Data\HandStructure;
use App\Data\Tiles\Tile;
use App\Enums\SlotState;

/**
 * How much of one settled hand a rack already covers.
 *
 * Coverage is computed slot by slot rather than as a count, because the number
 * is only half of what the player needs: the other half is *which* tiles they
 * are still short of. Both fall out of the same pass, so re-deriving either one
 * in a template would be the drift the decoding layer was built to avoid.
 */
readonly class Coverage
{
    /**
     * @param  list<list<MatchedSlot>>  $groups  slots parallel to the hand's groups
     */
    private function __construct(
        public array $groups,
        public int $covered,
        public int $jokersUsed,
    ) {
        //
    }

    /**
     * Work out what the rack covers of the given hand.
     *
     * Real tiles are laid into the groups no joker may fill before the groups
     * that take them, which is what stops a natural being spent on a slot a
     * joker could have filled while a pair goes wanting.
     */
    public static function of(Instantiation $instantiation, Rack $rack): self
    {
        $pool = $rack->counts();
        $jokers = $pool[Tile::joker()->code()] ?? 0;
        unset($pool[Tile::joker()->code()]);

        $states = array_map(
            fn (array $tiles): array => array_fill(0, count($tiles), SlotState::Missing),
            $instantiation->groups,
        );

        foreach ([false, true] as $eligible) {
            foreach ($instantiation->groups as $index => $tiles) {
                if ($instantiation->acceptsJokers[$index] !== $eligible) {
                    continue;
                }

                foreach ($tiles as $position => $tile) {
                    if (($pool[$tile->code()] ?? 0) > 0) {
                        $pool[$tile->code()]--;
                        $states[$index][$position] = SlotState::Held;
                    }
                }
            }
        }

        $jokersUsed = 0;

        foreach ($instantiation->groups as $index => $tiles) {
            if (! $instantiation->acceptsJokers[$index]) {
                continue;
            }

            foreach ($tiles as $position => $tile) {
                if ($states[$index][$position] === SlotState::Missing && $jokersUsed < $jokers) {
                    $states[$index][$position] = SlotState::Joker;
                    $jokersUsed++;
                }
            }
        }

        $groups = [];
        $covered = 0;

        foreach ($instantiation->groups as $index => $tiles) {
            $slots = [];

            foreach ($tiles as $position => $tile) {
                $slots[] = new MatchedSlot($tile, $states[$index][$position]);
                $covered += $states[$index][$position]->isCovered() ? 1 : 0;
            }

            $groups[] = $slots;
        }

        return new self($groups, $covered, $jokersUsed);
    }

    /**
     * Get how many more tiles the hand needs.
     */
    public function tilesAway(): int
    {
        return HandStructure::HAND_SIZE - $this->covered;
    }

    /**
     * Get the tiles still needed, gathered into one entry per face.
     *
     * The breakdown already marks every slot, so this is the take-away rather
     * than the detail: what to watch the discards for.
     *
     * @return list<array{tile: Tile, count: int}>
     */
    public function stillNeeded(): array
    {
        $needed = [];

        foreach ($this->missing() as $tile) {
            $needed[$tile->code()] ??= ['tile' => $tile, 'count' => 0];
            $needed[$tile->code()]['count']++;
        }

        return array_values($needed);
    }

    /**
     * Get the tiles the rack is still short of, in card order.
     *
     * @return list<Tile>
     */
    public function missing(): array
    {
        $missing = [];

        foreach ($this->groups as $slots) {
            foreach ($slots as $slot) {
                if ($slot->state === SlotState::Missing) {
                    $missing[] = $slot->tile;
                }
            }
        }

        return $missing;
    }
}

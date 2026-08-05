<?php

namespace App\Data\Decoding;

use App\Data\HandStructure;
use App\Data\TileGroup;
use App\Data\Tiles\SuitAssignment;
use App\Enums\TagTone;

/**
 * A whole card line, read aloud — the Visual Breakdown in one value.
 *
 * The panel that renders this holds no copy of its own. Every word on screen
 * comes from here, and everything here comes from the structure, so a hand and
 * its explanation cannot drift apart.
 */
readonly class HandReading
{
    /**
     * @param  list<GroupReading>  $groups
     * @param  list<RuleTag>  $tags
     * @param  list<string>  $suitVariables  the letters the player may still bind to real suits
     */
    private function __construct(
        public array $groups,
        public string $summary,
        public array $tags,
        public array $suitVariables,
    ) {
        //
    }

    /**
     * Read a hand under the given suit assignment.
     *
     * Concealed is passed in rather than read from the structure because it is
     * something the card prints beside the line, not something its tiles say.
     */
    public static function for(HandStructure $structure, SuitAssignment $assignment, bool $concealed): self
    {
        $groups = array_map(
            fn (TileGroup $group): GroupReading => GroupReading::for($group, $assignment),
            $structure->groups,
        );

        return new self(
            $groups,
            self::summarise($groups),
            self::tags($structure, $concealed),
            $structure->suitVariables(),
        );
    }

    /**
     * String the group readings into the one sentence that reads the hand out.
     *
     * @param  list<GroupReading>  $groups
     */
    private static function summarise(array $groups): string
    {
        $clauses = array_map(fn (GroupReading $group): string => lcfirst($group->sentence), $groups);

        $last = array_pop($clauses);

        $sentence = $clauses === []
            ? $last
            : implode(', ', $clauses).', and '.$last;

        return ucfirst($sentence).'.';
    }

    /**
     * Derive the rules the panel prints beneath the breakdown.
     *
     * @return list<RuleTag>
     */
    private static function tags(HandStructure $structure, bool $concealed): array
    {
        $tags = [
            $concealed
                ? new RuleTag('Concealed — no exposures', TagTone::Concealed)
                : new RuleTag('May be exposed', TagTone::Neutral),
        ];

        $suits = $structure->suitCount();

        if ($suits === 1) {
            $tags[] = new RuleTag('1 suit', TagTone::Suit);
        } elseif ($suits > 1) {
            $tags[] = new RuleTag("{$suits} suits, all different", TagTone::Suit);
        }

        $jokers = $structure->maxJokers();

        $tags[] = $jokers > 0
            ? new RuleTag("Jokers OK — up to {$jokers}", TagTone::Joker)
            : new RuleTag('No jokers anywhere', TagTone::Forbidden);

        if ($structure->hasPair()) {
            $tags[] = new RuleTag('Contains a pair — no jokers there', TagTone::Neutral);
        }

        if ($structure->usesFlowers()) {
            $tags[] = new RuleTag('Flowers are interchangeable', TagTone::Neutral);
        }

        return $tags;
    }
}

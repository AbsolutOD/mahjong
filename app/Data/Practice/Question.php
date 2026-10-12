<?php

namespace App\Data\Practice;

use App\Data\Decoding\GroupReading;
use App\Data\Decoding\HandReading;
use App\Data\Decoding\RuleTag;
use App\Data\Tiles\SuitAssignment;
use App\Enums\QuestionKind;
use App\Enums\TagTone;
use App\Mahjong\AmericanMahjong;
use App\Models\Category;
use App\Models\Hand;
use InvalidArgumentException;

/**
 * One practice question about one card line, with its answer and the reason.
 *
 * Nothing here is authored. The answer is read off the hand's structure and
 * the feedback is the decoder's own prose, so a card change cannot strand a
 * question whose answer stopped being true (issue #18).
 *
 * @phpstan-type QuestionKey array{kind: string, hand: string, group: int|null}
 */
readonly class Question
{
    /**
     * @param  int|null  $group  the group a joker question points at, by position in the line
     * @param  string  $answer  the choice that is right, as {@see self::choices()} values it
     * @param  string  $verdict  the answer said as the decoder says it — "3 suits, all different"
     * @param  string  $reason  why, in the decoder's own words
     */
    private function __construct(
        public QuestionKind $kind,
        public Category $category,
        public Hand $hand,
        public ?int $group,
        public string $answer,
        public string $verdict,
        public string $reason,
    ) {
        //
    }

    /**
     * Ask how many different suits a line needs.
     *
     * The verdict is the suit tag the decoder prints, and the reason is its
     * summary, which names the suit letter of every group under no binding.
     */
    public static function suitCount(Category $category, Hand $hand): self
    {
        $suits = $hand->structure->suitCount();

        throw_if($suits === 0, new InvalidArgumentException('A line with no suit letter has no suit count to ask about.'));

        $reading = HandReading::for($hand->structure, SuitAssignment::none(), $hand->concealed);

        $tag = array_values(array_filter($reading->tags, fn (RuleTag $tag): bool => $tag->tone === TagTone::Suit))[0];

        return new self(QuestionKind::SuitCount, $category, $hand, null, (string) $suits, $tag->label, $reading->summary);
    }

    /**
     * Ask whether a joker may stand in for a tile in one group of a line.
     */
    public static function jokerLegality(Category $category, Hand $hand, int $group): self
    {
        $tiles = $hand->structure->groups[$group]
            ?? throw new InvalidArgumentException("The line has no group at position [{$group}].");

        $reading = GroupReading::for($tiles, SuitAssignment::none());

        $rule = $reading->acceptsJokers
            ? 'It is '.AmericanMahjong::MINIMUM_JOKER_GROUP_SIZE.' or more identical tiles, and that is the only kind of group a joker may join.'
            : 'A joker only joins a group of '.AmericanMahjong::MINIMUM_JOKER_GROUP_SIZE.' or more identical tiles, never a pair, a single, NEWS or a run.';

        return new self(
            QuestionKind::JokerLegality,
            $category,
            $hand,
            $group,
            $reading->acceptsJokers ? 'yes' : 'no',
            $reading->label.': '.($reading->acceptsJokers ? 'jokers may substitute' : 'no jokers here'),
            ucfirst($reading->sentence).'. '.$rule,
        );
    }

    /**
     * Get the answers on offer, as the value the player's choice travels by
     * and the label it prints with.
     *
     * A list of pairs rather than a map, because PHP would turn the suit
     * counts' keys into integers and the choice travels as a string.
     *
     * @return list<array{value: string, label: string}>
     */
    public function choices(): array
    {
        return match ($this->kind) {
            QuestionKind::SuitCount => [
                ['value' => '1', 'label' => '1 suit'],
                ['value' => '2', 'label' => '2 suits'],
                ['value' => '3', 'label' => '3 suits'],
            ],
            QuestionKind::JokerLegality => [
                ['value' => 'yes', 'label' => 'Yes'],
                ['value' => 'no', 'label' => 'No'],
            ],
        };
    }

    /**
     * Determine whether the given choice is one the question offers.
     */
    public function offers(string $choice): bool
    {
        return in_array($choice, array_column($this->choices(), 'value'), strict: true);
    }

    /**
     * Determine whether the given choice is the right answer.
     */
    public function isCorrect(string $choice): bool
    {
        return $choice === $this->answer;
    }

    /**
     * Get what the question is about, small enough to keep between requests.
     *
     * @return QuestionKey
     */
    public function key(): array
    {
        return ['kind' => $this->kind->value, 'hand' => $this->hand->slug, 'group' => $this->group];
    }
}

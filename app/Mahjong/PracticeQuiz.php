<?php

namespace App\Mahjong;

use App\Data\Practice\Question;
use App\Enums\QuestionKind;
use App\Models\Card;
use App\Models\Category;
use App\Models\Hand;
use Random\Randomizer;

/**
 * Draws rounds of practice questions from the card (issue #18).
 *
 * A round is half suit-count and half joker-legality questions, shuffled, with
 * no line asked about twice in the same way. Both kinds are drawn so that
 * guessing the common answer stops paying:
 *
 * - **Suit count** picks the answer first, evenly from the counts the card
 *   offers, then a line with that count. Drawing lines evenly instead would
 *   make "3 suits" right two times in three, because most of the card is.
 * - **Joker legality** weights the groups no joker may join — pairs, singles,
 *   NEWS and year runs — above the ones it may, because every pung is a free
 *   "yes" and the traps are what the rules research says a learner gets wrong.
 *
 * @phpstan-import-type QuestionKey from Question
 */
class PracticeQuiz
{
    /**
     * The number of questions in a round.
     */
    public const int ROUND_SIZE = 10;

    /**
     * How many times likelier a joker question is to mark a group no joker may join.
     */
    public const int TRAP_WEIGHT = 2;

    public function __construct(
        private Randomizer $randomizer = new Randomizer,
    ) {
        //
    }

    /**
     * Draw a fresh round from the card.
     *
     * @return list<Question>
     */
    public function round(Card $card): array
    {
        $suitCount = intdiv(self::ROUND_SIZE, 2);

        $questions = [
            ...$this->suitCountQuestions($card, $suitCount),
            ...$this->jokerQuestions($card, self::ROUND_SIZE - $suitCount),
        ];

        return array_values($this->randomizer->shuffleArray($questions));
    }

    /**
     * Rebuild a question from its key, or null if the card no longer holds it.
     *
     * @param  QuestionKey  $key
     */
    public function find(Card $card, array $key): ?Question
    {
        foreach ($this->lines($card) as [$category, $hand]) {
            if ($hand->slug !== $key['hand']) {
                continue;
            }

            return match (QuestionKind::tryFrom($key['kind'])) {
                QuestionKind::SuitCount => $hand->structure->suitCount() > 0
                    ? Question::suitCount($category, $hand)
                    : null,
                QuestionKind::JokerLegality => $key['group'] !== null && isset($hand->structure->groups[$key['group']])
                    ? Question::jokerLegality($category, $hand, $key['group'])
                    : null,
                null => null,
            };
        }

        return null;
    }

    /**
     * Draw suit-count questions, each answer as likely as the next.
     *
     * Lines with no suit letter at all — the wind lines — have no suit count to
     * ask about, so they are never drawn.
     *
     * @return list<Question>
     */
    private function suitCountQuestions(Card $card, int $count): array
    {
        /** @var array<int, list<array{0: Category, 1: Hand}>> $bySuits */
        $bySuits = [];

        foreach ($this->lines($card) as $line) {
            $suits = $line[1]->structure->suitCount();

            if ($suits > 0) {
                $bySuits[$suits][] = $line;
            }
        }

        $questions = [];

        while (count($questions) < $count && $bySuits !== []) {
            $suits = $this->randomizer->pickArrayKeys($bySuits, 1)[0];
            $pick = $this->randomizer->getInt(0, count($bySuits[$suits]) - 1);

            [[$category, $hand]] = array_splice($bySuits[$suits], $pick, 1);
            $questions[] = Question::suitCount($category, $hand);

            if ($bySuits[$suits] === []) {
                unset($bySuits[$suits]);
            }
        }

        return $questions;
    }

    /**
     * Draw joker-legality questions, weighted toward the groups no joker may join.
     *
     * @return list<Question>
     */
    private function jokerQuestions(Card $card, int $count): array
    {
        /** @var list<array{category: Category, hand: Hand, group: int, weight: int}> $candidates */
        $candidates = [];

        foreach ($this->lines($card) as [$category, $hand]) {
            foreach ($hand->structure->groups as $position => $group) {
                $candidates[] = [
                    'category' => $category,
                    'hand' => $hand,
                    'group' => $position,
                    'weight' => $group->acceptsJokers() ? 1 : self::TRAP_WEIGHT,
                ];
            }
        }

        $questions = [];

        while (count($questions) < $count && $candidates !== []) {
            $pick = $this->weightedPick(array_column($candidates, 'weight'));
            $chosen = $candidates[$pick];

            $questions[] = Question::jokerLegality($chosen['category'], $chosen['hand'], $chosen['group']);

            /** One question per line, so a round never asks about the same line twice. */
            $candidates = array_values(array_filter(
                $candidates,
                fn (array $candidate): bool => $candidate['hand'] !== $chosen['hand'],
            ));
        }

        return $questions;
    }

    /**
     * Pick a position at random, each as likely as its weight says.
     *
     * @param  list<int>  $weights
     */
    private function weightedPick(array $weights): int
    {
        $roll = $this->randomizer->getInt(1, array_sum($weights));

        foreach ($weights as $position => $weight) {
            $roll -= $weight;

            if ($roll <= 0) {
                return $position;
            }
        }

        return array_key_last($weights);
    }

    /**
     * Get every line on the card beside the section it sits in, in card order.
     *
     * @return list<array{0: Category, 1: Hand}>
     */
    private function lines(Card $card): array
    {
        $lines = [];

        foreach ($card->categories as $category) {
            foreach ($category->hands as $hand) {
                $lines[] = [$category, $hand];
            }
        }

        return $lines;
    }
}

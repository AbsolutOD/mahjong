<?php

use App\Data\HandStructure;
use App\Data\Practice\Question;
use App\Enums\QuestionKind;
use App\Mahjong\PracticeQuiz;
use App\Models\Card;
use App\Models\Category;
use App\Models\Hand;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * A line of fourteen built from groups, off the database.
 *
 * @param  list<list<array<string, mixed>>>  $groups
 * @param  list<string>  $suits  the suit letters the line declares
 */
function quizLine(string $slug, array $groups, array $suits): Hand
{
    return new Hand([
        'slug' => $slug,
        'points' => 25,
        'concealed' => false,
        'structure' => HandStructure::fromArray([
            'variables' => array_fill_keys($suits, ['kind' => 'suit']),
            'groups' => $groups,
        ]),
    ]);
}

/**
 * Repeat one numbered tile of a suit into a group.
 *
 * @return list<array<string, mixed>>
 */
function quizGroup(string $suit, int $number, int $size): array
{
    return array_fill(0, $size, ['t' => 'num', 'suit' => $suit, 'n' => $number]);
}

/**
 * The fixture card: mostly three-suit lines, as the real card is, a pair of
 * one-suit and two-suit lines, and one wind line with no suit letter at all.
 */
function quizCard(): Card
{
    $hands = [
        quizLine('one-suit-a', [quizGroup('A', 1, 2), quizGroup('A', 3, 4), quizGroup('A', 5, 4), quizGroup('A', 7, 4)], ['A']),
        quizLine('one-suit-b', [quizGroup('A', 2, 2), quizGroup('A', 4, 4), quizGroup('A', 6, 4), quizGroup('A', 8, 4)], ['A']),
        quizLine('two-suits-a', [quizGroup('A', 1, 2), quizGroup('A', 3, 4), quizGroup('B', 5, 4), quizGroup('B', 7, 4)], ['A', 'B']),
        quizLine('two-suits-b', [quizGroup('A', 2, 2), quizGroup('A', 4, 4), quizGroup('B', 6, 4), quizGroup('B', 8, 4)], ['A', 'B']),
        ...array_map(
            fn (int $number): Hand => quizLine("three-suits-{$number}", [
                quizGroup('A', $number, 2), quizGroup('B', $number, 4), quizGroup('C', $number, 4), quizGroup('C', $number + 1, 4),
            ], ['A', 'B', 'C']),
            range(1, 6),
        ),
        quizLine('winds', [
            [['t' => 'wind', 'w' => 'N'], ['t' => 'wind', 'w' => 'E'], ['t' => 'wind', 'w' => 'W'], ['t' => 'wind', 'w' => 'S']],
            array_fill(0, 4, ['t' => 'wind', 'w' => 'N']),
            array_fill(0, 6, ['t' => 'flower']),
        ], []),
    ];

    $card = new Card(['name' => 'Quiz Fixture']);
    $category = new Category(['name' => 'Fixture', 'slug' => 'fixture']);

    $category->setRelation('hands', collect($hands));
    $card->setRelation('categories', collect([$category]));

    return $card;
}

function seededQuiz(int $seed = 1): PracticeQuiz
{
    return new PracticeQuiz(new Randomizer(new Mt19937($seed)));
}

test('a round is ten questions, half of each kind', function () {
    $round = seededQuiz()->round(quizCard());

    $kinds = array_map(fn (Question $question): string => $question->kind->value, $round);

    expect($round)->toHaveCount(PracticeQuiz::ROUND_SIZE)
        ->and(array_count_values($kinds))->toEqual(['suits' => 5, 'jokers' => 5]);
});

test('every answer is read off the line it asks about', function () {
    foreach (range(1, 50) as $seed) {
        foreach (seededQuiz($seed)->round(quizCard()) as $question) {
            $expected = $question->kind === QuestionKind::SuitCount
                ? (string) $question->hand->structure->suitCount()
                : ($question->hand->structure->groups[$question->group]->acceptsJokers() ? 'yes' : 'no');

            expect($question->answer)->toBe($expected)
                ->and($question->offers($question->answer))->toBeTrue();
        }
    }
});

test('a line with no suit letter is never asked for its suit count', function () {
    foreach (range(1, 50) as $seed) {
        foreach (seededQuiz($seed)->round(quizCard()) as $question) {
            if ($question->kind === QuestionKind::SuitCount) {
                expect($question->hand->slug)->not->toBe('winds');
            }
        }
    }

    expect(fn () => Question::suitCount(quizCard()->categories->first(), quizCard()->categories->first()->hands->last()))
        ->toThrow(InvalidArgumentException::class);
});

test('no round asks the same question of a line twice', function () {
    foreach (range(1, 50) as $seed) {
        $keys = array_map(
            fn (Question $question): string => $question->kind->value.':'.$question->hand->slug,
            seededQuiz($seed)->round(quizCard()),
        );

        expect(array_unique($keys))->toHaveCount(count($keys));
    }
});

/**
 * Six of the fixture's nine suited lines need three suits, so drawing lines
 * evenly would make "3 suits" right two times in three. Drawing the answer
 * first keeps every count about equally likely.
 */
test('every suit count is about as likely to be the answer as the next', function () {
    $answers = [];

    foreach (range(1, 300) as $seed) {
        foreach (seededQuiz($seed)->round(quizCard()) as $question) {
            if ($question->kind === QuestionKind::SuitCount) {
                $answers[] = $question->answer;
            }
        }
    }

    $shares = array_map(fn (int $count): float => $count / count($answers), array_count_values($answers));

    expect($shares)->toHaveKeys(['1', '2', '3']);

    foreach ($shares as $share) {
        expect($share)->toBeGreaterThan(0.25)->toBeLessThan(0.42);
    }
});

/**
 * Every pung is a free "yes", so the groups no joker may join are drawn more
 * often. The fixture's lines all lead with a pair, so "no" is a quarter of
 * its groups and would be a quarter of the answers if drawn evenly.
 */
test('joker questions lean toward the groups no joker may join', function () {
    $answers = [];

    foreach (range(1, 300) as $seed) {
        foreach (seededQuiz($seed)->round(quizCard()) as $question) {
            if ($question->kind === QuestionKind::JokerLegality) {
                $answers[] = $question->answer;
            }
        }
    }

    $noShare = count(array_keys($answers, 'no', strict: true)) / count($answers);

    expect($noShare)->toBeGreaterThan(0.33);
});

test('the same seed draws the same round', function () {
    $keys = fn (int $seed): array => array_map(fn (Question $question): array => $question->key(), seededQuiz($seed)->round(quizCard()));

    expect($keys(7))->toBe($keys(7))
        ->and($keys(7))->not->toBe($keys(8));
});

test('a question is rebuilt from its key, and a key the card no longer holds finds nothing', function () {
    $card = quizCard();
    $quiz = seededQuiz();

    foreach ($quiz->round($card) as $question) {
        $found = $quiz->find($card, $question->key());

        expect($found?->key())->toBe($question->key())
            ->and($found?->answer)->toBe($question->answer);
    }

    expect($quiz->find($card, ['kind' => 'suits', 'hand' => 'gone', 'group' => null]))->toBeNull()
        ->and($quiz->find($card, ['kind' => 'jokers', 'hand' => 'one-suit-a', 'group' => 9]))->toBeNull()
        ->and($quiz->find($card, ['kind' => 'suits', 'hand' => 'winds', 'group' => null]))->toBeNull()
        ->and($quiz->find($card, ['kind' => 'riddles', 'hand' => 'one-suit-a', 'group' => null]))->toBeNull();
});

test('a suit count is answered in the decoder\'s words', function () {
    $category = quizCard()->categories->first();
    $hand = $category->hands->firstWhere('slug', 'two-suits-a');

    $question = Question::suitCount($category, $hand);

    expect($question->answer)->toBe('2')
        ->and($question->verdict)->toBe('2 suits, all different')
        ->and($question->reason)->toContain('in suit A')->toContain('in suit B')
        ->and($question->isCorrect('2'))->toBeTrue()
        ->and($question->isCorrect('3'))->toBeFalse();
});

test('a joker question on a pair says no, and says why', function () {
    $category = quizCard()->categories->first();
    $hand = $category->hands->firstWhere('slug', 'one-suit-a');

    $pair = Question::jokerLegality($category, $hand, 0);
    $kong = Question::jokerLegality($category, $hand, 1);

    expect($pair->answer)->toBe('no')
        ->and($pair->verdict)->toBe('Pair: no jokers here')
        ->and($pair->reason)->toStartWith('Pair of 1s in suit A.')->toContain('never a pair')
        ->and($kong->answer)->toBe('yes')
        ->and($kong->verdict)->toBe('Kong: jokers may substitute');
});

test('a joker question names a group the line has', function () {
    $category = quizCard()->categories->first();

    expect(fn () => Question::jokerLegality($category, $category->hands->first(), 4))
        ->toThrow(InvalidArgumentException::class);
});

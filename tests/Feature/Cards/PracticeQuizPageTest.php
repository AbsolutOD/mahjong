<?php

use App\Actions\Cards\LoadCurrentCard;
use App\Data\Practice\Question;
use App\Enums\Phase;
use App\Enums\QuestionKind;
use App\Mahjong\LineRenderer;
use App\Mahjong\PracticeQuiz;
use App\Models\Card;
use Database\Seeders\CardSeeder;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Seed the real practice card and fix the draw, so a round is the same every run.
 */
beforeEach(function () {
    $this->seed(CardSeeder::class);

    app()->instance(PracticeQuiz::class, new PracticeQuiz(new Randomizer(new Mt19937(42))));
});

/**
 * Get the question on show, rebuilt the way the component rebuilds it.
 */
function questionOnShow(Testable $component): Question
{
    $key = $component->get('round')[$component->get('position')];

    return app(PracticeQuiz::class)->find(app(LoadCurrentCard::class)->handle(), $key);
}

/**
 * Get a choice the question on show counts as wrong.
 */
function wrongChoice(Question $question): string
{
    return array_values(array_diff(array_column($question->choices(), 'value'), [$question->answer]))[0];
}

test('practice is public, because drilling the card needs no account', function () {
    $this->get(route('practice'))
        ->assertOk()
        ->assertSee('Question 1 of 10');
});

/**
 * The page's heading had drifted to "Practice" alone; it names the phase the
 * way every nav does (issue #53). Rendered without the layout, so the page's
 * `<title>` cannot pass this on the heading's behalf.
 */
test('a round is headed with the phase\'s name', function () {
    Livewire::test('pages::practice.quiz')
        ->assertSee(Phase::PracticeAndQuiz->label());
});

test('a round is ten questions about lines the real card prints, half of each kind', function () {
    $component = Livewire::test('pages::practice.quiz');

    $round = $component->get('round');

    expect($round)->toHaveCount(10)
        ->and(array_count_values(array_column($round, 'kind')))->toEqual([
            QuestionKind::SuitCount->value => 5,
            QuestionKind::JokerLegality->value => 5,
        ]);
});

/**
 * Tiles would answer a suit-count question before it is read, since a tile's
 * suit band *is* its suit — so the quiz shows the card's shorthand alone.
 */
test('a question shows the line as shorthand, never as tiles', function () {
    $component = Livewire::test('pages::practice.quiz');

    $question = questionOnShow($component);

    $component->assertSee($question->kind->prompt())
        ->assertSee(app(LineRenderer::class)->renderGroups($question->hand->structure)[0])
        ->assertDontSeeHtml('data-tile-face');
});

test('a right answer says so, gives the reason, and links the line on the card', function () {
    $component = Livewire::test('pages::practice.quiz');
    $question = questionOnShow($component);

    $component->call('answer', $question->answer)
        ->assertSee('Right')
        ->assertSee($question->verdict)
        ->assertSee($question->reason)
        ->assertSee(route('card', ['category' => $question->category->slug, 'hand' => $question->hand->slug]))
        ->assertSee('1 right so far');
});

test('a wrong answer says so, with the same reason', function () {
    $component = Livewire::test('pages::practice.quiz');
    $question = questionOnShow($component);

    $component->call('answer', wrongChoice($question))
        ->assertSee('Not quite')
        ->assertSee($question->verdict)
        ->assertSee('0 right so far');
});

test('a question is answered once, and only with a choice it offers', function () {
    $component = Livewire::test('pages::practice.quiz');
    $question = questionOnShow($component);

    $component->call('answer', 'banana')
        ->assertSet('answers', [])
        ->call('answer', wrongChoice($question))
        ->call('answer', $question->answer)
        ->assertSet('answers', [wrongChoice($question)]);
});

test('the round only moves on once the question on show is answered', function () {
    Livewire::test('pages::practice.quiz')
        ->call('next')
        ->assertSet('position', 0);
});

test('ten answers end on the score screen, and another round starts over', function () {
    $component = Livewire::test('pages::practice.quiz');

    foreach (range(1, 10) as $number) {
        $question = questionOnShow($component);

        $component->call('answer', $number <= 7 ? $question->answer : wrongChoice($question))
            ->call('next');
    }

    $component->assertSee('Round over')
        ->assertSee('7 of 10')
        ->assertSee('Play another round')
        ->call('startRound')
        ->assertSet('position', 0)
        ->assertSet('answers', [])
        ->assertSee('Question 1 of 10');
});

/**
 * A deploy can reseed the card between two clicks; a question about a line the
 * card no longer prints has no honest answer left, so the round starts over.
 */
test('a round about lines the card no longer prints starts over', function () {
    $component = Livewire::test('pages::practice.quiz');

    $stale = $component->get('round');
    $stale[3]['hand'] = 'a-line-no-card-prints';

    $component->set('round', $stale)
        ->call('answer', questionOnShow($component)->answer)
        ->assertSet('position', 0);

    expect($component->get('round'))->toHaveCount(10)
        ->and(array_column($component->get('round'), 'hand'))->not->toContain('a-line-no-card-prints');
});

test('practice says so when no card is loaded', function () {
    Card::query()->delete();
    cache()->forget(LoadCurrentCard::CACHE_KEY);

    $this->get(route('practice'))
        ->assertOk()
        ->assertSee('No card is loaded');
});

/**
 * The answers are only as good as the card they are read from, so every
 * question the real card can produce is checked, not just one round's worth.
 */
test('every question the real card can ask has an answer it offers', function () {
    $card = app(LoadCurrentCard::class)->handle();

    foreach ($card->categories as $category) {
        foreach ($category->hands as $hand) {
            if ($hand->structure->suitCount() > 0) {
                $question = Question::suitCount($category, $hand);

                expect($question->offers($question->answer))->toBeTrue();
            }

            foreach (array_keys($hand->structure->groups) as $group) {
                $question = Question::jokerLegality($category, $hand, $group);

                expect($question->offers($question->answer))->toBeTrue();
            }
        }
    }
});

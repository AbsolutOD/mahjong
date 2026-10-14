<?php

/**
 * Practice & Quiz — the drill decided in issue #18.
 *
 * A round of ten questions drawn from the card: how many suits a line needs,
 * and whether a joker may join one marked group. Lines are shown as the
 * shorthand the card prints and never as tiles, because the tiles' suit bands
 * would give the suit count away.
 *
 * The component holds no copy about a hand. Every answer and reason arrives on
 * a {@see Question}, which reads them off the structure in the decoder's own
 * words. The round lives here and nowhere else: nothing is saved, and leaving
 * the page forgets it.
 *
 * @phpstan-import-type QuestionKey from Question
 */

use App\Actions\Cards\LoadCurrentCard;
use App\Data\Practice\Question;
use App\Enums\QuestionKind;
use App\Mahjong\LineRenderer;
use App\Mahjong\PracticeQuiz;
use App\Models\Card;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Practice & Quiz')] class extends Component {
    /**
     * The round, as the keys its questions are rebuilt from on every request.
     *
     * @var list<QuestionKey>
     */
    public array $round = [];

    /**
     * The choice made at each question answered so far, in round order.
     *
     * @var list<string>
     */
    public array $answers = [];

    /**
     * Where in the round the player is; one past the end is the score screen.
     */
    public int $position = 0;

    /**
     * Draw the first round.
     */
    public function mount(): void
    {
        $this->startRound();
    }

    /**
     * Start over if the card changed under the round.
     *
     * A deploy can reseed the card mid-round, and a question about a line the
     * card no longer prints has no honest answer left.
     */
    public function hydrate(): void
    {
        if (count($this->questions) !== count($this->round)) {
            $this->startRound();
        }
    }

    /**
     * Get the card the questions are drawn from — the newest one, read from the cache.
     */
    #[Computed]
    public function card(): ?Card
    {
        return app(LoadCurrentCard::class)->handle();
    }

    /**
     * Get the round's questions, rebuilt from the card.
     *
     * @return list<Question>
     */
    #[Computed]
    public function questions(): array
    {
        if ($this->card === null) {
            return [];
        }

        $quiz = app(PracticeQuiz::class);

        return array_values(array_filter(array_map(
            fn (array $key): ?Question => $quiz->find($this->card, $key),
            $this->round,
        )));
    }

    /**
     * Get the question on show, or null once the round is over.
     */
    #[Computed]
    public function question(): ?Question
    {
        return $this->questions[$this->position] ?? null;
    }

    /**
     * Get the choice made at the question on show, if one has been.
     */
    #[Computed]
    public function choice(): ?string
    {
        return $this->answers[$this->position] ?? null;
    }

    /**
     * Get how many of the answers so far were right.
     */
    #[Computed]
    public function score(): int
    {
        $right = 0;

        foreach ($this->answers as $position => $choice) {
            if ($this->questions[$position]->isCorrect($choice)) {
                $right++;
            }
        }

        return $right;
    }

    /**
     * Answer the question on show, once.
     */
    public function answer(string $choice): void
    {
        if ($this->question === null || $this->choice !== null || ! $this->question->offers($choice)) {
            return;
        }

        $this->answers[$this->position] = $choice;

        unset($this->choice, $this->score);
    }

    /**
     * Move on from an answered question.
     */
    public function next(): void
    {
        if ($this->choice === null) {
            return;
        }

        $this->position++;

        unset($this->question, $this->choice);
    }

    /**
     * Throw the round away and draw another.
     */
    public function startRound(): void
    {
        $this->round = $this->card === null
            ? []
            : array_map(fn (Question $question): array => $question->key(), app(PracticeQuiz::class)->round($this->card));

        $this->answers = [];
        $this->position = 0;

        unset($this->questions, $this->question, $this->choice, $this->score);
    }

    /**
     * Get the line a question asks about, group by group, as the card prints it.
     *
     * @return list<string>
     */
    public function groups(Question $question): array
    {
        return app(LineRenderer::class)->renderGroups($question->hand->structure);
    }
}; ?>

<div class="mx-auto max-w-2xl">
    @if ($this->card === null)
        <flux:callout variant="warning" icon="exclamation-triangle" heading="{{ __('No card is loaded') }}">
            {{ __('Run php artisan db:seed --class=CardSeeder to load the practice card.') }}
        </flux:callout>
    @elseif ($this->question === null)
        {{-- The score screen. --}}
        <div class="rounded-xl border border-zinc-200 p-6 text-center dark:border-zinc-700">
            <flux:heading size="xl" level="1">{{ __('Round over') }}</flux:heading>

            <p class="mt-4 text-4xl font-bold" data-test="score">
                {{ __(':score of :total', ['score' => $this->score, 'total' => count($this->questions)]) }}
            </p>

            <ol class="mt-6 space-y-2 text-left">
                @foreach ($this->questions as $position => $question)
                    @php($right = $question->isCorrect($answers[$position] ?? ''))

                    <li wire:key="summary-{{ $position }}" class="flex items-start gap-3 text-sm">
                        <flux:icon :name="$right ? 'check-circle' : 'x-circle'" variant="mini" @class(['mt-0.5 shrink-0', 'text-lime-600' => $right, 'text-red-600' => ! $right]) />
                        <span class="sr-only">{{ $right ? __('Right') : __('Wrong') }}</span>

                        <span class="min-w-0">
                            <span class="font-mono">{{ implode(' ', $this->groups($question)) }}</span>
                            <span class="block text-zinc-500">{{ $question->verdict }}</span>
                        </span>
                    </li>
                @endforeach
            </ol>

            <flux:button variant="primary" class="mt-6" wire:click="startRound">
                {{ __('Play another round') }}
            </flux:button>
        </div>
    @else
        @php($question = $this->question)

        <div class="flex items-baseline justify-between gap-4">
            <flux:heading size="lg" level="1">{{ __('Practice') }}</flux:heading>

            <flux:text size="sm">
                {{ __('Question :number of :total · :score right so far', ['number' => $position + 1, 'total' => count($this->questions), 'score' => $this->score]) }}
            </flux:text>
        </div>

        <div class="mt-4 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700" wire:key="question-{{ $position }}">
            <flux:heading size="base" level="2">{{ __($question->kind->prompt()) }}</flux:heading>

            {{--
                Shorthand only, never tiles: a tile's suit band is the suit
                count, so drawing the line would answer the question.
            --}}
            <p class="mt-4 flex flex-wrap gap-x-2 gap-y-1 font-mono text-lg tracking-tight">
                @foreach ($this->groups($question) as $index => $group)
                    @if ($index === $question->group)
                        <mark class="rounded border-2 border-amber-500 bg-amber-50 px-1 text-inherit dark:bg-amber-950/40">
                            <span class="sr-only">{{ __('Marked group:') }}</span>{{ $group }}
                        </mark>
                    @else
                        <span>{{ $group }}</span>
                    @endif
                @endforeach
            </p>

            <div class="mt-6 flex flex-wrap gap-2">
                @foreach ($question->choices() as ['value' => $value, 'label' => $label])
                    @php($picked = $this->choice === $value)
                    @php($isAnswer = $this->choice !== null && $question->isCorrect($value))

                    <button
                        type="button"
                        wire:key="choice-{{ $position }}-{{ $value }}"
                        wire:click="answer('{{ $value }}')"
                        @disabled($this->choice !== null)
                        aria-pressed="{{ $picked ? 'true' : 'false' }}"
                        @class([
                            'rounded-lg border px-4 py-2 text-sm font-medium',
                            'border-zinc-300 hover:border-zinc-500 dark:border-zinc-600' => $this->choice === null,
                            'border-lime-600 bg-lime-50 text-lime-800 dark:bg-lime-950/40 dark:text-lime-300' => $isAnswer,
                            'border-red-600 bg-red-50 text-red-800 dark:bg-red-950/40 dark:text-red-300' => $picked && ! $isAnswer,
                            'border-zinc-200 text-zinc-400 dark:border-zinc-700' => $this->choice !== null && ! $picked && ! $isAnswer,
                        ])
                    >{{ __($label) }}</button>
                @endforeach
            </div>

            <div aria-live="polite">
                @if ($this->choice !== null)
                    @php($right = $question->isCorrect($this->choice))

                    <flux:callout class="mt-6" :variant="$right ? 'success' : 'danger'" :icon="$right ? 'check-circle' : 'x-circle'" :heading="$right ? __('Right') : __('Not quite')">
                        <flux:callout.text>
                            <strong>{{ $question->verdict }}.</strong> {{ $question->reason }}
                        </flux:callout.text>

                        <x-slot name="actions">
                            <flux:button size="sm" href="{{ route('card', ['category' => $question->category->slug, 'hand' => $question->hand->slug]) }}" wire:navigate>
                                {{ __('See this line on the card') }}
                            </flux:button>
                        </x-slot>
                    </flux:callout>

                    <flux:button variant="primary" class="mt-4" wire:click="next">
                        {{ $position + 1 < count($this->questions) ? __('Next question') : __('See your score') }}
                    </flux:button>
                @endif
            </div>
        </div>
    @endif
</div>

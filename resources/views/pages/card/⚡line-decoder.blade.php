<?php

/**
 * The Line Decoder — the split workbench settled in issue #9.
 *
 * Category rail, then the card's lines as the shorthand they are printed in,
 * then a sticky Visual Breakdown panel that reads the selected line aloud.
 * Selection is a click and lives in the URL, so a line can be linked to.
 *
 * The component holds no copy: every word in the panel comes from a
 * {@see HandReading}, which comes from the hand's structure.
 */

use App\Data\Decoding\HandReading;
use App\Data\Tiles\SuitAssignment;
use App\Enums\Suit;
use App\Mahjong\LineRenderer;
use App\Models\Card;
use App\Models\Category;
use App\Models\Hand;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Line Decoder')] class extends Component {
    /**
     * The selection is carried by slug rather than row id.
     *
     * Reseeding the card recreates every row, so a link built from an id would
     * quietly point at a different hand after the next deploy.
     *
     * @see \App\Mahjong\Slug
     */
    #[Url(as: 'category')]
    public ?string $categorySlug = null;

    #[Url(as: 'hand')]
    public ?string $handSlug = null;

    /**
     * The suits the player has bound the hand's letters to, keyed by letter.
     *
     * @var array<string, string>
     */
    public array $suits = [];

    /**
     * Settle the selection, so the url always names the line actually on show.
     */
    public function mount(): void
    {
        $this->categorySlug = $this->category?->slug;
        $this->handSlug = $this->hand?->slug;
    }

    /**
     * Get the card being decoded — the newest one published.
     */
    #[Computed]
    public function card(): ?Card
    {
        return Card::query()->latest('year')->first();
    }

    /**
     * Get the card's sections, in the order the card prints them.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return $this->card?->categories()->withCount('hands')->get() ?? collect();
    }

    /**
     * Get the section being browsed, falling back to the first on the card.
     */
    #[Computed]
    public function category(): ?Category
    {
        return $this->categories->firstWhere('slug', $this->categorySlug) ?? $this->categories->first();
    }

    /**
     * Get the lines in the section being browsed.
     *
     * @return Collection<int, Hand>
     */
    #[Computed]
    public function hands(): Collection
    {
        return $this->category?->hands ?? collect();
    }

    /**
     * Get the line the breakdown panel is explaining.
     */
    #[Computed]
    public function hand(): ?Hand
    {
        return $this->hands->firstWhere('slug', $this->handSlug) ?? $this->hands->first();
    }

    /**
     * Get the reading of the selected line — everything the panel prints.
     */
    #[Computed]
    public function reading(): ?HandReading
    {
        return $this->hand === null
            ? null
            : HandReading::for($this->hand->structure, $this->assignment(), $this->hand->concealed);
    }

    /**
     * Browse a different section of the card, landing on its first line.
     */
    public function selectCategory(string $slug): void
    {
        $this->categorySlug = $slug;

        unset($this->category, $this->hands, $this->hand, $this->reading);

        $this->handSlug = $this->hands->first()?->slug;
    }

    /**
     * Decode a line.
     */
    public function selectHand(string $slug): void
    {
        $this->handSlug = $slug;

        unset($this->hand, $this->reading);
    }

    /**
     * Bind one of the hand's letters to a real suit.
     *
     * The card's colours mean the groups take *different* suits, so a suit can
     * only ever be in one place: choosing one that is already spoken for moves
     * it, and choosing the one a letter already holds releases it.
     */
    public function assign(string $variable, string $suit): void
    {
        $suits = array_filter(
            $this->suits,
            fn (string $bound, string $letter): bool => $bound !== $suit && $letter !== $variable,
            ARRAY_FILTER_USE_BOTH,
        );

        if (($this->suits[$variable] ?? null) !== $suit) {
            $suits[$variable] = $suit;
        }

        $this->suits = $suits;

        unset($this->reading);
    }

    /**
     * Send every letter back to being any suit at all.
     */
    public function clearSuits(): void
    {
        $this->suits = [];

        unset($this->reading);
    }

    /**
     * Render a line as the shorthand the card prints.
     */
    public function line(Hand $hand): string
    {
        return app(LineRenderer::class)->render($hand->structure);
    }

    /**
     * Get the bindings as the value object the tiles and prose read from.
     */
    private function assignment(): SuitAssignment
    {
        return SuitAssignment::of(
            array_map(Suit::from(...), $this->suits),
        );
    }
}; ?>

<div>
    @if ($this->card === null)
        <flux:callout variant="warning" icon="exclamation-triangle" heading="{{ __('No card is loaded') }}">
            {{ __('Run php artisan db:seed to load the practice card.') }}
        </flux:callout>
    @else
        <div class="flex gap-6 xl:gap-8">
            {{-- The card's sections. Below lg this becomes the chip row further down. --}}
            <aside class="hidden w-52 shrink-0 lg:block">
                <flux:subheading class="px-3 pb-2">{{ $this->card->name }}</flux:subheading>

                <div class="space-y-0.5">
                    @foreach ($this->categories as $category)
                        <button
                            type="button"
                            wire:key="category-{{ $category->slug }}"
                            wire:click="selectCategory('{{ $category->slug }}')"
                            @class([
                                'flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-left text-sm',
                                'bg-zinc-100 font-medium dark:bg-zinc-800' => $this->category?->is($category),
                                'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800/50' => ! $this->category?->is($category),
                            ])
                        >
                            <span>{{ $category->name }}</span>
                            <span class="text-xs opacity-60">{{ $category->hands_count }}</span>
                        </button>
                    @endforeach
                </div>
            </aside>

            <div class="min-w-0 flex-1">
                {{-- The same rail, as chips, where there is no room for a column. --}}
                <div class="-mx-4 mb-4 flex gap-2 overflow-x-auto px-4 pb-1 lg:hidden">
                    @foreach ($this->categories as $category)
                        <button
                            type="button"
                            wire:key="chip-{{ $category->slug }}"
                            wire:click="selectCategory('{{ $category->slug }}')"
                            @class([
                                'shrink-0 rounded-full border px-3 py-1 text-xs font-medium',
                                'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-900' => $this->category?->is($category),
                                'border-zinc-300 text-zinc-600 dark:border-zinc-600 dark:text-zinc-300' => ! $this->category?->is($category),
                            ])
                        >{{ $category->name }}</button>
                    @endforeach
                </div>

                <flux:heading size="lg">{{ $this->category?->name }}</flux:heading>
                <flux:subheading>
                    {{ trans_choice('{1} :count line · pick it to break it down|[2,*] :count lines · pick one to break it down', $this->hands->count(), ['count' => $this->hands->count()]) }}
                </flux:subheading>

                <div class="mt-4 space-y-1">
                    @foreach ($this->hands as $hand)
                        <button
                            type="button"
                            wire:key="hand-{{ $hand->slug }}"
                            wire:click="selectHand('{{ $hand->slug }}')"
                            @class([
                                'flex w-full items-center gap-3 rounded-lg border px-3 py-2 text-left',
                                'border-sky-500 bg-sky-50 dark:bg-sky-950/40' => $this->hand?->is($hand),
                                'border-transparent hover:border-zinc-200 hover:bg-zinc-50 dark:hover:border-zinc-700 dark:hover:bg-zinc-800/40' => ! $this->hand?->is($hand),
                            ])
                        >
                            <span class="grow font-mono text-sm tracking-tight">{{ $this->line($hand) }}</span>
                            <span class="shrink-0 font-mono text-xs text-zinc-500">{{ $hand->points }}</span>
                            <flux:badge size="sm" :color="$hand->concealed ? 'purple' : 'zinc'">
                                {{ $hand->concealed ? 'C' : 'X' }}
                            </flux:badge>
                        </button>
                    @endforeach
                </div>
            </div>

            {{--
                Large tiles are what make the panel worth looking at, but a
                six-group hand then runs past the viewport — so the panel
                scrolls itself rather than fighting the sticky position.
            --}}
            <aside class="sticky top-20 hidden h-fit max-h-[calc(100vh-6rem)] w-[27rem] shrink-0 overflow-y-auto xl:block">
                @includeWhen($this->reading, 'pages.card.breakdown', ['scope' => 'panel'])
            </aside>
        </div>

        {{-- Below xl the panel is a bottom sheet, so mobile keeps the same master/detail shape. --}}
        @if ($this->reading)
            <div class="fixed inset-x-0 bottom-0 z-30 max-h-[58vh] overflow-y-auto border-t border-zinc-200 bg-white p-4 shadow-2xl xl:hidden dark:border-zinc-700 dark:bg-zinc-900">
                @include('pages.card.breakdown', ['scope' => 'sheet'])
            </div>
        @endif
    @endif
</div>

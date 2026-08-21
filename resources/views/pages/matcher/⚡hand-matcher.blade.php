<?php

/**
 * The Hand Matcher — the rack workbench decided in issue #15.
 *
 * Tap tiles into a rack, and every line on the card is ranked by how many
 * tiles it is away, closest first. Each line is measured at its best binding
 * of the card's letters, and says which binding that is, because the card's
 * colours mean suits that must *differ* rather than fixed suits.
 *
 * The component holds no copy about a hand: the breakdown reads through
 * {@see HandReading}, the same layer the Line Decoder renders.
 */

use App\Actions\Cards\LoadCurrentCard;
use App\Data\Decoding\HandReading;
use App\Data\Matching\HandMatch;
use App\Data\Matching\Rack;
use App\Data\Tiles\Tile;
use App\Mahjong\HandMatcher;
use App\Mahjong\LineRenderer;
use App\Models\Card;
use App\Models\Hand;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Hand Matcher')] class extends Component {
    /**
     * The rack, as the tile codes it travels by, so a hand can be linked to.
     *
     * @see \App\Data\Matching\Rack::fromCodes()
     */
    #[Url(as: 'rack')]
    public string $rackCodes = '';

    /**
     * The line the breakdown is explaining, or null while it follows the top of
     * the ranking.
     *
     * Unlike the decoder, this is left unset until the player picks a line: the
     * thing worth linking to here is the rack, and the panel that follows the
     * best fit is what teaches as tiles arrive.
     */
    #[Url(as: 'hand')]
    public ?string $handSlug = null;

    /**
     * Settle the rack, so the url always names the tiles actually on show.
     */
    public function mount(): void
    {
        $this->rackCodes = implode(',', $this->rack->codes());
    }

    /**
     * Get the card being matched against — the newest one, read from the cache.
     */
    #[Computed]
    public function card(): ?Card
    {
        return app(LoadCurrentCard::class)->handle();
    }

    /**
     * Get the tiles in front of the player.
     */
    #[Computed]
    public function rack(): Rack
    {
        return Rack::fromCodes(array_values(array_filter(explode(',', $this->rackCodes))));
    }

    /**
     * Get every line on the card, closest first.
     *
     * @return list<HandMatch>
     */
    #[Computed]
    public function matches(): array
    {
        return $this->card === null
            ? []
            : app(HandMatcher::class)->rank($this->card, $this->rack);
    }

    /**
     * Get the line the breakdown is explaining, which is the best fit until picked.
     */
    #[Computed]
    public function match(): ?HandMatch
    {
        foreach ($this->matches as $match) {
            if ($match->hand->slug === $this->handSlug) {
                return $match;
            }
        }

        return $this->matches[0] ?? null;
    }

    /**
     * Get the reading of the line on show — every word the breakdown prints.
     */
    #[Computed]
    public function reading(): ?HandReading
    {
        return $this->match === null
            ? null
            : HandReading::for(
                $this->match->hand->structure,
                $this->match->instantiation->suits,
                $this->match->hand->concealed,
            );
    }

    /**
     * Rack one more tile, if the game could supply it.
     */
    public function addTile(string $code): void
    {
        $tile = Tile::tryFromCode($code);

        if ($tile !== null && $this->rack->canHold($tile)) {
            $this->setRack($this->rack->add($tile));
        }
    }

    /**
     * Take one copy of a tile back off the rack.
     */
    public function removeTile(string $code): void
    {
        $tile = Tile::tryFromCode($code);

        if ($tile !== null) {
            $this->setRack($this->rack->remove($tile));
        }
    }

    /**
     * Sweep the rack, back to ranking nothing.
     */
    public function clearRack(): void
    {
        $this->setRack(Rack::empty());
    }

    /**
     * Break down a different line.
     */
    public function selectHand(string $slug): void
    {
        $this->handSlug = $slug;

        unset($this->match, $this->reading);
    }

    /**
     * Render a line as the shorthand the card prints.
     */
    public function line(Hand $hand): string
    {
        return app(LineRenderer::class)->render($hand->structure);
    }

    /**
     * Put a new rack in the url and forget everything derived from the old one.
     */
    private function setRack(Rack $rack): void
    {
        $this->rackCodes = implode(',', $rack->codes());

        unset($this->rack, $this->matches, $this->match, $this->reading);
    }
}; ?>

@php
    /** Tile is already imported by the component above; a second import here is a fatal. */
    use App\Data\Tiles\TileFace;
    use App\Enums\Dragon;
    use App\Enums\Suit;
    use App\Enums\Wind;

    /**
     * The set, laid out to be tapped from. The winds are in card order rather
     * than enum order, because that is the order a player says them in.
     *
     * @var list<array{name: string, tiles: list<Tile>}>
     */
    $palette = [
        ...array_map(fn (Suit $suit): array => [
            'name' => $suit->label(),
            'tiles' => array_map(fn (int $number): Tile => Tile::number($suit, $number), range(1, 9)),
        ], Suit::cases()),
        [
            'name' => __('Winds'),
            'tiles' => array_map(Tile::wind(...), [Wind::East, Wind::South, Wind::West, Wind::North]),
        ],
        [
            'name' => __('Dragons, flower and joker'),
            'tiles' => [...array_map(Tile::dragon(...), Dragon::cases()), Tile::flower(), Tile::joker()],
        ],
    ];
@endphp

<div>
    @if ($this->card === null)
        <flux:callout variant="warning" icon="exclamation-triangle" heading="{{ __('No card is loaded') }}">
            {{ __('Run php artisan db:seed --class=CardSeeder to load the practice card.') }}
        </flux:callout>
    @else
        <section>
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <div>
                    <flux:heading size="lg">{{ __('Your rack') }}</flux:heading>
                    <flux:subheading>
                        {{ __(':count of :max tiles · tap a tile below to add it, tap it on the rack to take it back', [
                            'count' => $this->rack->size(),
                            'max' => App\Data\HandStructure::HAND_SIZE,
                        ]) }}
                    </flux:subheading>
                </div>

                @if (! $this->rack->isEmpty())
                    <flux:button size="sm" variant="ghost" wire:click="clearRack">{{ __('Clear rack') }}</flux:button>
                @endif
            </div>

            <div class="mt-3 flex flex-wrap gap-1.5 rounded-xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900/50">
                @foreach ($this->rack->tiles as $position => $tile)
                    <button
                        type="button"
                        wire:key="racked-{{ $position }}-{{ $tile->code() }}"
                        wire:click="removeTile('{{ $tile->code() }}')"
                        class="rounded-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-500"
                    >
                        <x-tile :face="TileFace::of($tile)" />
                        <span class="sr-only">{{ __('Take :tile off the rack', ['tile' => TileFace::of($tile)->name]) }}</span>
                    </button>
                @endforeach

                {{-- The empty slots are drawn, so the rack always shows how much room is left. --}}
                @for ($slot = $this->rack->size(); $slot < App\Data\HandStructure::HAND_SIZE; $slot++)
                    <div
                        wire:key="empty-{{ $slot }}"
                        class="h-16 w-12 shrink-0 rounded-lg border-2 border-dashed border-zinc-300 dark:border-zinc-700"
                        aria-hidden="true"
                    ></div>
                @endfor
            </div>
        </section>

        <section class="mt-6">
            <div class="flex flex-wrap gap-x-8 gap-y-4">
                @foreach ($palette as $row)
                    <div wire:key="palette-{{ $row['name'] }}">
                        <flux:subheading class="mb-1.5">{{ $row['name'] }}</flux:subheading>

                        <div class="flex flex-wrap gap-1">
                            @foreach ($row['tiles'] as $tile)
                                @php($available = $this->rack->canHold($tile))

                                <button
                                    type="button"
                                    wire:key="palette-{{ $tile->code() }}"
                                    wire:click="addTile('{{ $tile->code() }}')"
                                    @disabled(! $available)
                                    @class([
                                        'rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-500',
                                        'hover:-translate-y-0.5 transition-transform' => $available,
                                        'cursor-not-allowed opacity-30' => ! $available,
                                    ])
                                >
                                    <x-tile :face="TileFace::of($tile)" size="sm" />
                                    <span class="sr-only">
                                        {{ $available
                                            ? __('Add :tile to the rack', ['tile' => TileFace::of($tile)->name])
                                            : __('No more :tile can be racked', ['tile' => TileFace::of($tile)->name]) }}
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <flux:separator class="my-6" />

        <div class="flex gap-6 xl:gap-8">
            <div class="min-w-0 flex-1">
                <flux:heading size="lg">{{ __('Closest lines') }}</flux:heading>
                <flux:subheading>
                    {{ $this->rack->isEmpty()
                        ? __('Every line is a whole hand away until you rack a tile. Distance is the only thing ranking this list.')
                        : __('Ranked by how many tiles away each line is — not by how easy it would be to finish.') }}
                </flux:subheading>

                <div class="mt-4 space-y-1">
                    @foreach ($this->matches as $match)
                        <button
                            type="button"
                            wire:key="match-{{ $match->hand->slug }}"
                            wire:click="selectHand('{{ $match->hand->slug }}')"
                            @class([
                                'flex w-full items-center gap-3 rounded-lg border px-3 py-2 text-left',
                                'border-sky-500 bg-sky-50 dark:bg-sky-950/40' => $this->match?->hand->slug === $match->hand->slug,
                                'border-transparent hover:border-zinc-200 hover:bg-zinc-50 dark:hover:border-zinc-700 dark:hover:bg-zinc-800/40' => $this->match?->hand->slug !== $match->hand->slug,
                            ])
                        >
                            <flux:badge size="sm" :color="$match->isComplete() ? 'lime' : 'zinc'" class="shrink-0 tabular-nums">
                                {{ $match->isComplete()
                                    ? __('complete')
                                    : trans_choice('{1} :count away|[2,*] :count away', $match->tilesAway(), ['count' => $match->tilesAway()]) }}
                            </flux:badge>

                            <span class="min-w-0 grow">
                                <span class="block truncate font-mono text-sm tracking-tight">{{ $this->line($match->hand) }}</span>

                                @if ($match->instantiation->bindings !== [])
                                    <span class="block truncate font-mono text-xs text-zinc-500">
                                        @foreach ($match->instantiation->bindings as $variable => $value)
                                            {{ $variable }}={{ $value }}{{ ! $loop->last ? ' ' : '' }}
                                        @endforeach
                                    </span>
                                @endif
                            </span>

                            <span class="shrink-0 font-mono text-xs text-zinc-500">{{ $match->hand->points }}</span>

                            <flux:badge size="sm" :color="$match->hand->concealed ? 'purple' : 'zinc'">
                                {{ $match->hand->concealed ? 'C' : 'X' }}
                            </flux:badge>
                        </button>
                    @endforeach
                </div>
            </div>

            <aside class="sticky top-20 hidden h-fit max-h-[calc(100vh-6rem)] w-[27rem] shrink-0 overflow-y-auto xl:block">
                @includeWhen($this->match, 'pages.matcher.fit', ['scope' => 'panel'])
            </aside>
        </div>

        {{-- Below xl the panel follows the list rather than covering the palette. --}}
        @if ($this->match)
            <div class="mt-6 xl:hidden">
                @include('pages.matcher.fit', ['scope' => 'inline'])
            </div>
        @endif
    @endif
</div>

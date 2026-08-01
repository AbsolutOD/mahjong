<?php

/**
 * PROTOTYPE — three tile-face styles (issue #8), switchable with ?variant=A|B|C.
 * Throwaway: no tests, no polish. Real hands come from the seeded practice card
 * so the faces are judged at real density, not in a vacuum.
 */

use App\Data\TileGroup;
use App\Data\Tiles\DragonTile;
use App\Data\Tiles\FlowerTile;
use App\Data\Tiles\NumberTile;
use App\Data\Tiles\TileSpec;
use App\Data\Tiles\WindTile;
use App\Data\Tiles\ZeroTile;
use App\Enums\Suit;
use App\Mahjong\AmericanMahjong;
use App\Mahjong\LineRenderer;
use App\Models\Card;
use App\Models\Hand;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::prototype')] #[Title('Tile faces prototype')] class extends Component
{
    #[Url]
    public string $variant = 'A';

    /** Suits bound to the card's suit variables, or null while abstract. */
    #[Url]
    public bool $assigned = false;

    public const VARIANTS = [
        'A' => 'Traditional facsimile',
        'B' => 'Flat schematic (no SVG)',
        'C' => 'Hybrid: scaffolded tile',
    ];

    public function toggleAssigned(): void
    {
        $this->assigned = ! $this->assigned;
    }

    /** @return array<string, string|null> */
    public function assignments(): array
    {
        return $this->assigned
            ? ['A' => 'dots', 'B' => 'bams', 'C' => 'craks', 'D' => 'dots']
            : ['A' => null, 'B' => null, 'C' => null, 'D' => null];
    }

    /**
     * Get the whole physical tile set, grouped for the specimen sheet.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    #[Computed]
    public function specimens(): array
    {
        $suited = fn (string $suit): array => array_map(
            fn (int $n): array => ['kind' => 'number', 'suit' => $suit, 'number' => $n],
            range(1, 9),
        );

        return [
            'Dots' => $suited('dots'),
            'Bams' => $suited('bams'),
            'Craks' => $suited('craks'),
            'Winds' => array_map(
                fn (string $w): array => ['kind' => 'wind', 'wind' => $w],
                ['E', 'S', 'W', 'N'],
            ),
            'Dragons & extras' => [
                ['kind' => 'dragon', 'dragon' => 'red'],
                ['kind' => 'dragon', 'dragon' => 'green'],
                ['kind' => 'dragon', 'dragon' => 'white'],
                ['kind' => 'flower'],
                ['kind' => 'joker'],
            ],
        ];
    }

    /**
     * Get the abstract slots the card prints, which have no physical tile.
     *
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function abstractSlots(): array
    {
        return [
            ['kind' => 'number', 'suit' => null, 'number' => 1, 'variable' => 'A'],
            ['kind' => 'number', 'suit' => null, 'number' => 'X', 'variable' => 'A'],
            ['kind' => 'number', 'suit' => null, 'number' => 'Y', 'variable' => 'A'],
            ['kind' => 'number', 'suit' => null, 'number' => 'D', 'variable' => 'B'],
            ['kind' => 'number', 'suit' => 'bams', 'number' => 'X', 'variable' => 'A'],
            ['kind' => 'number', 'suit' => 'craks', 'number' => 'Y', 'variable' => 'B'],
        ];
    }

    /**
     * Get the faces shown at every size, for the size ladder.
     *
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function ladder(): array
    {
        return [
            ['kind' => 'number', 'suit' => 'bams', 'number' => 3],
            ['kind' => 'number', 'suit' => 'dots', 'number' => 7],
            ['kind' => 'number', 'suit' => 'craks', 'number' => 9],
            ['kind' => 'dragon', 'dragon' => 'white'],
            ['kind' => 'number', 'suit' => null, 'number' => 'X', 'variable' => 'A'],
            ['kind' => 'joker'],
        ];
    }

    #[Computed]
    public function card(): ?Card
    {
        return Card::query()->latest('year')->first();
    }

    /**
     * Get a spread of real hands: a run, a year hand with soap, winds, flowers.
     *
     * @return \Illuminate\Support\Collection<int, Hand>
     */
    #[Computed]
    public function hands(): \Illuminate\Support\Collection
    {
        return Hand::query()
            ->whereIn('id', Hand::query()->pluck('id')->take(60))
            ->get()
            ->filter(fn (Hand $hand): bool => $hand->structure->groups !== [])
            ->take(60)
            ->pipe(fn ($hands) => collect([
                $hands->first(fn (Hand $h): bool => $h->structure->usesFlowers()),
                $hands->first(fn (Hand $h): bool => $this->holds($h, ZeroTile::class)),
                $hands->first(fn (Hand $h): bool => $this->holds($h, WindTile::class)),
                $hands->first(fn (Hand $h): bool => $this->holds($h, DragonTile::class)),
                $hands->first(),
            ])->filter()->unique('id'));
    }

    /**
     * Render a hand as the shorthand the card prints.
     */
    public function line(Hand $hand): string
    {
        return app(LineRenderer::class)->render($hand->structure);
    }

    /**
     * Break a hand into groups of renderable faces.
     *
     * @return list<list<array<string, mixed>>>
     */
    public function groups(Hand $hand): array
    {
        return array_map(
            fn (TileGroup $group): array => array_map($this->face(...), $group->tiles),
            $hand->structure->groups,
        );
    }

    /**
     * Get a fourteen-tile rack of real tiles, for the density check.
     *
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function rack(): array
    {
        return [
            ['kind' => 'number', 'suit' => 'bams', 'number' => 2],
            ['kind' => 'number', 'suit' => 'bams', 'number' => 2],
            ['kind' => 'number', 'suit' => 'bams', 'number' => 5],
            ['kind' => 'number', 'suit' => 'dots', 'number' => 1],
            ['kind' => 'number', 'suit' => 'dots', 'number' => 3],
            ['kind' => 'number', 'suit' => 'dots', 'number' => 8],
            ['kind' => 'number', 'suit' => 'dots', 'number' => 9],
            ['kind' => 'number', 'suit' => 'craks', 'number' => 4],
            ['kind' => 'number', 'suit' => 'craks', 'number' => 7],
            ['kind' => 'wind', 'wind' => 'N'],
            ['kind' => 'dragon', 'dragon' => 'red'],
            ['kind' => 'dragon', 'dragon' => 'white'],
            ['kind' => 'flower'],
            ['kind' => 'joker'],
        ];
    }

    /**
     * Determine whether a hand holds a tile of the given spec class.
     *
     * @param  class-string  $class
     */
    private function holds(Hand $hand, string $class): bool
    {
        foreach ($hand->structure->tiles() as $tile) {
            if ($tile instanceof $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * Turn a card tile spec into the props a tile component takes.
     *
     * @return array<string, mixed>
     */
    private function face(TileSpec $tile): array
    {
        $variable = $tile->suitVariable();
        $suit = $variable ? $this->assignments()[$variable] ?? null : null;

        return match (true) {
            $tile instanceof NumberTile => [
                'kind' => 'number',
                'suit' => $suit,
                'number' => $tile->number->literal ?? $tile->symbol(),
                'variable' => $variable,
            ],
            // An unassigned dragon has no face yet, so it renders as a "D" slot.
            $tile instanceof DragonTile => $suit
                ? ['kind' => 'dragon', 'dragon' => AmericanMahjong::dragonForSuit(Suit::from($suit))->value]
                : ['kind' => 'number', 'suit' => null, 'number' => 'D', 'variable' => $variable],
            $tile instanceof ZeroTile => ['kind' => 'dragon', 'dragon' => 'white'],
            $tile instanceof WindTile => ['kind' => 'wind', 'wind' => $tile->symbol()],
            $tile instanceof FlowerTile => ['kind' => 'flower'],
            default => ['kind' => 'joker'],
        };
    }
}; ?>

<div
    x-data
    @keydown.window.arrow-left="$wire.variant = {'A':'C','B':'A','C':'B'}[$wire.variant]"
    @keydown.window.arrow-right="$wire.variant = {'A':'B','B':'C','C':'A'}[$wire.variant]"
    class="space-y-10 pb-24"
>
    <div class="flex flex-wrap items-end justify-between gap-4 pt-6">
        <div>
            <flux:heading size="xl">{{ $this->variant }} — {{ static::VARIANTS[$this->variant] }}</flux:heading>
            <flux:text class="mt-1">
                @if ($this->variant === 'A')
                    Ivory face, engraved line art, traditional colours. You read the suit off the artwork.
                    Learn these and you can sit at a real table.
                @elseif ($this->variant === 'B')
                    No artwork, no SVG — HTML and Tailwind only. Suit is colour plus a word; number is a numeral.
                    Card slots stay grey until a real suit is assigned.
                @else
                    Chunky traditional silhouette plus scaffolding a real tile lacks: a suit-coloured band and a
                    permanent corner index.
                @endif
            </flux:text>
        </div>

        <flux:button size="sm" wire:click="toggleAssigned" icon="{{ $this->assigned ? 'eye-slash' : 'eye' }}">
            {{ $this->assigned ? 'Back to card slots' : 'Assign A→Dots, B→Bams, C→Craks' }}
        </flux:button>
    </div>

    {{-- 1. The physical set --}}
    <section class="space-y-4">
        <flux:heading size="lg">The set</flux:heading>

        @foreach ($this->specimens as $label => $faces)
            <div class="flex flex-wrap items-center gap-2">
                <span class="w-28 shrink-0 text-xs font-medium tracking-wide text-zinc-500 uppercase">{{ $label }}</span>
                @foreach ($faces as $face)
                    <x-prototype.tile :face="$face" :variant="$this->variant" />
                @endforeach
            </div>
        @endforeach
    </section>

    {{-- 2. Slots the card prints that have no physical tile --}}
    <section class="space-y-4">
        <flux:heading size="lg">Card slots (no physical tile exists)</flux:heading>
        <flux:text>A number in “suit A”, a run position X or Y, a dragon matching suit B, and two half-resolved slots.</flux:text>

        <div class="flex flex-wrap items-center gap-2">
            @foreach ($this->abstractSlots as $face)
                <x-prototype.tile :face="$face" :variant="$this->variant" />
            @endforeach
        </div>
    </section>

    {{-- 3. Size ladder --}}
    <section class="space-y-4">
        <flux:heading size="lg">Sizes</flux:heading>
        <flux:text>sm is the line list, md the breakdown, lg the focus deck.</flux:text>

        <div class="flex flex-wrap items-end gap-6">
            @foreach (['sm', 'md', 'lg'] as $size)
                <div class="flex items-end gap-1.5">
                    @foreach ($this->ladder as $face)
                        <x-prototype.tile :face="$face" :variant="$this->variant" :size="$size" />
                    @endforeach
                </div>
            @endforeach
        </div>
    </section>

    {{-- 4. Real hands from the seeded card --}}
    <section class="space-y-4">
        <flux:heading size="lg">Real hands</flux:heading>

        @if (! $this->card)
            <flux:callout variant="danger" icon="exclamation-triangle" heading="No card seeded">
                Run <code>php artisan db:seed</code> to load the practice card.
            </flux:callout>
        @else
            <div class="space-y-5">
                @foreach ($this->hands as $hand)
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="font-mono text-sm text-zinc-500">{{ $this->line($hand) }}</div>

                        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2">
                            @foreach ($this->groups($hand) as $group)
                                <div class="flex items-center gap-1">
                                    @foreach ($group as $face)
                                        <x-prototype.tile :face="$face" :variant="$this->variant" size="sm" />
                                    @endforeach
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-2">
                            @foreach ($this->groups($hand) as $group)
                                <div class="flex items-center gap-1">
                                    @foreach ($group as $face)
                                        <x-prototype.tile :face="$face" :variant="$this->variant" />
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- 5. Density check: a full rack --}}
    <section class="space-y-4">
        <flux:heading size="lg">A rack of fourteen</flux:heading>
        <flux:text>The hand matcher’s density. Can you scan this in one pass?</flux:text>

        <div class="flex flex-wrap gap-1 rounded-lg bg-zinc-100 p-3 dark:bg-zinc-900">
            @foreach ($this->rack as $face)
                <x-prototype.tile :face="$face" :variant="$this->variant" />
            @endforeach
        </div>
    </section>

    {{-- Floating variant switcher — prototype only --}}
    <div class="fixed inset-x-0 bottom-4 z-50 flex justify-center print:hidden">
        <div class="flex items-center gap-1 rounded-full border border-zinc-700 bg-zinc-900 px-2 py-1.5 text-white shadow-xl">
            <flux:button size="sm" variant="ghost" icon="chevron-left" inset
                wire:click="$set('variant', '{{ ['A' => 'C', 'B' => 'A', 'C' => 'B'][$this->variant] }}')" class="text-white!" />

            <span class="px-2 text-xs font-medium whitespace-nowrap">
                {{ $this->variant }} — {{ static::VARIANTS[$this->variant] }}
            </span>

            <flux:button size="sm" variant="ghost" icon="chevron-right" inset
                wire:click="$set('variant', '{{ ['A' => 'B', 'B' => 'C', 'C' => 'A'][$this->variant] }}')" class="text-white!" />
        </div>
    </div>
</div>

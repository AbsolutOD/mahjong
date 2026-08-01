<?php

/**
 * PROTOTYPE — three variants of the Line Decoder (issue #9), switchable with
 * ?variant=A|B|C. Throwaway: no tests, no polish, placeholder tiles.
 */

use App\Data\HandStructure;
use App\Data\TileGroup;
use App\Data\Tiles\DragonTile;
use App\Data\Tiles\FlowerTile;
use App\Data\Tiles\NumberTile;
use App\Data\Tiles\TileSpec;
use App\Data\Tiles\WindTile;
use App\Data\Tiles\ZeroTile;
use App\Enums\Dragon;
use App\Enums\Suit;
use App\Mahjong\AmericanMahjong;
use App\Mahjong\LineRenderer;
use App\Models\Card;
use App\Models\Category;
use App\Models\Hand;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::prototype')] #[Title('Line Decoder prototype')] class extends Component
{
    #[Url]
    public string $variant = 'A';

    #[Url]
    public ?int $categoryId = null;

    #[Url]
    public ?int $handId = null;

    /** @var array<string, string|null> */
    public array $assignments = ['A' => null, 'B' => null, 'C' => null, 'D' => null];

    public function mount(): void
    {
        $this->categoryId ??= $this->categories->first()?->id;
        $this->handId ??= $this->hands->first()?->id;
    }

    #[Computed]
    public function card(): ?Card
    {
        return Card::query()->latest('year')->first();
    }

    /** @return \Illuminate\Support\Collection<int, Category> */
    #[Computed]
    public function categories(): \Illuminate\Support\Collection
    {
        return $this->card?->categories()->with('hands')->get() ?? collect();
    }

    #[Computed]
    public function category(): ?Category
    {
        return $this->categories->firstWhere('id', $this->categoryId) ?? $this->categories->first();
    }

    /** @return \Illuminate\Support\Collection<int, Hand> */
    #[Computed]
    public function hands(): \Illuminate\Support\Collection
    {
        return $this->category?->hands ?? collect();
    }

    #[Computed]
    public function hand(): ?Hand
    {
        return $this->hands->firstWhere('id', $this->handId) ?? $this->hands->first();
    }

    public function selectCategory(int $id): void
    {
        $this->categoryId = $id;
        unset($this->category, $this->hands, $this->hand);
        $this->handId = $this->hands->first()?->id;
    }

    public function selectHand(int $id): void
    {
        $this->handId = $this->handId === $id && $this->variant === 'A' ? null : $id;
    }

    public function step(int $direction): void
    {
        $ids = $this->hands->pluck('id')->all();
        $index = array_search($this->handId, $ids, strict: true);
        $this->handId = $ids[(((int) $index) + $direction + count($ids)) % count($ids)];
    }

    public function assign(string $variable, ?string $suit): void
    {
        $this->assignments[$variable] = $suit;
    }

    public function randomiseAssignments(): void
    {
        $suits = collect(Suit::cases())->shuffle()->pluck('value')->all();

        foreach (array_keys($this->assignments) as $index => $variable) {
            $this->assignments[$variable] = $suits[$index] ?? null;
        }
    }

    public function clearAssignments(): void
    {
        $this->assignments = array_map(fn () => null, $this->assignments);
    }

    /**
     * Render a hand as the shorthand the card prints.
     */
    public function line(Hand $hand): string
    {
        return app(LineRenderer::class)->render($hand->structure);
    }

    /**
     * Break a hand into its groups, each with display tiles and plain English.
     *
     * @return list<array<string, mixed>>
     */
    public function breakdown(Hand $hand): array
    {
        return array_map(fn (TileGroup $group): array => [
            'label' => $this->groupLabel($group),
            'tiles' => array_map($this->tileView(...), $group->tiles),
            'description' => $this->groupDescription($group),
            'jokers' => $group->acceptsJokers(),
            'suit' => $group->suitVariable(),
        ], $hand->structure->groups);
    }

    /**
     * Get the tiles of a hand in card order, ready to render.
     *
     * @return list<array<string, mixed>>
     */
    public function tiles(Hand $hand): array
    {
        return array_map($this->tileView(...), $hand->structure->tiles());
    }

    /**
     * Get the rule tags derived from a hand's structure.
     *
     * @return list<array{label: string, color: string}>
     */
    public function tags(Hand $hand): array
    {
        $structure = $hand->structure;

        $tags = [[
            'label' => $hand->concealed ? 'Concealed — no exposures' : 'May be exposed',
            'color' => $hand->concealed ? 'purple' : 'zinc',
        ]];

        $suits = $structure->suitCount();

        if ($suits > 0) {
            $tags[] = ['label' => $suits.' '.str('suit')->plural($suits).', all different', 'color' => 'sky'];
        }

        $tags[] = $structure->maxJokers() > 0
            ? ['label' => 'Jokers OK — up to '.$structure->maxJokers(), 'color' => 'amber']
            : ['label' => 'No jokers anywhere', 'color' => 'red'];

        if ($structure->hasPair()) {
            $tags[] = ['label' => 'Contains a pair (no jokers there)', 'color' => 'zinc'];
        }

        if ($structure->usesFlowers()) {
            $tags[] = ['label' => 'Flowers are interchangeable', 'color' => 'zinc'];
        }

        return $tags;
    }

    /**
     * Get the one-sentence plain-English reading of a whole hand.
     */
    public function summary(Hand $hand): string
    {
        $parts = array_map(fn (array $group): string => lcfirst($group['description']), $this->breakdown($hand));

        $last = array_pop($parts);

        return ucfirst(implode(', ', $parts).', and '.$last).'.';
    }

    /**
     * Get the suits the user has assigned, as a label per variable.
     *
     * @return array<string, string|null>
     */
    public function assignedLabels(HandStructure $structure): array
    {
        return collect($structure->suitVariables())
            ->mapWithKeys(fn (string $variable): array => [
                $variable => $this->assignments[$variable] ?? null,
            ])
            ->all();
    }

    /**
     * Get one tile's display shape, resolving the suit if one is assigned.
     *
     * @return array{symbol: string, variable: ?string, assigned: ?string}
     */
    private function tileView(TileSpec $tile): array
    {
        $variable = $tile->suitVariable();
        $assigned = $variable ? $this->assignments[$variable] ?? null : null;
        $suit = $assigned ? Suit::from($assigned) : null;

        $symbol = $tile instanceof DragonTile && $suit
            ? strtoupper(substr(AmericanMahjong::dragonForSuit($suit)->value, 0, 1))
            : $tile->symbol();

        return [
            'symbol' => $symbol,
            'variable' => $variable,
            'assigned' => $suit?->label(),
        ];
    }

    /**
     * Get the vocabulary word for a group of this size.
     */
    private function groupLabel(TileGroup $group): string
    {
        if (! $group->isIdentical()) {
            return match ($group->size()) {
                2 => 'Pair',
                4 => 'Four tiles',
                default => $group->size().' tiles',
            };
        }

        return match ($group->size()) {
            1 => 'Single',
            2 => 'Pair',
            3 => 'Pung',
            4 => 'Kong',
            5 => 'Quint',
            6 => 'Sextet',
            default => $group->size().' tiles',
        };
    }

    /**
     * Describe a group the way a teacher would say it out loud.
     */
    private function groupDescription(TileGroup $group): string
    {
        $label = $this->groupLabel($group);
        $first = $group->tiles[0];
        $suitPhrase = $this->suitPhrase($group->suitVariable());

        if ($group->isIdentical()) {
            return match (true) {
                $first instanceof FlowerTile => "{$label} of flowers",
                $first instanceof DragonTile => "{$label} of dragons {$suitPhrase}",
                $first instanceof ZeroTile => "{$label} of soaps (the white dragon standing in for zero)",
                $first instanceof WindTile => "{$label} of {$first->symbol()} winds",
                $first instanceof NumberTile && $first->number->isVariable() => $first->number->offset === 0
                    ? "{$label} of any one number — call it {$first->symbol()} — {$suitPhrase}"
                    : "{$label} of {$first->symbol()}, {$first->number->offset} higher than {$first->number->variable}, {$suitPhrase}",
                default => "{$label} of {$first->symbol()}s {$suitPhrase}",
            };
        }

        $symbols = implode(' ', array_map(fn (TileSpec $tile): string => $tile->symbol(), $group->tiles));

        if ($this->isConsecutiveRun($group)) {
            return "{$group->size()} consecutive numbers {$suitPhrase}, reading {$symbols}";
        }

        if ($first instanceof WindTile) {
            return 'the four winds, '.$symbols;
        }

        $soap = $this->containsZero($group) ? ', with a soap for the zero' : '';

        return "the number {$symbols} {$suitPhrase}{$soap}";
    }

    /**
     * Get the phrase naming the suit a group binds to.
     */
    private function suitPhrase(?string $variable): string
    {
        if ($variable === null) {
            return '';
        }

        $assigned = $this->assignments[$variable] ?? null;

        return $assigned
            ? 'in '.Suit::from($assigned)->label()
            : "in suit {$variable}";
    }

    /**
     * Determine whether a group's numbers step up one at a time.
     */
    private function isConsecutiveRun(TileGroup $group): bool
    {
        foreach ($group->tiles as $tile) {
            if (! $tile instanceof NumberTile || ! $tile->number->isVariable()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether a group holds a soap.
     */
    private function containsZero(TileGroup $group): bool
    {
        foreach ($group->tiles as $tile) {
            if ($tile instanceof ZeroTile) {
                return true;
            }
        }

        return false;
    }
}; ?>

<div
    x-data
    @keydown.window.arrow-left="$wire.variant = {'A':'C','B':'A','C':'B'}[$wire.variant]"
    @keydown.window.arrow-right="$wire.variant = {'A':'B','B':'C','C':'A'}[$wire.variant]"
>
    @if (! $this->card)
        <flux:callout variant="danger" icon="exclamation-triangle" heading="No card seeded">
            Run <code>php artisan db:seed</code> to load the practice card.
        </flux:callout>
    @else
        @include('pages.prototype.decoder.variant-'.strtolower($this->variant))

        {{-- Floating variant switcher — prototype only --}}
        <div class="fixed inset-x-0 bottom-4 z-50 flex justify-center print:hidden">
            <div class="flex items-center gap-1 rounded-full border border-zinc-700 bg-zinc-900 px-2 py-1.5 text-white shadow-xl">
                <flux:button size="sm" variant="ghost" icon="chevron-left" inset
                    wire:click="$set('variant', '{{ ['A' => 'C', 'B' => 'A', 'C' => 'B'][$this->variant] }}')" class="text-white!" />

                <span class="px-2 text-xs font-medium whitespace-nowrap">
                    {{ $this->variant }} — {{ ['A' => 'Card page, inline breakdown', 'B' => 'Split workbench', 'C' => 'Focus deck'][$this->variant] }}
                </span>

                <flux:button size="sm" variant="ghost" icon="chevron-right" inset
                    wire:click="$set('variant', '{{ ['A' => 'B', 'B' => 'C', 'C' => 'A'][$this->variant] }}')" class="text-white!" />
            </div>
        </div>
    @endif
</div>

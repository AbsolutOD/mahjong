{{-- PROTOTYPE — Variant B's Visual Breakdown panel. --}}
<div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex items-start justify-between gap-2">
        <div>
            <flux:heading size="lg">{{ $hand->category->name }}</flux:heading>
            <p class="font-mono text-xs text-zinc-500">{{ $this->line($hand) }}</p>
        </div>
        <flux:badge color="zinc">{{ $hand->points }} pts</flux:badge>
    </div>

    <flux:separator class="my-4" />

    <div class="space-y-4">
        @foreach ($this->breakdown($hand) as $group)
            <div>
                <div class="flex flex-wrap gap-1">
                    @foreach ($group['tiles'] as $tile)
                        <x-prototype-tile size="lg" :symbol="$tile['symbol']" :variable="$tile['variable']" :assigned="$tile['assigned']" />
                    @endforeach
                </div>
                <p class="mt-2 text-sm">{{ $group['description'] }}</p>
                <p class="text-xs {{ $group['jokers'] ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-500' }}">
                    {{ $group['jokers'] ? 'Jokers may substitute here' : 'No jokers here' }}
                </p>
            </div>
        @endforeach
    </div>

    <flux:separator class="my-4" />

    <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $this->summary($hand) }}</p>

    <div class="mt-3 flex flex-wrap gap-2">
        @foreach ($this->tags($hand) as $tag)
            <flux:badge size="sm" :color="$tag['color']">{{ $tag['label'] }}</flux:badge>
        @endforeach
    </div>

    {{-- Suit assignment: the card's letters are "different suits", not fixed ones --}}
    @if ($hand->structure->suitVariables())
        <flux:separator class="my-4" />

        <div class="flex items-center justify-between">
            <flux:subheading>Try it in real suits</flux:subheading>
            <flux:button size="xs" variant="ghost" wire:click="clearAssignments">Reset</flux:button>
        </div>

        <div class="mt-2 space-y-2">
            @foreach ($hand->structure->suitVariables() as $variable)
                <div class="flex items-center gap-2" wire:key="assign-{{ $variable }}">
                    <span class="w-4 font-mono text-sm">{{ $variable }}</span>
                    @foreach (\App\Enums\Suit::cases() as $suit)
                        <button
                            type="button"
                            wire:click="assign('{{ $variable }}', '{{ $suit->value }}')"
                            class="rounded border px-2 py-1 text-xs {{ ($this->assignments[$variable] ?? null) === $suit->value ? 'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-900' : 'border-zinc-300 text-zinc-600 dark:border-zinc-600 dark:text-zinc-300' }}"
                        >{{ $suit->label() }}</button>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif
</div>

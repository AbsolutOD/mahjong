{{-- PROTOTYPE — Variant C: focus deck. One line at a time, big tiles, step through the category. --}}
<div class="mx-auto max-w-2xl pb-28">
    <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-3">
        @foreach ($this->categories as $category)
            <button
                type="button"
                wire:key="c-cat-{{ $category->id }}"
                wire:click="selectCategory({{ $category->id }})"
                class="shrink-0 rounded-full border px-3 py-1 text-xs font-medium {{ $this->category?->id === $category->id ? 'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-900' : 'border-zinc-300 text-zinc-600 dark:border-zinc-600 dark:text-zinc-300' }}"
            >{{ $category->name }}</button>
        @endforeach
    </div>

    @if ($hand = $this->hand)
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:badge size="sm" color="zinc">
                    {{ $this->hands->search(fn ($h) => $h->id === $hand->id) + 1 }} of {{ $this->hands->count() }}
                </flux:badge>
                <div class="flex items-center gap-2">
                    <flux:badge size="sm" :color="$hand->concealed ? 'purple' : 'zinc'">
                        {{ $hand->concealed ? 'Concealed' : 'Exposed OK' }}
                    </flux:badge>
                    <flux:badge size="sm" color="zinc">{{ $hand->points }} pts</flux:badge>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-end justify-center gap-x-5 gap-y-4">
                @foreach ($this->breakdown($hand) as $group)
                    <div class="text-center">
                        <div class="flex gap-1">
                            @foreach ($group['tiles'] as $tile)
                                <x-prototype-tile size="lg" :symbol="$tile['symbol']" :variable="$tile['variable']" :assigned="$tile['assigned']" />
                            @endforeach
                        </div>
                        <div class="mt-2 text-[11px] font-medium uppercase tracking-wide text-zinc-500">
                            {{ $group['label'] }}{{ $group['jokers'] ? ' · jokers' : '' }}
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="mt-8 text-center text-base text-zinc-700 dark:text-zinc-300">{{ $this->summary($hand) }}</p>

            <div class="mt-5 flex flex-wrap justify-center gap-2">
                @foreach ($this->tags($hand) as $tag)
                    <flux:badge size="sm" :color="$tag['color']">{{ $tag['label'] }}</flux:badge>
                @endforeach
            </div>

            <div class="mt-6 flex items-center justify-center gap-3">
                <flux:button size="sm" variant="subtle" wire:click="randomiseAssignments">Show real suits</flux:button>
                <flux:button size="sm" variant="ghost" wire:click="clearAssignments">Back to letters</flux:button>
            </div>
        </div>

        <div class="mt-4 flex items-center justify-between">
            <flux:button icon="chevron-left" wire:click="step(-1)">Previous</flux:button>
            <span class="font-mono text-xs text-zinc-400">{{ $this->line($hand) }}</span>
            <flux:button icon:trailing="chevron-right" wire:click="step(1)">Next</flux:button>
        </div>
    @endif
</div>

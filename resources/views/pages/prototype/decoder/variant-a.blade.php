{{-- PROTOTYPE — Variant A: the whole card as one page, breakdown opens inline under the line. --}}
<div class="mx-auto max-w-3xl pb-28">
    <div class="flex items-baseline gap-3">
        <flux:heading size="xl">{{ $this->card->name }}</flux:heading>
        <flux:badge size="sm" color="zinc">{{ $this->card->year }}</flux:badge>
    </div>
    <flux:subheading>Tap any line to read it in plain English.</flux:subheading>

    <div class="sticky top-0 z-20 -mx-4 mt-4 flex gap-2 overflow-x-auto border-b border-zinc-200 bg-white/95 px-4 py-3 backdrop-blur dark:border-zinc-700 dark:bg-zinc-800/95">
        @foreach ($this->categories as $category)
            <a
                href="#cat-{{ $category->id }}"
                class="shrink-0 rounded-full border border-zinc-300 px-3 py-1 text-xs font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700"
            >{{ $category->name }}</a>
        @endforeach
    </div>

    @foreach ($this->categories as $category)
        <section id="cat-{{ $category->id }}" class="mt-10 scroll-mt-20">
            <flux:heading size="lg">{{ $category->name }}</flux:heading>
            <flux:separator class="mt-2" />

            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($category->hands as $hand)
                    @php($open = $this->handId === $hand->id)

                    <div wire:key="a-hand-{{ $hand->id }}">
                        <button
                            type="button"
                            wire:click="selectHand({{ $hand->id }})"
                            class="flex w-full items-center gap-4 py-3 text-left hover:bg-zinc-50 dark:hover:bg-zinc-700/40"
                        >
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                @foreach ($this->breakdown($hand) as $group)
                                    <div class="flex gap-0.5">
                                        @foreach ($group['tiles'] as $tile)
                                            <x-prototype-tile size="sm" :symbol="$tile['symbol']" :variable="$tile['variable']" :assigned="$tile['assigned']" />
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>

                            <flux:spacer />

                            <div class="flex shrink-0 items-center gap-2">
                                <span class="font-mono text-sm text-zinc-500">{{ $hand->points }}</span>
                                <flux:badge size="sm" :color="$hand->concealed ? 'purple' : 'zinc'">
                                    {{ $hand->concealed ? 'C' : 'X' }}
                                </flux:badge>
                                <flux:icon.chevron-down class="size-4 text-zinc-400 {{ $open ? 'rotate-180' : '' }}" />
                            </div>
                        </button>

                        @if ($open)
                            <div class="mb-4 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-900">
                                <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $this->summary($hand) }}</p>

                                <div class="mt-4 space-y-3">
                                    @foreach ($this->breakdown($hand) as $group)
                                        <div class="flex items-center gap-4">
                                            <div class="flex gap-1">
                                                @foreach ($group['tiles'] as $tile)
                                                    <x-prototype-tile :symbol="$tile['symbol']" :variable="$tile['variable']" :assigned="$tile['assigned']" />
                                                @endforeach
                                            </div>
                                            <div class="text-sm">
                                                <div class="font-medium">{{ $group['description'] }}</div>
                                                <div class="text-xs text-zinc-500">
                                                    {{ $group['jokers'] ? 'Jokers may substitute here' : 'No jokers here' }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach ($this->tags($hand) as $tag)
                                        <flux:badge size="sm" :color="$tag['color']">{{ $tag['label'] }}</flux:badge>
                                    @endforeach
                                </div>

                                <p class="mt-3 font-mono text-xs text-zinc-400">{{ $this->line($hand) }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</div>

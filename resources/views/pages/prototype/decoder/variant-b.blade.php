{{-- PROTOTYPE — Variant B: split workbench. Category rail + line list + sticky breakdown panel with suit assignment. --}}
<div class="pb-28">
    <div class="flex gap-8">
        {{-- Category rail (desktop) --}}
        <aside class="hidden w-52 shrink-0 lg:block">
            <div class="space-y-0.5">
                @foreach ($this->categories as $category)
                    <button
                        type="button"
                        wire:key="b-cat-{{ $category->id }}"
                        wire:click="selectCategory({{ $category->id }})"
                        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm {{ $this->category?->id === $category->id ? 'bg-zinc-100 font-medium dark:bg-zinc-700' : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-700/50' }}"
                    >
                        <span>{{ $category->name }}</span>
                        <span class="text-xs opacity-60">{{ $category->hands->count() }}</span>
                    </button>
                @endforeach
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            {{-- Category chips (mobile) --}}
            <div class="-mx-4 mb-4 flex gap-2 overflow-x-auto px-4 lg:hidden">
                @foreach ($this->categories as $category)
                    <button
                        type="button"
                        wire:key="b-chip-{{ $category->id }}"
                        wire:click="selectCategory({{ $category->id }})"
                        class="shrink-0 rounded-full border px-3 py-1 text-xs font-medium {{ $this->category?->id === $category->id ? 'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-900' : 'border-zinc-300 text-zinc-600 dark:border-zinc-600 dark:text-zinc-300' }}"
                    >{{ $category->name }}</button>
                @endforeach
            </div>

            <flux:heading size="lg">{{ $this->category?->name }}</flux:heading>
            <flux:subheading>{{ $this->hands->count() }} lines · pick one to break it down</flux:subheading>

            <div class="mt-4 space-y-1">
                @foreach ($this->hands as $hand)
                    @php($selected = $this->hand?->id === $hand->id)

                    <button
                        type="button"
                        wire:key="b-hand-{{ $hand->id }}"
                        wire:click="selectHand({{ $hand->id }})"
                        class="flex w-full items-center gap-3 rounded-lg border px-3 py-2 text-left {{ $selected ? 'border-sky-500 bg-sky-50 dark:bg-sky-950/40' : 'border-transparent hover:border-zinc-200 hover:bg-zinc-50 dark:hover:border-zinc-700 dark:hover:bg-zinc-700/30' }}"
                    >
                        <span class="grow font-mono text-sm tracking-tight">{{ $this->line($hand) }}</span>
                        <span class="shrink-0 font-mono text-xs text-zinc-500">{{ $hand->points }}</span>
                        <flux:badge size="sm" :color="$hand->concealed ? 'purple' : 'zinc'">{{ $hand->concealed ? 'C' : 'X' }}</flux:badge>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Breakdown panel (desktop) --}}
        <aside class="sticky top-6 hidden h-fit w-96 shrink-0 xl:block">
            @includeWhen($this->hand, 'pages.prototype.decoder.panel', ['hand' => $this->hand])
        </aside>
    </div>

    {{-- Breakdown sheet (below xl) --}}
    @if ($this->hand)
        <div class="fixed inset-x-0 bottom-0 z-30 max-h-[58vh] overflow-y-auto border-t border-zinc-200 bg-white p-4 pb-20 shadow-2xl xl:hidden dark:border-zinc-700 dark:bg-zinc-900">
            @include('pages.prototype.decoder.panel', ['hand' => $this->hand])
        </div>
    @endif
</div>

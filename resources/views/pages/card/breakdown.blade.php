{{--
    The Visual Breakdown — one block per group, then the hand read as a
    sentence and the rules that follow from its shape.

    Nothing here is written per hand. Every label, sentence and tag arrives on
    the HandReading, so this file only lays them out.

    Rendered twice — once as the desktop panel, once as the mobile sheet — so
    every key is scoped to the copy it belongs to.

    @param string $scope  which copy this is, so its keys stay unique
--}}
<div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <flux:heading size="lg">{{ $this->category?->name }}</flux:heading>
            <p class="truncate font-mono text-xs text-zinc-500">{{ $this->line($this->hand) }}</p>
        </div>
        <flux:badge color="zinc">{{ $this->hand->points }} pts</flux:badge>
    </div>

    <flux:separator class="my-4" />

    <div class="space-y-5">
        @foreach ($this->reading->groups as $index => $group)
            <div wire:key="{{ $scope }}-group-{{ $this->hand->id }}-{{ $index }}">
                <div class="flex items-baseline justify-between gap-2">
                    <flux:subheading class="font-medium">{{ $group->label }}</flux:subheading>

                    <span @class([
                        'text-xs',
                        'text-amber-600 dark:text-amber-400' => $group->acceptsJokers,
                        'text-zinc-500' => ! $group->acceptsJokers,
                    ])>
                        {{ $group->acceptsJokers ? __('Jokers may substitute') : __('No jokers here') }}
                    </span>
                </div>

                <div class="mt-1.5 flex flex-wrap gap-1">
                    @foreach ($group->faces as $position => $face)
                        <x-tile wire:key="{{ $scope }}-face-{{ $this->hand->id }}-{{ $index }}-{{ $position }}" :face="$face" size="lg" />
                    @endforeach
                </div>

                <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">{{ $group->sentence }}</p>
            </div>
        @endforeach
    </div>

    <flux:separator class="my-4" />

    <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $this->reading->summary }}</p>

    <div class="mt-3 flex flex-wrap gap-1.5">
        @foreach ($this->reading->tags as $tag)
            <flux:badge size="sm" :color="$tag->tone->value">{{ $tag->label }}</flux:badge>
        @endforeach
    </div>

    {{--
        The affordance the prototype called the most valuable thing on the
        panel: the card's letters mean *different* suits, not fixed ones, and
        binding them is the only way that lands.
    --}}
    @if ($this->reading->suitVariables !== [])
        <flux:separator class="my-4" />

        <div class="flex items-center justify-between gap-2">
            <flux:subheading>{{ __('Try it in real suits') }}</flux:subheading>

            @if ($this->suits !== [])
                <flux:button size="xs" variant="ghost" wire:click="clearSuits">{{ __('Reset') }}</flux:button>
            @endif
        </div>

        <div class="mt-2 space-y-2">
            @foreach ($this->reading->suitVariables as $variable)
                <div class="flex items-center gap-2" wire:key="{{ $scope }}-bind-{{ $variable }}">
                    <span class="w-4 font-mono text-sm font-semibold">{{ $variable }}</span>

                    @foreach (App\Enums\Suit::cases() as $suit)
                        <button
                            type="button"
                            wire:key="{{ $scope }}-bind-{{ $variable }}-{{ $suit->value }}"
                            wire:click="assign('{{ $variable }}', '{{ $suit->value }}')"
                            aria-pressed="{{ ($this->suits[$variable] ?? null) === $suit->value ? 'true' : 'false' }}"
                            @class([
                                'rounded border px-2 py-1 text-xs',
                                'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-900' => ($this->suits[$variable] ?? null) === $suit->value,
                                'border-zinc-300 text-zinc-600 hover:border-zinc-400 dark:border-zinc-600 dark:text-zinc-300' => ($this->suits[$variable] ?? null) !== $suit->value,
                            ])
                        >{{ $suit->label() }}</button>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif
</div>

{{--
    The fit — one line of the card, measured against the rack.

    The words are the Line Decoder's: every label, sentence and rule tag comes
    off the HandReading, so a hand and its explanation cannot drift apart. What
    this adds is the rack's answer, slot by slot — the tiles you hold, the ones
    a joker would cover, and the ones you are still short of.

    The tiles drawn here are the settled hand rather than the card's slots: a
    line that says "any one number" is shown at the number that fits your rack
    best, and the binding it assumed is printed above.

    Rendered twice — once as the desktop panel, once inline below the list — so
    every key is scoped to the copy it belongs to.

    @param string $scope  which copy this is, so its keys stay unique
--}}
@php
    use App\Data\Tiles\TileFace;
    use App\Enums\SlotState;

    $match = $this->match;
@endphp

<div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <flux:heading size="lg">
                {{ $match->isComplete()
                    ? __('You have this hand')
                    : trans_choice('{1} :count tile away|[2,*] :count tiles away', $match->tilesAway(), ['count' => $match->tilesAway()]) }}
            </flux:heading>
            <p class="truncate font-mono text-xs text-zinc-500">{{ $this->line($match->hand) }}</p>
        </div>

        <div class="flex shrink-0 items-center gap-1.5">
            <flux:badge color="zinc">{{ $match->hand->points }} pts</flux:badge>
            <flux:badge :color="$match->hand->concealed ? 'purple' : 'zinc'">
                {{ $match->hand->concealed ? 'C' : 'X' }}
            </flux:badge>
        </div>
    </div>

    {{--
        The binding is named rather than assumed silently: it is the same lesson
        the decoder's "try it in real suits" teaches, that the card's letters
        mean suits which must differ.
    --}}
    @if ($match->instantiation->bindings !== [])
        <div class="mt-3 flex flex-wrap items-center gap-1.5">
            <span class="text-xs text-zinc-500">{{ __('Read as') }}</span>

            @foreach ($match->instantiation->bindings as $variable => $value)
                <flux:badge size="sm" color="sky" wire:key="{{ $scope }}-binding-{{ $match->hand->slug }}-{{ $variable }}">
                    {{ $variable }} = {{ $value }}
                </flux:badge>
            @endforeach
        </div>
    @endif

    <flux:separator class="my-4" />

    <div class="space-y-5">
        @foreach ($this->reading->groups as $index => $group)
            <div wire:key="{{ $scope }}-group-{{ $match->hand->slug }}-{{ $index }}">
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

                <div class="mt-1.5 flex flex-wrap gap-1.5">
                    @foreach ($match->coverage->groups[$index] as $position => $slot)
                        @php
                            /** Each slot says in a word what the rack does about it; the colour only agrees with it. */
                            [$mark, $spoken, $ring, $ink] = match ($slot->state) {
                                SlotState::Held => [__('have'), __('You hold this tile'), 'ring-emerald-500', 'text-emerald-600 dark:text-emerald-400'],
                                SlotState::Joker => [__('joker'), __('A joker would cover this tile'), 'ring-amber-500', 'text-amber-600 dark:text-amber-400'],
                                SlotState::Missing => [__('need'), __('You are still short of this tile'), 'ring-transparent', 'text-zinc-500'],
                            };
                        @endphp

                        <div class="flex flex-col items-center gap-0.5"
                            wire:key="{{ $scope }}-slot-{{ $match->hand->slug }}-{{ $index }}-{{ $position }}">
                            <div @class(['rounded-lg ring-2', $ring, 'opacity-40' => $slot->state === SlotState::Missing])>
                                <x-tile :face="TileFace::of($slot->tile)" />
                            </div>

                            <span @class(['text-[10px] font-semibold uppercase tracking-wide', $ink]) aria-hidden="true">
                                {{ $mark }}
                            </span>
                            <span class="sr-only">{{ $spoken }}</span>
                        </div>
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

    @if ($match->coverage->stillNeeded() !== [])
        <flux:separator class="my-4" />

        <flux:subheading>{{ __('Still to find') }}</flux:subheading>

        <div class="mt-2 flex flex-wrap gap-1.5">
            @foreach ($match->coverage->stillNeeded() as $need)
                <span class="flex items-center gap-1 rounded-md border border-zinc-200 px-1.5 py-1 text-xs dark:border-zinc-700"
                    wire:key="{{ $scope }}-need-{{ $match->hand->slug }}-{{ $need['tile']->code() }}">
                    <x-tile :face="TileFace::of($need['tile'])" size="sm" />
                    <span class="font-mono">&times;{{ $need['count'] }}</span>
                    <span class="sr-only">{{ TileFace::of($need['tile'])->name }}</span>
                </span>
            @endforeach
        </div>

        {{--
            The one thing this app cannot know. There is no game state here — no
            discards, no exposures, no other players — so a count of what is left
            in the world would be a promise it cannot keep (issue #15).
        --}}
        <flux:text size="sm" class="mt-3">
            {{ __('Closeness only. This says nothing about how easy the hand is to finish, or who else is holding what.') }}
        </flux:text>
    @endif
</div>

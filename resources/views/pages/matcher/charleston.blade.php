{{--
    The Charleston advice — what to pass at thirteen tiles, what to discard at
    fourteen (issue #17).

    Every reason comes off the TileAdvice, so the sentences are unit-tested
    rather than written here. What this adds is the one limit the advice cannot
    get past, and the passes it does not model as controls: courtesy, blind and
    the second Charleston are copy, because the advice is the same list for all
    of them.
--}}
@php
    use App\Data\Tiles\TileFace;

    $advice = $this->advice;
@endphp

<section class="mb-6 rounded-xl border border-sky-200 bg-sky-50/60 p-4 dark:border-sky-900 dark:bg-sky-950/30">
    <flux:heading size="lg">{{ __($advice->call()) }}</flux:heading>
    <flux:subheading>
        {{ __('Ordered by how few of your leading hands want each tile, then how few lines on the card do. Jokers are never passed.') }}
    </flux:subheading>

    <ol class="mt-3 space-y-2">
        @foreach ($advice->recommended() as $tileAdvice)
            <li class="flex items-center gap-3" wire:key="pass-{{ $tileAdvice->tile->code() }}-{{ $tileAdvice->copy }}">
                <x-tile :face="TileFace::of($tileAdvice->tile)" size="sm" />
                <span class="text-sm text-zinc-700 dark:text-zinc-300">
                    <span class="sr-only">{{ TileFace::of($tileAdvice->tile)->name }}:</span>
                    {{ __($tileAdvice->reason()) }}
                </span>
            </li>
        @endforeach
    </ol>

    {{-- The limit #15 accepted, carried into the advice: there is no game state here. --}}
    <flux:text size="sm" class="mt-3">
        {{ __('This reads the card, not the table — it cannot know which tiles are already dead.') }}
    </flux:text>

    @if (count($advice->recommended()) > 1)
        <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-zinc-600 dark:text-zinc-400">
            <li>{{ __('Courtesy pass: offer from the top of this list, as many tiles as you and the player across agree on.') }}</li>
            <li>{{ __('Blind pass: on the last pass of each Charleston you may pass on tiles you were just handed, unseen, and top up from this list.') }}</li>
            <li>{{ __('Second Charleston: nothing about the advice changes. Update your rack after each pass and read it again.') }}</li>
        </ul>
    @endif
</section>

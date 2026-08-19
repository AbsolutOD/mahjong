{{--
    The front door (issue #32).

    Anonymous v1 has no dashboard behind a login, so `/` is the whole front
    door: the only place the product gets to say what it is before a visitor
    is dropped into the workbench.

    The tiles below are drawn from fixed `Tile`s rather than read from the
    seeded card, on purpose — a front door that goes blank when the card is
    missing is #30's defect in a new costume.
--}}
@php
    use App\Data\Tiles\Tile;
    use App\Data\Tiles\TileFace;
    use App\Enums\Dragon;
    use App\Enums\Suit;

    /** A taste of the set: a run, a dragon, and the joker the card leans on. */
    $taste = [
        Tile::number(Suit::Dots, 2),
        Tile::number(Suit::Dots, 4),
        Tile::number(Suit::Bams, 6),
        Tile::dragon(Dragon::Red),
        Tile::joker(),
    ];

    /** The three PRD phases, in the order they ship. */
    $phases = [
        [
            'name' => 'Line Decoder',
            'blurb' => 'Read any line on the card as tiles, in plain English: what each group is, which suits have to differ, and where a joker is legal.',
            'route' => route('card'),
        ],
        [
            'name' => 'Hand Matcher',
            'blurb' => 'Lay out your rack and see which lines you are closest to, how many tiles away each one is, and what to pass in the Charleston.',
            'route' => null,
        ],
        [
            'name' => 'Practice & Quiz',
            'blurb' => 'Drill the things that cost you the game: whether a hand needs one suit or three, and whether that joker is actually allowed.',
            'route' => null,
        ],
    ];
@endphp

<x-layouts::public>
    <section class="mx-auto max-w-3xl px-2 py-12 text-center sm:py-20">
        <flux:heading size="xl" level="1" class="text-4xl! font-bold tracking-tight sm:text-5xl!">
            Learn to read the American Mah Jongg card
        </flux:heading>

        <flux:subheading size="lg" class="mt-6">
            The card is printed in a shorthand that assumes you already know it — <span class="whitespace-nowrap font-mono text-sm">FFFF 2025 DDD DDD</span> is a real hand, and nothing on the card explains it. {{ config('app.name') }} reads the card out loud: every line, as tiles, in words.
        </flux:subheading>

        <div class="mt-10 flex flex-wrap items-end justify-center gap-2" aria-hidden="true">
            @foreach ($taste as $tile)
                <x-tile :face="TileFace::of($tile)" size="lg" />
            @endforeach
        </div>

        <flux:button href="{{ route('card') }}" variant="primary" class="mt-10" wire:navigate>
            Open the Line Decoder
        </flux:button>

        <flux:text size="sm" class="mt-4">No account, no sign-up.</flux:text>
    </section>

    <flux:separator class="my-4" />

    <section class="mx-auto max-w-5xl px-2 py-12">
        <flux:heading size="lg" level="2" class="text-center">Three ways in, as they arrive</flux:heading>

        <div class="mt-8 grid gap-4 sm:grid-cols-3">
            @foreach ($phases as $phase)
                <div @class([
                    'flex flex-col gap-3 rounded-xl border p-5',
                    'border-zinc-200 dark:border-zinc-800' => $phase['route'],
                    'border-dashed border-zinc-200/80 dark:border-zinc-800/80' => ! $phase['route'],
                ])>
                    <div class="flex items-start justify-between gap-2">
                        <flux:heading size="base" level="3">{{ $phase['name'] }}</flux:heading>

                        @if ($phase['route'])
                            <flux:badge size="sm" color="lime" inset="top">Ready</flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc" inset="top">Not built yet</flux:badge>
                        @endif
                    </div>

                    <flux:text size="sm" class="grow">{{ $phase['blurb'] }}</flux:text>

                    @if ($phase['route'])
                        <flux:button href="{{ $phase['route'] }}" size="sm" class="self-start" wire:navigate>
                            Open it
                        </flux:button>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    <flux:separator class="my-4" />

    <footer class="mx-auto max-w-3xl px-2 pb-12 text-center">
        <flux:text size="sm">
            {{ config('app.name') }} teaches with its own practice card — 50 original hands written in the National Mah Jongg League's style. It is not the NMJL card, and none of the League's text is reproduced here. To play, buy their card.
        </flux:text>
    </footer>
</x-layouts::public>

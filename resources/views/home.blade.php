{{--
    The front door (issue #32).

    Anonymous v1 has no dashboard behind a login, so `/` is the whole front
    door: the only place the product gets to say what it is before a visitor
    is dropped into the workbench.

    The tiles below are drawn from fixed `Tile`s rather than read from the
    seeded card, on purpose — see the guard in tests/Feature/FrontDoorTest.php.
--}}
@php
    use App\Data\Tiles\Tile;
    use App\Data\Tiles\TileFace;
    use App\Enums\Dragon;
    use App\Enums\Suit;

    /**
     * A taste of the set: part of a run, a dragon, and the joker.
     *
     * @var list<Tile>
     */
    $taste = [
        Tile::number(Suit::Dots, 2),
        Tile::number(Suit::Dots, 4),
        Tile::number(Suit::Bams, 6),
        Tile::dragon(Dragon::Red),
        Tile::joker(),
    ];

    /**
     * The three PRD phases, in the order they ship.
     *
     * @var list<array{name: string, blurb: string, route: string|null}>
     */
    $phases = [
        [
            'name' => __('Line Decoder'),
            'blurb' => __('Read any line on the card as tiles, in plain English: what each group is, which suits have to differ, and where a joker is legal.'),
            'route' => route('card'),
        ],
        [
            'name' => __('Hand Matcher'),
            'blurb' => __('Lay out your rack and see which lines you are closest to, how many tiles away each one is, and what to pass in the Charleston.'),
            'route' => null,
        ],
        [
            'name' => __('Practice & Quiz'),
            'blurb' => __('Drill the things that cost you the game: whether a hand needs one suit or three, and whether that joker is actually allowed.'),
            'route' => null,
        ],
    ];
@endphp

<x-layouts::public>
    <section class="mx-auto max-w-3xl px-2 py-12 text-center sm:py-20">
        <flux:heading size="xl" level="1" class="text-4xl! font-bold tracking-tight sm:text-5xl!">
            {{ __('Learn to read the American Mah Jongg card') }}
        </flux:heading>

        <flux:subheading size="lg" class="mt-6">
            {{ __('The card is printed in a shorthand that assumes you already know it.') }}
            <span class="whitespace-nowrap font-mono text-sm">FFFF 2025 DDD DDD</span>
            {{ __('is a real hand, and nothing on the card explains it.') }}
            {{ __(':name reads the card out loud: every line, as tiles, in words.', ['name' => config('app.name')]) }}
        </flux:subheading>

        <div class="mt-10 flex flex-wrap items-end justify-center gap-2">
            @foreach ($taste as $tile)
                <x-tile :face="TileFace::of($tile)" size="lg" />
            @endforeach
        </div>

        <flux:button href="{{ route('card') }}" variant="primary" class="mt-10" wire:navigate>
            {{ __('Open the Line Decoder') }}
        </flux:button>

        <flux:text size="sm" class="mt-4">{{ __('No account, no sign-up.') }}</flux:text>
    </section>

    <flux:separator class="my-4" />

    <section class="mx-auto max-w-5xl px-2 py-12">
        <flux:heading size="lg" level="2" class="text-center">
            {{ __('Three ways in, as they arrive') }}
        </flux:heading>

        <div class="mt-8 grid gap-4 sm:grid-cols-3">
            @foreach ($phases as $phase)
                @php($ready = $phase['route'] !== null)

                <div @class([
                    'flex flex-col gap-3 rounded-xl border p-5',
                    'border-zinc-200 dark:border-zinc-800' => $ready,
                    'border-dashed border-zinc-300 dark:border-zinc-700' => ! $ready,
                ])>
                    <div class="flex items-start justify-between gap-2">
                        <flux:heading size="base" level="3">{{ $phase['name'] }}</flux:heading>

                        <flux:badge size="sm" :color="$ready ? 'lime' : 'zinc'" inset="top">
                            {{ $ready ? __('Ready') : __('Not built yet') }}
                        </flux:badge>
                    </div>

                    <flux:text size="sm" class="grow">{{ $phase['blurb'] }}</flux:text>

                    @if ($ready)
                        <flux:button href="{{ $phase['route'] }}" size="sm" class="self-start" wire:navigate>
                            {{ __('Open it') }}
                        </flux:button>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    <flux:separator class="my-4" />

    <footer class="mx-auto max-w-3xl px-2 pb-12 text-center">
        <flux:text size="sm">
            {{ __(':name teaches with its own practice card — 50 original hands written in the National Mah Jongg League\'s style. It is not the NMJL card, and none of the League\'s text is reproduced here. To play, buy their card.', ['name' => config('app.name')]) }}
        </flux:text>
    </footer>
</x-layouts::public>

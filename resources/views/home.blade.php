{{--
    The front door (issue #32).

    Anonymous v1 asks for no login, so `/` is the whole front door: the only
    place the product gets to say what it is before a visitor is dropped into
    the workbench. The signed-in dashboard offers the same ways in (#47).

    The tiles below are drawn from fixed `Tile`s rather than read from the
    seeded card, on purpose — see the guard in tests/Feature/FrontDoorTest.php.
    The sample line is fixed for the same reason, and a test holds it to the
    practice card instead.
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
     * A line off the practice card, opened in the decoder by its slugs.
     *
     * @var array{line: string, route: string}
     */
    $sample = [
        'line' => 'FFFF 2026 222 666',
        'route' => route('card', ['category' => 'year', 'hand' => 'ffff-2026a-222b-666b']),
    ];
@endphp

<x-layouts::public>
    <section class="mx-auto max-w-3xl px-2 py-12 text-center sm:py-20">
        <flux:heading size="xl" level="1" class="text-4xl! font-bold tracking-tight sm:text-5xl!">
            {{ __('Learn to read the American Mah Jongg card') }}
        </flux:heading>

        <flux:subheading size="lg" class="mt-6">
            {{ __('The card is printed in a shorthand that assumes you already know it.') }}
            <a href="{{ $sample['route'] }}" class="whitespace-nowrap font-mono text-sm underline underline-offset-4" wire:navigate>{{ $sample['line'] }}</a>
            {{ __('is a hand on our practice card, and nothing on the card explains it.') }}
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
            {{ __('Three ways in') }}
        </flux:heading>

        <x-ways-in class="mt-8" />
    </section>

    <flux:separator class="my-4" />

    <footer class="mx-auto max-w-3xl px-2 pb-12 text-center">
        <flux:text size="sm">
            {{ __(':name teaches with its own practice card — 54 original hands written in the National Mah Jongg League\'s style. It is not the NMJL card, and none of the League\'s text is reproduced here. To play, buy their card.', ['name' => config('app.name')]) }}
        </flux:text>
    </footer>
</x-layouts::public>

@php
    use App\Data\Tiles\DragonTile;
    use App\Data\Tiles\NumberTile;
    use App\Data\Tiles\NumberValue;
    use App\Data\Tiles\SuitAssignment;
    use App\Data\Tiles\Tile;
    use App\Data\Tiles\TileFace;
    use App\Data\Tiles\ZeroTile;
    use App\Enums\Suit;

    $assignment = $assign
        ? SuitAssignment::of(['A' => Suit::Dots, 'B' => Suit::Bams, 'C' => Suit::Craks])
        : SuitAssignment::none();

    /** The three states a card slot renders in, side by side. */
    $slots = [
        new NumberTile('A', NumberValue::literal(7)),
        new NumberTile('A', NumberValue::variable('X')),
        new NumberTile('B', NumberValue::variable('X', 1)),
        new NumberTile('B', NumberValue::variable('X', 2)),
        new DragonTile('C'),
        new ZeroTile,
    ];

    $set = [
        'Dots' => array_map(fn (int $n): Tile => Tile::number(Suit::Dots, $n), range(1, 9)),
        'Bams' => array_map(fn (int $n): Tile => Tile::number(Suit::Bams, $n), range(1, 9)),
        'Craks' => array_map(fn (int $n): Tile => Tile::number(Suit::Craks, $n), range(1, 9)),
        'Winds' => array_map(Tile::wind(...), [
            App\Enums\Wind::East,
            App\Enums\Wind::South,
            App\Enums\Wind::West,
            App\Enums\Wind::North,
        ]),
        'Dragons, flower and joker' => [
            ...array_map(Tile::dragon(...), App\Enums\Dragon::cases()),
            Tile::flower(),
            Tile::joker(),
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-neutral-950">
    <div class="mx-auto max-w-4xl space-y-12 px-6 py-10">
        <header class="space-y-2">
            <flux:heading size="xl">Tile faces</flux:heading>
            <flux:text>
                Every face in the American set, plus the card slots that have no physical tile.
                Grey means the card has not chosen a suit yet.
            </flux:text>
        </header>

        <section class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <flux:heading size="lg">Card slots</flux:heading>

                <flux:button href="{{ route('tiles', $assign ? [] : ['assign' => 1]) }}" size="sm" variant="filled">
                    {{ $assign ? 'Unbind the suits' : 'Try it in real suits' }}
                </flux:button>
            </div>

            <div class="flex flex-wrap items-end gap-3">
                @foreach ($slots as $slot)
                    <x-tile :face="TileFace::for($slot, $assignment)" size="lg" />
                @endforeach
            </div>
        </section>

        @foreach ($set as $heading => $tiles)
            <section class="space-y-4">
                <flux:heading size="lg">{{ $heading }}</flux:heading>

                <div class="flex flex-wrap items-end gap-3">
                    @foreach ($tiles as $tile)
                        <x-tile :face="TileFace::of($tile)" size="lg" />
                    @endforeach
                </div>
            </section>
        @endforeach

        <section class="space-y-4">
            <flux:heading size="lg">Size ladder</flux:heading>

            <div class="flex flex-wrap items-end gap-3">
                @foreach (['sm', 'md', 'lg'] as $size)
                    <x-tile :face="TileFace::of(Tile::dragon(App\Enums\Dragon::Red))" :size="$size" />
                    <x-tile :face="TileFace::of(Tile::dragon(App\Enums\Dragon::Green))" :size="$size" />
                    <x-tile :face="TileFace::of(Tile::number(Suit::Bams, 7))" :size="$size" />
                @endforeach
            </div>
        </section>
    </div>

    @fluxScripts
    </body>
</html>

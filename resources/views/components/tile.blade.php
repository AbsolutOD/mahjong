{{--
    One tile face, drawn as SVG (issues #5, #8, #11).

    A traditional silhouette plus two things a real tile does not have: a
    suit-coloured edge band and a permanent playing-card corner index. The
    scaffolding is what lets a card slot stay honest — an unresolved slot draws
    an empty dashed well and no artwork at all, so the tile never asserts a
    suit the card has not chosen.

    Every decision about *what* to draw was made by the TileFace; this template
    only draws it.
--}}
@props([
    'face',
    'size' => 'md',
])

@php
    $box = match ($size) {
        'sm' => 'w-8',
        'lg' => 'w-20',
        default => 'w-12',
    };

    $ink = $face->tone->ink();
    $tile = $face->tile;

    /**
     * Marks per row for each number, laid out so the pattern reads at a glance
     * rather than as a uniform grid — 7 is a lone mark over two rows of three,
     * the way the traditional tile draws it.
     */
    $rows = [
        1 => [1], 2 => [1, 1], 3 => [1, 2], 4 => [2, 2], 5 => [2, 1, 2],
        6 => [3, 3], 7 => [1, 3, 3], 8 => [4, 4], 9 => [3, 3, 3],
    ];

    $pips = [];

    if ($tile?->number !== null && $tile->suit !== App\Enums\Suit::Craks) {
        $lines = $rows[$tile->number];
        $top = count($lines) === 1 ? 48 : 30 + (3 - count($lines)) * 7;

        foreach ($lines as $row => $count) {
            $y = $top + $row * (count($lines) === 3 ? 15 : 18);

            foreach (range(1, $count) as $column) {
                $pips[] = [30 + ($column - ($count + 1) / 2) * ($count > 3 ? 10 : 13), $y];
            }
        }
    }

    $single = $tile?->number === 1;
@endphp

<div {{ $attributes->class(['shrink-0', $box]) }}>
    <svg viewBox="0 0 60 80" class="h-auto w-full drop-shadow-sm" role="img"
        aria-label="{{ $face->name }}" data-tile-face data-tone="{{ $face->tone->value }}">
        <title>{{ $face->name }}</title>

        <rect x="1" y="1" width="58" height="78" rx="6" fill="var(--color-tile-body)"
            stroke="var(--color-tile-edge)" stroke-width="1.5" />

        {{-- the suit band: scaffolding a real tile does not have --}}
        <path d="M1 7 a6 6 0 0 1 6 -6 h46 a6 6 0 0 1 6 6 v6 h-58 z" fill="{{ $ink }}" />

        {{-- the corner index, permanent, the way a playing card carries one --}}
        <text data-index x="6" y="26" font-size="12" font-weight="700" fill="{{ $ink }}"
            font-family="ui-sans-serif,system-ui,sans-serif">{{ $face->index }}</text>

        @if (! $face->isResolved())
            {{-- nothing is settled enough to draw: an empty well and the letter it waits on --}}
            <g data-well>
                <rect x="12" y="30" width="36" height="40" rx="4" fill="none" stroke="{{ $ink }}"
                    stroke-width="2" stroke-dasharray="4 3" opacity="0.5" />
                <text x="30" y="60" text-anchor="middle" font-size="26" font-weight="800" fill="{{ $ink }}"
                    font-family="ui-sans-serif,system-ui,sans-serif">{{ $face->well }}</text>
            </g>
        @else
            <g data-artwork>
                @if ($pips !== [])
                    @foreach ($pips as [$cx, $cy])
                        @if ($tile->suit === App\Enums\Suit::Dots)
                            <circle data-pip cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $single ? 13 : 5.5 }}" fill="{{ $ink }}" />
                        @else
                            <rect data-pip x="{{ $cx - ($single ? 6 : 2.6) }}" y="{{ $cy - ($single ? 14 : 6) }}"
                                width="{{ $single ? 12 : 5.2 }}" height="{{ $single ? 28 : 12 }}"
                                rx="{{ $single ? 6 : 2.6 }}" fill="{{ $ink }}" />
                        @endif
                    @endforeach

                @elseif ($tile->number !== null)
                    <text x="34" y="52" text-anchor="middle" font-size="30" font-weight="800" fill="{{ $ink }}"
                        font-family="ui-sans-serif,system-ui,sans-serif">{{ $tile->number }}</text>

                @elseif ($tile->type === App\Enums\TileType::Flower)
                    @foreach ([0, 72, 144, 216, 288] as $angle)
                        <ellipse cx="32" cy="35" rx="5" ry="10" transform="rotate({{ $angle }} 32 45)"
                            fill="{{ $ink }}" opacity="0.8" />
                    @endforeach
                    <circle cx="32" cy="45" r="4" fill="var(--color-tile-body)" />

                @elseif ($tile->type === App\Enums\TileType::Joker)
                    <text x="32" y="55" text-anchor="middle" font-size="30" font-weight="800" fill="{{ $ink }}"
                        font-family="ui-sans-serif,system-ui,sans-serif">J</text>

                @elseif ($face->glyph() === null)
                    {{-- the soap: a blank framed square, which is the whole point of it --}}
                    <rect x="16" y="32" width="30" height="30" rx="3" fill="none" stroke="{{ $ink }}" stroke-width="3" />
                @endif

                @if ($face->glyph() !== null)
                    <text x="{{ $tile->number === null ? 32 : 34 }}"
                        y="{{ $tile->number === null ? 56 : 72 }}" text-anchor="middle"
                        font-size="{{ $tile->number === null ? 30 : 16 }}" fill="{{ $ink }}"
                        font-family="'PingFang SC','Hiragino Sans GB',serif">{{ $face->glyph() }}</text>
                @endif
            </g>
        @endif

        @if ($face->word() !== null)
            {{-- load-bearing accessibility, not decoration: hue is never the only signal --}}
            <text data-word x="32" y="73" text-anchor="middle" font-size="9" font-weight="700" fill="{{ $ink }}"
                font-family="ui-sans-serif,system-ui,sans-serif">{{ $face->word() }}</text>
        @endif
    </svg>
</div>

{{--
    PROTOTYPE variant C — "Hybrid: scaffolded tile" (issue #8).
    Traditional silhouette, flattened and chunked up for legibility, PLUS learner
    scaffolding a real tile doesn't have: a suit-coloured edge band and a
    permanent playing-card corner index (numeral + suit letter). Abstract card
    slots keep the band and index and blank the artwork.
--}}
@props([
    'kind' => 'number',
    'suit' => null,
    'number' => null,
    'wind' => null,
    'dragon' => null,
    'variable' => null,
    'size' => 'md',
])

@php
    $box = match ($size) {
        'sm' => 'w-8',
        'lg' => 'w-20',
        default => 'w-12',
    };

    $abstract = $kind === 'number' && ($suit === null || ! is_int($number));

    $suitInk = ['dots' => '#0284c7', 'bams' => '#059669', 'craks' => '#e11d48'];
    $suitLetter = ['dots' => 'D', 'bams' => 'B', 'craks' => 'C'];

    $band = match (true) {
        $kind === 'number' => $suit ? $suitInk[$suit] : '#a1a1aa',
        $kind === 'wind' => '#475569',
        $kind === 'dragon' => ['red' => '#dc2626', 'green' => '#15803d', 'white' => '#0891b2'][$dragon],
        $kind === 'flower' => '#d97706',
        default => '#7c3aed',
    };

    $index = match (true) {
        $abstract => (is_int($number) ? $number : ($number ?? '?')).($suit ? $suitLetter[$suit] : ($variable ?? '')),
        $kind === 'number' => $number.$suitLetter[$suit],
        $kind === 'wind' => $wind,
        $kind === 'dragon' => ['red' => 'RD', 'green' => 'GD', 'white' => 'SO'][$dragon],
        $kind === 'flower' => 'FL',
        default => 'JK',
    };

    /** Chunky grids: rows of n per line, laid out in the 12..48 / 26..70 well. */
    $rows = [
        1 => [1], 2 => [1, 1], 3 => [1, 2], 4 => [2, 2], 5 => [2, 1, 2],
        6 => [3, 3], 7 => [1, 3, 3], 8 => [4, 4], 9 => [3, 3, 3],
    ];

    $marks = [];

    if ($kind === 'number' && is_int($number)) {
        $lines = $rows[$number];
        $top = count($lines) === 1 ? 48 : 30 + (3 - count($lines)) * 7;

        foreach ($lines as $row => $count) {
            $y = $top + $row * (count($lines) === 3 ? 15 : 18);

            foreach (range(1, $count) as $col) {
                $marks[] = [30 + ($col - ($count + 1) / 2) * ($count > 3 ? 10 : 13), $y];
            }
        }
    }
@endphp

<div {{ $attributes->class(['shrink-0', $box]) }}>
    <svg viewBox="0 0 60 80" class="h-auto w-full drop-shadow-sm">
        <rect x="1" y="1" width="58" height="78" rx="6" fill="#faf7ef" stroke="#c9c2b2" stroke-width="1.5" />

        {{-- suit band: the scaffolding a real tile doesn't have --}}
        <path d="M1 7 a6 6 0 0 1 6 -6 h46 a6 6 0 0 1 6 6 v6 h-58 z" fill="{{ $band }}" />

        {{-- corner index, like a playing card --}}
        <text x="6" y="26" font-size="12" font-weight="700" fill="{{ $band }}"
            font-family="ui-sans-serif,system-ui,sans-serif">{{ $index }}</text>

        @if ($kind === 'number' && ! $abstract)
            @if ($suit === 'craks')
                <text x="34" y="52" text-anchor="middle" font-size="30" font-weight="800" fill="{{ $band }}"
                    font-family="ui-sans-serif,system-ui,sans-serif">{{ $number }}</text>
                <text x="34" y="72" text-anchor="middle" font-size="16" fill="{{ $band }}"
                    font-family="'PingFang SC','Hiragino Sans GB',serif">萬</text>

            @elseif ($suit === 'dots')
                @foreach ($marks as [$cx, $cy])
                    <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $number === 1 ? 13 : 5.5 }}" fill="{{ $band }}" />
                @endforeach

            @else
                @foreach ($marks as [$cx, $cy])
                    <rect x="{{ $cx - ($number === 1 ? 6 : 2.6) }}" y="{{ $cy - ($number === 1 ? 14 : 6) }}"
                        width="{{ $number === 1 ? 12 : 5.2 }}" height="{{ $number === 1 ? 28 : 12 }}"
                        rx="{{ $number === 1 ? 6 : 2.6 }}" fill="{{ $band }}" />
                @endforeach
            @endif

        @elseif ($abstract)
            <rect x="12" y="30" width="36" height="40" rx="4" fill="none" stroke="{{ $band }}"
                stroke-width="2" stroke-dasharray="4 3" opacity="0.5" />
            <text x="30" y="60" text-anchor="middle" font-size="26" font-weight="800" fill="{{ $band }}"
                font-family="ui-sans-serif,system-ui,sans-serif">{{ is_int($number) ? $number : ($number ?? '?') }}</text>

        @elseif ($kind === 'wind')
            <text x="32" y="50" text-anchor="middle" font-size="24" fill="{{ $band }}"
                font-family="'PingFang SC','Hiragino Sans GB',serif">{{ ['E' => '東', 'S' => '南', 'W' => '西', 'N' => '北'][$wind] }}</text>
            <text x="32" y="70" text-anchor="middle" font-size="10" font-weight="700" fill="{{ $band }}"
                font-family="ui-sans-serif,system-ui,sans-serif">{{ ['E' => 'EAST', 'S' => 'SOUTH', 'W' => 'WEST', 'N' => 'NORTH'][$wind] }}</text>

        @elseif ($kind === 'dragon')
            @if ($dragon === 'white')
                <rect x="16" y="32" width="30" height="30" rx="3" fill="none" stroke="{{ $band }}" stroke-width="3" />
            @else
                <text x="32" y="56" text-anchor="middle" font-size="30" fill="{{ $band }}"
                    font-family="'PingFang SC','Hiragino Sans GB',serif">{{ $dragon === 'red' ? '中' : '發' }}</text>
            @endif
            <text x="32" y="73" text-anchor="middle" font-size="9" font-weight="700" fill="{{ $band }}"
                font-family="ui-sans-serif,system-ui,sans-serif">{{ ['red' => 'RED', 'green' => 'GREEN', 'white' => 'SOAP'][$dragon] }}</text>

        @elseif ($kind === 'flower')
            @foreach ([0, 72, 144, 216, 288] as $angle)
                <ellipse cx="32" cy="35" rx="5" ry="10" transform="rotate({{ $angle }} 32 45)" fill="{{ $band }}" opacity="0.8" />
            @endforeach
            <circle cx="32" cy="45" r="4" fill="#faf7ef" />
            <text x="32" y="73" text-anchor="middle" font-size="9" font-weight="700" fill="{{ $band }}"
                font-family="ui-sans-serif,system-ui,sans-serif">FLOWER</text>

        @else
            <text x="32" y="55" text-anchor="middle" font-size="30" font-weight="800" fill="{{ $band }}"
                font-family="ui-sans-serif,system-ui,sans-serif">J</text>
            <text x="32" y="73" text-anchor="middle" font-size="9" font-weight="700" fill="{{ $band }}"
                font-family="ui-sans-serif,system-ui,sans-serif">JOKER</text>
        @endif
    </svg>
</div>

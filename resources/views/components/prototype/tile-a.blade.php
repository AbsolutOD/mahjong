{{--
    PROTOTYPE variant A — "Traditional facsimile" (issue #8).
    Faces mimic a real American Mah Jongg tile: ivory face, engraved line art,
    traditional colours. The suit is read from the artwork, not from a colour code.
    Abstract card slots (suit A/B/C, number X/Y/Z) are drawn as a ghosted face.
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

    /** Traditional-ish dot colours, indexed by position. */
    $dotInk = ['#1d4ed8', '#166534', '#b91c1c'];

    /** Dot layouts: [x, y] in a 0..60 / 0..80 face. */
    $dots = [
        1 => [[30, 40]],
        2 => [[30, 26], [30, 54]],
        3 => [[18, 24], [30, 40], [42, 56]],
        4 => [[20, 28], [40, 28], [20, 52], [40, 52]],
        5 => [[20, 26], [40, 26], [30, 40], [20, 54], [40, 54]],
        6 => [[20, 22], [40, 22], [20, 40], [40, 40], [20, 58], [40, 58]],
        7 => [[17, 20], [30, 29], [43, 38], [20, 54], [40, 54], [20, 66], [40, 66]],
        8 => [[20, 18], [40, 18], [20, 34], [40, 34], [20, 50], [40, 50], [20, 66], [40, 66]],
        9 => [[16, 22], [30, 22], [44, 22], [16, 40], [30, 40], [44, 40], [16, 58], [30, 58], [44, 58]],
    ];

    /** Bam layouts: stalk centres. 1 Bam is the bird, drawn separately. */
    $bams = [
        2 => [[30, 26], [30, 54]],
        3 => [[30, 22], [21, 54], [39, 54]],
        4 => [[21, 28], [39, 28], [21, 54], [39, 54]],
        5 => [[20, 26], [40, 26], [30, 40], [20, 56], [40, 56]],
        6 => [[18, 28], [30, 28], [42, 28], [18, 55], [30, 55], [42, 55]],
        7 => [[30, 20], [18, 42], [30, 42], [42, 42], [18, 62], [30, 62], [42, 62]],
        8 => [[17, 26], [26, 26], [35, 26], [44, 26], [17, 55], [26, 55], [35, 55], [44, 55]],
        9 => [[18, 22], [30, 22], [42, 22], [18, 42], [30, 42], [42, 42], [18, 62], [30, 62], [42, 62]],
    ];

    $crakNumerals = [1 => '一', '二', '三', '四', '五', '六', '七', '八', '九'];

    $windGlyph = ['E' => '東', 'S' => '南', 'W' => '西', 'N' => '北'];
@endphp

<div {{ $attributes->class(['shrink-0', $box]) }}>
    <svg viewBox="0 0 60 80" class="h-auto w-full drop-shadow-sm">
        {{-- tile body --}}
        <rect x="1" y="1" width="58" height="78" rx="6" fill="#f7f2e4" stroke="#a8a093" stroke-width="1.5" />
        <rect x="4" y="4" width="52" height="72" rx="4" fill="none" stroke="#e0d8c4" stroke-width="1" />

        <g @if ($abstract) opacity="0.35" @endif>
            @if ($kind === 'number')
                @php $shownSuit = $suit ?? 'dots'; @endphp

                @if ($shownSuit === 'dots')
                    @foreach ($dots[is_int($number) ? $number : 5] as $i => [$cx, $cy])
                        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ (is_int($number) && $number === 1) ? 15 : 6.5 }}"
                            fill="none" stroke="{{ $dotInk[$i % 3] }}" stroke-width="2" />
                        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ (is_int($number) && $number === 1) ? 7 : 2.5 }}"
                            fill="{{ $dotInk[($i + 1) % 3] }}" />
                    @endforeach

                @elseif ($shownSuit === 'bams')
                    @if (is_int($number) && $number === 1)
                        {{-- 1 Bam: the bird --}}
                        <ellipse cx="30" cy="46" rx="12" ry="16" fill="none" stroke="#166534" stroke-width="2" />
                        <path d="M30 30 q10 -8 14 -14 q-2 10 -8 16" fill="none" stroke="#b91c1c" stroke-width="2" />
                        <circle cx="26" cy="34" r="1.8" fill="#166534" />
                        <path d="M22 52 q8 10 16 0" fill="none" stroke="#166534" stroke-width="2" />
                    @else
                        @foreach ($bams[is_int($number) ? $number : 5] as $i => [$cx, $cy])
                            <g stroke="{{ $i % 4 === 1 ? '#b91c1c' : '#166534' }}" stroke-width="2" fill="none">
                                <line x1="{{ $cx }}" y1="{{ $cy - 8 }}" x2="{{ $cx }}" y2="{{ $cy + 8 }}" />
                                <line x1="{{ $cx - 3.5 }}" y1="{{ $cy - 8 }}" x2="{{ $cx + 3.5 }}" y2="{{ $cy - 8 }}" />
                                <line x1="{{ $cx - 3 }}" y1="{{ $cy }}" x2="{{ $cx + 3 }}" y2="{{ $cy }}" />
                                <line x1="{{ $cx - 3.5 }}" y1="{{ $cy + 8 }}" x2="{{ $cx + 3.5 }}" y2="{{ $cy + 8 }}" />
                            </g>
                        @endforeach
                    @endif

                @else
                    <text x="30" y="34" text-anchor="middle" font-size="26" fill="#166534"
                        font-family="'PingFang SC','Hiragino Sans GB','Songti SC',serif">{{ is_int($number) ? $crakNumerals[$number] : '五' }}</text>
                    <text x="30" y="68" text-anchor="middle" font-size="26" fill="#b91c1c"
                        font-family="'PingFang SC','Hiragino Sans GB','Songti SC',serif">萬</text>
                @endif

            @elseif ($kind === 'wind')
                <text x="30" y="54" text-anchor="middle" font-size="34" fill="#1f2937"
                    font-family="'PingFang SC','Hiragino Sans GB','Songti SC',serif">{{ $windGlyph[$wind] ?? '東' }}</text>

            @elseif ($kind === 'dragon' && $dragon === 'red')
                <text x="30" y="54" text-anchor="middle" font-size="34" fill="#b91c1c"
                    font-family="'PingFang SC','Hiragino Sans GB','Songti SC',serif">中</text>

            @elseif ($kind === 'dragon' && $dragon === 'green')
                <text x="30" y="54" text-anchor="middle" font-size="34" fill="#166534"
                    font-family="'PingFang SC','Hiragino Sans GB','Songti SC',serif">發</text>

            @elseif ($kind === 'dragon')
                {{-- soap: the white dragon's empty frame --}}
                <rect x="14" y="22" width="32" height="38" rx="2" fill="none" stroke="#1d4ed8" stroke-width="2" />
                <rect x="18" y="26" width="24" height="30" rx="1" fill="none" stroke="#1d4ed8" stroke-width="1" />

            @elseif ($kind === 'flower')
                <g stroke="#b91c1c" stroke-width="2" fill="none">
                    @foreach ([0, 72, 144, 216, 288] as $angle)
                        <ellipse cx="30" cy="30" rx="5" ry="11" transform="rotate({{ $angle }} 30 41)" />
                    @endforeach
                </g>
                <circle cx="30" cy="41" r="4" fill="#166534" />
                <text x="30" y="72" text-anchor="middle" font-size="12" fill="#166534"
                    font-family="'PingFang SC',serif">花</text>

            @elseif ($kind === 'joker')
                <path d="M18 24 l6 -8 l6 8 l6 -8 l6 8" fill="none" stroke="#b91c1c" stroke-width="2" />
                <circle cx="24" cy="16" r="2" fill="#166534" />
                <circle cx="36" cy="16" r="2" fill="#166534" />
                <text x="30" y="48" text-anchor="middle" font-size="11" fill="#1f2937"
                    font-family="Georgia,serif" letter-spacing="1">JOKER</text>
                <path d="M18 58 h24" stroke="#b91c1c" stroke-width="1.5" />
            @endif
        </g>

        @if ($abstract)
            <text x="30" y="{{ is_int($number) ? 52 : 46 }}" text-anchor="middle" font-size="26" font-weight="700"
                fill="#3f3a2e" font-family="Georgia,serif">{{ is_int($number) ? $number : ($number ?? '?') }}</text>
            @if ($suit || $variable)
                <text x="30" y="70" text-anchor="middle" font-size="12" fill="#8a806c"
                    font-family="Georgia,serif">{{ $suit ? ucfirst($suit) : 'suit '.$variable }}</text>
            @endif
        @endif
    </svg>
</div>

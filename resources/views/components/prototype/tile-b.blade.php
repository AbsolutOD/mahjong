{{--
    PROTOTYPE variant B — "Flat schematic" (issue #8).
    No traditional artwork and no SVG at all: pure HTML + Tailwind, so tiles are
    themeable, dark-mode native and legible at 24px. Suit is carried by colour +
    word, number by a big arabic numeral. Abstract card slots are deliberately
    GREY — colour only appears once a real suit is assigned.
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
        'sm' => 'h-11 w-8 rounded-md text-lg',
        'lg' => 'h-27 w-20 rounded-xl text-5xl',
        default => 'h-16 w-12 rounded-lg text-3xl',
    };

    $caption = match ($size) {
        'sm' => 'text-[7px]',
        'lg' => 'text-[11px]',
        default => 'text-[9px]',
    };

    $abstract = $kind === 'number' && ($suit === null || ! is_int($number));

    $tones = [
        'dots' => ['bg-sky-50 dark:bg-sky-950', 'text-sky-600 dark:text-sky-300', 'bg-sky-600 dark:bg-sky-500'],
        'bams' => ['bg-emerald-50 dark:bg-emerald-950', 'text-emerald-600 dark:text-emerald-300', 'bg-emerald-600 dark:bg-emerald-500'],
        'craks' => ['bg-rose-50 dark:bg-rose-950', 'text-rose-600 dark:text-rose-300', 'bg-rose-600 dark:bg-rose-500'],
        'wind' => ['bg-slate-50 dark:bg-slate-900', 'text-slate-700 dark:text-slate-200', 'bg-slate-600 dark:bg-slate-400'],
        'red' => ['bg-red-50 dark:bg-red-950', 'text-red-600 dark:text-red-300', 'bg-red-600 dark:bg-red-500'],
        'green' => ['bg-green-50 dark:bg-green-950', 'text-green-700 dark:text-green-300', 'bg-green-700 dark:bg-green-500'],
        'white' => ['bg-cyan-50 dark:bg-cyan-950', 'text-cyan-700 dark:text-cyan-300', 'bg-cyan-600 dark:bg-cyan-500'],
        'flower' => ['bg-amber-50 dark:bg-amber-950', 'text-amber-600 dark:text-amber-300', 'bg-amber-600 dark:bg-amber-500'],
        'joker' => ['bg-violet-50 dark:bg-violet-950', 'text-violet-600 dark:text-violet-300', 'bg-violet-600 dark:bg-violet-500'],
        'grey' => ['bg-zinc-100 dark:bg-zinc-800', 'text-zinc-500 dark:text-zinc-400', 'bg-zinc-400 dark:bg-zinc-600'],
    ];

    $suitWord = ['dots' => 'DOT', 'bams' => 'BAM', 'craks' => 'CRAK'];

    [$glyph, $word, $key] = match (true) {
        $abstract => [
            is_int($number) ? (string) $number : ($number ?? '?'),
            $suit ? $suitWord[$suit] : ($variable ? 'suit '.$variable : 'any'),
            $suit ?? 'grey',
        ],
        $kind === 'number' => [(string) $number, $suitWord[$suit], $suit],
        $kind === 'wind' => [$wind, ['E' => 'EAST', 'S' => 'SOUTH', 'W' => 'WEST', 'N' => 'NORTH'][$wind], 'wind'],
        $kind === 'dragon' => [
            ['red' => 'D', 'green' => 'D', 'white' => '□'][$dragon],
            ['red' => 'RED', 'green' => 'GREEN', 'white' => 'SOAP'][$dragon],
            $dragon,
        ],
        $kind === 'flower' => ['✿', 'FLOWER', 'flower'],
        default => ['J', 'JOKER', 'joker'],
    };

    [$face, $ink, $band] = $tones[$key];
@endphp

<div {{ $attributes->class([
    'relative flex shrink-0 flex-col items-center justify-center overflow-hidden font-bold ring-1 ring-inset ring-black/10 dark:ring-white/10',
    $box, $face, $ink,
    'border-2 border-dashed border-zinc-300 dark:border-zinc-600' => $abstract,
]) }}>
    <span class="leading-none">{{ $glyph }}</span>

    <span class="{{ $caption }} absolute inset-x-0 bottom-0 py-px text-center font-semibold tracking-widest text-white {{ $band }}">
        {{ $word }}
    </span>
</div>

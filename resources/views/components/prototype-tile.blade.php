{{-- PROTOTYPE — placeholder tile face. Real tiles are issues #8 / #11. --}}
@props([
    'symbol',
    'variable' => null,
    'assigned' => null,
    'size' => 'md',
    'joker' => false,
])

@php
    $palette = [
        'A' => 'border-sky-400 text-sky-700 dark:text-sky-300 dark:border-sky-500',
        'B' => 'border-rose-400 text-rose-700 dark:text-rose-300 dark:border-rose-500',
        'C' => 'border-emerald-400 text-emerald-700 dark:text-emerald-300 dark:border-emerald-500',
        'D' => 'border-violet-400 text-violet-700 dark:text-violet-300 dark:border-violet-500',
    ];

    $tone = $variable
        ? ($palette[$variable] ?? 'border-zinc-400 text-zinc-700 dark:text-zinc-300')
        : 'border-zinc-300 text-zinc-800 dark:border-zinc-600 dark:text-zinc-100';

    $box = match ($size) {
        'sm' => 'h-9 w-7 text-sm rounded',
        'lg' => 'h-20 w-15 text-3xl rounded-lg',
        default => 'h-14 w-11 text-xl rounded-md',
    };

    $footnote = match ($size) {
        'sm' => 'text-[8px]',
        'lg' => 'text-[11px]',
        default => 'text-[9px]',
    };
@endphp

<div {{ $attributes->class([
    'relative inline-flex shrink-0 flex-col items-center justify-center border-2 bg-white font-semibold shadow-sm dark:bg-zinc-900',
    $box,
    $tone,
]) }}>
    <span class="leading-none">{{ $symbol }}</span>

    @if ($variable)
        <span class="{{ $footnote }} absolute bottom-0.5 font-normal uppercase tracking-wide opacity-70">
            {{ $assigned ?? $variable }}
        </span>
    @endif
</div>

{{--
    The three ways into the product, one card per PRD phase.

    Shared by the front door and the signed-in dashboard (issue #47), so the
    two cannot drift into describing different products.
--}}
@php
    /**
     * The three PRD phases, in the order they shipped.
     *
     * @var list<array{name: string, blurb: string, route: string}>
     */
    $phases = [
        [
            'name' => __('Line Decoder'),
            'blurb' => __('Read any line on the card as tiles, in plain English: what each group is, which suits have to differ, and where a joker is legal.'),
            'route' => route('card'),
        ],
        [
            'name' => __('Hand Matcher'),
            'blurb' => __('Lay out your rack and see which lines you are closest to, how many tiles away each one is, which tiles you are still short of, and which to pass in the Charleston.'),
            'route' => route('matcher'),
        ],
        [
            'name' => __('Practice & Quiz'),
            'blurb' => __('Drill the things that cost you the game: whether a hand needs one suit or three, and whether that joker is actually allowed.'),
            'route' => route('practice'),
        ],
    ];
@endphp

<div {{ $attributes->class('grid gap-4 sm:grid-cols-3') }}>
    @foreach ($phases as $phase)
        <a
            href="{{ $phase['route'] }}"
            class="group flex flex-col gap-3 rounded-xl border border-zinc-200 p-5 transition hover:border-zinc-400 dark:border-zinc-800 dark:hover:border-zinc-600"
            wire:navigate
        >
            <flux:heading size="base" level="3">{{ $phase['name'] }}</flux:heading>

            <flux:text size="sm" class="grow">{{ $phase['blurb'] }}</flux:text>

            <flux:text size="sm" class="font-medium text-zinc-800! group-hover:underline dark:text-white!">
                {{ __('Open it') }} &rarr;
            </flux:text>
        </a>
    @endforeach
</div>

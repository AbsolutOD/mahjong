{{--
    The three ways into the product, one card per PRD phase.

    Shared by the front door and the signed-in dashboard (issue #47); the
    phases themselves are App\Enums\Phase, which the navs loop over too (issue #53).
--}}
<div {{ $attributes->class('grid gap-4 sm:grid-cols-3') }}>
    @foreach (App\Enums\Phase::cases() as $phase)
        <a
            href="{{ $phase->url() }}"
            class="group flex flex-col gap-3 rounded-xl border border-zinc-200 p-5 transition hover:border-zinc-400 dark:border-zinc-800 dark:hover:border-zinc-600"
            wire:navigate
        >
            <flux:heading size="base" level="3">{{ $phase->label() }}</flux:heading>

            <flux:text size="sm" class="grow">{{ $phase->blurb() }}</flux:text>

            <flux:text size="sm" class="font-medium text-zinc-800! group-hover:underline dark:text-white!">
                {{ __('Open it') }} &rarr;
            </flux:text>
        </a>
    @endforeach
</div>

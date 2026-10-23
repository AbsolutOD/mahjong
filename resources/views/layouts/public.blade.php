{{--
    The shell for pages that need no account.

    Learning the card is the app's whole point, so the Line Decoder sits in
    front of the sign-in wall; this layout is the app chrome without a sidebar,
    a team, or anything that assumes a user.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        @include('partials.head')
    </head>

    <body class="flex min-h-full flex-col bg-white antialiased dark:bg-neutral-950">
        <header class="sticky top-0 z-40 border-b border-zinc-200 bg-white/80 backdrop-blur dark:border-zinc-800 dark:bg-neutral-950/80">
            <div class="mx-auto flex max-w-[100rem] items-center justify-between gap-4 px-4 py-3 sm:px-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2" wire:navigate>
                    <x-app-logo-icon class="size-6" />
                    <span class="font-semibold">{{ config('app.name') }}</span>
                </a>

                <div class="flex items-center gap-2">
                    {{--
                        One link per phase, the same three the landing page offers.

                        `aria-current` is set by hand because Flux marks the current item
                        with `data-current`, which styles the link but tells a screen
                        reader nothing.
                    --}}
                    <flux:navbar>
                        @foreach (App\Enums\Phase::cases() as $phase)
                            <flux:navbar.item
                                :href="$phase->url()"
                                :current="$phase->isCurrent()"
                                :aria-current="$phase->isCurrent() ? 'page' : false"
                                wire:navigate
                            >
                                @if ($phase->shortLabel() === $phase->label())
                                    {{ $phase->label() }}
                                @else
                                    <span class="sm:hidden">{{ $phase->shortLabel() }}</span>
                                    <span class="max-sm:hidden">{{ $phase->label() }}</span>
                                @endif
                            </flux:navbar.item>
                        @endforeach
                    </flux:navbar>

                    {{--
                        The phases render here for everyone, so a signed-in learner who
                        opens one needs a way back to their account (issues #51, #54).
                        A guest still sees no way in: there is nothing to sign in to.
                    --}}
                    @auth
                        <flux:dropdown position="bottom" align="end">
                            <button type="button" class="shrink-0 rounded-full" aria-label="{{ __('Account') }}" data-test="account-menu-button">
                                <flux:avatar :initials="auth()->user()->initials()" size="sm" circle />
                            </button>

                            <flux:menu>
                                <div class="grid px-1 py-1.5 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                                <flux:menu.separator />
                                <flux:menu.item :href="route('dashboard')" icon="layout-grid" wire:navigate>
                                    {{ __('Dashboard') }}
                                </flux:menu.item>
                                <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                                    {{ __('Settings') }}
                                </flux:menu.item>
                                <form method="POST" action="{{ route('logout') }}" class="w-full">
                                    @csrf
                                    <flux:menu.item
                                        as="button"
                                        type="submit"
                                        icon="arrow-right-start-on-rectangle"
                                        class="w-full cursor-pointer"
                                        data-test="logout-button"
                                    >
                                        {{ __('Log out') }}
                                    </flux:menu.item>
                                </form>
                            </flux:menu>
                        </flux:dropdown>
                    @endauth
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-[100rem] grow px-4 py-6 sm:px-6">
            {{ $slot }}
        </main>

        @fluxScripts
    </body>
</html>

@props(['showTeam' => true])

<flux:dropdown position="bottom" align="start">
    <button type="button" class="group flex w-full items-center rounded-lg p-1 hover:bg-zinc-800/5 dark:hover:bg-white/10" data-test="sidebar-menu-button">
        <flux:avatar :initials="auth()->user()->initials()" size="sm" />
        <div class="in-data-flux-sidebar-collapsed-desktop:hidden mx-2 grid flex-1 text-start text-sm leading-tight">
            <span class="truncate font-medium text-zinc-500 group-hover:text-zinc-800 dark:text-white/80 dark:group-hover:text-white">{{ auth()->user()->name }}</span>
            @if($showTeam && auth()->user()->currentTeam)
                <span class="truncate text-xs text-zinc-400 dark:text-zinc-500">{{ auth()->user()->currentTeam->name }}</span>
            @endif
        </div>
        <flux:icon name="chevrons-up-down" variant="micro" class="in-data-flux-sidebar-collapsed-desktop:hidden ms-auto size-4 text-zinc-400 group-hover:text-zinc-800 dark:text-white/80 dark:group-hover:text-white" />
    </button>

    <x-account-menu />
</flux:dropdown>

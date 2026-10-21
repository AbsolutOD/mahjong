{{--
    Where Fortify sends every sign-in (issue #47).

    An account keeps nothing for a learner — #18 settled that a round is
    forgotten the moment the page is left — so the dashboard earns no content
    of its own, and offers the front door's ways in instead.

    Sending sign-ins to the front door was the other option, and was turned
    down because the pending-invitations modal and the accepted-invitation
    toast live here, and the public shell assumes no user.
--}}
<x-layouts::app :title="__('Dashboard')">
    <livewire:pages::teams.pending-invitations-modal />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-2">
        <flux:heading size="xl" level="1">{{ __('Where to next?') }}</flux:heading>

        <flux:subheading>{{ __('Nothing is saved to your account yet, so every tool works just as it does signed out.') }}</flux:subheading>

        <x-ways-in class="mt-6" />
    </div>
</x-layouts::app>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <style>
            /* Fix mobile sidebar overlay z-index over fixed bottom action bars */
            ui-sidebar, [data-flux-sidebar-backdrop] {
                z-index: 60 !important;
            }
        </style>
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 sticky top-0 z-40">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

            <flux:navbar class="-mb-px max-lg:hidden ml-4">
                <flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>
                <flux:navbar.item icon="clock" :href="route('tracker')" :current="request()->routeIs('tracker')" wire:navigate>
                    {{ __('Tracker') }}
                </flux:navbar.item>
                <flux:navbar.item icon="cog-8-tooth" :href="route('manage')" :current="request()->routeIs('manage')" wire:navigate>
                    {{ __('Manage') }}
                </flux:navbar.item>
                
                @if(auth()->check() && auth()->user()->is_admin)
                @php $openAdminIssues = App\Models\Issue::where('status', 'open')->count(); @endphp
                <flux:navbar.item icon="flag" :href="route('issues')" :current="request()->routeIs('issues')" :badge="$openAdminIssues ?: null" wire:navigate>
                    {{ __('Issues') }}
                </flux:navbar.item>
                <flux:navbar.item icon="users" :href="route('members')" :current="request()->routeIs('members')" wire:navigate>
                    {{ __('Members') }}
                </flux:navbar.item>
                <flux:navbar.item icon="megaphone" :href="route('broadcast')" :current="request()->routeIs('broadcast')" wire:navigate>
                    {{ __('Broadcast') }}
                </flux:navbar.item>
                @endif
            </flux:navbar>

            <flux:spacer />

            <x-desktop-user-menu />
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')">
                    <flux:sidebar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard')  }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clock" :href="route('tracker')" :current="request()->routeIs('tracker')" wire:navigate>
                        {{ __('Tracker') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="cog-8-tooth" :href="route('manage')" :current="request()->routeIs('manage')" wire:navigate>
                        {{ __('Manage') }}
                    </flux:sidebar.item>
                    
                    @if(auth()->check() && auth()->user()->is_admin)
                    <flux:sidebar.item icon="flag" :href="route('issues')" :current="request()->routeIs('issues')" wire:navigate>
                        {{ __('Issues') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="users" :href="route('members')" :current="request()->routeIs('members')" wire:navigate>
                        {{ __('Members') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="megaphone" :href="route('broadcast')" :current="request()->routeIs('broadcast')" wire:navigate>
                        {{ __('Broadcast') }}
                    </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu :name="auth()->user()->name" />
        </flux:sidebar>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist
        
        <livewire:quick-setup />
        <livewire:report-issue />
        <livewire:notifications />
        @include('partials.help-modals')

        @fluxScripts
    </body>
</html>

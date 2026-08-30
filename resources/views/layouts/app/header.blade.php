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
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 antialiased selection:bg-amber-500 selection:text-white">
        <flux:header container class="border-b border-zinc-200/80 bg-white/80 dark:border-zinc-800/80 dark:bg-zinc-950/90 backdrop-blur-xl sticky top-0 z-40">
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

            <flux:navbar class="me-1.5 space-x-1 rtl:space-x-reverse py-0!">
                <flux:tooltip :content="__('Repository')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="folder-git-2"
                        href="https://github.com/hoirilm/activity-tracker"
                        target="_blank"
                        :label="__('Repository')"
                    />
                </flux:tooltip>
                <flux:tooltip :content="__('Documentation')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="book-open-text"
                        href="https://laravel.com/docs/starter-kits#livewire"
                        target="_blank"
                        :label="__('Documentation')"
                    />
                </flux:tooltip>
            </flux:navbar>

            <x-desktop-user-menu />
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-zinc-200/80 bg-white/95 dark:border-zinc-800/80 dark:bg-zinc-950/95 backdrop-blur-2xl">
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

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/hoirilm/activity-tracker" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

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


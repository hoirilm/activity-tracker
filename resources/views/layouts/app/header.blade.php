<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

            <flux:navbar class="-mb-px max-lg:hidden">
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
                <flux:navbar.item icon="bug-ant" :href="route('issues')" :current="request()->routeIs('issues')" wire:navigate>
                    {{ __('Issues') }}
                </flux:navbar.item>
                @endif
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
                @if(auth()->check())
                <flux:modal.trigger name="report-issue-modal">
                    <flux:navbar.item class="!h-10 [&>div>svg]:size-5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20" icon="bug-ant" href="#" :label="__('Report Issue')" />
                </flux:modal.trigger>
                @endif
                <flux:tooltip :content="__('Search')" position="bottom">
                    <flux:navbar.item class="!h-10 [&>div>svg]:size-5" icon="magnifying-glass" href="#" :label="__('Search')" />
                </flux:tooltip>
                <flux:tooltip :content="__('Repository')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="folder-git-2"
                        href="https://github.com/laravel/livewire-starter-kit"
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
                    
                    @if(auth()->check())
                        <flux:modal.trigger name="report-issue-modal">
                            <flux:sidebar.item icon="bug-ant" class="text-red-500 hover:text-red-600">
                                {{ __('Report Issue') }}
                            </flux:sidebar.item>
                        </flux:modal.trigger>
                    @endif
                    
                    @if(auth()->check() && auth()->user()->is_admin)
                    <flux:sidebar.item icon="bug-ant" :href="route('issues')" :current="request()->routeIs('issues')" wire:navigate>
                        {{ __('Issues') }}
                    </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>
        </flux:sidebar>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist
        
        <livewire:report-issue />
        <livewire:notifications />
        @include('partials.help-modals')

        @fluxScripts
    </body>
</html>

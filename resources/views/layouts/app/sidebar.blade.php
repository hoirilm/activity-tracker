<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <style>
            /* Smooth Sidebar Animation (Gemini-style) */
            ui-sidebar {
                transition: width 400ms cubic-bezier(0.2, 0, 0, 1), 
                            padding 400ms cubic-bezier(0.2, 0, 0, 1) !important;
                will-change: width;
                overflow-x: hidden !important; /* Prevent text wrapping glitches */
            }
            
            /* Smooth layout shift for main content area */
            body {
                transition: grid-template-columns 400ms cubic-bezier(0.2, 0, 0, 1) !important;
            }
        </style>
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item id="tour-dashboard" icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>
                <flux:sidebar.item id="tour-tracker" icon="clock" :href="route('tracker')" :current="request()->routeIs('tracker')" wire:navigate>
                    {{ __('Tracker') }}
                </flux:sidebar.item>
                <flux:sidebar.item id="tour-manage" icon="cog-8-tooth" :href="route('manage')" :current="request()->routeIs('manage')" wire:navigate>
                    {{ __('Manage') }}
                </flux:sidebar.item>
                
                @if(auth()->check() && auth()->user()->is_admin)
                @php $openAdminIssues = App\Models\Issue::where('status', 'open')->count(); @endphp
                <flux:sidebar.item id="tour-issues" icon="flag" :href="route('issues')" :current="request()->routeIs('issues')" :badge="$openAdminIssues ?: null" wire:navigate>
                    {{ __('Issues') }}
                </flux:sidebar.item>
                <flux:sidebar.item id="tour-members" icon="users" :href="route('members')" :current="request()->routeIs('members')" wire:navigate>
                    {{ __('Members') }}
                </flux:sidebar.item>
                <flux:sidebar.item id="tour-broadcast" icon="megaphone" :href="route('broadcast')" :current="request()->routeIs('broadcast')" wire:navigate>
                    {{ __('Broadcast') }}
                </flux:sidebar.item>
                @endif
                

            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/hoirilm/activity-tracker" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="calendar-days" href="https://openproject.pactindo.com/weeklog/" target="_blank">
                    {{ __('Weeklog Primavisi') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

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
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist
        
        <!-- Floating Action Menu Top Right for Desktop -->
        @if(auth()->check())
        <div x-data="{ open: false }" @click.outside="open = false" class="fixed top-4 right-[4.25rem] z-40 hidden lg:block">
            <!-- Menu Options (Floats below the button) -->
            <div x-show="open" 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
                 class="absolute top-12 right-0 mt-2 w-56 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-lg py-1.5 z-50 origin-top-right"
                 style="display: none;">
                
                <div class="px-3 py-1.5 text-[10px] font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Help & Support</div>
                
                <!-- Help -->
                <flux:modal.trigger name="help-modal">
                    <button @click="open = false" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors duration-150 text-left cursor-pointer">
                        <flux:icon name="question-mark-circle" class="size-4 text-zinc-400 dark:text-zinc-500" />
                        <span>Help Center</span>
                    </button>
                </flux:modal.trigger>
                
                <!-- FAQ -->
                <flux:modal.trigger name="faq-modal">
                    <button @click="open = false" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors duration-150 text-left cursor-pointer">
                        <flux:icon name="information-circle" class="size-4 text-zinc-400 dark:text-zinc-500" />
                        <span>FAQ</span>
                    </button>
                </flux:modal.trigger>

                <hr class="my-1 border-zinc-100 dark:border-zinc-800" />

                <flux:modal.trigger name="report-issue-modal">
                    <button @click="open = false" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors duration-150 text-left cursor-pointer">
                        <flux:icon name="bug-ant" class="size-4 text-zinc-400 dark:text-zinc-500" />
                        <span>Report Bug</span>
                    </button>
                </flux:modal.trigger>
            </div>

            <!-- Trigger Button (Question Mark Icon) -->
            <button @click="open = !open" 
                    type="button"
                    id="tour-help-button"
                    class="shadow-lg flex items-center justify-center size-10 rounded-full bg-zinc-800 hover:bg-zinc-700 text-white dark:bg-zinc-100 dark:hover:bg-zinc-200 dark:text-zinc-900 transition-all duration-300 transform active:scale-95 cursor-pointer"
                    :class="open ? 'rotate-180 bg-zinc-650 dark:bg-zinc-350!' : ''">
                <!-- Question mark icon -->
                <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                </svg>
                <!-- Close (X) icon when open -->
                <svg x-show="open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-5" style="display: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        @endif
        
        <livewire:report-issue />
        <livewire:notifications />
        @include('partials.help-modals')

        @fluxScripts
    </body>
</html>

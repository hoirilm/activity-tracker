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
                overflow-x: hidden !important;
                min-height: 100vh !important;
            }
            body {
                transition: grid-template-columns 400ms cubic-bezier(0.2, 0, 0, 1) !important;
            }

            /* Prevent layout offset on mobile screens for custom Alpine drawer */
            @media (max-width: 1023px) {
                body {
                    display: block !important;
                    grid-template-columns: 1fr !important;
                }
                ui-sidebar {
                    display: none !important;
                }
            }
        </style>
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800" x-data="{ mobileNavOpen: false }">
        <!-- Desktop Sidebar (Hidden on mobile) -->
        <flux:sidebar sticky collapsible class="hidden lg:flex h-screen border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
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

            <x-desktop-user-menu :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile Top Bar (Clean & Compact) -->
        <header class="lg:hidden sticky top-0 z-30 flex items-center justify-between px-3 sm:px-4 py-2.5 bg-white/90 dark:bg-zinc-950/90 backdrop-blur-xl border-b border-zinc-200/80 dark:border-zinc-800/80">
            <div class="flex items-center gap-3">
                <button @click="mobileNavOpen = true" type="button" class="p-2 rounded-xl text-zinc-700 dark:text-zinc-300 hover:text-amber-500 dark:hover:text-amber-400 hover:bg-zinc-100 dark:hover:bg-zinc-900 active:scale-95 transition-all cursor-pointer">
                    <flux:icon name="bars-2" class="size-6" />
                </button>
                <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
            </div>
        </header>

        <!-- Glassmorphism Mobile Drawer Backdrop (z-998) -->
        <div x-show="mobileNavOpen" 
             @click="mobileNavOpen = false"
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/70 backdrop-blur-md z-[998] lg:hidden"
             style="display: none;"></div>

        <!-- Glassmorphism Mobile Sidebar Drawer Panel (z-999) -->
        <div x-show="mobileNavOpen"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="fixed inset-y-0 left-0 w-72 bg-white/95 dark:bg-zinc-950/95 backdrop-blur-2xl border-r border-zinc-200/80 dark:border-zinc-800/80 shadow-2xl z-[999] lg:hidden flex flex-col justify-between p-4 overflow-y-auto"
             style="display: none;">

            <div class="space-y-6">
                <!-- Drawer Header -->
                <div class="flex items-center justify-between pb-3 border-b border-zinc-200/80 dark:border-zinc-800/80">
                    <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
                    <button @click="mobileNavOpen = false" type="button" class="p-1.5 rounded-lg text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer">
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>

                <!-- Navigation Links -->
                <nav class="space-y-1">
                    <a href="{{ route('dashboard') }}" wire:navigate @click="mobileNavOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-amber-500/20 via-amber-500/10 to-transparent text-amber-600 dark:text-amber-400 font-semibold border-l-2 border-amber-500 shadow-2xs' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900 hover:text-zinc-900 dark:hover:text-white' }}">
                        <flux:icon name="home" class="size-5" />
                        <span>{{ __('Dashboard') }}</span>
                    </a>

                    <a href="{{ route('tracker') }}" wire:navigate @click="mobileNavOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('tracker') ? 'bg-gradient-to-r from-amber-500/20 via-amber-500/10 to-transparent text-amber-600 dark:text-amber-400 font-semibold border-l-2 border-amber-500 shadow-2xs' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900 hover:text-zinc-900 dark:hover:text-white' }}">
                        <flux:icon name="clock" class="size-5" />
                        <span>{{ __('Tracker') }}</span>
                    </a>

                    <a href="{{ route('manage') }}" wire:navigate @click="mobileNavOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('manage') ? 'bg-gradient-to-r from-amber-500/20 via-amber-500/10 to-transparent text-amber-600 dark:text-amber-400 font-semibold border-l-2 border-amber-500 shadow-2xs' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900 hover:text-zinc-900 dark:hover:text-white' }}">
                        <flux:icon name="cog-8-tooth" class="size-5" />
                        <span>{{ __('Manage') }}</span>
                    </a>

                    @if(auth()->check() && auth()->user()->is_admin)
                        @php $openAdminIssues = App\Models\Issue::where('status', 'open')->count(); @endphp
                        <a href="{{ route('issues') }}" wire:navigate @click="mobileNavOpen = false" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('issues') ? 'bg-gradient-to-r from-amber-500/20 via-amber-500/10 to-transparent text-amber-600 dark:text-amber-400 font-semibold border-l-2 border-amber-500 shadow-2xs' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900 hover:text-zinc-900 dark:hover:text-white' }}">
                            <div class="flex items-center gap-3">
                                <flux:icon name="flag" class="size-5" />
                                <span>{{ __('Issues') }}</span>
                            </div>
                            @if($openAdminIssues)
                                <span class="px-2 py-0.5 text-xs font-bold bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded-full border border-amber-500/30">{{ $openAdminIssues }}</span>
                            @endif
                        </a>

                        <a href="{{ route('members') }}" wire:navigate @click="mobileNavOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('members') ? 'bg-gradient-to-r from-amber-500/20 via-amber-500/10 to-transparent text-amber-600 dark:text-amber-400 font-semibold border-l-2 border-amber-500 shadow-2xs' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900 hover:text-zinc-900 dark:hover:text-white' }}">
                            <flux:icon name="users" class="size-5" />
                            <span>{{ __('Members') }}</span>
                        </a>

                        <a href="{{ route('broadcast') }}" wire:navigate @click="mobileNavOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('broadcast') ? 'bg-gradient-to-r from-amber-500/20 via-amber-500/10 to-transparent text-amber-600 dark:text-amber-400 font-semibold border-l-2 border-amber-500 shadow-2xs' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900 hover:text-zinc-900 dark:hover:text-white' }}">
                            <flux:icon name="megaphone" class="size-5" />
                            <span>{{ __('Broadcast') }}</span>
                        </a>
                    @endif
                </nav>
            </div>

            <!-- User Profile Footer -->
            <div class="pt-4 border-t border-zinc-200/80 dark:border-zinc-800/80">
                <x-desktop-user-menu :name="auth()->user()->name" />
            </div>
        </div>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist
        
        <!-- Floating Action Menu Top Right (Help, FAQ & Bug Report) -->
        @if(auth()->check())
        <div x-data="{ open: false }" @click.outside="open = false" class="fixed top-3.5 right-[3.4rem] sm:top-4 sm:right-[3.65rem] z-40">
            <!-- Menu Options (Floats below the button) -->
            <div x-show="open" 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
                 class="absolute top-12 right-0 mt-2 w-52 rounded-xl border border-zinc-200/80 dark:border-zinc-800 bg-white/95 dark:bg-zinc-900/95 backdrop-blur-xl shadow-lg py-1.5 z-50 origin-top-right overflow-hidden"
                 style="display: none;">
                
                <div class="px-3 py-1.5 text-[10px] font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Help & Support</div>
                
                <!-- Quick Setup -->
                <button @click="open = false; $dispatch('open-quick-setup')" class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:bg-zinc-100 dark:hover:bg-zinc-800/80 transition-colors duration-150 text-left cursor-pointer">
                    <flux:icon name="sparkles" class="size-4 text-indigo-500 dark:text-indigo-400 shrink-0" />
                    <span>Quick Setup</span>
                </button>

                <!-- Help -->
                <flux:modal.trigger name="help-modal">
                    <button @click="open = false" class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800/80 transition-colors duration-150 text-left cursor-pointer">
                        <flux:icon name="question-mark-circle" class="size-4 text-zinc-400 dark:text-zinc-400 shrink-0" />
                        <span>Help Center</span>
                    </button>
                </flux:modal.trigger>
                
                <!-- FAQ -->
                <flux:modal.trigger name="faq-modal">
                    <button @click="open = false" class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800/80 transition-colors duration-150 text-left cursor-pointer">
                        <flux:icon name="information-circle" class="size-4 text-zinc-400 dark:text-zinc-400 shrink-0" />
                        <span>FAQ</span>
                    </button>
                </flux:modal.trigger>

                <div class="my-1 border-t border-zinc-100 dark:border-zinc-800/80"></div>

                <flux:modal.trigger name="report-issue-modal">
                    <button @click="open = false" class="flex w-full items-center gap-2.5 px-3 py-2 text-xs font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800/80 transition-colors duration-150 text-left cursor-pointer">
                        <flux:icon name="bug-ant" class="size-4 text-zinc-400 dark:text-zinc-400 shrink-0" />
                        <span>Report Bug</span>
                    </button>
                </flux:modal.trigger>
            </div>

            <!-- Trigger Button (Sleek Glassmorphic Help Icon) -->
            <button @click="open = !open" 
                    type="button"
                    id="tour-help-button"
                    class="flex items-center justify-center size-9 rounded-xl bg-white/80 dark:bg-zinc-900/80 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800/80 text-zinc-700 dark:text-zinc-300 hover:text-indigo-500 dark:hover:text-indigo-400 shadow-xs transition-all duration-300 active:scale-95 cursor-pointer"
                    :class="open ? 'rotate-180 text-indigo-500 dark:text-indigo-400 border-indigo-500/30' : ''">
                <!-- Question mark icon -->
                <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                </svg>
                <!-- Close (X) icon when open -->
                <svg x-show="open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4.5" style="display: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        @endif
        
        <livewire:quick-setup />
        <livewire:report-issue />
        <livewire:notifications />
        @include('partials.help-modals')

        <!-- Global Floating Back to Top Button (Active exclusively in Desktop Mode) -->
        <div x-data="{ scrolled: false }" 
             @scroll.window="scrolled = (window.pageYOffset > 300)"
             x-cloak
             x-show="scrolled" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-6 scale-90"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-6 scale-90"
             class="hidden md:block fixed bottom-6 right-6 lg:bottom-8 lg:right-8 z-[9999] pointer-events-none"
             style="display: none;">
            
            <button 
                @click="window.scrollTo({top: 0, behavior: 'smooth'})"
                type="button"
                class="pointer-events-auto flex items-center gap-2 px-4 py-2.5 rounded-full bg-zinc-900/90 dark:bg-white/90 text-white dark:text-zinc-900 backdrop-blur-xl shadow-xl hover:shadow-2xl hover:scale-105 active:scale-95 transition-all duration-200 cursor-pointer border border-zinc-700/30 dark:border-zinc-200/30 text-xs font-semibold"
                title="Back to top"
            >
                <flux:icon name="arrow-up" class="size-4" />
                <span>Back to top</span>
            </button>
        </div>

        @fluxScripts
    </body>
</html>

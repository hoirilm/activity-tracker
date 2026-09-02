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

            /* Navigation Items - Ultra Minimalist (Vercel Style) */
            [data-flux-sidebar-item] {
                transition: all 160ms cubic-bezier(0.16, 1, 0.3, 1) !important;
                border-radius: 0.5rem !important; /* rounded-lg */
                font-size: 0.8125rem !important; /* 13px */
                font-weight: 450 !important;
                min-height: 2.125rem !important; /* 34px */
                padding-left: 0.625rem !important;
                padding-right: 0.625rem !important;
                margin-top: 1.5px !important;
                margin-bottom: 1.5px !important;
                letter-spacing: -0.01em !important;
                position: relative !important;
                border: 1px solid transparent !important;
            }

            /* Inactive Items (Clean, Muted) */
            html.dark [data-flux-sidebar-item]:not([data-current]) {
                color: #a1a1aa !important; /* zinc-400 */
                background: transparent !important;
            }
            html:not(.dark) [data-flux-sidebar-item]:not([data-current]) {
                color: #71717a !important; /* zinc-500 */
                background: transparent !important;
            }

            /* Inactive Item Hover (Soft Barely-There Glass Hover) */
            html.dark [data-flux-sidebar-item]:not([data-current]):hover {
                background-color: rgba(255, 255, 255, 0.04) !important;
                color: #fafafa !important;
            }
            html:not(.dark) [data-flux-sidebar-item]:not([data-current]):hover {
                background-color: rgba(0, 0, 0, 0.035) !important;
                color: #09090b !important;
            }

            /* Inactive Icons */
            html.dark [data-flux-sidebar-item]:not([data-current]) svg,
            html.dark [data-flux-sidebar-item]:not([data-current]) [data-slot="icon"] {
                color: #71717a !important; /* zinc-500 */
                transition: color 160ms ease !important;
            }
            html:not(.dark) [data-flux-sidebar-item]:not([data-current]) svg,
            html:not(.dark) [data-flux-sidebar-item]:not([data-current]) [data-slot="icon"] {
                color: #a1a1aa !important; /* zinc-400 */
                transition: color 160ms ease !important;
            }
            html.dark [data-flux-sidebar-item]:not([data-current]):hover svg,
            html.dark [data-flux-sidebar-item]:not([data-current]):hover [data-slot="icon"] {
                color: #f4f4f5 !important;
            }
            html:not(.dark) [data-flux-sidebar-item]:not([data-current]):hover svg,
            html:not(.dark) [data-flux-sidebar-item]:not([data-current]):hover [data-slot="icon"] {
                color: #18181b !important;
            }

            /* Active State - Ultra Minimalist: Crisp White Text, Clean Subtle Plate */
            html.dark [data-flux-sidebar-item][data-current] {
                background: rgba(255, 255, 255, 0.06) !important;
                color: #ffffff !important;
                font-weight: 600 !important;
                border: 1px solid rgba(255, 255, 255, 0.08) !important;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2) !important;
            }
            html:not(.dark) [data-flux-sidebar-item][data-current] {
                background: rgba(0, 0, 0, 0.04) !important;
                color: #09090b !important;
                font-weight: 600 !important;
                border: 1px solid rgba(0, 0, 0, 0.06) !important;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
            }

            /* Active Icon */
            html.dark [data-flux-sidebar-item][data-current] svg,
            html.dark [data-flux-sidebar-item][data-current] [data-slot="icon"] {
                color: #ffffff !important;
            }
            html:not(.dark) [data-flux-sidebar-item][data-current] svg,
            html:not(.dark) [data-flux-sidebar-item][data-current] [data-slot="icon"] {
                color: #09090b !important;
            }

            /* Glowing Amber Dot Indicator on Active Item (Vercel Style) */
            ui-sidebar:not([data-flux-sidebar-collapsed-desktop]) [data-flux-sidebar-item][data-current]:not(:has([data-flux-navlist-badge]))::after {
                content: '';
                position: absolute;
                right: 0.65rem;
                top: 50%;
                transform: translateY(-50%);
                width: 5.5px;
                height: 5.5px;
                border-radius: 9999px;
                background-color: #f59e0b;
                box-shadow: 0 0 8px rgba(245, 158, 11, 0.65), 0 0 2px rgba(245, 158, 11, 0.9);
            }

            /* When Collapsed: glowing dot appears bottom right of icon */
            ui-sidebar[data-flux-sidebar-collapsed-desktop] [data-flux-sidebar-item][data-current]::after {
                content: '';
                position: absolute;
                right: 0.5rem;
                bottom: 0.35rem;
                width: 5px;
                height: 5px;
                border-radius: 9999px;
                background-color: #f59e0b;
                box-shadow: 0 0 6px rgba(245, 158, 11, 0.7);
            }

            /* Badge (Issues Count) - Minimalist Monospace Capsule */
            html.dark [data-flux-sidebar-item] [data-flux-navlist-badge],
            html.dark [data-flux-navlist-badge] {
                background-color: rgba(255, 255, 255, 0.06) !important;
                color: #d4d4d8 !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
                border-radius: 9999px !important;
                font-size: 10px !important;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
                font-weight: 600 !important;
                padding: 0.5px 6px !important;
                line-height: 1.2 !important;
            }
            html:not(.dark) [data-flux-sidebar-item] [data-flux-navlist-badge],
            html:not(.dark) [data-flux-navlist-badge] {
                background-color: rgba(0, 0, 0, 0.05) !important;
                color: #52525b !important;
                border: 1px solid rgba(0, 0, 0, 0.08) !important;
                border-radius: 9999px !important;
                font-size: 10px !important;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
                font-weight: 600 !important;
                padding: 0.5px 6px !important;
                line-height: 1.2 !important;
            }

            /* Active item with badge: badge gains subtle amber glow */
            html.dark [data-flux-sidebar-item][data-current] [data-flux-navlist-badge] {
                background-color: rgba(245, 158, 11, 0.15) !important;
                color: #fbbf24 !important;
                border-color: rgba(245, 158, 11, 0.3) !important;
            }
            html:not(.dark) [data-flux-sidebar-item][data-current] [data-flux-navlist-badge] {
                background-color: rgba(245, 158, 11, 0.12) !important;
                color: #d97706 !important;
                border-color: rgba(245, 158, 11, 0.25) !important;
            }

            /* Dynamic User Menu: In-place Accordion vs Collapsed Flyout */
            .desktop-user-menu-flyout {
                display: none !important;
            }
            @media (min-width: 1024px) {
                ui-sidebar[data-flux-sidebar-collapsed-desktop] .desktop-user-menu-flyout {
                    display: block !important;
                }
                ui-sidebar[data-flux-sidebar-collapsed-desktop] .desktop-user-menu-expanded {
                    display: none !important;
                }
                ui-sidebar[data-flux-sidebar-collapsed-desktop] .desktop-user-menu-wrapper {
                    background: transparent !important;
                    border-color: transparent !important;
                    padding: 0 !important;
                    box-shadow: none !important;
                }
            }

            /* Ensure Livewire loading indicators are strictly hidden when not loading */
            [wire\:loading], [wire\:loading\.delay] {
                display: none;
            }
        </style>
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800" x-data="{ mobileNavOpen: false }" @close-mobile-nav.window="mobileNavOpen = false">
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
        <header class="lg:hidden sticky top-0 z-30 flex items-center justify-between px-3 sm:px-4 py-2.5 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                <button @click="mobileNavOpen = true" type="button" class="p-2 rounded-xl text-zinc-700 dark:text-zinc-300 hover:text-amber-500 dark:hover:text-amber-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 active:scale-95 transition-all cursor-pointer">
                    <flux:icon name="bars-2" class="size-6" />
                </button>
                <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
            </div>
        </header>

        <!-- Solid Mobile Drawer Backdrop (z-998) -->
        <div x-show="mobileNavOpen" 
             @click="mobileNavOpen = false"
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/60 z-[998] lg:hidden"
             style="display: none;"></div>

        <!-- Solid Mobile Sidebar Drawer Panel (z-999) -->
        <div x-show="mobileNavOpen"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="fixed inset-y-0 left-0 w-72 bg-white dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700 shadow-2xl z-[999] lg:hidden flex flex-col justify-between p-4 overflow-y-auto"
             style="display: none;">

            <div class="space-y-6">
                <!-- Drawer Header -->
                <div class="flex items-center justify-between pb-3 border-b border-zinc-200 dark:border-zinc-700">
                    <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
                    <button @click="mobileNavOpen = false" type="button" class="p-1.5 rounded-lg text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer">
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>

                <!-- Navigation Links -->
                <nav class="space-y-1">
                    <a href="{{ route('dashboard') }}" wire:navigate @click="mobileNavOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-amber-500/20 via-amber-500/10 to-transparent text-amber-600 dark:text-amber-400 font-semibold border-l-2 border-amber-500 shadow-2xs' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-white' }}">
                        <flux:icon name="home" class="size-5" />
                        <span>{{ __('Dashboard') }}</span>
                    </a>

                    <a href="{{ route('tracker') }}" wire:navigate @click="mobileNavOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('tracker') ? 'bg-gradient-to-r from-amber-500/20 via-amber-500/10 to-transparent text-amber-600 dark:text-amber-400 font-semibold border-l-2 border-amber-500 shadow-2xs' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-white' }}">
                        <flux:icon name="clock" class="size-5" />
                        <span>{{ __('Tracker') }}</span>
                    </a>

                    <a href="{{ route('manage') }}" wire:navigate @click="mobileNavOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('manage') ? 'bg-gradient-to-r from-amber-500/20 via-amber-500/10 to-transparent text-amber-600 dark:text-amber-400 font-semibold border-l-2 border-amber-500 shadow-2xs' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-white' }}">
                        <flux:icon name="cog-8-tooth" class="size-5" />
                        <span>{{ __('Manage') }}</span>
                    </a>

                    @if(auth()->check() && auth()->user()->is_admin)
                        @php $openAdminIssues = App\Models\Issue::where('status', 'open')->count(); @endphp
                        <a href="{{ route('issues') }}" wire:navigate @click="mobileNavOpen = false" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('issues') ? 'bg-gradient-to-r from-amber-500/20 via-amber-500/10 to-transparent text-amber-600 dark:text-amber-400 font-semibold border-l-2 border-amber-500 shadow-2xs' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-white' }}">
                            <div class="flex items-center gap-3">
                                <flux:icon name="flag" class="size-5" />
                                <span>{{ __('Issues') }}</span>
                            </div>
                            @if($openAdminIssues)
                                <span class="px-2 py-0.5 text-xs font-bold bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded-full border border-amber-500/30">{{ $openAdminIssues }}</span>
                            @endif
                        </a>

                        <a href="{{ route('members') }}" wire:navigate @click="mobileNavOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('members') ? 'bg-gradient-to-r from-amber-500/20 via-amber-500/10 to-transparent text-amber-600 dark:text-amber-400 font-semibold border-l-2 border-amber-500 shadow-2xs' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-white' }}">
                            <flux:icon name="users" class="size-5" />
                            <span>{{ __('Members') }}</span>
                        </a>

                        <a href="{{ route('broadcast') }}" wire:navigate @click="mobileNavOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('broadcast') ? 'bg-gradient-to-r from-amber-500/20 via-amber-500/10 to-transparent text-amber-600 dark:text-amber-400 font-semibold border-l-2 border-amber-500 shadow-2xs' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-white' }}">
                            <flux:icon name="megaphone" class="size-5" />
                            <span>{{ __('Broadcast') }}</span>
                        </a>
                    @endif
                </nav>
            </div>

            <!-- User Profile Footer -->
            <div class="pt-2">
                <x-desktop-user-menu :name="auth()->user()->name" />
            </div>
        </div>

        {{ $slot }}
        
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
                 class="absolute top-12 right-0 mt-2 w-52 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-lg py-1.5 z-50 origin-top-right overflow-hidden"
                 style="display: none;">
                
                <div class="px-3 py-1.5 text-[10px] font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Help & Support</div>
                
                <!-- Quick Setup -->
                <button @click="open = false; $dispatch('open-quick-setup')" class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors duration-150 text-left cursor-pointer">
                    <flux:icon name="sparkles" class="size-4 text-amber-500 dark:text-amber-400 shrink-0" />
                    <span>Quick Setup</span>
                </button>

                <!-- Help -->
                <flux:modal.trigger name="help-modal">
                    <button @click="open = false" class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors duration-150 text-left cursor-pointer">
                        <flux:icon name="question-mark-circle" class="size-4 text-zinc-400 dark:text-zinc-400 shrink-0" />
                        <span>Help Center</span>
                    </button>
                </flux:modal.trigger>
                
                <!-- FAQ -->
                <flux:modal.trigger name="faq-modal">
                    <button @click="open = false" class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors duration-150 text-left cursor-pointer">
                        <flux:icon name="information-circle" class="size-4 text-zinc-400 dark:text-zinc-400 shrink-0" />
                        <span>FAQ</span>
                    </button>
                </flux:modal.trigger>

                <div class="my-1 border-t border-zinc-100 dark:border-zinc-800"></div>

                <flux:modal.trigger name="report-issue-modal">
                    <button @click="open = false" class="flex w-full items-center gap-2.5 px-3 py-2 text-xs font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors duration-150 text-left cursor-pointer">
                        <flux:icon name="bug-ant" class="size-4 text-zinc-400 dark:text-zinc-400 shrink-0" />
                        <span>Report Bug</span>
                    </button>
                </flux:modal.trigger>
            </div>

            <!-- Trigger Button (Solid Help Icon) -->
            <button @click="open = !open" 
                    type="button"
                    id="tour-help-button"
                    class="flex items-center justify-center size-9 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:text-amber-500 dark:hover:text-amber-400 shadow-xs transition-all duration-300 active:scale-95 cursor-pointer"
                    :class="open ? 'rotate-180 text-amber-500 dark:text-amber-400 border-amber-500/30' : ''">
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
                class="pointer-events-auto flex items-center gap-2 px-4 py-2.5 rounded-full bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 shadow-xl hover:shadow-2xl hover:scale-105 active:scale-95 transition-all duration-200 cursor-pointer border border-zinc-700 dark:border-zinc-300 text-xs font-semibold"
                title="Back to top"
            >
                <flux:icon name="arrow-up" class="size-4" />
                <span>Back to top</span>
            </button>
        </div>

        @fluxScripts
    </body>
</html>

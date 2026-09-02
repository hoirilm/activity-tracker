<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        @include('partials.head')
        <!-- Google Fonts: Plus Jakarta Sans & JetBrains Mono for High-End SaaS UI/UX -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700;800&family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            }
            .font-mono {
                font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
            }
        </style>
    </head>
    <body class="bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-zinc-200 antialiased selection:bg-amber-500 selection:text-white overflow-x-hidden">
        <!-- Navigation Header -->
        <header class="sticky top-0 z-50 w-full border-b border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 transition-all duration-200">
            <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8 h-14 sm:h-16 flex items-center justify-between">
                <div class="flex items-center gap-2 sm:gap-3">
                    <x-app-logo-icon class="size-6 sm:size-8 text-amber-500" />
                    <span class="font-extrabold text-base sm:text-xl tracking-tight text-zinc-900 dark:text-white">
                        {{ config('app.name', 'Klakoan') }}
                    </span>
                </div>
                
                <nav class="hidden md:flex items-center gap-6">
                    <a href="#features" class="text-sm font-medium text-zinc-600 hover:text-amber-600 dark:text-zinc-400 dark:hover:text-amber-400 transition-colors">{{ __('welcome.nav_features') }}</a>
                    <a href="#workflow" class="text-sm font-medium text-zinc-600 hover:text-amber-600 dark:text-zinc-400 dark:hover:text-amber-400 transition-colors">{{ __('welcome.nav_workflow') }}</a>
                    <a href="#security" class="text-sm font-medium text-zinc-600 hover:text-amber-600 dark:text-zinc-400 dark:hover:text-amber-400 transition-colors">{{ __('welcome.nav_security') }}</a>
                </nav>

                <div class="flex items-center gap-1.5 sm:gap-3">
                    <!-- Language Toggle Switcher -->
                    <div class="flex items-center bg-zinc-100 dark:bg-zinc-900 rounded-lg sm:rounded-xl p-0.5 sm:p-1 border border-zinc-200/60 dark:border-zinc-800/60 text-[10px] sm:text-[11px] font-bold font-mono">
                        <a href="{{ route('language.switch', 'id') }}"
                           class="px-2 py-0.5 rounded-md sm:rounded-lg transition-all cursor-pointer {{ app()->getLocale() === 'id' ? 'bg-amber-500 text-white shadow-2xs' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                            ID
                        </a>
                        <a href="{{ route('language.switch', 'en') }}"
                           class="px-2 py-0.5 rounded-md sm:rounded-lg transition-all cursor-pointer {{ app()->getLocale() === 'en' ? 'bg-amber-500 text-white shadow-2xs' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                            EN
                        </a>
                    </div>

                    @if (Route::has('login'))
                        @auth
                            <a href="{{ route('dashboard') }}"
                               class="inline-flex items-center justify-center px-3 sm:px-4 py-1.5 sm:py-2 text-xs font-semibold text-white bg-amber-500 hover:bg-amber-600 rounded-lg sm:rounded-xl shadow-md shadow-amber-500/20 active:scale-95 transition-all whitespace-nowrap">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center justify-center px-2 sm:px-3 py-1.5 sm:py-2 text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:text-amber-600 dark:hover:text-amber-400 transition-colors whitespace-nowrap">
                                Log in
                            </a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="inline-flex items-center justify-center px-3 sm:px-4 py-1.5 sm:py-2 text-xs font-semibold text-white bg-amber-500 hover:bg-amber-600 rounded-lg sm:rounded-xl shadow-md shadow-amber-500/20 active:scale-95 transition-all whitespace-nowrap">
                                    {{ __('welcome.nav_register') }}
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </header>

        <!-- Hero Section with Motion Mouse Parallax & Interactive Mockup Tabs -->
        <section class="relative overflow-hidden pt-8 pb-14 sm:pt-16 sm:pb-20 lg:pt-28 lg:pb-32" id="hero-parallax-container">
            <!-- Background Ambient Glow & Mesh Elements -->
            <div class="parallax-element absolute -top-24 left-1/2 -translate-x-1/2 w-[800px] h-[500px] bg-gradient-to-tr from-amber-500/15 via-emerald-500/10 to-indigo-500/10 rounded-full blur-3xl pointer-events-none" data-speed="0.2"></div>
            <div class="parallax-element absolute top-1/3 -left-32 size-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none" data-speed="0.4"></div>
            <div class="parallax-element absolute bottom-10 right-0 size-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none" data-speed="0.3"></div>

            <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8 relative">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 sm:gap-12 lg:gap-8 items-center">
                    <!-- Hero Text -->
                    <div class="lg:col-span-6 text-center lg:text-left reveal">
                        <div class="inline-flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-full text-[11px] sm:text-xs font-semibold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/25 mb-3 sm:mb-4 shadow-2xs">
                            <span class="relative flex h-1.5 sm:h-2 w-1.5 sm:w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-1.5 sm:h-2 w-1.5 sm:w-2 bg-amber-500"></span>
                            </span>
                            <span>{{ __('welcome.hero_badge') }}</span>
                        </div>

                        <h1 class="text-2xl sm:text-4xl lg:text-6xl font-extrabold tracking-tight text-zinc-900 dark:text-white leading-[1.15] mb-3 sm:mb-4">
                            <span>{{ app()->getLocale() === 'id' ? 'Lacak Waktu Kerja' : 'Track Work Time' }}</span>
                            <span class="text-zinc-500 dark:text-zinc-400">{{ app()->getLocale() === 'id' ? ' Lebih Cerdas' : ' Smarter' }}</span>
                        </h1>

                        <!-- Feature Highlight Badge with Smooth Typewriter Animation -->
                        <div class="inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-1.5 sm:py-2 rounded-xl sm:rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/25 mb-4 sm:mb-6 shadow-sm max-w-full">
                            <flux:icon name="sparkles" class="size-3.5 sm:size-4 text-amber-500 shrink-0 animate-pulse" />
                            <span class="text-[11px] sm:text-sm font-mono font-bold tracking-wide">
                                <span>{{ app()->getLocale() === 'id' ? 'Tingkatkan Hasil dengan' : 'Boost Results with' }}</span>
                                <span class="text-amber-500 font-extrabold ml-1">
                                    <span id="hero-typewriter-text">Task Stream ⚡</span><span class="animate-pulse text-amber-500 font-normal">|</span>
                                </span>
                            </span>
                        </div>

                        <p class="text-xs sm:text-base text-zinc-600 dark:text-zinc-400 max-w-xl mx-auto lg:mx-0 mb-6 sm:mb-8 leading-relaxed font-normal">
                            {{ __('welcome.hero_desc') }}
                        </p>

                        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-2.5 sm:gap-3.5">
                            @auth
                                <a href="{{ route('dashboard') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-5 sm:px-6 py-3 sm:py-3.5 text-xs sm:text-sm font-bold text-white bg-amber-500 hover:bg-amber-600 rounded-xl sm:rounded-2xl shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 active:scale-95 transition-all">
                                    {{ __('welcome.hero_cta_dashboard') }}
                                </a>
                            @else
                                <a href="{{ route('register') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-5 sm:px-6 py-3 sm:py-3.5 text-xs sm:text-sm font-bold text-white bg-amber-500 hover:bg-amber-600 rounded-xl sm:rounded-2xl shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 active:scale-95 transition-all">
                                    {{ __('welcome.hero_cta_start') }}
                                </a>
                                <a href="#features" class="w-full sm:w-auto inline-flex items-center justify-center px-5 sm:px-6 py-3 sm:py-3.5 text-xs sm:text-sm font-bold text-zinc-700 dark:text-zinc-300 bg-zinc-200/60 hover:bg-zinc-200 dark:bg-zinc-800/60 dark:hover:bg-zinc-800 rounded-xl sm:rounded-2xl transition-all">
                                    {{ __('welcome.hero_cta_explore') }}
                                </a>
                            @endauth
                        </div>

                        <!-- Quick Info Stats -->
                        <div class="grid grid-cols-3 gap-2 sm:gap-3.5 mt-6 sm:mt-10 pt-6 sm:pt-8 border-t border-zinc-200 dark:border-zinc-800">
                            <!-- Stat Card 1 -->
                            <div class="p-2 sm:p-3 rounded-xl sm:rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 flex flex-col sm:flex-row items-center text-center sm:text-left gap-1.5 sm:gap-3 shadow-2xs hover:border-amber-500/40 transition-all group cursor-default">
                                <div class="size-7 sm:size-9 rounded-lg sm:rounded-xl bg-amber-500/10 text-amber-500 border border-amber-500/20 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                    <flux:icon name="bolt" class="size-3.5 sm:size-4 text-amber-500" />
                                </div>
                                <div class="min-w-0 w-full">
                                    <div class="text-[11px] sm:text-xs font-bold text-zinc-900 dark:text-white truncate">Task Stream</div>
                                    <div class="text-[9px] sm:text-[10px] text-zinc-500 dark:text-zinc-400 truncate">Quick Add</div>
                                </div>
                            </div>

                            <!-- Stat Card 2 -->
                            <div class="p-2 sm:p-3 rounded-xl sm:rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 flex flex-col sm:flex-row items-center text-center sm:text-left gap-1.5 sm:gap-3 shadow-2xs hover:border-emerald-500/40 transition-all group cursor-default">
                                <div class="size-7 sm:size-9 rounded-lg sm:rounded-xl bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                    <flux:icon name="queue-list" class="size-3.5 sm:size-4 text-emerald-500" />
                                </div>
                                <div class="min-w-0 w-full">
                                    <div class="text-[11px] sm:text-xs font-bold text-zinc-900 dark:text-white truncate">Kanban</div>
                                    <div class="text-[9px] sm:text-[10px] text-zinc-500 dark:text-zinc-400 truncate">4-Column</div>
                                </div>
                            </div>

                            <!-- Stat Card 3 -->
                            <div class="p-2 sm:p-3 rounded-xl sm:rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 flex flex-col sm:flex-row items-center text-center sm:text-left gap-1.5 sm:gap-3 shadow-2xs hover:border-sky-500/40 transition-all group cursor-default">
                                <div class="size-7 sm:size-9 rounded-lg sm:rounded-xl bg-sky-500/10 text-sky-500 border border-sky-500/20 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                    <flux:icon name="arrow-down-tray" class="size-3.5 sm:size-4 text-sky-500" />
                                </div>
                                <div class="min-w-0 w-full">
                                    <div class="text-[11px] sm:text-xs font-bold text-zinc-900 dark:text-white truncate">Backup</div>
                                    <div class="text-[9px] sm:text-[10px] text-zinc-500 dark:text-zinc-400 truncate">JSON Safe</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Interactive Dynamic 3D Mockup Container (x-data Alpine with Mouse Parallax) -->
                    <div class="lg:col-span-6 reveal" style="transition-delay: 200ms;" x-data="{ activeTab: 'stream', searchDemo: '', isChecked: false }">
                        <div id="mockup-3d-card" class="relative mx-auto max-w-[540px] lg:max-w-none transition-transform duration-200 ease-out">
                            <!-- Background Glow & Motion Badges -->
                            <div class="absolute -inset-3 bg-gradient-to-r from-amber-500/20 via-emerald-500/20 to-indigo-500/20 rounded-3xl blur-2xl dark:opacity-75"></div>
                            
                            <!-- Floating Badge 1 (Live Timer Pill) -->
                            <div class="hidden sm:flex absolute -top-5 -left-5 z-20 items-center gap-2 px-3 py-1.5 rounded-2xl bg-zinc-900 border border-emerald-500/40 text-emerald-400 shadow-xl animate-[bounce_4s_infinite]">
                                <span class="size-2 rounded-full bg-emerald-500 animate-ping"></span>
                                <span class="font-mono text-xs font-bold">00:42:18</span>
                                <span class="text-[10px] text-zinc-400 uppercase font-mono">Live Tracking</span>
                            </div>

                            <!-- Floating Badge 2 (Backup Safe Badge) -->
                            <div class="hidden sm:flex absolute -bottom-5 -right-5 z-20 items-center gap-2 px-3 py-1.5 rounded-2xl bg-zinc-900 border border-amber-500/40 text-amber-400 shadow-xl">
                                <flux:icon name="shield-check" class="size-4 text-amber-500" />
                                <span class="font-mono text-xs font-bold">100% JSON Safe</span>
                            </div>

                            <!-- App Mockup Card Structure -->
                            <div class="relative bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
                                <!-- Mockup Top Bar & Tab Switcher -->
                                <div class="px-4 py-3 bg-zinc-100/80 dark:bg-zinc-950/80 border-b border-zinc-200/80 dark:border-zinc-800 flex flex-col sm:flex-row items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <div class="flex gap-1.5">
                                            <span class="w-3 h-3 rounded-full bg-rose-400 block"></span>
                                            <span class="w-3 h-3 rounded-full bg-amber-400 block"></span>
                                            <span class="w-3 h-3 rounded-full bg-emerald-400 block"></span>
                                        </div>
                                        <span class="text-[11px] text-zinc-400 font-mono">klakoan.com/dashboard</span>
                                    </div>

                                    <!-- Interactive Preview Tab Selector -->
                                    <div class="flex items-center bg-zinc-200/70 dark:bg-zinc-800/70 p-0.5 rounded-lg text-[10px] font-mono font-bold">
                                        <button type="button" @click="activeTab = 'stream'" :class="{ 'bg-white dark:bg-zinc-900 text-amber-500 shadow-2xs': activeTab === 'stream', 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200': activeTab !== 'stream' }" class="px-2 py-0.5 rounded-md transition-all cursor-pointer">
                                            {{ __('welcome.hero_tab_stream') }}
                                        </button>
                                        <button type="button" @click="activeTab = 'kanban'" :class="{ 'bg-white dark:bg-zinc-900 text-amber-500 shadow-2xs': activeTab === 'kanban', 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200': activeTab !== 'kanban' }" class="px-2 py-0.5 rounded-md transition-all cursor-pointer">
                                            {{ __('welcome.hero_tab_kanban') }}
                                        </button>
                                        <button type="button" @click="activeTab = 'backup'" :class="{ 'bg-white dark:bg-zinc-900 text-amber-500 shadow-2xs': activeTab === 'backup', 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200': activeTab !== 'backup' }" class="px-2 py-0.5 rounded-md transition-all cursor-pointer">
                                            {{ __('welcome.hero_tab_backup') }}
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Mockup Content Views -->
                                <div class="p-5 space-y-4 min-h-[360px]">
                                    <!-- TAB 1: Task Stream Command Center Mockup -->
                                    <div x-show="activeTab === 'stream'" class="space-y-3.5 transition-all">
                                        <!-- Header & Progress Meter -->
                                        <div class="flex items-center justify-between pb-2 border-b border-zinc-200/60 dark:border-zinc-800/60">
                                            <div class="flex items-center gap-2">
                                                <div class="size-7 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-500">
                                                    <flux:icon name="bolt" class="size-3.5" />
                                                </div>
                                                <div>
                                                    <span class="text-xs font-mono font-bold uppercase tracking-wider text-zinc-900 dark:text-zinc-100">ON PROGRESS STREAM</span>
                                                    <div class="flex items-center gap-2 mt-0.5">
                                                        <div class="w-24 bg-zinc-200 dark:bg-zinc-800 h-1.5 rounded-full overflow-hidden">
                                                            <div class="bg-gradient-to-r from-amber-500 to-emerald-500 h-full rounded-full transition-all" :style="{ width: isChecked ? '67%' : '33%' }"></div>
                                                        </div>
                                                        <span class="text-[10px] font-mono text-zinc-400" x-text="isChecked ? '2/3 done (67%)' : '1/3 done (33%)'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-amber-500 text-white shadow-2xs cursor-pointer hover:bg-amber-600 transition-colors">+ Quick Add</span>
                                        </div>

                                        <!-- Interactive Search Input Bar -->
                                        <div class="relative">
                                            <input type="text" x-model="searchDemo" placeholder="Try typing 'qris' or 'design'..." class="w-full h-8 pl-7 pr-3 rounded-xl text-xs bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-amber-500 transition-all" />
                                            <flux:icon name="magnifying-glass" class="absolute left-2.5 top-1/2 -translate-y-1/2 size-3.5 text-zinc-400" />
                                        </div>

                                        <!-- Interactive Task Item Stream -->
                                        <div class="space-y-2">
                                            <!-- Task Item 1 -->
                                            <div x-show="!searchDemo || 'develop qris tuntas transfer'.includes(searchDemo.toLowerCase())" class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-zinc-800 flex items-center justify-between gap-2 hover:border-amber-500/50 transition-all">
                                                <div class="flex items-center gap-2.5 min-w-0">
                                                    <button type="button" @click="isChecked = !isChecked" class="size-4.5 rounded-md border flex items-center justify-center cursor-pointer transition-all" :class="isChecked ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-zinc-300 dark:border-zinc-700 hover:border-amber-500'">
                                                        <flux:icon name="check" x-show="isChecked" class="size-3 stroke-[3]" />
                                                    </button>
                                                    <span class="text-xs font-semibold text-zinc-900 dark:text-zinc-100 truncate" :class="{ 'line-through text-zinc-400': isChecked }">Develop QRIS Tuntas Transfer</span>
                                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-mono bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">BAG</span>
                                                </div>
                                                <span class="text-[10px] font-mono text-zinc-400">2h ago</span>
                                            </div>

                                            <!-- Task Item 2 -->
                                            <div x-show="!searchDemo || 'refactor authentication passkey'.includes(searchDemo.toLowerCase())" class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-zinc-800 flex items-center justify-between gap-2 hover:border-amber-500/50 transition-all">
                                                <div class="flex items-center gap-2.5 min-w-0">
                                                    <div class="size-4.5 rounded-md border border-zinc-300 dark:border-zinc-700 flex items-center justify-center"></div>
                                                    <span class="text-xs font-semibold text-zinc-900 dark:text-zinc-100 truncate">Refactor Authentication Passkey</span>
                                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-mono bg-sky-500/10 text-sky-600 dark:text-sky-400 border border-sky-500/20">Security</span>
                                                </div>
                                                <span class="text-[10px] font-mono text-zinc-400">5h ago</span>
                                            </div>
                                        </div>

                                        <!-- Live Activity Tracking Bar -->
                                        <div class="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/25 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <span class="size-2 rounded-full bg-emerald-500 animate-ping"></span>
                                                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 font-mono">ACTIVE TRACKING</span>
                                            </div>
                                            <div class="flex items-center gap-2 font-mono text-xs font-extrabold text-emerald-500">
                                                <flux:icon name="clock" class="size-3.5 animate-spin" />
                                                <span>00:42:18</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TAB 2: Workspace Kanban Studio Mockup -->
                                    <div x-show="activeTab === 'kanban'" class="space-y-3 transition-all" x-cloak>
                                        <div class="flex items-center justify-between pb-2 border-b border-zinc-200/60 dark:border-zinc-800/60">
                                            <span class="text-xs font-mono font-bold uppercase tracking-wider text-zinc-900 dark:text-zinc-100">4-COLUMN KANBAN STUDIO</span>
                                            <span class="text-[10px] font-mono text-amber-500">Manage Workspace</span>
                                        </div>

                                        <div class="grid grid-cols-4 gap-2">
                                            <!-- Col 1: On Hold -->
                                            <div class="p-2 rounded-xl bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 text-center space-y-1.5">
                                                <div class="text-[10px] font-bold text-rose-500 uppercase font-mono">On Hold</div>
                                                <div class="p-1.5 rounded-lg bg-white dark:bg-zinc-900 text-[10px] font-medium text-zinc-700 dark:text-zinc-300 shadow-2xs border border-zinc-200 dark:border-zinc-800">
                                                    Fix Bug #104
                                                </div>
                                            </div>
                                            <!-- Col 2: New -->
                                            <div class="p-2 rounded-xl bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 text-center space-y-1.5">
                                                <div class="text-[10px] font-bold text-sky-500 uppercase font-mono">New</div>
                                                <div class="p-1.5 rounded-lg bg-white dark:bg-zinc-900 text-[10px] font-medium text-zinc-700 dark:text-zinc-300 shadow-2xs border border-zinc-200 dark:border-zinc-800">
                                                    Header UI
                                                </div>
                                            </div>
                                            <!-- Col 3: On Progress -->
                                            <div class="p-2 rounded-xl bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 text-center space-y-1.5">
                                                <div class="text-[10px] font-bold text-amber-500 uppercase font-mono">On Progress</div>
                                                <div class="p-1.5 rounded-lg bg-amber-500/10 text-[10px] font-medium text-amber-700 dark:text-amber-300 border border-amber-500/30">
                                                    QRIS Transfer
                                                </div>
                                            </div>
                                            <!-- Col 4: Done -->
                                            <div class="p-2 rounded-xl bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 text-center space-y-1.5">
                                                <div class="text-[10px] font-bold text-emerald-500 uppercase font-mono">Done</div>
                                                <div class="p-1.5 rounded-lg bg-white dark:bg-zinc-900 text-[10px] font-medium text-zinc-400 line-through border border-zinc-200 dark:border-zinc-800">
                                                    Design Mock
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TAB 3: Data Backup & Restore Manager Mockup -->
                                    <div x-show="activeTab === 'backup'" class="space-y-3 transition-all" x-cloak>
                                        <div class="flex items-center justify-between pb-2 border-b border-zinc-200/60 dark:border-zinc-800/60">
                                            <span class="text-xs font-mono font-bold uppercase tracking-wider text-zinc-900 dark:text-zinc-100">BACKUP & RESTORE MANAGER</span>
                                            <span class="text-[10px] font-mono text-emerald-500">100% JSON Safe</span>
                                        </div>

                                        <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 space-y-2">
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">klakoan-backup-2026.json</span>
                                                <span class="px-2 py-0.5 rounded text-[9px] font-mono bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">Verified</span>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2 text-center text-[10px]">
                                                <div class="p-2 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-700 dark:text-amber-300 font-bold">
                                                    Mode: Merge (Recommended)
                                                </div>
                                                <div class="p-2 rounded-lg bg-zinc-200 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 font-bold">
                                                    Mode: Replace
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Grid Section Showcase -->
        <section id="features" class="py-20 bg-zinc-100/70 dark:bg-zinc-900/40 border-y border-zinc-200/80 dark:border-zinc-800/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16 reveal">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 mb-3">
                        <flux:icon name="sparkles" class="size-3.5" />
                        <span>State-of-the-Art Architecture</span>
                    </div>
                    <h2 class="text-3xl font-extrabold text-zinc-900 dark:text-white sm:text-4xl">
                        {{ __('welcome.features_title') }}
                    </h2>
                    <p class="mt-4 text-base sm:text-lg text-zinc-600 dark:text-zinc-400">
                        {{ __('welcome.features_desc') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8 reveal">
                    <!-- Feature 1: Task Stream & Quick Add -->
                    <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 hover:border-amber-500/50 transition-all hover:-translate-y-2 hover:shadow-xl hover:shadow-amber-500/10 duration-300 group">
                        <div class="size-12 rounded-2xl bg-amber-500/10 text-amber-500 border border-amber-500/20 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                            <flux:icon name="bolt" class="size-6 text-amber-500" />
                        </div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2">{{ __('welcome.feature1_title') }}</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed">
                            {{ __('welcome.feature1_desc') }}
                        </p>
                    </div>

                    <!-- Feature 2: Workspace Kanban Studio -->
                    <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 hover:border-emerald-500/50 transition-all hover:-translate-y-2 hover:shadow-xl hover:shadow-emerald-500/10 duration-300 group">
                        <div class="size-12 rounded-2xl bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                            <flux:icon name="queue-list" class="size-6 text-emerald-500" />
                        </div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2">{{ __('welcome.feature2_title') }}</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed">
                            {{ __('welcome.feature2_desc') }}
                        </p>
                    </div>

                    <!-- Feature 3: Data Backup & Restore -->
                    <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 hover:border-sky-500/50 transition-all hover:-translate-y-2 hover:shadow-xl hover:shadow-sky-500/10 duration-300 group">
                        <div class="size-12 rounded-2xl bg-sky-500/10 text-sky-500 border border-sky-500/20 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                            <flux:icon name="arrow-down-tray" class="size-6 text-sky-500" />
                        </div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2">{{ __('welcome.feature3_title') }}</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed">
                            {{ __('welcome.feature3_desc') }}
                        </p>
                    </div>

                    <!-- Feature 4: Passkeys & Google SSO -->
                    <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 hover:border-purple-500/50 transition-all hover:-translate-y-2 hover:shadow-xl hover:shadow-purple-500/10 duration-300 group">
                        <div class="size-12 rounded-2xl bg-purple-500/10 text-purple-500 border border-purple-500/20 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                            <flux:icon name="finger-print" class="size-6 text-purple-500" />
                        </div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2">{{ __('welcome.feature4_title') }}</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed">
                            {{ __('welcome.feature4_desc') }}
                        </p>
                    </div>

                    <!-- Feature 5: Real-Time & Parallel Timers -->
                    <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 hover:border-rose-500/50 transition-all hover:-translate-y-2 hover:shadow-xl hover:shadow-rose-500/10 duration-300 group">
                        <div class="size-12 rounded-2xl bg-rose-500/10 text-rose-500 border border-rose-500/20 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                            <flux:icon name="clock" class="size-6 text-rose-500" />
                        </div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2">{{ __('welcome.feature5_title') }}</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed">
                            {{ __('welcome.feature5_desc') }}
                        </p>
                    </div>

                    <!-- Feature 6: Excel Export & Analytics -->
                    <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 hover:border-indigo-500/50 transition-all hover:-translate-y-2 hover:shadow-xl hover:shadow-indigo-500/10 duration-300 group">
                        <div class="size-12 rounded-2xl bg-indigo-500/10 text-indigo-500 border border-indigo-500/20 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                            <flux:icon name="chart-bar" class="size-6 text-indigo-500" />
                        </div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2">{{ __('welcome.feature6_title') }}</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed">
                            {{ __('welcome.feature6_desc') }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Workflow Step-by-Step Section -->
        <section id="workflow" class="py-20 bg-white dark:bg-zinc-950">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16 reveal">
                    <h2 class="text-3xl font-extrabold text-zinc-900 dark:text-white sm:text-4xl">
                        {{ __('welcome.workflow_title') }}
                    </h2>
                    <p class="mt-4 text-base sm:text-lg text-zinc-600 dark:text-zinc-400">
                        {{ __('welcome.workflow_desc') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-10 relative reveal">
                    <!-- Step 1 -->
                    <div class="text-center space-y-4 group cursor-default p-6 rounded-2xl bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200/70 dark:border-zinc-800 hover:border-amber-500/50 transition-all">
                        <div class="w-14 h-14 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-xl font-bold font-mono mx-auto shadow-lg shadow-amber-500/30 group-hover:scale-110 group-hover:rotate-6 transition-transform duration-300">
                            01
                        </div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white">{{ __('welcome.step1_title') }}</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 text-xs leading-relaxed max-w-xs mx-auto">
                            {{ __('welcome.step1_desc') }}
                        </p>
                    </div>

                    <!-- Step 2 -->
                    <div class="text-center space-y-4 group cursor-default p-6 rounded-2xl bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200/70 dark:border-zinc-800 hover:border-amber-500/50 transition-all">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-500 text-white flex items-center justify-center text-xl font-bold font-mono mx-auto shadow-lg shadow-emerald-500/30 group-hover:scale-110 group-hover:rotate-6 transition-transform duration-300">
                            02
                        </div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white">{{ __('welcome.step2_title') }}</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 text-xs leading-relaxed max-w-xs mx-auto">
                            {{ __('welcome.step2_desc') }}
                        </p>
                    </div>

                    <!-- Step 3 -->
                    <div class="text-center space-y-4 group cursor-default p-6 rounded-2xl bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200/70 dark:border-zinc-800 hover:border-amber-500/50 transition-all">
                        <div class="w-14 h-14 rounded-2xl bg-indigo-500 text-white flex items-center justify-center text-xl font-bold font-mono mx-auto shadow-lg shadow-indigo-500/30 group-hover:scale-110 group-hover:rotate-6 transition-transform duration-300">
                            03
                        </div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white">{{ __('welcome.step3_title') }}</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 text-xs leading-relaxed max-w-xs mx-auto">
                            {{ __('welcome.step3_desc') }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Security / Passkeys Section -->
        <section id="security" class="py-20 bg-zinc-100/70 dark:bg-zinc-900/40 border-t border-zinc-200/80 dark:border-zinc-800/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center reveal">
                    <!-- Security Visual Box -->
                    <div class="order-2 lg:order-1">
                        <div class="bg-gradient-to-tr from-zinc-900 via-amber-950 to-zinc-900 p-8 rounded-3xl shadow-2xl border border-amber-500/20 flex items-center justify-center relative overflow-hidden">
                            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-amber-500/10 via-transparent to-transparent"></div>
                            <div class="bg-zinc-900 border border-zinc-700 p-6 rounded-2xl text-white text-center max-w-sm space-y-4 relative z-10">
                                <flux:icon name="shield-check" class="size-16 mx-auto text-amber-400" />
                                <h4 class="text-lg font-bold">{{ __('welcome.security_mockup_title') }}</h4>
                                <p class="text-xs text-white/80 leading-relaxed">{{ __('welcome.security_mockup_desc') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Security Text Content -->
                    <div class="order-1 lg:order-2 space-y-6 text-center lg:text-left">
                        <div class="inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                            {{ __('welcome.security_badge') }}
                        </div>
                        <h2 class="text-3xl font-extrabold text-zinc-900 dark:text-white sm:text-4xl">
                            {{ __('welcome.security_title') }}
                        </h2>
                        <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed text-sm sm:text-base">
                            {{ __('welcome.security_desc') }}
                        </p>
                        
                        <ul class="space-y-3.5 text-sm text-zinc-600 dark:text-zinc-400 text-left">
                            <li class="flex items-start gap-3">
                                <flux:icon name="check-circle" class="size-5 text-amber-500 shrink-0 mt-0.5" />
                                <span><strong>{{ __('welcome.security_passkey_title') }}</strong> {{ __('welcome.security_passkey_desc') }}</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <flux:icon name="check-circle" class="size-5 text-amber-500 shrink-0 mt-0.5" />
                                <span><strong>{{ __('welcome.security_2fa_title') }}</strong> {{ __('welcome.security_2fa_desc') }}</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <flux:icon name="check-circle" class="size-5 text-amber-500 shrink-0 mt-0.5" />
                                <span><strong>{{ __('welcome.security_enc_title') }}</strong> {{ __('welcome.security_enc_desc') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Bottom Banner -->
        <section class="py-20 bg-zinc-900 text-white relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-amber-500/10 via-transparent to-emerald-500/10"></div>
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-8 reveal relative z-10">
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight">
                    {{ __('welcome.cta_title') }}
                </h2>
                <p class="text-zinc-400 max-w-2xl mx-auto text-base sm:text-lg leading-relaxed">
                    {{ __('welcome.cta_desc') }}
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 text-sm font-bold text-zinc-900 bg-amber-500 hover:bg-amber-400 rounded-2xl shadow-lg hover:scale-105 active:scale-95 transition-all duration-300">
                            {{ __('welcome.cta_go_dashboard') }}
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 text-sm font-bold text-zinc-900 bg-amber-500 hover:bg-amber-400 rounded-2xl shadow-lg hover:scale-105 active:scale-95 transition-all duration-300">
                            {{ __('welcome.cta_start_free') }}
                        </a>
                        <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 text-sm font-bold text-white border border-zinc-700 hover:bg-zinc-800 rounded-2xl transition-all">
                            {{ __('welcome.cta_login') }}
                        </a>
                    @endauth
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-12 bg-zinc-950 text-zinc-400 border-t border-zinc-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-3">
                    <x-app-logo-icon class="size-6 text-amber-500" />
                    <span class="font-bold text-lg text-white tracking-tight">
                        {{ config('app.name', 'Klakoan') }}
                    </span>
                </div>
                
                <p class="text-xs text-zinc-500 font-mono">
                    &copy; {{ date('Y') }} {{ config('app.name', 'Klakoan') }}. {{ __('welcome.footer_rights') }}
                </p>
            </div>
        </footer>

        <!-- Custom Motion Styles & Motion Parallax Scripts -->
        <style>
            .reveal {
                opacity: 0;
                transform: translateY(30px);
                transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .reveal.active {
                opacity: 1;
                transform: translateY(0);
            }
            [x-cloak] { display: none !important; }
        </style>

        <script>
            document.addEventListener("DOMContentLoaded", () => {
                // 1. Scroll-driven reveal Observer
                const revealObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add("active");
                        }
                    });
                }, { threshold: 0.1 });
                document.querySelectorAll(".reveal").forEach(el => revealObserver.observe(el));

                // 2. Mouse Parallax Motion on Hero Mockup Card
                const container = document.getElementById("hero-parallax-container");
                const mockupCard = document.getElementById("mockup-3d-card");
                const parallaxEls = document.querySelectorAll(".parallax-element");

                if (container && mockupCard) {
                    container.addEventListener("mousemove", (e) => {
                        const rect = container.getBoundingClientRect();
                        const x = e.clientX - rect.left - rect.width / 2;
                        const y = e.clientY - rect.top - rect.height / 2;

                        // Subtle 3D tilt transform
                        const rotateX = (-y / rect.height) * 10;
                        const rotateY = (x / rect.width) * 10;
                        mockupCard.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;

                        // Scroll/Mouse Background Parallax Shift
                        parallaxEls.forEach(el => {
                            const speed = parseFloat(el.getAttribute("data-speed") || "0.2");
                            const moveX = (x * speed * 0.1);
                            const moveY = (y * speed * 0.1);
                            el.style.transform = `translate(${moveX}px, ${moveY}px)`;
                        });
                    });

                    container.addEventListener("mouseleave", () => {
                        mockupCard.style.transform = "perspective(1000px) rotateX(0deg) rotateY(0deg)";
                        parallaxEls.forEach(el => el.style.transform = "translate(0px, 0px)");
                    });
                }

                // 3. High-Performance Typewriter Loop
                const typeTextEl = document.getElementById("hero-typewriter-text");
                if (typeTextEl) {
                    const phrases = @js(app()->getLocale() === 'id' ? [
                        'Task Stream ⚡',
                        'Kanban Studio 📋',
                        'JSON Safe Backup 📦',
                        'Passkeys & Google 🔑',
                        'Laporan Excel 📊'
                    ] : [
                        'Task Stream ⚡',
                        'Kanban Studio 📋',
                        'JSON Safe Backup 📦',
                        'Passkeys & Google 🔑',
                        'Excel Export 📊'
                    ]);

                    let phraseIdx = 0;
                    let charIdx = phrases[0].length;
                    let isDeleting = false;

                    function typeLoop() {
                        const currentPhrase = phrases[phraseIdx];

                        if (!isDeleting) {
                            charIdx++;
                            typeTextEl.textContent = currentPhrase.substring(0, charIdx);

                            if (charIdx >= currentPhrase.length) {
                                isDeleting = true;
                                setTimeout(typeLoop, 2000);
                                return;
                            }
                        } else {
                            charIdx--;
                            typeTextEl.textContent = currentPhrase.substring(0, charIdx);

                            if (charIdx <= 0) {
                                isDeleting = false;
                                phraseIdx = (phraseIdx + 1) % phrases.length;
                            }
                        }

                        const speed = isDeleting ? 35 : 75;
                        setTimeout(typeLoop, speed);
                    }

                    setTimeout(typeLoop, 1500);
                }
            });
        </script>
    </body>
</html>

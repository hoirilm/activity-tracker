<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        @include('partials.head')
    </head>
    <body class="bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-zinc-200 antialiased selection:bg-orange-500 selection:text-white">
        <!-- Navigation -->
        <header class="sticky top-0 z-50 w-full border-b border-zinc-200/80 dark:border-zinc-800/80 bg-white/80 dark:bg-zinc-950/80 backdrop-blur-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <x-app-logo-icon class="size-8 text-orange-600 dark:text-orange-500" />
                    <span class="font-bold text-xl tracking-tight text-zinc-900 dark:text-white">
                        {{ config('app.name', 'Activity Tracker') }}
                    </span>
                </div>
                
                <nav class="hidden md:flex items-center gap-6">
                    <a href="#features" class="text-sm font-medium text-zinc-600 hover:text-orange-600 dark:text-zinc-400 dark:hover:text-orange-500 transition-colors">{{ __('welcome.nav_features') }}</a>
                    <a href="#workflow" class="text-sm font-medium text-zinc-600 hover:text-orange-600 dark:text-zinc-400 dark:hover:text-orange-500 transition-colors">{{ __('welcome.nav_workflow') }}</a>
                    <a href="#security" class="text-sm font-medium text-zinc-600 hover:text-orange-600 dark:text-zinc-400 dark:hover:text-orange-500 transition-colors">{{ __('welcome.nav_security') }}</a>
                </nav>

                <div class="flex items-center gap-3">
                    <!-- Language Toggle -->
                    <div class="flex items-center bg-zinc-100 dark:bg-zinc-800 rounded-lg p-0.5 text-[11px] font-bold uppercase tracking-wider">
                        <a href="{{ route('language.switch', 'id') }}"
                           class="px-2.5 py-1 rounded-md transition-colors cursor-pointer {{ app()->getLocale() === 'id' ? 'bg-white dark:bg-zinc-900 shadow-xs text-zinc-900 dark:text-zinc-100' : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200' }}">
                            ID
                        </a>
                        <a href="{{ route('language.switch', 'en') }}"
                           class="px-2.5 py-1 rounded-md transition-colors cursor-pointer {{ app()->getLocale() === 'en' ? 'bg-white dark:bg-zinc-900 shadow-xs text-zinc-900 dark:text-zinc-100' : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200' }}">
                            EN
                        </a>
                    </div>

                    @if (Route::has('login'))
                        @auth
                            <a
                                href="{{ route('dashboard') }}"
                                class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-500 dark:bg-orange-500 dark:hover:bg-orange-400 rounded-lg shadow-sm transition-all"
                            >
                                Dashboard
                            </a>
                        @else
                            <a
                                href="{{ route('login') }}"
                                class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:text-orange-600 dark:hover:text-orange-500 transition-colors"
                            >
                                Log in
                            </a>

                            @if (Route::has('register'))
                                <a
                                    href="{{ route('register') }}"
                                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-500 dark:bg-orange-500 dark:hover:bg-orange-400 rounded-lg shadow-sm transition-all"
                                >
                                    {{ __('welcome.nav_register') }}
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative overflow-hidden pt-20 pb-16 lg:pt-32 lg:pb-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
                    <!-- Hero Text -->
                    <div class="lg:col-span-6 text-center lg:text-left">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-orange-500/10 text-orange-600 dark:text-orange-400 border border-orange-500/20 mb-6">
                            <span class="flex h-2 w-2 rounded-full bg-orange-500 animate-pulse"></span>
                            {{ __('welcome.hero_badge') }}
                        </div>
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-zinc-900 dark:text-white leading-[1.1] mb-6">
                            {!! __('welcome.hero_title') !!}
                        </h1>
                        <p class="text-lg text-zinc-600 dark:text-zinc-400 max-w-xl mx-auto lg:mx-0 mb-8 leading-relaxed">
                            {{ __('welcome.hero_desc') }}
                        </p>
                        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                            @auth
                                <a href="{{ route('dashboard') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 text-base font-semibold text-white bg-orange-600 hover:bg-orange-500 dark:bg-orange-500 dark:hover:bg-orange-400 rounded-xl shadow-lg shadow-orange-500/20 hover:shadow-orange-500/30 transition-all">
                                    {{ __('welcome.hero_cta_dashboard') }}
                                </a>
                            @else
                                <a href="{{ route('register') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 text-base font-semibold text-white bg-orange-600 hover:bg-orange-500 dark:bg-orange-500 dark:hover:bg-orange-400 rounded-xl shadow-lg shadow-orange-500/20 hover:shadow-orange-500/30 transition-all">
                                    {{ __('welcome.hero_cta_start') }}
                                </a>
                                <a href="#features" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 text-base font-semibold text-zinc-700 dark:text-zinc-300 bg-zinc-200/50 hover:bg-zinc-200 dark:bg-zinc-800/50 dark:hover:bg-zinc-800 rounded-xl transition-all">
                                    {{ __('welcome.hero_cta_explore') }}
                                </a>
                            @endauth
                        </div>
                        
                        <!-- Quick Info Stats -->
                        <div class="grid grid-cols-3 gap-6 mt-12 pt-8 border-t border-zinc-200 dark:border-zinc-800">
                            <div>
                                <div class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-white">Real-Time</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ __('welcome.stat_realtime') }}</div>
                            </div>
                            <div>
                                <div class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-white">Paralel</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ __('welcome.stat_parallel') }}</div>
                            </div>
                            <div>
                                <div class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-white">Excel</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ __('welcome.stat_excel') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Interactive Dashboard Preview (Mockup) -->
                    <div class="lg:col-span-6">
                        <div class="relative mx-auto max-w-[500px] lg:max-w-none">
                            <!-- Background glow -->
                            <div class="absolute -inset-4 bg-orange-500/10 rounded-3xl blur-2xl dark:bg-orange-500/5"></div>
                            
                            <!-- App Mockup Container -->
                            <div class="relative bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
                                <!-- Title Bar -->
                                <div class="px-4 py-3 bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800 flex items-center gap-2">
                                    <div class="flex gap-1.5">
                                        <span class="w-3 h-3 rounded-full bg-red-400 block"></span>
                                        <span class="w-3 h-3 rounded-full bg-yellow-400 block"></span>
                                        <span class="w-3 h-3 rounded-full bg-green-400 block"></span>
                                    </div>
                                    <div class="text-xs text-zinc-400 mx-auto font-mono">activity-tracker.test/tracker</div>
                                </div>
                                
                                <!-- Mockup Content -->
                                <div class="p-6 space-y-6">
                                    <!-- Running Timer Card -->
                                    <div class="p-4 rounded-xl border border-orange-200 bg-orange-50/50 dark:border-orange-950/50 dark:bg-orange-950/20 flex items-center justify-between">
                                        <div class="space-y-1">
                                            <span class="text-xs font-semibold text-orange-600 dark:text-orange-400 uppercase tracking-wider">{{ __('welcome.mockup_running_label') }}</span>
                                            <h4 class="font-bold text-zinc-900 dark:text-white text-sm sm:text-base">{{ __('welcome.mockup_running_title') }}</h4>
                                            <div class="flex gap-2">
                                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] bg-zinc-200 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 font-medium">{{ __('welcome.mockup_running_project') }}</span>
                                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] bg-orange-100 dark:bg-orange-900/50 text-orange-600 dark:text-orange-400 font-medium">{{ __('welcome.mockup_running_cat') }}</span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-mono text-2xl font-bold text-orange-600 dark:text-orange-500 animate-pulse">01:42:35</div>
                                            <button class="mt-2 px-3 py-1 rounded bg-red-600 text-white text-xs font-semibold hover:bg-red-500 transition-colors">
                                                {{ __('welcome.mockup_stop') }}
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Stats Grid -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-1">
                                            <span class="text-xs text-zinc-500">{{ __('welcome.mockup_duration_today') }}</span>
                                            <div class="text-lg font-bold text-zinc-950 dark:text-white">05h 12m</div>
                                        </div>
                                        <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-1">
                                            <span class="text-xs text-zinc-500">{{ __('welcome.mockup_total_projects') }}</span>
                                            <div class="text-lg font-bold text-zinc-950 dark:text-white">8</div>
                                        </div>
                                    </div>

                                    <!-- Recent List -->
                                    <div class="space-y-3">
                                        <h5 class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">{{ __('welcome.mockup_recent_label') }}</h5>
                                        
                                        <div class="flex items-center justify-between py-2 border-b border-zinc-100 dark:border-zinc-800/50">
                                            <div>
                                                <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">{{ __('welcome.mockup_activity_1') }}</p>
                                                <span class="text-[10px] text-zinc-400">{{ __('welcome.mockup_category_1') }}</span>
                                            </div>
                                            <div class="text-right">
                                                <span class="font-mono text-xs text-zinc-500">02h 15m</span>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between py-2">
                                            <div>
                                                <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">{{ __('welcome.mockup_activity_2') }}</p>
                                                <span class="text-[10px] text-zinc-400">{{ __('welcome.mockup_category_2') }}</span>
                                            </div>
                                            <div class="text-right">
                                                <span class="font-mono text-xs text-zinc-500">01h 00m</span>
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

        <!-- Features Section -->
        <section id="features" class="py-20 bg-zinc-100 dark:bg-zinc-900/40 border-y border-zinc-200 dark:border-zinc-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-3xl font-extrabold text-zinc-900 dark:text-white sm:text-4xl">
                        {{ __('welcome.features_title') }}
                    </h2>
                    <p class="mt-4 text-lg text-zinc-600 dark:text-zinc-450">
                        {{ __('welcome.features_desc') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Feature 1 -->
                    <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800/80 hover:border-orange-500/50 dark:hover:border-orange-500/50 transition-all hover:-translate-y-1">
                        <div class="w-12 h-12 rounded-xl bg-orange-500/10 text-orange-600 dark:text-orange-400 flex items-center justify-center mb-6">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2">{{ __('welcome.feature1_title') }}</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed">
                            {{ __('welcome.feature1_desc') }}
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800/80 hover:border-orange-500/50 dark:hover:border-orange-500/50 transition-all hover:-translate-y-1">
                        <div class="w-12 h-12 rounded-xl bg-orange-500/10 text-orange-600 dark:text-orange-400 flex items-center justify-center mb-6">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2">{{ __('welcome.feature2_title') }}</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed">
                            {{ __('welcome.feature2_desc') }}
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800/80 hover:border-orange-500/50 dark:hover:border-orange-500/50 transition-all hover:-translate-y-1">
                        <div class="w-12 h-12 rounded-xl bg-orange-500/10 text-orange-600 dark:text-orange-400 flex items-center justify-center mb-6">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2">{{ __('welcome.feature3_title') }}</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed">
                            {{ __('welcome.feature3_desc') }}
                        </p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800/80 hover:border-orange-500/50 dark:hover:border-orange-500/50 transition-all hover:-translate-y-1">
                        <div class="w-12 h-12 rounded-xl bg-orange-500/10 text-orange-600 dark:text-orange-400 flex items-center justify-center mb-6">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2">{{ __('welcome.feature4_title') }}</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed">
                            {{ __('welcome.feature4_desc') }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Workflow / How it works Section -->
        <section id="workflow" class="py-20 bg-white dark:bg-zinc-950">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-3xl font-extrabold text-zinc-900 dark:text-white sm:text-4xl">
                        {{ __('welcome.workflow_title') }}
                    </h2>
                    <p class="mt-4 text-lg text-zinc-600 dark:text-zinc-400">
                        {{ __('welcome.workflow_desc') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-12 relative">
                    <!-- Step 1 -->
                    <div class="text-center space-y-4">
                        <div class="w-16 h-16 rounded-full bg-orange-600 text-white flex items-center justify-center text-2xl font-bold mx-auto shadow-lg shadow-orange-500/20">
                            1
                        </div>
                        <h3 class="text-xl font-bold text-zinc-900 dark:text-white">{{ __('welcome.step1_title') }}</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 text-sm max-w-xs mx-auto">
                            {{ __('welcome.step1_desc') }}
                        </p>
                    </div>

                    <!-- Step 2 -->
                    <div class="text-center space-y-4">
                        <div class="w-16 h-16 rounded-full bg-orange-600 text-white flex items-center justify-center text-2xl font-bold mx-auto shadow-lg shadow-orange-500/20">
                            2
                        </div>
                        <h3 class="text-xl font-bold text-zinc-900 dark:text-white">{{ __('welcome.step2_title') }}</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 text-sm max-w-xs mx-auto">
                            {{ __('welcome.step2_desc') }}
                        </p>
                    </div>

                    <!-- Step 3 -->
                    <div class="text-center space-y-4">
                        <div class="w-16 h-16 rounded-full bg-orange-600 text-white flex items-center justify-center text-2xl font-bold mx-auto shadow-lg shadow-orange-500/20">
                            3
                        </div>
                        <h3 class="text-xl font-bold text-zinc-900 dark:text-white">{{ __('welcome.step3_title') }}</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 text-sm max-w-xs mx-auto">
                            {{ __('welcome.step3_desc') }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Security / Passkeys Section -->
        <section id="security" class="py-20 bg-zinc-100 dark:bg-zinc-900/40 border-t border-zinc-200 dark:border-zinc-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <!-- Image/Visual -->
                    <div class="order-2 lg:order-1">
                        <div class="bg-gradient-to-tr from-orange-600 to-amber-500 p-8 rounded-3xl shadow-xl flex items-center justify-center">
                            <div class="bg-white/10 backdrop-blur-md border border-white/20 p-6 rounded-2xl text-white text-center max-w-sm space-y-4">
                                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                <h4 class="text-lg font-bold">{{ __('welcome.security_mockup_title') }}</h4>
                                <p class="text-xs text-white/80">{{ __('welcome.security_mockup_desc') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Text content -->
                    <div class="order-1 lg:order-2 space-y-6 text-center lg:text-left">
                        <div class="inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-orange-500/10 text-orange-600 dark:text-orange-400 border border-orange-500/20">
                            {{ __('welcome.security_badge') }}
                        </div>
                        <h2 class="text-3xl font-extrabold text-zinc-900 dark:text-white sm:text-4xl">
                            {{ __('welcome.security_title') }}
                        </h2>
                        <p class="text-zinc-650 dark:text-zinc-405 leading-relaxed">
                            {{ __('welcome.security_desc') }}
                        </p>
                        
                        <ul class="space-y-4 text-sm text-zinc-600 dark:text-zinc-400 text-left">
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-orange-600 dark:text-orange-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span><strong>{{ __('welcome.security_passkey_title') }}</strong> {{ __('welcome.security_passkey_desc') }}</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-orange-600 dark:text-orange-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span><strong>{{ __('welcome.security_2fa_title') }}</strong> {{ __('welcome.security_2fa_desc') }}</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-orange-600 dark:text-orange-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span><strong>{{ __('welcome.security_enc_title') }}</strong> {{ __('welcome.security_enc_desc') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Bottom -->
        <section class="py-20 bg-orange-600 text-white">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-8">
                <h2 class="text-3xl sm:text-4xl font-extrabold">
                    {{ __('welcome.cta_title') }}
                </h2>
                <p class="text-orange-100 max-w-2xl mx-auto text-lg leading-relaxed">
                    {{ __('welcome.cta_desc') }}
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-orange-600 bg-white hover:bg-orange-50 rounded-xl shadow-lg transition-all">
                            {{ __('welcome.cta_go_dashboard') }}
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-orange-600 bg-white hover:bg-orange-50 rounded-xl shadow-lg transition-all">
                            {{ __('welcome.cta_start_free') }}
                        </a>
                        <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-white border border-white hover:bg-white/10 rounded-xl transition-all">
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
                    <x-app-logo-icon class="size-6 text-orange-500" />
                    <span class="font-bold text-lg text-white tracking-tight">
                        {{ config('app.name', 'Activity Tracker') }}
                    </span>
                </div>
                
                <p class="text-xs text-zinc-500">
                    &copy; {{ date('Y') }} {{ config('app.name', 'Activity Tracker') }}. {{ __('welcome.footer_rights') }}
                </p>
            </div>
        </footer>
    </body>
</html>

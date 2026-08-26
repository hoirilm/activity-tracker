<x-layouts::auth :title="__('Register')">
    <div class="w-full max-w-5xl overflow-hidden rounded-3xl border border-zinc-800/80 bg-zinc-900/60 backdrop-blur-2xl shadow-2xl grid grid-cols-1 lg:grid-cols-12 min-h-[660px]">
        
        <!-- Left Hero Section (Brand & Value Proposition) -->
        <div class="lg:col-span-6 p-8 sm:p-12 flex flex-col justify-between relative bg-gradient-to-br from-zinc-900/90 via-zinc-900/60 to-indigo-950/40 border-b lg:border-b-0 lg:border-r border-zinc-800/60 overflow-hidden">
            <!-- Background Glow Orbs -->
            <div class="absolute -top-24 -left-24 w-72 h-72 rounded-full bg-indigo-500/15 blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -right-24 w-72 h-72 rounded-full bg-emerald-500/15 blur-3xl pointer-events-none"></div>

            <div class="relative z-10">
                <!-- Brand Logo & Title -->
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3 group" wire:navigate>
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-tr from-indigo-600 to-emerald-400 p-0.5 shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform duration-300">
                        <div class="flex h-full w-full items-center justify-center rounded-[10px] bg-zinc-950">
                            <x-app-logo-icon class="h-6 w-6 text-emerald-400" />
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-lg font-bold tracking-tight text-white group-hover:text-emerald-400 transition-colors">Dev Track</span>
                        <span class="text-xs text-zinc-400 font-medium">Activity Tracker</span>
                    </div>
                </a>

                <!-- Hero Content -->
                <div class="mt-10 space-y-4">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                        </span>
                        Developer Onboarding
                    </div>

                    <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white leading-tight">
                        Start tracking with <span class="bg-gradient-to-r from-emerald-400 via-teal-300 to-indigo-400 bg-clip-text text-transparent">zero friction</span>.
                    </h1>

                    <p class="text-zinc-400 text-sm leading-relaxed">
                        Join your workspace, leverage instant keyboard navigation, and record your daily developer output effortlessly.
                    </p>
                </div>
            </div>

            <!-- Clean Feature Showcase Carousel (Auto-Sliding) -->
            <div 
                x-data="{
                    active: 0,
                    total: 4,
                    progress: 0,
                    progressInterval: null,
                    isPaused: false,

                    init() {
                        this.startTimer();
                    },
                    
                    startTimer() {
                        this.progress = 0;
                        clearInterval(this.progressInterval);
                        this.progressInterval = setInterval(() => {
                            if (!this.isPaused) {
                                this.progress += 2;
                                if (this.progress >= 100) {
                                    this.next();
                                }
                            }
                        }, 80);
                    },
                    
                    next() {
                        this.active = (this.active + 1) % this.total;
                        this.progress = 0;
                    },
                    
                    goTo(idx) {
                        this.active = idx;
                        this.progress = 0;
                    }
                }"
                x-on:mouseenter="isPaused = true"
                x-on:mouseleave="isPaused = false"
                class="relative z-10 mt-8 select-none"
            >
                <!-- Showcase Container Box -->
                <div class="rounded-2xl border border-zinc-800/90 bg-zinc-950/80 backdrop-blur-xl p-5 shadow-2xl relative overflow-hidden">
                    
                    <!-- Segmented Progress Indicator Bar -->
                    <div class="flex items-center gap-2 mb-4 pb-3 border-b border-zinc-900">
                        <template x-for="i in total" :key="i - 1">
                            <button 
                                type="button"
                                x-on:click="goTo(i - 1)"
                                class="h-1.5 flex-1 rounded-full bg-zinc-800/90 overflow-hidden cursor-pointer transition-all hover:h-2"
                                :aria-label="'Feature ' + i"
                            >
                                <div 
                                    class="h-full bg-gradient-to-r from-emerald-400 to-teal-400 rounded-full transition-all duration-75"
                                    :style="active === (i - 1) ? `width: ${progress}%` : (active > (i - 1) ? 'width: 100%' : 'width: 0%')"
                                ></div>
                            </button>
                        </template>
                    </div>

                    <!-- Feature Slides -->
                    <div class="min-h-[135px] flex flex-col justify-between">
                        
                        <!-- SLIDE 0: Keyboard-First UX -->
                        <div x-show="active === 0" x-transition:enter="transition ease-out duration-300 transform opacity-0 translate-y-1.5" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">KEYBOARD-FIRST</span>
                                    <span class="text-xs font-semibold text-white">Instant Hotkey Flow</span>
                                </div>
                                <span class="text-[11px] font-mono text-zinc-500">01 / 04</span>
                            </div>

                            <div class="rounded-xl border border-zinc-800/90 bg-zinc-900/80 p-3 flex items-center justify-between text-xs font-mono text-zinc-300">
                                <div class="flex items-center gap-2">
                                    <kbd class="px-1.5 py-0.5 rounded bg-zinc-950 border border-zinc-700 text-[11px] text-emerald-400 font-semibold shadow-xs">⌘ /</kbd>
                                    <span class="text-zinc-400">Focus task bar</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <kbd class="px-1.5 py-0.5 rounded bg-zinc-950 border border-zinc-700 text-[11px] text-emerald-400 font-semibold shadow-xs">⌘ ↵</kbd>
                                    <span class="text-zinc-400">Log & start tracking</span>
                                </div>
                            </div>

                            <p class="text-[11px] text-zinc-400 leading-relaxed">
                                Record daily activities effortlessly with zero context-switching or mouse reliance.
                            </p>
                        </div>

                        <!-- SLIDE 1: Real-time & Parallel Tracking -->
                        <div x-show="active === 1" x-cloak x-transition:enter="transition ease-out duration-300 transform opacity-0 translate-y-1.5" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-teal-500/10 text-teal-400 border border-teal-500/20">TIME TRACKING</span>
                                    <span class="text-xs font-semibold text-white">Sequential & Parallel Modes</span>
                                </div>
                                <span class="text-[11px] font-mono text-zinc-500">02 / 04</span>
                            </div>

                            <div class="rounded-xl border border-zinc-800/90 bg-zinc-900/80 p-3 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span class="text-xs font-mono text-zinc-300">Live Duration Counter</span>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-medium bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
                                    Parallel Supported
                                </span>
                            </div>

                            <p class="text-[11px] text-zinc-400 leading-relaxed">
                                Client-side calculation for lag-free timers with optional parallel multi-task tracking.
                            </p>
                        </div>

                        <!-- SLIDE 2: Data Portability (Excel & CSV) -->
                        <div x-show="active === 2" x-cloak x-transition:enter="transition ease-out duration-300 transform opacity-0 translate-y-1.5" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">PORTABILITY</span>
                                    <span class="text-xs font-semibold text-white">Full Export & Import</span>
                                </div>
                                <span class="text-[11px] font-mono text-zinc-500">03 / 04</span>
                            </div>

                            <div class="rounded-xl border border-zinc-800/90 bg-zinc-900/80 p-3 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2 text-zinc-300 font-mono">
                                    <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span>.XLSX & .CSV Formats</span>
                                </div>
                                <span class="text-[11px] font-mono text-indigo-300">Auto Duration Calculations</span>
                            </div>

                            <p class="text-[11px] text-zinc-400 leading-relaxed">
                                Seamlessly export activity reports with project relations or import past history anytime.
                            </p>
                        </div>

                        <!-- SLIDE 3: Enterprise Auth & Security -->
                        <div x-show="active === 3" x-cloak x-transition:enter="transition ease-out duration-300 transform opacity-0 translate-y-1.5" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-purple-500/10 text-purple-400 border border-purple-500/20">SECURITY</span>
                                    <span class="text-xs font-semibold text-white">Passkey & Whitelist Access</span>
                                </div>
                                <span class="text-[11px] font-mono text-zinc-500">04 / 04</span>
                            </div>

                            <div class="rounded-xl border border-zinc-800/90 bg-zinc-900/80 p-3 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2 text-zinc-300">
                                    <svg class="w-4 h-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    <span class="font-mono">FIDO2 Touch ID / Passkeys</span>
                                </div>
                                <span class="text-[11px] font-mono text-emerald-400">12-Factor Whitelist</span>
                            </div>

                            <p class="text-[11px] text-zinc-400 leading-relaxed">
                                Instant biometric login coupled with strict email domain whitelist authorization.
                            </p>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Right Form Section -->
        <div class="lg:col-span-6 p-8 sm:p-12 flex flex-col justify-center bg-zinc-900/40">
            <div class="w-full max-w-md mx-auto space-y-6">
                
                <!-- Auth Header -->
                <div class="space-y-1 text-left">
                    <h2 class="text-2xl font-bold tracking-tight text-white">{{ __('Create an account') }}</h2>
                    <p class="text-sm text-zinc-400">{{ __('Enter your details below to get started with Dev Track') }}</p>
                </div>

                <!-- Session Status -->
                <x-auth-session-status class="text-center" :status="session('status')" />

                <!-- Registration Form -->
                <form method="POST" action="{{ route('register.store') }}" class="space-y-4" autocomplete="off">
                    @csrf

                    <!-- Name -->
                    <div class="space-y-1.5">
                        <flux:input
                            name="name"
                            :label="__('Full Name')"
                            :value="old('name')"
                            type="text"
                            required
                            autofocus
                            autocomplete="off"
                            placeholder="John Doe"
                        />
                    </div>

                    <!-- Email Address -->
                    <div class="space-y-1.5">
                        <flux:input
                            name="email"
                            :label="__('Work Email')"
                            :value="old('email')"
                            type="email"
                            required
                            autocomplete="off"
                            placeholder="developer@company.com"
                        />
                    </div>

                    <!-- Password -->
                    <div class="space-y-1.5">
                        <flux:input
                            name="password"
                            :label="__('Password')"
                            type="password"
                            required
                            autocomplete="off"
                            :placeholder="__('••••••••')"
                            passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                            viewable
                        />
                    </div>

                    <!-- Confirm Password -->
                    <div class="space-y-1.5">
                        <flux:input
                            name="password_confirmation"
                            :label="__('Confirm Password')"
                            type="password"
                            required
                            autocomplete="off"
                            :placeholder="__('••••••••')"
                            passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                            viewable
                        />
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit" data-test="register-user-button" class="w-full py-3 px-4 rounded-xl font-semibold text-sm text-zinc-950 bg-gradient-to-r from-emerald-400 to-teal-400 hover:from-emerald-300 hover:to-teal-300 active:scale-[0.99] shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30 transition-all duration-200 flex items-center justify-center gap-2 group cursor-pointer">
                            <span>{{ __('Create Account') }}</span>
                            <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Divider -->
                    <div class="relative flex items-center py-2">
                        <div class="flex-grow border-t border-zinc-800"></div>
                        <span class="flex-shrink-0 mx-4 text-[11px] font-semibold uppercase tracking-wider text-zinc-500">{{ __('Or sign up with') }}</span>
                        <div class="flex-grow border-t border-zinc-800"></div>
                    </div>

                    <!-- Google SSO Button -->
                    <div class="flex items-center justify-center">
                        <a href="{{ route('auth.google') }}" class="w-full py-2.5 px-4 rounded-xl border border-zinc-800 bg-zinc-950/60 hover:bg-zinc-800/60 text-zinc-200 text-sm font-medium flex items-center justify-center gap-2.5 transition-all duration-200 hover:border-zinc-700 active:scale-[0.99]">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M22.56 12.25C22.56 11.47 22.49 10.72 22.36 10H12V14.26H17.92C17.67 15.63 16.89 16.81 15.72 17.59V20.35H19.28C21.36 18.43 22.56 15.6 22.56 12.25Z" fill="#4285F4"/>
                                <path d="M12 23C14.97 23 17.46 22.02 19.28 20.35L15.72 17.59C14.73 18.25 13.48 18.66 12 18.66C9.14 18.66 6.71 16.73 5.84 14.13H2.18V16.97C3.99 20.57 7.7 23 12 23Z" fill="#34A853"/>
                                <path d="M5.84 14.13C5.62 13.47 5.49 12.75 5.49 12C5.49 11.25 5.62 10.53 5.84 9.87V7.03H2.18C1.43 8.52 1 10.2 1 12C1 13.8 1.43 15.48 2.18 16.97L5.84 14.13Z" fill="#FBBC05"/>
                                <path d="M12 5.34C13.62 5.34 15.06 5.9 16.21 7L19.35 3.86C17.45 2.08 14.97 1 12 1C7.7 1 3.99 3.43 2.18 7.03L5.84 9.87C6.71 7.27 9.14 5.34 12 5.34Z" fill="#EA4335"/>
                            </svg>
                            <span>Google Account</span>
                        </a>
                    </div>
                </form>

                <!-- Footer Sign In Link -->
                <div class="text-center text-xs text-zinc-400 pt-1">
                    <span>{{ __('Already have an account?') }}</span>
                    <flux:link :href="route('login')" wire:navigate class="text-indigo-400 hover:text-indigo-300 font-medium ml-1">{{ __('Log in') }}</flux:link>
                </div>
            </div>
        </div>

    </div>
</x-layouts::auth>

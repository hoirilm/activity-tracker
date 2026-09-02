<x-layouts::auth :title="__('Forgot password')">
    <div class="w-full max-w-md p-8 sm:p-10 rounded-3xl border border-zinc-800 bg-zinc-900 shadow-2xl space-y-6">
        <!-- Brand Logo & Header -->
        <div class="text-center space-y-3">
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center group" wire:navigate>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-tr from-amber-500 to-orange-400 p-0.5 shadow-lg shadow-amber-500/20 group-hover:scale-105 transition-transform duration-300">
                    <div class="flex h-full w-full items-center justify-center rounded-[10px] bg-zinc-950">
                        <x-app-logo-icon class="h-6 w-6 text-amber-400" />
                    </div>
                </div>
            </a>
            <div class="space-y-1">
                <h2 class="text-2xl font-bold tracking-tight text-white">{{ __('Forgot password') }}</h2>
                <p class="text-xs text-zinc-400">{{ __('Enter your email to receive a password reset link') }}</p>
            </div>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5" autocomplete="off">
            @csrf

            <!-- Email Address -->
            <div class="space-y-1.5">
                <flux:input
                    name="email"
                    :label="__('Email address')"
                    type="email"
                    required
                    autofocus
                    autocomplete="off"
                    placeholder="email@example.com"
                />
            </div>

            <button type="submit" data-test="email-password-reset-link-button" class="w-full py-3 px-4 rounded-xl font-semibold text-sm text-zinc-950 bg-gradient-to-r from-amber-400 via-amber-500 to-orange-500 hover:from-amber-300 hover:to-orange-400 active:scale-[0.99] shadow-lg shadow-amber-500/20 hover:shadow-amber-500/30 transition-all duration-200 flex items-center justify-center gap-2 group cursor-pointer">
                <span>{{ __('Email password reset link') }}</span>
                <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </form>

        <div class="text-center text-xs text-zinc-400 pt-2">
            <span>{{ __('Or, return to') }}</span>
            <flux:link :href="route('login')" wire:navigate class="text-amber-400 hover:text-amber-300 font-medium ml-1">{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>

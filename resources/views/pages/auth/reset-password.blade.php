<x-layouts::auth :title="__('Reset password')">
    <div class="w-full max-w-md p-8 sm:p-10 rounded-3xl border border-zinc-800/80 bg-zinc-900/60 backdrop-blur-2xl shadow-2xl space-y-6">
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
                <h2 class="text-2xl font-bold tracking-tight text-white">{{ __('Reset password') }}</h2>
                <p class="text-xs text-zinc-400">{{ __('Please enter your new password below') }}</p>
            </div>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="space-y-5" autocomplete="off">
            @csrf
            <!-- Token -->
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <!-- Email Address -->
            <div class="space-y-1.5">
                <flux:input
                    name="email"
                    value="{{ request('email') }}"
                    :label="__('Email')"
                    type="email"
                    required
                    autocomplete="email"
                />
            </div>

            <!-- Password -->
            <div class="space-y-1.5">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Password')"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                    viewable
                />
            </div>

            <!-- Confirm Password -->
            <div class="space-y-1.5">
                <flux:input
                    name="password_confirmation"
                    :label="__('Confirm password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Confirm password')"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                    viewable
                />
            </div>

            <button type="submit" data-test="reset-password-button" class="w-full py-3 px-4 rounded-xl font-semibold text-sm text-zinc-950 bg-gradient-to-r from-amber-400 via-amber-500 to-orange-500 hover:from-amber-300 hover:to-orange-400 active:scale-[0.99] shadow-lg shadow-amber-500/20 hover:shadow-amber-500/30 transition-all duration-200 flex items-center justify-center gap-2 group cursor-pointer">
                <span>{{ __('Reset password') }}</span>
                <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </form>
    </div>
</x-layouts::auth>

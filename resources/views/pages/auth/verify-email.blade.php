<x-layouts::auth :title="__('Verify email address')">
    <div class="w-full max-w-md p-8 sm:p-10 rounded-3xl border border-zinc-800 bg-zinc-900 shadow-2xl space-y-6 text-center">
        <!-- Brand Logo & Header -->
        <div class="space-y-3">
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center group" wire:navigate>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-tr from-amber-500 to-orange-400 p-0.5 shadow-lg shadow-amber-500/20 group-hover:scale-105 transition-transform duration-300">
                    <div class="flex h-full w-full items-center justify-center rounded-[10px] bg-zinc-950">
                        <x-app-logo-icon class="h-6 w-6 text-amber-400" />
                    </div>
                </div>
            </a>
            <div class="space-y-1">
                <h2 class="text-2xl font-bold tracking-tight text-white">{{ __('Verify your email') }}</h2>
                <p class="text-xs text-zinc-400 leading-relaxed">{{ __('Please verify your email address by clicking on the link we just emailed to you.') }}</p>
            </div>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-medium">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </div>
        @endif

        <div class="flex flex-col items-center justify-between space-y-3 pt-2">
            <form method="POST" action="{{ route('verification.send') }}" class="w-full">
                @csrf
                <button type="submit" class="w-full py-3 px-4 rounded-xl font-semibold text-sm text-zinc-950 bg-gradient-to-r from-amber-400 via-amber-500 to-orange-500 hover:from-amber-300 hover:to-orange-400 active:scale-[0.99] shadow-lg shadow-amber-500/20 hover:shadow-amber-500/30 transition-all duration-200 flex items-center justify-center gap-2 group cursor-pointer">
                    <span>{{ __('Resend verification email') }}</span>
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit" class="text-xs font-medium text-zinc-400 hover:text-zinc-200 transition-colors cursor-pointer py-1" data-test="logout-button">
                    {{ __('Log out') }}
                </button>
            </form>
        </div>
    </div>
</x-layouts::auth>

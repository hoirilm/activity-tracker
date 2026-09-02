<x-layouts::auth :title="__('Two-factor confirmation')">
    <div class="w-full max-w-md p-8 sm:p-10 rounded-3xl border border-zinc-800 bg-zinc-900 shadow-2xl space-y-6">
        <!-- Brand Logo -->
        <div class="text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center group mb-2" wire:navigate>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-tr from-amber-500 to-orange-400 p-0.5 shadow-lg shadow-amber-500/20 group-hover:scale-105 transition-transform duration-300">
                    <div class="flex h-full w-full items-center justify-center rounded-[10px] bg-zinc-950">
                        <x-app-logo-icon class="h-6 w-6 text-amber-400" />
                    </div>
                </div>
            </a>
        </div>

        <div
            class="relative w-full h-auto"
            x-cloak
            x-data="{
                showRecoveryInput: @js($errors->has('recovery_code')),
                code: '',
                recovery_code: '',
                focusOtp() {
                    this.$nextTick(() => this.$refs.otp?.querySelector('input')?.focus());
                },
                init() {
                    if (! this.showRecoveryInput) {
                        this.focusOtp();
                    }
                },
                toggleInput() {
                    this.showRecoveryInput = !this.showRecoveryInput;

                    this.code = '';
                    this.recovery_code = '';

                    $nextTick(() => {
                        this.showRecoveryInput
                            ? this.$refs.recovery_code?.focus()
                            : this.focusOtp();
                    });
                },
            }"
        >
            <div x-show="!showRecoveryInput" class="text-center space-y-1">
                <h2 class="text-2xl font-bold tracking-tight text-white">{{ __('Authentication code') }}</h2>
                <p class="text-xs text-zinc-400">{{ __('Enter the authentication code provided by your authenticator application.') }}</p>
            </div>

            <div x-show="showRecoveryInput" class="text-center space-y-1">
                <h2 class="text-2xl font-bold tracking-tight text-white">{{ __('Recovery code') }}</h2>
                <p class="text-xs text-zinc-400">{{ __('Please confirm access to your account by entering one of your emergency recovery codes.') }}</p>
            </div>

            <form method="POST" action="{{ route('two-factor.login.store') }}" class="mt-6">
                @csrf

                <div class="space-y-5 text-center">
                    <div x-show="!showRecoveryInput">
                        <div class="flex items-center justify-center my-5" x-ref="otp">
                            <flux:otp
                                x-model="code"
                                length="6"
                                name="code"
                                label="OTP Code"
                                label:sr-only
                                class="mx-auto"
                             />
                        </div>
                    </div>

                    <div x-show="showRecoveryInput">
                        <div class="my-5">
                            <flux:input
                                type="text"
                                name="recovery_code"
                                x-ref="recovery_code"
                                x-bind:required="showRecoveryInput"
                                autocomplete="one-time-code"
                                x-model="recovery_code"
                            />
                        </div>

                        @error('recovery_code')
                            <flux:text color="red">
                                {{ $message }}
                            </flux:text>
                        @enderror
                    </div>

                    <button type="submit" class="w-full py-3 px-4 rounded-xl font-semibold text-sm text-zinc-950 bg-gradient-to-r from-amber-400 via-amber-500 to-orange-500 hover:from-amber-300 hover:to-orange-400 active:scale-[0.99] shadow-lg shadow-amber-500/20 hover:shadow-amber-500/30 transition-all duration-200 flex items-center justify-center gap-2 group cursor-pointer">
                        <span>{{ __('Continue') }}</span>
                        <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </div>

                <div class="mt-5 space-x-0.5 text-xs leading-5 text-center text-zinc-400">
                    <span class="opacity-70">{{ __('or you can') }}</span>
                    <div class="inline font-medium text-amber-400 hover:text-amber-300 underline cursor-pointer">
                        <span x-show="!showRecoveryInput" @click="toggleInput()">{{ __('login using a recovery code') }}</span>
                        <span x-show="showRecoveryInput" @click="toggleInput()">{{ __('login using an authentication code') }}</span>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layouts::auth>

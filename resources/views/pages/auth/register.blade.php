<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6" autocomplete="off">
            @csrf
            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="off"
                :placeholder="__('Full name')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="off"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="off"
                :placeholder="__('Password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="off"
                :placeholder="__('Confirm password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full hover:scale-[1.02] active:scale-95 transition-all duration-300" data-test="register-user-button">
                    {{ __('Create account') }}
                </flux:button>
            </div>

            <div class="relative flex items-center">
                <div class="flex-grow border-t border-zinc-200 dark:border-zinc-800"></div>
                <span class="flex-shrink-0 mx-4 text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Or sign up with') }}</span>
                <div class="flex-grow border-t border-zinc-200 dark:border-zinc-800"></div>
            </div>

            <div class="flex items-center justify-center">
                <flux:button href="{{ route('auth.google') }}" variant="outline" class="w-full flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-95 transition-all duration-300">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.56 12.25C22.56 11.47 22.49 10.72 22.36 10H12V14.26H17.92C17.67 15.63 16.89 16.81 15.72 17.59V20.35H19.28C21.36 18.43 22.56 15.6 22.56 12.25Z" fill="#4285F4"/>
                        <path d="M12 23C14.97 23 17.46 22.02 19.28 20.35L15.72 17.59C14.73 18.25 13.48 18.66 12 18.66C9.14 18.66 6.71 16.73 5.84 14.13H2.18V16.97C3.99 20.57 7.7 23 12 23Z" fill="#34A853"/>
                        <path d="M5.84 14.13C5.62 13.47 5.49 12.75 5.49 12C5.49 11.25 5.62 10.53 5.84 9.87V7.03H2.18C1.43 8.52 1 10.2 1 12C1 13.8 1.43 15.48 2.18 16.97L5.84 14.13Z" fill="#FBBC05"/>
                        <path d="M12 5.34C13.62 5.34 15.06 5.9 16.21 7L19.35 3.86C17.45 2.08 14.97 1 12 1C7.7 1 3.99 3.43 2.18 7.03L5.84 9.87C6.71 7.27 9.14 5.34 12 5.34Z" fill="#EA4335"/>
                    </svg>
                    Google
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>

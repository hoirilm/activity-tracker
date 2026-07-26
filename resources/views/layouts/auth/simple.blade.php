<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <style>
            @keyframes authSlideUp {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .animate-auth-entrance {
                animation: authSlideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            }
            .auth-logo-hover {
                transition: transform 0.3s ease;
            }
            .auth-logo-hover:hover {
                transform: scale(1.05) rotate(-2deg);
            }
        </style>
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-2 animate-auth-entrance">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium group" wire:navigate>
                    <span class="flex h-9 w-9 mb-1 items-center justify-center rounded-md auth-logo-hover">
                        <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                    </span>
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>

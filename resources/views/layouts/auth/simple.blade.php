<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <style>
            @keyframes authSlideUp {
                from { opacity: 0; transform: translateY(20px) scale(0.99); }
                to { opacity: 1; transform: translateY(0) scale(1); }
            }
            .animate-auth-entrance {
                animation: authSlideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            }
            .bg-auth-grid {
                background-size: 32px 32px;
                background-image: radial-gradient(circle, rgba(120, 119, 198, 0.15) 1px, transparent 1px);
            }
        </style>
    </head>
    <body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased selection:bg-amber-500 selection:text-white dark:bg-zinc-950 dark:text-zinc-100 relative overflow-x-hidden">
        <!-- Ambient Glow Elements -->
        <div class="pointer-events-none fixed inset-0 z-0 overflow-hidden">
            <div class="absolute -top-32 -left-32 h-[500px] w-[500px] rounded-full bg-amber-500/15 blur-[130px]"></div>
            <div class="absolute top-1/2 -right-32 h-[450px] w-[450px] rounded-full bg-orange-500/10 blur-[120px]"></div>
            <div class="absolute -bottom-32 left-1/3 h-[400px] w-[400px] rounded-full bg-amber-600/10 blur-[120px]"></div>
            <div class="absolute inset-0 bg-auth-grid"></div>
        </div>

        <!-- Content Shell -->
        <div class="relative z-10 flex min-h-svh w-full flex-col items-center justify-center p-4 sm:p-6 lg:p-8">
            <div class="w-full flex justify-center animate-auth-entrance">
                {{ $slot }}
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


<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <script>
            // Ensure auth pages always render in dark mode
            document.documentElement.classList.add('dark');
        </script>
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
    <body class="dark min-h-screen bg-zinc-950 text-zinc-100 antialiased selection:bg-amber-500 selection:text-white relative overflow-x-hidden">
        <!-- Background Grid Pattern -->
        <div class="pointer-events-none fixed inset-0 z-0 overflow-hidden bg-zinc-950">
            <div class="absolute inset-0 bg-auth-grid opacity-30"></div>
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
        <script>
            document.documentElement.classList.add('dark');
        </script>
    </body>
</html>


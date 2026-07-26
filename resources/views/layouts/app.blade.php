<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="flex flex-col min-h-full">
        <div class="flex-1">
            {{ $slot }}
        </div>
        <footer class="border-t border-zinc-200 dark:border-zinc-800 py-4 text-center mt-8">
            <p class="text-[11px] text-zinc-400 dark:text-zinc-600">
                {{ config('app.name', 'Activity Tracker') }} <span class="font-mono">{{ config('app.version', 'dev') }}</span> &nbsp;&bull;&nbsp; &copy; {{ date('Y') }} All rights reserved.
            </p>
        </footer>
    </flux:main>
    <livewire:onboarding-tour />
</x-layouts::app.sidebar>

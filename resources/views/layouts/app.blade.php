<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="flex flex-col min-h-full !px-2.5 lg:!px-8">
        <div class="flex-1">
            {{ $slot }}
        </div>
        <footer class="border-t border-zinc-200/80 dark:border-zinc-800/80 py-4 text-center mt-8">
            <p class="text-[11px] text-zinc-500 dark:text-zinc-500 font-mono">
                <span class="font-bold text-zinc-700 dark:text-zinc-300">{{ config('app.name', 'Klakoan') }}</span> <span class="text-amber-600 dark:text-amber-400 font-semibold">{{ config('app.version', 'v2.0') }}</span> &nbsp;&bull;&nbsp; &copy; {{ date('Y') }} All rights reserved.
            </p>
        </footer>
    </flux:main>
    <livewire:onboarding-tour />
    <livewire:task-celebration />
</x-layouts::app.sidebar>


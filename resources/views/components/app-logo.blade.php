@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand :name="config('app.name', 'Klakoan')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500 border border-amber-500/20 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30 shadow-xs">
            <x-app-logo-icon class="size-5 text-amber-500 dark:text-amber-400" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="config('app.name', 'Klakoan')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500 border border-amber-500/20 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30 shadow-xs">
            <x-app-logo-icon class="size-5 text-amber-500 dark:text-amber-400" />
        </x-slot>
    </flux:brand>
@endif


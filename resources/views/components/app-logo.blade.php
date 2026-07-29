@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand :name="config('app.name', 'Klakoan Activity Tracker')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-xl bg-zinc-900 text-white dark:bg-white dark:text-zinc-900 shadow-xs">
            <x-app-logo-icon class="size-4.5 fill-current text-white dark:text-zinc-900" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="config('app.name', 'Klakoan Activity Tracker')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-xl bg-zinc-900 text-white dark:bg-white dark:text-zinc-900 shadow-xs">
            <x-app-logo-icon class="size-4.5 fill-current text-white dark:text-zinc-900" />
        </x-slot>
    </flux:brand>
@endif

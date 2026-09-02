<div class="flex items-start max-md:flex-col">
    <style>
        /* Settings Navigation Items - Theme Styling */
        [data-flux-navlist-item] {
            transition: all 180ms ease !important;
            border-radius: 0.75rem !important; /* rounded-xl */
            font-size: 0.8125rem !important;
            font-weight: 500 !important;
            min-height: 2.25rem !important;
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
            margin-top: 2px !important;
            margin-bottom: 2px !important;
        }

        /* Hover on Inactive Items */
        html.dark [data-flux-navlist-item]:not([data-current]):hover {
            background-color: rgba(255, 255, 255, 0.05) !important;
            color: #f4f4f5 !important;
        }
        html:not(.dark) [data-flux-navlist-item]:not([data-current]):hover {
            background-color: rgba(0, 0, 0, 0.04) !important;
            color: #18181b !important;
        }

        /* Active State - Signature Warm Amber Gradient & Indicator */
        html.dark [data-flux-navlist-item][data-current] {
            background: linear-gradient(90deg, rgba(245, 158, 11, 0.18) 0%, rgba(245, 158, 11, 0.05) 70%, transparent 100%) !important;
            color: #fbbf24 !important; /* amber-400 */
            font-weight: 600 !important;
            border-left: 3px solid #f59e0b !important; /* amber-500 */
            border-top-left-radius: 0.25rem !important;
            border-bottom-left-radius: 0.25rem !important;
            border-top: 1px solid rgba(245, 158, 11, 0.15) !important;
            border-bottom: 1px solid rgba(245, 158, 11, 0.08) !important;
            border-right: 1px solid transparent !important;
        }
        html:not(.dark) [data-flux-navlist-item][data-current] {
            background: linear-gradient(90deg, rgba(245, 158, 11, 0.15) 0%, rgba(245, 158, 11, 0.04) 70%, transparent 100%) !important;
            color: #d97706 !important; /* amber-600 */
            font-weight: 600 !important;
            border-left: 3px solid #f59e0b !important; /* amber-500 */
            border-top-left-radius: 0.25rem !important;
            border-bottom-left-radius: 0.25rem !important;
            border-top: 1px solid rgba(245, 158, 11, 0.15) !important;
            border-bottom: 1px solid rgba(245, 158, 11, 0.08) !important;
            border-right: 1px solid transparent !important;
        }

        /* Active Icon Glow */
        html.dark [data-flux-navlist-item][data-current] svg,
        html.dark [data-flux-navlist-item][data-current] [data-slot="icon"] {
            color: #fbbf24 !important;
        }
        html:not(.dark) [data-flux-navlist-item][data-current] svg,
        html:not(.dark) [data-flux-navlist-item][data-current] [data-slot="icon"] {
            color: #d97706 !important;
        }
    </style>

    <div class="me-10 w-full pb-4 md:w-[220px] shrink-0">
        <flux:navlist aria-label="{{ __('Settings') }}" class="space-y-1">
            <flux:navlist.item icon="user" :href="route('profile.edit')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            <flux:navlist.item icon="shield-check" :href="route('security.edit')" wire:navigate>{{ __('Security') }}</flux:navlist.item>
            <flux:navlist.item icon="arrow-path-rounded-square" :href="route('backup.edit')" wire:navigate>{{ __('Backup & Restore') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden my-4" />

    <div class="flex-1 self-stretch max-md:pt-4">
        <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-100 font-bold">{{ $heading ?? '' }}</flux:heading>
        <flux:subheading class="text-zinc-500 dark:text-zinc-400 text-xs mt-1">{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-6 w-full max-w-xl">
            {{ $slot }}
        </div>
    </div>
</div>

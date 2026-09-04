<div class="flex items-start max-md:flex-col">
    <style>
        /* Settings Navigation Items - Ultra Minimalist (Vercel Style) */
        [data-flux-navlist-item] {
            transition: all 160ms cubic-bezier(0.16, 1, 0.3, 1) !important;
            border-radius: 0.5rem !important; /* rounded-lg */
            font-size: 0.8125rem !important; /* 13px */
            font-weight: 450 !important;
            min-height: 2.125rem !important; /* 34px */
            padding-left: 0.625rem !important;
            padding-right: 0.625rem !important;
            margin-top: 1.5px !important;
            margin-bottom: 1.5px !important;
            letter-spacing: -0.01em !important;
            position: relative !important;
            border: 1px solid transparent !important;
        }

        /* Inactive Items (Clean, Muted) */
        html.dark [data-flux-navlist-item]:not([data-current]) {
            color: #a1a1aa !important; /* zinc-400 */
            background: transparent !important;
        }
        html:not(.dark) [data-flux-navlist-item]:not([data-current]) {
            color: #71717a !important; /* zinc-500 */
            background: transparent !important;
        }

        /* Inactive Item Hover */
        html.dark [data-flux-navlist-item]:not([data-current]):hover {
            background-color: rgba(255, 255, 255, 0.04) !important;
            color: #fafafa !important;
        }
        html:not(.dark) [data-flux-navlist-item]:not([data-current]):hover {
            background-color: rgba(0, 0, 0, 0.035) !important;
            color: #09090b !important;
        }

        /* Inactive Icons */
        html.dark [data-flux-navlist-item]:not([data-current]) svg,
        html.dark [data-flux-navlist-item]:not([data-current]) [data-slot="icon"] {
            color: #71717a !important;
            transition: color 160ms ease !important;
        }
        html:not(.dark) [data-flux-navlist-item]:not([data-current]) svg,
        html:not(.dark) [data-flux-navlist-item]:not([data-current]) [data-slot="icon"] {
            color: #a1a1aa !important;
            transition: color 160ms ease !important;
        }
        html.dark [data-flux-navlist-item]:not([data-current]):hover svg,
        html.dark [data-flux-navlist-item]:not([data-current]):hover [data-slot="icon"] {
            color: #f4f4f5 !important;
        }
        html:not(.dark) [data-flux-navlist-item]:not([data-current]):hover svg,
        html:not(.dark) [data-flux-navlist-item]:not([data-current]):hover [data-slot="icon"] {
            color: #18181b !important;
        }

        /* Active State - Ultra Minimalist: Crisp White Text, Clean Subtle Plate */
        html.dark [data-flux-navlist-item][data-current] {
            background: rgba(255, 255, 255, 0.06) !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2) !important;
        }
        html:not(.dark) [data-flux-navlist-item][data-current] {
            background: rgba(0, 0, 0, 0.04) !important;
            color: #09090b !important;
            font-weight: 600 !important;
            border: 1px solid rgba(0, 0, 0, 0.06) !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
        }

        /* Active Icon */
        html.dark [data-flux-navlist-item][data-current] svg,
        html.dark [data-flux-navlist-item][data-current] [data-slot="icon"] {
            color: #ffffff !important;
        }
        html:not(.dark) [data-flux-navlist-item][data-current] svg,
        html:not(.dark) [data-flux-navlist-item][data-current] [data-slot="icon"] {
            color: #09090b !important;
        }

        /* Glowing Amber Dot Indicator on Active Item */
        [data-flux-navlist-item][data-current]::after {
            content: '';
            position: absolute;
            right: 0.65rem;
            top: 50%;
            transform: translateY(-50%);
            width: 5.5px;
            height: 5.5px;
            border-radius: 9999px;
            background-color: #f59e0b;
            box-shadow: 0 0 8px rgba(245, 158, 11, 0.65), 0 0 2px rgba(245, 158, 11, 0.9);
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

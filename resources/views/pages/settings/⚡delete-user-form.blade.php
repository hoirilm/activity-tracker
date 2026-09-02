<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="mt-10 pt-8 border-t border-zinc-200/80 dark:border-zinc-800/80 space-y-4">
    <div class="relative">
        <flux:heading class="text-rose-600 dark:text-rose-400 font-bold">{{ __('Delete account') }}</flux:heading>
        <flux:subheading class="text-zinc-500 dark:text-zinc-400 text-xs mt-0.5">{{ __('Delete your account and all of its resources') }}</flux:subheading>
    </div>

    <flux:modal.trigger name="confirm-user-deletion">
        <button type="button" 
                class="cursor-pointer bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 font-semibold rounded-xl px-4 py-2.5 text-xs border border-rose-500/30 active:scale-95 transition-all flex items-center gap-2" 
                data-test="delete-user-button">
            <flux:icon name="trash" class="size-3.5 text-rose-500" />
            <span>{{ __('Delete account') }}</span>
        </button>
    </flux:modal.trigger>

    <livewire:pages::settings.delete-user-modal />
</section>

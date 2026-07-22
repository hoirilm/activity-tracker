<x-layouts::app :title="__('Help')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="border-b border-zinc-100 dark:border-zinc-800 pb-4">
            <flux:heading size="xl">Help & Support Center</flux:heading>
            <flux:text class="mt-1">Everything you need to get help and make the most out of your activity tracking experience.</flux:text>
        </div>
        
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl">
            <div class="space-y-3 p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-2">
                    <flux:icon name="book-open" class="size-5 text-zinc-500" />
                    <flux:heading size="lg">User Guides</flux:heading>
                </div>
                <flux:text>Read our detailed documentation on how to set up projects, define categories, manage custom times, and generate weekly exports.</flux:text>
                <div class="pt-2">
                    <flux:button href="https://laravel.com/docs/starter-kits#livewire" target="_blank" variant="filled" size="sm">Open Docs</flux:button>
                </div>
            </div>
            
            <div class="space-y-3 p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-2">
                    <flux:icon name="bug-ant" class="size-5 text-zinc-500" />
                    <flux:heading size="lg">Report an Issue</flux:heading>
                </div>
                <flux:text>Encountered a problem or want to suggest a new feature? You can file a detailed report directly through the floating help menu.</flux:text>
                <div class="pt-2">
                    <flux:modal.trigger name="report-issue-modal">
                        <flux:button variant="filled" size="sm">File Bug Report</flux:button>
                    </flux:modal.trigger>
                </div>
            </div>
            
            <div class="space-y-3 p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-800 md:col-span-2">
                <div class="flex items-center gap-2">
                    <flux:icon name="chat-bubble-left-right" class="size-5 text-zinc-500" />
                    <flux:heading size="lg">Contact Administrator</flux:heading>
                </div>
                <flux:text>If you require immediate help or technical support for your user account (such as password resets, permission changes, or database restores), please contact the platform administrator at <a href="mailto:admin@marikerja.com" class="text-accent underline font-semibold">admin@marikerja.com</a>.</flux:text>
            </div>
        </div>
    </div>
</x-layouts::app>

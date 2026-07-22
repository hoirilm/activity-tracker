<!-- HELP CENTER MODAL -->
<flux:modal name="help-modal" class="min-w-[22rem] md:w-[38rem] backdrop:backdrop-blur-sm z-[200]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Help & Support Center</flux:heading>
            <flux:text class="mt-1">Everything you need to get help and make the most out of your activity tracking experience.</flux:text>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[60vh] overflow-y-auto pr-2">
            <div class="space-y-2 p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-2">
                    <flux:icon name="book-open" class="size-4 text-zinc-500" />
                    <flux:heading size="md">User Guides</flux:heading>
                </div>
                <flux:text class="text-xs">Read our detailed documentation on how to set up projects, define categories, manage custom times, and generate weekly exports.</flux:text>
                <div class="pt-1">
                    <flux:button href="https://laravel.com/docs/starter-kits#livewire" target="_blank" variant="filled" size="xs">Open Docs</flux:button>
                </div>
            </div>
            
            <div class="space-y-2 p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-2">
                    <flux:icon name="bug-ant" class="size-4 text-zinc-500" />
                    <flux:heading size="md">Report an Issue</flux:heading>
                </div>
                <flux:text class="text-xs">Encountered a problem or want to suggest a new feature? You can file a detailed report directly through the floating help menu.</flux:text>
                <div class="pt-1">
                    <flux:modal.trigger name="report-issue-modal">
                        <flux:button variant="filled" size="xs">File Bug Report</flux:button>
                    </flux:modal.trigger>
                </div>
            </div>
            
            <div class="space-y-2 p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-800 md:col-span-2">
                <div class="flex items-center gap-2">
                    <flux:icon name="chat-bubble-left-right" class="size-4 text-zinc-500" />
                    <flux:heading size="md">Contact Administrator</flux:heading>
                </div>
                <flux:text class="text-xs">If you require immediate help or technical support for your user account (such as password resets, permission changes, or database restores), please contact the platform administrator at <a href="mailto:admin@marikerja.com" class="text-accent underline font-semibold">admin@marikerja.com</a>.</flux:text>
            </div>
        </div>

        <div class="flex justify-end mt-6">
            <flux:modal.close>
                <flux:button variant="ghost">Close</flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>

<!-- FAQ MODAL -->
<flux:modal name="faq-modal" class="min-w-[22rem] md:w-[36rem] backdrop:backdrop-blur-sm z-[200]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Frequently Asked Questions</flux:heading>
            <flux:text class="mt-1">Find quick answers to common questions about tracking your activities.</flux:text>
        </div>
        
        <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
            <div class="space-y-1">
                <flux:heading size="md" class="text-zinc-800 dark:text-zinc-200">What is Activity Tracker?</flux:heading>
                <flux:text class="text-sm">Activity Tracker is a sleek time management platform designed to help teams and individuals log work hours, categorize efforts, and review project progress.</flux:text>
            </div>
            
            <div class="space-y-1">
                <flux:heading size="md" class="text-zinc-800 dark:text-zinc-200">How do I start tracking time?</flux:heading>
                <flux:text class="text-sm">Navigate to the <strong>Tracker</strong> page from the sidebar, fill in your current project/category, and click the <strong>Start</strong> button to begin log. You can stop or save logs at any time.</flux:text>
            </div>
            
            <div class="space-y-1">
                <flux:heading size="md" class="text-zinc-800 dark:text-zinc-200">Can I edit my logged activities?</flux:heading>
                <flux:text class="text-sm">Yes, under the <strong>Tracker</strong> page, you'll find a history of your logged activities where you can edit description details or make adjustments to the time logs.</flux:text>
            </div>
            
            <div class="space-y-1">
                <flux:heading size="md" class="text-zinc-800 dark:text-zinc-200">How do I report system bugs or suggest features?</flux:heading>
                <flux:text class="text-sm">Click the Help/Question Mark floating menu at the top right of your screen and select <strong>Report Bug</strong>. Fill out the brief form and submit; our administrator will review it promptly.</flux:text>
            </div>
        </div>

        <div class="flex justify-end mt-6">
            <flux:modal.close>
                <flux:button variant="ghost">Close</flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>

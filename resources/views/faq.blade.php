<x-layouts::app :title="__('FAQ')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="border-b border-zinc-100 dark:border-zinc-800 pb-4">
            <flux:heading size="xl">Frequently Asked Questions</flux:heading>
            <flux:text class="mt-1">Find quick answers to common questions about tracking your activities.</flux:text>
        </div>
        
        <div class="mt-4 space-y-6 max-w-3xl">
            <div class="space-y-1">
                <flux:heading size="md" class="text-zinc-800 dark:text-zinc-200">What is Activity Tracker?</flux:heading>
                <flux:text>Activity Tracker is a sleek time management platform designed to help teams and individuals log work hours, categorize efforts, and review project progress.</flux:text>
            </div>
            
            <div class="space-y-1">
                <flux:heading size="md" class="text-zinc-800 dark:text-zinc-200">How do I start tracking time?</flux:heading>
                <flux:text>Navigate to the <strong>Tracker</strong> page from the sidebar, fill in your current project/category, and click the <strong>Start</strong> button to begin log. You can stop or save logs at any time.</flux:text>
            </div>
            
            <div class="space-y-1">
                <flux:heading size="md" class="text-zinc-800 dark:text-zinc-200">Can I edit my logged activities?</flux:heading>
                <flux:text>Yes, under the <strong>Tracker</strong> page, you'll find a history of your logged activities where you can edit description details or make adjustments to the time logs.</flux:text>
            </div>
            
            <div class="space-y-1">
                <flux:heading size="md" class="text-zinc-800 dark:text-zinc-200">How do I report system bugs or suggest features?</flux:heading>
                <flux:text>Click the Help/Question Mark floating menu at the bottom left of your screen and select <strong>Report Bug</strong>. Fill out the brief form and submit; our administrator will review it promptly.</flux:text>
            </div>
        </div>
    </div>
</x-layouts::app>

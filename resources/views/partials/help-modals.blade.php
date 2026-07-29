<!-- HELP CENTER MODAL -->
<flux:modal name="help-modal" class="w-[calc(100vw-2rem)] max-w-2xl backdrop:backdrop-blur-md z-[200]">
    <div class="space-y-5">
        <div class="flex items-center gap-3">
            <div class="size-10 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                <flux:icon name="question-mark-circle" class="size-5" />
            </div>
            <div>
                <flux:heading size="lg" class="font-bold tracking-tight">Help &amp; Support Center</flux:heading>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Everything you need to get help and make the most out of your activity tracking experience.</flux:text>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[60vh] overflow-y-auto pr-1 custom-scrollbar">
            <div class="space-y-2.5 p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-zinc-800 hover:border-zinc-400 dark:hover:border-zinc-600 transition-all group">
                <div class="flex items-center gap-2">
                    <div class="size-7 rounded-lg bg-zinc-200/80 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                        <flux:icon name="book-open" class="size-4" />
                    </div>
                    <flux:heading size="md" class="font-semibold text-sm">User Guides</flux:heading>
                </div>
                <flux:text class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">Read our detailed documentation on how to set up projects, define categories, manage custom times, and generate weekly exports.</flux:text>
                <div class="pt-1">
                    <button type="button" onclick="window.open('https://laravel.com/docs/starter-kits#livewire', '_blank')" class="bg-zinc-900 hover:bg-zinc-800 text-white dark:bg-zinc-100 dark:hover:bg-white dark:text-zinc-900 font-semibold text-xs px-3.5 py-1.5 rounded-xl active:scale-95 transition-all shadow-2xs cursor-pointer">Open Docs &rarr;</button>
                </div>
            </div>
            
            <div class="space-y-2.5 p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-zinc-800 hover:border-zinc-400 dark:hover:border-zinc-600 transition-all group">
                <div class="flex items-center gap-2">
                    <div class="size-7 rounded-lg bg-zinc-200/80 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                        <flux:icon name="bug-ant" class="size-4" />
                    </div>
                    <flux:heading size="md" class="font-semibold text-sm">Report an Issue</flux:heading>
                </div>
                <flux:text class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">Encountered a problem or want to suggest a new feature? You can file a detailed report directly through the floating help menu.</flux:text>
                <div class="pt-1">
                    <flux:modal.trigger name="report-issue-modal">
                        <button type="button" class="bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs px-3.5 py-1.5 rounded-xl border border-rose-500 active:scale-95 transition-all shadow-2xs cursor-pointer">File Bug Report</button>
                    </flux:modal.trigger>
                </div>
            </div>
            
            <div class="space-y-2.5 p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-zinc-800 md:col-span-2">
                <div class="flex items-center gap-2">
                    <div class="size-7 rounded-lg bg-zinc-200/80 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                        <flux:icon name="chat-bubble-left-right" class="size-4" />
                    </div>
                    <flux:heading size="md" class="font-semibold text-sm">Contact Administrator</flux:heading>
                </div>
                <flux:text class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">If you require immediate help or technical support for your user account (such as password resets or permission changes), contact platform administrator at <a href="mailto:admin@marikerja.com" class="text-zinc-900 dark:text-zinc-100 underline font-semibold">admin@marikerja.com</a>.</flux:text>
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <flux:modal.close>
                <flux:button variant="ghost" size="sm">Close</flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>

<!-- FAQ MODAL -->
<flux:modal name="faq-modal" class="w-[calc(100vw-2rem)] max-w-2xl backdrop:backdrop-blur-md z-[200]">
    <div class="space-y-5">
        <div class="flex items-center gap-3">
            <div class="size-10 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                <flux:icon name="information-circle" class="size-5" />
            </div>
            <div>
                <flux:heading size="lg" class="font-bold tracking-tight">Frequently Asked Questions</flux:heading>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Browse common questions grouped by feature. Click any question to expand it.</flux:text>
            </div>
        </div>
        
        <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-1 custom-scrollbar" x-data="{ open: null }">

            <!-- GENERAL -->
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-[10px] font-mono font-medium px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60 uppercase tracking-wider">
                        General
                    </span>
                </div>
                <div class="space-y-2">
                    <div class="bg-zinc-100/80 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-700/60 rounded-xl overflow-hidden transition-all">
                        <button type="button" @click="open = open === 'g1' ? null : 'g1'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-left text-zinc-800 dark:text-zinc-200 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors cursor-pointer" :class="open === 'g1' && 'text-zinc-900 dark:text-zinc-100'">
                            <span>What is Activity Tracker?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 'g1' && 'rotate-180 text-zinc-700 dark:text-zinc-300'" />
                        </button>
                        <div x-show="open === 'g1'" x-collapse class="px-4 pb-3" style="display:none">
                            <div class="p-3 bg-zinc-200/60 dark:bg-zinc-950 rounded-xl border-l-2 border-zinc-400 dark:border-zinc-600 text-xs text-zinc-800 dark:text-zinc-300 leading-relaxed">
                                Activity Tracker is a real-time time management platform for teams and individuals to log work hours, categorize tasks by project, and review productivity through an interactive dashboard.
                            </div>
                        </div>
                    </div>
                    <div class="bg-zinc-100/80 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-700/60 rounded-xl overflow-hidden transition-all">
                        <button type="button" @click="open = open === 'g2' ? null : 'g2'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-left text-zinc-800 dark:text-zinc-200 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors cursor-pointer" :class="open === 'g2' && 'text-zinc-900 dark:text-zinc-100'">
                            <span>Who can use this platform?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 'g2' && 'rotate-180 text-zinc-700 dark:text-zinc-300'" />
                        </button>
                        <div x-show="open === 'g2'" x-collapse class="px-4 pb-3" style="display:none">
                            <div class="p-3 bg-zinc-200/60 dark:bg-zinc-950 rounded-xl border-l-2 border-zinc-400 dark:border-zinc-600 text-xs text-zinc-800 dark:text-zinc-300 leading-relaxed">
                                Any registered user can track their activities, view their dashboard, and export reports. Admins additionally can manage workspace members, projects, categories, broadcast announcements, and review bug reports.
                            </div>
                        </div>
                    </div>
                    <div class="bg-zinc-100/80 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-700/40 rounded-xl overflow-hidden transition-all">
                        <button type="button" @click="open = open === 'g3' ? null : 'g3'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-left text-zinc-800 dark:text-zinc-200 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors cursor-pointer" :class="open === 'g3' && 'text-zinc-900 dark:text-zinc-100'">
                            <span>Is there a dark mode?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 'g3' && 'rotate-180 text-zinc-700 dark:text-zinc-300'" />
                        </button>
                        <div x-show="open === 'g3'" x-collapse class="px-4 pb-3" style="display:none">
                            <div class="p-3 bg-zinc-200/60 dark:bg-zinc-950 rounded-xl border-l-2 border-zinc-400 dark:border-zinc-600 text-xs text-zinc-800 dark:text-zinc-300 leading-relaxed">
                                Yes. Activity Tracker fully supports both light and dark modes. You can toggle the theme from the user menu in the navigation bar.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TRACKER -->
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-[10px] font-mono font-medium px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60 uppercase tracking-wider">
                        Tracker
                    </span>
                </div>
                <div class="space-y-2">
                    <div class="bg-zinc-100/80 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-700/40 rounded-xl overflow-hidden transition-all">
                        <button type="button" @click="open = open === 't1' ? null : 't1'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-left text-zinc-800 dark:text-zinc-200 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors cursor-pointer" :class="open === 't1' && 'text-zinc-900 dark:text-zinc-100'">
                            <span>How do I start tracking time?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 't1' && 'rotate-180 text-zinc-700 dark:text-zinc-300'" />
                        </button>
                        <div x-show="open === 't1'" x-collapse class="px-4 pb-3" style="display:none">
                            <div class="p-3 bg-zinc-200/60 dark:bg-zinc-950 rounded-xl border-l-2 border-zinc-400 dark:border-zinc-600 text-xs text-zinc-800 dark:text-zinc-300 leading-relaxed">
                                Go to the <strong>Tracker</strong> page, fill in your activity description, select a project and category, then click <strong>Start</strong>. A live timer will appear showing elapsed time. Click <strong>Stop</strong> when done.
                            </div>
                        </div>
                    </div>
                    <div class="bg-zinc-100/80 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-700/40 rounded-xl overflow-hidden transition-all">
                        <button type="button" @click="open = open === 't2' ? null : 't2'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-left text-zinc-800 dark:text-zinc-200 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors cursor-pointer" :class="open === 't2' && 'text-zinc-900 dark:text-zinc-100'">
                            <span>Can I run multiple activities at the same time?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 't2' && 'rotate-180 text-zinc-700 dark:text-zinc-300'" />
                        </button>
                        <div x-show="open === 't2'" x-collapse class="px-4 pb-3" style="display:none">
                            <div class="p-3 bg-zinc-200/60 dark:bg-zinc-950 rounded-xl border-l-2 border-zinc-400 dark:border-zinc-600 text-xs text-zinc-800 dark:text-zinc-300 leading-relaxed">
                                Yes. When you start a new activity while one is already running, the system detects it as <strong>parallel tracking</strong>. All running activities are shown simultaneously with live timers.
                            </div>
                        </div>
                    </div>
                    <div class="bg-zinc-100/80 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-700/40 rounded-xl overflow-hidden transition-all">
                        <button type="button" @click="open = open === 't3' ? null : 't3'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-left text-zinc-800 dark:text-zinc-200 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors cursor-pointer" :class="open === 't3' && 'text-zinc-900 dark:text-zinc-100'">
                            <span>Can I edit or delete a logged activity?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 't3' && 'rotate-180 text-zinc-700 dark:text-zinc-300'" />
                        </button>
                        <div x-show="open === 't3'" x-collapse class="px-4 pb-3" style="display:none">
                            <div class="p-3 bg-zinc-200/60 dark:bg-zinc-950 rounded-xl border-l-2 border-zinc-400 dark:border-zinc-600 text-xs text-zinc-800 dark:text-zinc-300 leading-relaxed">
                                Yes. In the activity history list on the Tracker page, each entry has a <strong>three-dot menu (⋯)</strong>. Click it to select <strong>Edit</strong> or <strong>Delete</strong>.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DASHBOARD -->
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-[10px] font-mono font-medium px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60 uppercase tracking-wider">
                        Dashboard
                    </span>
                </div>
                <div class="space-y-2">
                    <div class="bg-zinc-100/80 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-700/40 rounded-xl overflow-hidden transition-all">
                        <button type="button" @click="open = open === 'd1' ? null : 'd1'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-left text-zinc-800 dark:text-zinc-200 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors cursor-pointer" :class="open === 'd1' && 'text-zinc-900 dark:text-zinc-100'">
                            <span>What does the Activity Overview chart show?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 'd1' && 'rotate-180 text-zinc-700 dark:text-zinc-300'" />
                        </button>
                        <div x-show="open === 'd1'" x-collapse class="px-4 pb-3" style="display:none">
                            <div class="p-3 bg-zinc-200/60 dark:bg-zinc-950 rounded-xl border-l-2 border-zinc-400 dark:border-zinc-600 text-xs text-zinc-800 dark:text-zinc-300 leading-relaxed">
                                The chart visualizes your total tracked hours per period. Use the <strong>Weekly / Monthly</strong> toggle buttons to switch between a 7-day or 30-day view smoothly.
                            </div>
                        </div>
                    </div>
                    <div class="bg-zinc-100/80 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-700/40 rounded-xl overflow-hidden transition-all">
                        <button type="button" @click="open = open === 'd2' ? null : 'd2'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-left text-zinc-800 dark:text-zinc-200 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors cursor-pointer" :class="open === 'd2' && 'text-zinc-900 dark:text-zinc-100'">
                            <span>What is shown in Time Allocation?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 'd2' && 'rotate-180 text-zinc-700 dark:text-zinc-300'" />
                        </button>
                        <div x-show="open === 'd2'" x-collapse class="px-4 pb-3" style="display:none">
                            <div class="p-3 bg-zinc-200/60 dark:bg-zinc-950 rounded-xl border-l-2 border-zinc-400 dark:border-zinc-600 text-xs text-zinc-800 dark:text-zinc-300 leading-relaxed">
                                The Time Allocation card shows each project and its share of total tracked hours as a percentage bar. Click on a project row to expand category breakdowns.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECURITY -->
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-[10px] font-mono font-medium px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60 uppercase tracking-wider">
                        Security
                    </span>
                </div>
                <div class="space-y-2">
                    <div class="bg-zinc-100/80 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-700/40 rounded-xl overflow-hidden transition-all">
                        <button type="button" @click="open = open === 's1' ? null : 's1'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-left text-zinc-800 dark:text-zinc-200 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors cursor-pointer" :class="open === 's1' && 'text-zinc-900 dark:text-zinc-100'">
                            <span>What is Passkey (WebAuthn) authentication?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 's1' && 'rotate-180 text-zinc-700 dark:text-zinc-300'" />
                        </button>
                        <div x-show="open === 's1'" x-collapse class="px-4 pb-3" style="display:none">
                            <div class="p-3 bg-zinc-200/60 dark:bg-zinc-950 rounded-xl border-l-2 border-zinc-400 dark:border-zinc-600 text-xs text-zinc-800 dark:text-zinc-300 leading-relaxed">
                                Passkeys let you log in without a password using your device's biometrics (fingerprint or Face ID). You can configure it under <strong>Settings &rarr; Security</strong>.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="flex justify-end pt-2">
            <flux:modal.close>
                <flux:button variant="ghost" size="sm">Close</flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>

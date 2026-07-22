<!-- HELP CENTER MODAL -->
<flux:modal name="help-modal" class="min-w-[22rem] md:w-[38rem] backdrop:backdrop-blur-sm z-[200]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Help &amp; Support Center</flux:heading>
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
<flux:modal name="faq-modal" class="min-w-[22rem] md:w-[42rem] backdrop:backdrop-blur-sm z-[200]">
    <div class="space-y-5">
        <div>
            <flux:heading size="lg">Frequently Asked Questions</flux:heading>
            <flux:text class="mt-1">Browse common questions grouped by feature. Click any question to expand it.</flux:text>
        </div>
        
        <div class="space-y-5 max-h-[65vh] overflow-y-auto pr-1" x-data="{ open: null }">

            <!-- GENERAL -->
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <flux:icon name="information-circle" class="size-4 text-zinc-400" />
                    <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">General</span>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800 border border-zinc-100 dark:border-zinc-800 rounded-xl overflow-hidden">
                    <div>
                        <button type="button" @click="open = open === 'g1' ? null : 'g1'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-left text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer">
                            <span>What is Activity Tracker?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 'g1' && 'rotate-180'" />
                        </button>
                        <div x-show="open === 'g1'" x-collapse class="px-4 pb-3 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed" style="display:none">
                            Activity Tracker is a real-time time management platform for teams and individuals to log work hours, categorize tasks by project, and review productivity through an interactive dashboard.
                        </div>
                    </div>
                    <div>
                        <button type="button" @click="open = open === 'g2' ? null : 'g2'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-left text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer">
                            <span>Who can use this platform?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 'g2' && 'rotate-180'" />
                        </button>
                        <div x-show="open === 'g2'" x-collapse class="px-4 pb-3 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed" style="display:none">
                            Any registered user can track their activities, view their dashboard, and export reports. Admins additionally can manage workspace members, projects, categories, broadcast announcements, and review bug reports.
                        </div>
                    </div>
                    <div>
                        <button type="button" @click="open = open === 'g3' ? null : 'g3'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-left text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer">
                            <span>Is there a dark mode?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 'g3' && 'rotate-180'" />
                        </button>
                        <div x-show="open === 'g3'" x-collapse class="px-4 pb-3 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed" style="display:none">
                            Yes. Activity Tracker fully supports both light and dark modes. You can toggle the theme from the user menu in the top navigation bar — the preference is saved to your account settings.
                        </div>
                    </div>
                </div>
            </div>

            <!-- TRACKER -->
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <flux:icon name="clock" class="size-4 text-zinc-400" />
                    <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">Tracker</span>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800 border border-zinc-100 dark:border-zinc-800 rounded-xl overflow-hidden">
                    <div>
                        <button type="button" @click="open = open === 't1' ? null : 't1'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-left text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer">
                            <span>How do I start tracking time?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 't1' && 'rotate-180'" />
                        </button>
                        <div x-show="open === 't1'" x-collapse class="px-4 pb-3 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed" style="display:none">
                            Go to the <strong>Tracker</strong> page, fill in your activity description, select a project and category, then click <strong>Start</strong>. A live timer will appear showing elapsed time. Click <strong>Stop</strong> when you are done.
                        </div>
                    </div>
                    <div>
                        <button type="button" @click="open = open === 't2' ? null : 't2'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-left text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer">
                            <span>Can I run multiple activities at the same time?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 't2' && 'rotate-180'" />
                        </button>
                        <div x-show="open === 't2'" x-collapse class="px-4 pb-3 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed" style="display:none">
                            Yes. When you start a new activity while one is already running, the system detects it as <strong>parallel tracking</strong>. All running activities are shown simultaneously with their own live timers on the Dashboard and Tracker page.
                        </div>
                    </div>
                    <div>
                        <button type="button" @click="open = open === 't3' ? null : 't3'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-left text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer">
                            <span>Can I edit or delete a logged activity?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 't3' && 'rotate-180'" />
                        </button>
                        <div x-show="open === 't3'" x-collapse class="px-4 pb-3 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed" style="display:none">
                            Yes. In the activity history list on the Tracker page, each entry has a <strong>three-dot menu (⋯)</strong>. Click it to select <strong>Edit</strong> (to modify start/end time or description) or <strong>Delete</strong> to permanently remove it.
                        </div>
                    </div>
                    <div>
                        <button type="button" @click="open = open === 't4' ? null : 't4'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-left text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer">
                            <span>How do I filter history by date?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 't4' && 'rotate-180'" />
                        </button>
                        <div x-show="open === 't4'" x-collapse class="px-4 pb-3 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed" style="display:none">
                            Use the <strong>date range picker</strong> at the top of the Tracker page. You can choose quick presets (Today, Yesterday, This Week, This Month) or set a custom date range to filter your activity history.
                        </div>
                    </div>
                </div>
            </div>

            <!-- DASHBOARD -->
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <flux:icon name="chart-bar" class="size-4 text-zinc-400" />
                    <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">Dashboard</span>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800 border border-zinc-100 dark:border-zinc-800 rounded-xl overflow-hidden">
                    <div>
                        <button type="button" @click="open = open === 'd1' ? null : 'd1'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-left text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer">
                            <span>What does the Activity Overview chart show?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 'd1' && 'rotate-180'" />
                        </button>
                        <div x-show="open === 'd1'" x-collapse class="px-4 pb-3 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed" style="display:none">
                            The bar chart visualizes your total tracked hours per period. Use the <strong>Weekly / Monthly / Yearly</strong> toggle buttons to switch between a 7-day view, 30-day view, or 12-month view — all update smoothly without reloading the page.
                        </div>
                    </div>
                    <div>
                        <button type="button" @click="open = open === 'd2' ? null : 'd2'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-left text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer">
                            <span>What is shown in Time Allocation?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 'd2' && 'rotate-180'" />
                        </button>
                        <div x-show="open === 'd2'" x-collapse class="px-4 pb-3 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed" style="display:none">
                            The Time Allocation card shows each project and its share of total tracked hours as a percentage bar. Click on a project row to expand and reveal a breakdown of time per category within that project.
                        </div>
                    </div>
                    <div>
                        <button type="button" @click="open = open === 'd3' ? null : 'd3'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-left text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer">
                            <span>Can I stop a running activity from the Dashboard?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 'd3' && 'rotate-180'" />
                        </button>
                        <div x-show="open === 'd3'" x-collapse class="px-4 pb-3 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed" style="display:none">
                            Yes. The "Currently Working On" section on the Dashboard displays all your running activities with live timers. Each card has a <strong>Stop</strong> button that ends the activity immediately.
                        </div>
                    </div>
                </div>
            </div>

            <!-- PROJECTS & CATEGORIES -->
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <flux:icon name="folder" class="size-4 text-zinc-400" />
                    <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">Projects &amp; Categories</span>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800 border border-zinc-100 dark:border-zinc-800 rounded-xl overflow-hidden">
                    <div>
                        <button type="button" @click="open = open === 'm1' ? null : 'm1'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-left text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer">
                            <span>How do I add a new project or category?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 'm1' && 'rotate-180'" />
                        </button>
                        <div x-show="open === 'm1'" x-collapse class="px-4 pb-3 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed" style="display:none">
                            Go to the <strong>Manage</strong> page from the sidebar. You will find separate sections for Projects and Categories, each with an input field and an <strong>Add</strong> button to create new entries instantly.
                        </div>
                    </div>
                    <div>
                        <button type="button" @click="open = open === 'm2' ? null : 'm2'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-left text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer">
                            <span>Can I rename or delete a project?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 'm2' && 'rotate-180'" />
                        </button>
                        <div x-show="open === 'm2'" x-collapse class="px-4 pb-3 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed" style="display:none">
                            Yes. On the Manage page, each project and category has inline <strong>Edit</strong> and <strong>Delete</strong> action buttons. Editing replaces the name in place; deleting is permanent and cannot be undone.
                        </div>
                    </div>
                </div>
            </div>

            <!-- EXPORT & IMPORT -->
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <flux:icon name="arrow-down-tray" class="size-4 text-zinc-400" />
                    <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">Export &amp; Import</span>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800 border border-zinc-100 dark:border-zinc-800 rounded-xl overflow-hidden">
                    <div>
                        <button type="button" @click="open = open === 'e1' ? null : 'e1'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-left text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer">
                            <span>How do I export my activity history?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 'e1' && 'rotate-180'" />
                        </button>
                        <div x-show="open === 'e1'" x-collapse class="px-4 pb-3 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed" style="display:none">
                            On the Tracker page, use the date range filter to select the period you want, then click the <strong>Export</strong> button. Your browser will download an Excel (.xlsx) file containing all activities within that date range.
                        </div>
                    </div>
                    <div>
                        <button type="button" @click="open = open === 'e2' ? null : 'e2'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-left text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer">
                            <span>Can I import activities from Excel?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 'e2' && 'rotate-180'" />
                        </button>
                        <div x-show="open === 'e2'" x-collapse class="px-4 pb-3 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed" style="display:none">
                            Yes. Click the <strong>Import</strong> button on the Tracker page and upload an Excel file that matches the exported format. The system will parse and add all valid rows to your activity history automatically.
                        </div>
                    </div>
                </div>
            </div>

            <!-- MEMBERS (ADMIN) -->
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <flux:icon name="users" class="size-4 text-zinc-400" />
                    <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">Members (Admin Only)</span>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800 border border-zinc-100 dark:border-zinc-800 rounded-xl overflow-hidden">
                    <div>
                        <button type="button" @click="open = open === 'mem1' ? null : 'mem1'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-left text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer">
                            <span>How do I manage workspace members?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 'mem1' && 'rotate-180'" />
                        </button>
                        <div x-show="open === 'mem1'" x-collapse class="px-4 pb-3 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed" style="display:none">
                            Admins can access the <strong>Members</strong> page from the sidebar. You can search members, filter by role (All / Admin / Member), and toggle admin privileges for any user using the Admin toggle on each member card.
                        </div>
                    </div>
                    <div>
                        <button type="button" @click="open = open === 'mem2' ? null : 'mem2'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-left text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer">
                            <span>How does the broadcast announcement work?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 'mem2' && 'rotate-180'" />
                        </button>
                        <div x-show="open === 'mem2'" x-collapse class="px-4 pb-3 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed" style="display:none">
                            Admins can send a broadcast message to all workspace members via the <strong>Broadcast</strong> page. All users will receive the message as a notification in their notification panel instantly.
                        </div>
                    </div>
                </div>
            </div>

            <!-- NOTIFICATIONS -->
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <flux:icon name="bell" class="size-4 text-zinc-400" />
                    <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">Notifications</span>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800 border border-zinc-100 dark:border-zinc-800 rounded-xl overflow-hidden">
                    <div>
                        <button type="button" @click="open = open === 'n1' ? null : 'n1'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-left text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer">
                            <span>Where can I see my notifications?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 'n1' && 'rotate-180'" />
                        </button>
                        <div x-show="open === 'n1'" x-collapse class="px-4 pb-3 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed" style="display:none">
                            Click the <strong>bell icon</strong> in the top navigation bar to open the notification panel. Unread notifications are highlighted, and you can mark individual ones as read or clear all at once.
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECURITY -->
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <flux:icon name="shield-check" class="size-4 text-zinc-400" />
                    <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">Security</span>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800 border border-zinc-100 dark:border-zinc-800 rounded-xl overflow-hidden">
                    <div>
                        <button type="button" @click="open = open === 's1' ? null : 's1'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-left text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer">
                            <span>What is Passkey (WebAuthn) authentication?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 's1' && 'rotate-180'" />
                        </button>
                        <div x-show="open === 's1'" x-collapse class="px-4 pb-3 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed" style="display:none">
                            Passkeys let you log in without a password using your device's biometric (fingerprint or Face ID). Once registered, you can approve login instantly from your Settings &rarr; Security page. It is faster and more secure than a traditional password.
                        </div>
                    </div>
                    <div>
                        <button type="button" @click="open = open === 's2' ? null : 's2'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-left text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer">
                            <span>How do I enable Two-Factor Authentication (2FA)?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 's2' && 'rotate-180'" />
                        </button>
                        <div x-show="open === 's2'" x-collapse class="px-4 pb-3 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed" style="display:none">
                            Go to <strong>Settings &rarr; Security</strong>, find the Two-Factor Authentication section, and click <strong>Enable</strong>. Scan the QR code with your authenticator app (Google Authenticator, Authy, etc.) and confirm with the generated code.
                        </div>
                    </div>
                    <div>
                        <button type="button" @click="open = open === 's3' ? null : 's3'" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-left text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer">
                            <span>How do I report a bug or suggest a feature?</span>
                            <flux:icon name="chevron-down" class="size-4 text-zinc-400 shrink-0 transition-transform duration-200" ::class="open === 's3' && 'rotate-180'" />
                        </button>
                        <div x-show="open === 's3'" x-collapse class="px-4 pb-3 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed" style="display:none">
                            Click the <strong>Help / Question Mark (?) floating button</strong> in the top navigation, then select <strong>Report Bug</strong>. Fill in the title, describe the issue, and submit. An admin will receive it and follow up via the Issues page.
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="flex justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Close</flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>

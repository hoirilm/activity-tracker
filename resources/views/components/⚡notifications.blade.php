<?php

use Livewire\Component;
use App\Models\Notification;

new class extends Component
{
    public $isOpen = false;

    public function mount()
    {
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->notifications()->count() === 0) {
                // Seed 5 sample notifications
                $user->notifications()->createMany([
                    [
                        'title' => 'Welcome to MariKerja! 🚀',
                        'body' => 'Thank you for using Activity Tracker. You can now monitor and track your daily tasks in real-time.',
                        'type' => 'info',
                        'created_at' => now()->subHours(24),
                    ],
                    [
                        'title' => 'New: Help & Support Center',
                        'body' => 'Need help? Click the question mark icon at the bottom-left to access FAQs, guides, or submit bug reports.',
                        'type' => 'success',
                        'created_at' => now()->subHours(8),
                    ],
                    [
                        'title' => 'Daily Reports Activated',
                        'body' => 'Your workspace is now configured to automatically summarize and email your activity logs daily.',
                        'type' => 'info',
                        'created_at' => now()->subHours(2),
                    ],
                    [
                        'title' => 'Multi-Tenancy Security',
                        'body' => 'All your projects, categories, and logs are now fully isolated and encrypted per user account.',
                        'type' => 'success',
                        'created_at' => now()->subMinutes(45),
                    ],
                    [
                        'title' => 'System Update: v1.1.0',
                        'body' => 'We successfully migrated to Semantic Versioning v1.1.0 with a new design layout.',
                        'type' => 'info',
                        'created_at' => now()->subMinutes(5),
                    ],
                ]);
            }
        }
    }

    public function toggle()
    {
        $this->isOpen = !$this->isOpen;
    }

    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->find($id);
        if ($notification) {
            $notification->update(['read_at' => now()]);
        }
    }

    public function clearAll()
    {
        auth()->user()->notifications()->delete();
    }

    public function getNotificationsProperty()
    {
        return auth()->user()->notifications()->take(5)->get();
    }

    public function getUnreadCountProperty()
    {
        return auth()->user()->notifications()->whereNull('read_at')->count();
    }
};
?>

<div>
    <!-- Floating Bell Icon Trigger Button -->
    @if(auth()->check())
    <div class="fixed top-4 right-4 z-40 hidden lg:block">
        <button wire:click="toggle" 
                type="button" 
                class="shadow-md flex items-center justify-center size-10 rounded-full bg-zinc-800 hover:bg-zinc-700 text-white dark:bg-zinc-100 dark:hover:bg-zinc-200 dark:text-zinc-900 transition-all duration-300 transform active:scale-95 cursor-pointer relative">
            <flux:icon name="bell" class="size-5" />
            @if($this->unreadCount > 0)
                <span class="absolute -top-1 -right-1 flex items-center justify-center size-5 bg-red-500 text-white text-[10px] font-bold rounded-full border-2 border-white dark:border-zinc-800">
                    {{ $this->unreadCount }}
                </span>
            @endif
        </button>
    </div>
    @endif

    <!-- macOS style Notification Drawer -->
    <div x-data="{ show: @entangle('isOpen') }" 
         x-show="show"
         @click.outside="show = false; $wire.set('isOpen', false)"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed top-0 right-0 h-full w-80 bg-zinc-100/70 dark:bg-zinc-950/70 backdrop-blur-xl border-l border-zinc-200/50 dark:border-zinc-800/30 shadow-2xl z-50 p-4 flex flex-col justify-between"
         style="display: none;">
        
        <div class="space-y-4 overflow-y-auto pr-1">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-zinc-200/50 dark:border-zinc-800/30 pb-3">
                <div class="flex items-center gap-2">
                    <flux:icon name="bell" class="size-5 text-zinc-500 dark:text-zinc-400" />
                    <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Notification Center</span>
                </div>
                <button @click="show = false; $wire.set('isOpen', false)" class="text-zinc-400 hover:text-zinc-650 dark:hover:text-zinc-200 transition-colors cursor-pointer">
                    <flux:icon name="x-mark" class="size-5" />
                </button>
            </div>

            <!-- Notifications List -->
            <div class="space-y-3">
                @forelse($this->notifications as $notif)
                    <div wire:key="notif-{{ $notif->id }}" 
                         class="group relative bg-white/85 dark:bg-zinc-900/80 border border-zinc-200/40 dark:border-zinc-800/30 backdrop-blur-md rounded-2xl p-3.5 shadow-xs hover:shadow-sm transition-all duration-200 {{ $notif->read_at ? 'opacity-60' : '' }}">
                        
                        <!-- macOS Banner Header -->
                        <div class="flex items-center justify-between text-[11px] text-zinc-400 dark:text-zinc-500 mb-1.5">
                            <div class="flex items-center gap-1.5">
                                <div class="size-3.5 rounded bg-zinc-800 dark:bg-zinc-200 flex items-center justify-center">
                                    <span class="text-[8px] font-bold text-white dark:text-zinc-900">M</span>
                                </div>
                                <span class="font-medium text-zinc-650 dark:text-zinc-450 uppercase tracking-wider text-[9px]">MariKerja</span>
                                <span>•</span>
                                <span>{{ $notif->created_at->diffForHumans(null, true) }}</span>
                            </div>
                            
                            @if(!$notif->read_at)
                                <button wire:click="markAsRead({{ $notif->id }})" class="opacity-0 group-hover:opacity-100 text-zinc-400 hover:text-zinc-650 dark:hover:text-zinc-200 transition-opacity duration-150 cursor-pointer">
                                    <flux:icon name="check" class="size-3.5" />
                                </button>
                            @endif
                        </div>

                        <!-- Banner Title & Body -->
                        <h4 class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 mb-0.5">{{ $notif->title }}</h4>
                        <p class="text-[11px] leading-snug text-zinc-500 dark:text-zinc-400">{{ $notif->body }}</p>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-12 text-center space-y-2">
                        <div class="size-12 rounded-full bg-zinc-200/50 dark:bg-zinc-800/50 flex items-center justify-center text-zinc-400">
                            <flux:icon name="bell" class="size-6" />
                        </div>
                        <flux:text class="text-xs text-zinc-400">No recent notifications</flux:text>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Footer actions -->
        @if($this->notifications->count() > 0)
            <div class="border-t border-zinc-200/50 dark:border-zinc-800/30 pt-3 flex justify-end gap-2">
                <flux:button wire:click="clearAll" variant="ghost" size="xs" class="cursor-pointer">Clear All</flux:button>
            </div>
        @endif
    </div>
</div>

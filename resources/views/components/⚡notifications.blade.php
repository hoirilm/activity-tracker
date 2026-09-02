<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Notification;
use Illuminate\Support\Str;

new class extends Component
{
    public ?int $lastNotificationId = null;

    public function mount()
    {
        if (auth()->check()) {
            $this->lastNotificationId = auth()->user()->notifications()->latest()->value('id');
        }
    }

    #[On('notify')]
    #[On('refresh-notifications')]
    #[On('broadcast-sent')]
    public function handleNewNotificationEvent()
    {
        if (!auth()->check()) return;

        $latestId = auth()->user()->notifications()->latest()->value('id');
        $this->lastNotificationId = $latestId;
        $this->dispatch('auto-show-notification');
    }

    public function pollNewNotifications()
    {
        if (!auth()->check()) return;

        $latestId = auth()->user()->notifications()->latest()->value('id');
        if ($latestId && $latestId !== $this->lastNotificationId) {
            $this->lastNotificationId = $latestId;
            $this->dispatch('auto-show-notification');
        }
    }

    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->find($id);
        if ($notification) {
            $notification->update(['read_at' => now()]);
        }
    }

    public function dismiss($id)
    {
        $notification = auth()->user()->notifications()->find($id);
        if ($notification) {
            $notification->delete();
        }
    }

    public function clearAll()
    {
        auth()->user()->notifications()->delete();
    }

    public function markAllAsRead()
    {
        auth()->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function getNotificationsProperty()
    {
        return auth()->user()->notifications()->take(10)->get();
    }

    public function getUnreadCountProperty()
    {
        return auth()->user()->notifications()->whereNull('read_at')->count();
    }
};
?>

<div x-data="{ 
        open: false, 
        expanded: false 
     }" 
     x-on:auto-show-notification.window="open = true; expanded = false"
     x-on:toggle-notifications.window="open = !open"
     wire:poll.4000ms="pollNewNotifications"
     class="relative">

    <!-- Floating Bell Icon Trigger Button in Top Right -->
    @if(auth()->check())
    <div class="fixed top-3.5 right-3.5 sm:top-4 sm:right-4 z-40">
        <button @click="open = !open" 
                type="button" 
                class="flex items-center justify-center size-8.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white hover:border-zinc-300 dark:hover:border-zinc-600 shadow-xs transition-all duration-200 active:scale-95 cursor-pointer relative"
                title="Notification Center">
            <flux:icon name="bell" class="size-4" />
            @if($this->unreadCount > 0)
                <span class="absolute -top-1 -right-1 flex items-center justify-center size-3.5 bg-rose-500 text-white text-[8px] font-mono font-bold rounded-full border border-white dark:border-zinc-900 shadow-xs">
                    {{ $this->unreadCount }}
                </span>
            @endif
        </button>
    </div>
    @endif

    <!-- macOS Floating Notification Popup -->
    <div x-show="open"
         @click.outside="open = false; expanded = false"
         @keydown.escape.window="open = false; expanded = false"
         x-transition:enter="transition ease-out duration-150 transform"
         x-transition:enter-start="-translate-y-1 scale-98"
         x-transition:enter-end="translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-100 transform"
         x-transition:leave-start="translate-y-0 scale-100"
         x-transition:leave-end="-translate-y-1 scale-98"
         class="fixed top-3.5 right-3.5 sm:top-4 sm:right-4 z-50 w-[310px] sm:w-[340px] max-w-[calc(100vw-1.75rem)] select-none font-sans"
         style="display: none;">

        @if($this->notifications->count() > 0)
            @php 
                $latest = $this->notifications->first(); 
                $totalCount = $this->notifications->count();
                $cleanLatestTitle = Str::replace(['📢 ', '📢'], '', $latest->title);
            @endphp

            <!-- Solid Instant Container (Responsive Light & Dark Mode) -->
            <div class="rounded-2xl p-3.5 transition-all duration-200 ease-out relative overflow-hidden bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 border border-zinc-200 dark:border-zinc-700 shadow-2xl">

                <!-- Header Bar -->
                <div class="flex items-center justify-between px-0.5 pb-2.5 mb-1.5 border-b border-zinc-200/60 dark:border-zinc-800/80">
                    <div class="flex items-center gap-2">
                        <flux:icon name="bell" class="size-4 text-zinc-700 dark:text-zinc-300" />
                        <span class="text-xs font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Notifications</span>
                    </div>

                    <div class="flex items-center gap-1.5">
                        @if($totalCount > 1)
                            <button type="button" 
                                    @click="expanded = !expanded" 
                                    class="px-2.5 py-0.5 rounded-full bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-[10px] font-medium border border-zinc-200 dark:border-zinc-700 shadow-xs transition-all cursor-pointer">
                                <span x-text="expanded ? 'Show less' : '+{{ $totalCount - 1 }} more'"></span>
                            </button>
                        @endif

                        <button type="button" 
                                @click="open = false; expanded = false" 
                                class="size-5 rounded-full bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white flex items-center justify-center text-[10px] transition-all cursor-pointer"
                                title="Close">
                            ✕
                        </button>
                    </div>
                </div>

                <!-- Notifications Deck -->
                <div class="space-y-2">
                    <!-- Top / Latest Notification Card (Always Visible) -->
                    <div wire:key="mac-notif-{{ $latest->id }}"
                         @click="!expanded && (expanded = true)"
                         class="group relative rounded-xl p-3 bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/80 dark:border-zinc-700/60 hover:bg-zinc-100 dark:hover:bg-zinc-800/90 transition-all duration-200 cursor-pointer shadow-xs {{ $latest->read_at ? 'opacity-60' : '' }}">

                        <div>
                            <!-- Top Bar: Status Bar on Left & Timestamp on Far Top-Right -->
                            <div class="flex items-center justify-between gap-1.5 mb-1.5">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    @if($latest->type === 'success' || str_contains(strtolower($latest->title), 'success'))
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 tracking-wide">
                                            <span class="size-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(52,211,153,0.8)]"></span>
                                            <span>Success</span>
                                        </span>
                                    @elseif($latest->type === 'warning' || str_contains(strtolower($latest->title), 'warning'))
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-amber-600 dark:text-amber-400 tracking-wide">
                                            <span class="size-1.5 rounded-full bg-amber-500 shadow-[0_0_8px_rgba(251,191,36,0.8)]"></span>
                                            <span>Warning</span>
                                        </span>
                                    @elseif($latest->type === 'danger' || str_contains(strtolower($latest->title), 'error') || str_contains(strtolower($latest->title), 'failed'))
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-rose-600 dark:text-rose-400 tracking-wide">
                                            <span class="size-1.5 rounded-full bg-rose-500 shadow-[0_0_8px_rgba(251,113,133,0.8)]"></span>
                                            <span>Alert</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-sky-600 dark:text-sky-400 tracking-wide">
                                            <span class="size-1.5 rounded-full bg-sky-500 shadow-[0_0_8px_rgba(56,189,248,0.8)]"></span>
                                            <span>Info</span>
                                        </span>
                                    @endif
                                </div>

                                <span class="text-[10px] text-zinc-500 dark:text-zinc-400 font-normal shrink-0">{{ $latest->created_at->diffForHumans(null, true) }}</span>
                            </div>

                            <!-- Notification Title -->
                            <h4 class="font-semibold text-xs text-zinc-900 dark:text-zinc-100 tracking-tight leading-snug">
                                {{ $cleanLatestTitle }}
                            </h4>

                            <!-- Markdown Message Body (Clean Left-aligned, No Indentation) -->
                            <div class="text-[11.5px] leading-relaxed text-zinc-700 dark:text-zinc-300 mt-1 font-sans break-words text-left [&>p]:m-0 [&>p]:leading-relaxed [&>ul]:list-disc [&>ul]:pl-4 [&>ol]:list-decimal [&>ol]:pl-4 [&>h1]:text-xs [&>h1]:font-bold [&>h2]:text-xs [&>h2]:font-bold [&>h3]:text-xs [&>h3]:font-bold [&>code]:bg-zinc-200 dark:[&>code]:bg-zinc-700/50 [&>code]:px-1 [&>code]:rounded">
                                {!! Str::markdown(trim($latest->body)) !!}
                            </div>

                            <!-- Bottom Row: Mark as read button on Bottom Right -->
                            <div class="flex items-center justify-end mt-1.5">
                                @if(!$latest->read_at)
                                    <button type="button" 
                                            wire:click.stop="markAsRead({{ $latest->id }})" 
                                            class="text-[10px] text-zinc-500 hover:text-emerald-600 dark:text-zinc-400 dark:hover:text-emerald-400 font-medium flex items-center gap-1 transition-colors cursor-pointer"
                                            title="Mark as read">
                                        <span>Mark as read</span>
                                        <flux:icon name="check" class="size-2.5" />
                                    </button>
                                @else
                                    <span class="text-[10px] text-zinc-400 dark:text-zinc-500 font-medium flex items-center gap-1">
                                        <span>Read</span>
                                        <flux:icon name="check" class="size-2.5 opacity-60" />
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Older Notifications (Smooth CSS Grid Row Expansion Animation) -->
                    @if($totalCount > 1)
                        <div class="grid transition-all duration-350 ease-[cubic-bezier(0.16,1,0.3,1)]"
                             :class="expanded ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0 pointer-events-none'">
                            <div class="overflow-hidden space-y-2">
                                <div class="space-y-2 max-h-[50vh] overflow-y-auto custom-scrollbar pr-0.5 pt-0.5">
                                    @foreach($this->notifications->skip(1) as $notif)
                                        @php 
                                            $cleanTitle = Str::replace(['📢 ', '📢'], '', $notif->title);
                                        @endphp
                                        <div wire:key="mac-notif-{{ $notif->id }}" 
                                             class="group relative rounded-xl p-3 bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/80 dark:border-zinc-700/60 hover:bg-zinc-100 dark:hover:bg-zinc-800/90 transition-all duration-200 shadow-xs {{ $notif->read_at ? 'opacity-60' : '' }}">

                                            <div>
                                                <!-- Top Bar: Status Bar on Left & Timestamp on Far Top-Right -->
                                                <div class="flex items-center justify-between gap-1.5 mb-1.5">
                                                    <div class="flex items-center gap-1.5 min-w-0">
                                                        @if($notif->type === 'success' || str_contains(strtolower($notif->title), 'success'))
                                                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 tracking-wide">
                                                                <span class="size-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(52,211,153,0.8)]"></span>
                                                                <span>Success</span>
                                                            </span>
                                                        @elseif($notif->type === 'warning' || str_contains(strtolower($notif->title), 'warning'))
                                                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-amber-600 dark:text-amber-400 tracking-wide">
                                                                <span class="size-1.5 rounded-full bg-amber-500 shadow-[0_0_8px_rgba(251,191,36,0.8)]"></span>
                                                                <span>Warning</span>
                                                            </span>
                                                        @elseif($notif->type === 'danger' || str_contains(strtolower($notif->title), 'error') || str_contains(strtolower($notif->title), 'failed'))
                                                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-rose-600 dark:text-rose-400 tracking-wide">
                                                                <span class="size-1.5 rounded-full bg-rose-500 shadow-[0_0_8px_rgba(251,113,133,0.8)]"></span>
                                                                <span>Alert</span>
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-sky-600 dark:text-sky-400 tracking-wide">
                                                                <span class="size-1.5 rounded-full bg-sky-500 shadow-[0_0_8px_rgba(56,189,248,0.8)]"></span>
                                                                <span>Info</span>
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <span class="text-[10px] text-zinc-500 dark:text-zinc-400 font-normal shrink-0">{{ $notif->created_at->diffForHumans(null, true) }}</span>
                                                </div>

                                                <!-- Notification Title -->
                                                <h4 class="font-semibold text-xs text-zinc-900 dark:text-zinc-100 tracking-tight leading-snug">
                                                    {{ $cleanTitle }}
                                                </h4>

                                                <!-- Markdown Message Body (Clean Left-aligned, No Indentation) -->
                                                <div class="text-[11.5px] leading-relaxed text-zinc-700 dark:text-zinc-300 mt-1 font-sans break-words text-left [&>p]:m-0 [&>p]:leading-relaxed [&>ul]:list-disc [&>ul]:pl-4 [&>ol]:list-decimal [&>ol]:pl-4 [&>h1]:text-xs [&>h1]:font-bold [&>h2]:text-xs [&>h2]:font-bold [&>h3]:text-xs [&>h3]:font-bold [&>code]:bg-zinc-200 dark:[&>code]:bg-zinc-700/50 [&>code]:px-1 [&>code]:rounded">
                                                    {!! Str::markdown(trim($notif->body)) !!}
                                                </div>

                                                <!-- Bottom Row: Mark as read button on Bottom Right -->
                                                <div class="flex items-center justify-end mt-1.5">
                                                    @if(!$notif->read_at)
                                                        <button type="button" 
                                                                wire:click.stop="markAsRead({{ $notif->id }})" 
                                                                class="text-[10px] text-zinc-500 hover:text-emerald-600 dark:text-zinc-400 dark:hover:text-emerald-400 font-medium flex items-center gap-1 transition-colors cursor-pointer"
                                                                title="Mark as read">
                                                            <span>Mark as read</span>
                                                            <flux:icon name="check" class="size-2.5" />
                                                        </button>
                                                    @else
                                                        <span class="text-[10px] text-zinc-400 dark:text-zinc-500 font-medium flex items-center gap-1">
                                                            <span>Read</span>
                                                            <flux:icon name="check" class="size-2.5 opacity-60" />
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Footer Actions -->
                                <div class="pt-1.5 flex items-center justify-between text-[10px] px-1">
                                    <button type="button" wire:click="markAllAsRead" class="text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200 font-medium transition-colors cursor-pointer">
                                        Mark all as read
                                    </button>
                                    <button type="button" wire:click="clearAll" class="text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 font-medium transition-colors cursor-pointer">
                                        Clear all
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

            </div>

        @else
            <!-- Empty Notifications State (Responsive Light & Dark Mode) -->
            <div class="rounded-2xl p-4 text-center bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 border border-zinc-200 dark:border-zinc-700 shadow-2xl">
                
                <!-- Header Bar -->
                <div class="flex items-center justify-between pb-2.5 mb-2 border-b border-zinc-200/60 dark:border-zinc-800/80">
                    <div class="flex items-center gap-2">
                        <flux:icon name="bell" class="size-4 text-zinc-700 dark:text-zinc-300" />
                        <span class="text-xs font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Notifications</span>
                    </div>

                    <button @click="open = false" 
                            class="size-5 rounded-full bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white flex items-center justify-center text-[10px] transition-all cursor-pointer"
                            title="Close">
                        ✕
                    </button>
                </div>

                <div class="py-4 flex flex-col items-center justify-center gap-1.5">
                    <flux:icon name="bell" class="size-5 text-zinc-400 dark:text-zinc-500" />
                    <span class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">No Notifications</span>
                    <span class="text-[10px] text-zinc-500 dark:text-zinc-400">You're all caught up for today.</span>
                </div>
            </div>
        @endif
    </div>
</div>





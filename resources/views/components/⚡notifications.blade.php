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
     wire:poll.1500ms="pollNewNotifications"
     class="relative">

    <!-- Floating Bell Icon Trigger Button in Top Right -->
    @if(auth()->check())
    <div class="fixed top-3.5 right-3.5 sm:top-4 sm:right-4 z-40">
        <button @click="open = !open" 
                type="button" 
                class="flex items-center justify-center size-8.5 rounded-xl bg-white/80 dark:bg-zinc-900/80 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800/80 text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white hover:border-zinc-300 dark:hover:border-zinc-700 shadow-xs transition-all duration-300 active:scale-95 cursor-pointer relative"
                title="Notification Center">
            <flux:icon name="bell" class="size-4" />
            @if($this->unreadCount > 0)
                <span class="absolute -top-1 -right-1 flex items-center justify-center size-3.5 bg-red-500 text-white text-[8px] font-mono font-bold rounded-full border border-white dark:border-zinc-900 shadow-xs">
                    {{ $this->unreadCount }}
                </span>
            @endif
        </button>
    </div>
    @endif

    <!-- macOS Floating Notification Popup (True Frosted Glass Overlapping Top Right) -->
    <div x-show="open"
         @click.outside="open = false; expanded = false"
         @keydown.escape.window="open = false; expanded = false"
         x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-300 transform"
         x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition cubic-bezier(0.16, 1, 0.3, 1) duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
         class="fixed top-3.5 right-3.5 sm:top-4 sm:right-4 z-50 w-[310px] sm:w-[340px] max-w-[calc(100vw-1.75rem)] select-none font-sans"
         style="display: none;">

        @if($this->notifications->count() > 0)
            @php 
                $latest = $this->notifications->first(); 
                $totalCount = $this->notifications->count();
                $cleanLatestTitle = Str::replace(['📢 ', '📢'], '', $latest->title);
            @endphp

            <!-- ========================================== -->
            <!-- GAMBAR 1: COLLAPSED STATE (Single Stack Card) -->
            <!-- ========================================== -->
            <div x-show="!expanded" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="relative cursor-pointer group"
                 @click="expanded = true">
                
                <!-- Stack Deck Layers Underneath with True Frosted Glass -->
                @if($totalCount > 1)
                    <div class="absolute -bottom-1 inset-x-2.5 h-6 rounded-2xl -z-10 shadow-md scale-[0.98] transition-all"
                         style="background-color: rgba(24, 24, 28, 0.45); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1);"></div>
                @endif
                @if($totalCount > 2)
                    <div class="absolute -bottom-2 inset-x-5 h-6 rounded-2xl -z-20 shadow-sm scale-[0.96] transition-all"
                         style="background-color: rgba(24, 24, 28, 0.3); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.06);"></div>
                @endif

                <!-- Main Top Card (Apple Frosted Glass) -->
                <div class="rounded-2xl p-3.5 transition-all duration-200 relative overflow-hidden"
                     style="background-color: rgba(22, 22, 26, 0.68); backdrop-filter: blur(36px) saturate(190%); -webkit-backdrop-filter: blur(36px) saturate(190%); box-shadow: 0 16px 40px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.14);">
                    
                    <div>
                        <!-- Status Bar / Type Indicator, Timestamp & Close Button -->
                        <div class="flex items-center justify-between gap-1.5 mb-1.5">
                            <div class="flex items-center gap-1.5 min-w-0">
                                @if($latest->type === 'success' || str_contains(strtolower($latest->title), 'success'))
                                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-400 tracking-wide">
                                        <span class="size-1.5 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]"></span>
                                        <span>Success</span>
                                    </span>
                                @elseif($latest->type === 'warning' || str_contains(strtolower($latest->title), 'warning'))
                                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-amber-400 tracking-wide">
                                        <span class="size-1.5 rounded-full bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.8)]"></span>
                                        <span>Warning</span>
                                    </span>
                                @elseif($latest->type === 'danger' || str_contains(strtolower($latest->title), 'error') || str_contains(strtolower($latest->title), 'failed'))
                                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-rose-400 tracking-wide">
                                        <span class="size-1.5 rounded-full bg-rose-400 shadow-[0_0_8px_rgba(251,113,133,0.8)]"></span>
                                        <span>Alert</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-sky-400 tracking-wide">
                                        <span class="size-1.5 rounded-full bg-sky-400 shadow-[0_0_8px_rgba(56,189,248,0.8)]"></span>
                                        <span>Info</span>
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-[10px] text-zinc-400 font-normal">{{ $latest->created_at->diffForHumans(null, true) }}</span>
                                
                                <button type="button" 
                                        @click.stop="open = false; expanded = false" 
                                        class="size-5 rounded-full bg-white/10 hover:bg-white/20 text-zinc-300 hover:text-white flex items-center justify-center text-[10px] transition-all cursor-pointer"
                                        title="Close">
                                    ✕
                                </button>
                            </div>
                        </div>

                        <!-- Notification Title -->
                        <h4 class="font-semibold text-xs text-zinc-100 tracking-tight leading-snug">
                            {{ $cleanLatestTitle }}
                        </h4>
                        
                        <!-- Markdown Body (Clean Left-aligned, No Indentation) -->
                        <div class="text-[11.5px] leading-relaxed text-zinc-300/90 mt-1 font-sans break-words text-left [&>p]:m-0 [&>p]:leading-relaxed [&>ul]:list-disc [&>ul]:pl-4 [&>ol]:list-decimal [&>ol]:pl-4 [&>h1]:text-xs [&>h1]:font-bold [&>h2]:text-xs [&>h2]:font-bold [&>h3]:text-xs [&>h3]:font-bold [&>code]:bg-white/10 [&>code]:px-1 [&>code]:rounded line-clamp-2">
                            {!! Str::markdown(trim($latest->body)) !!}
                        </div>

                        @if($totalCount > 1)
                            <div class="mt-2.5 text-[10px] text-zinc-400 hover:text-zinc-200 font-medium flex items-center gap-1 transition-colors">
                                <span>+{{ $totalCount - 1 }} more {{ Str::plural('notification', $totalCount - 1) }}</span>
                                <flux:icon name="chevron-down" class="size-2.5 opacity-70" />
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- GAMBAR 2: EXPANDED STATE (Multi Stack Cards) -->
            <!-- ========================================== -->
            <div x-show="expanded" 
                 x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-250 transform"
                 x-transition:enter-start="opacity-0 translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="space-y-2.5 p-3 rounded-2xl"
                 style="background-color: rgba(22, 22, 26, 0.68); backdrop-filter: blur(36px) saturate(190%); -webkit-backdrop-filter: blur(36px) saturate(190%); box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.14);">
                
                <!-- macOS Panel Header (No divider line) -->
                <div class="flex items-center justify-between px-1 pb-1 text-white">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-bold tracking-wide text-zinc-200">Klakoan</span>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button type="button" 
                                @click="expanded = false" 
                                class="px-2.5 py-0.5 rounded-full bg-white/10 hover:bg-white/20 text-zinc-300 hover:text-white text-[10px] font-medium border border-white/10 shadow-xs transition-all cursor-pointer">
                            Show less
                        </button>

                        <button type="button" 
                                @click="open = false; expanded = false" 
                                class="size-5 rounded-full bg-white/10 hover:bg-white/20 text-zinc-400 hover:text-white flex items-center justify-center text-[10px] transition-all cursor-pointer"
                                title="Close">
                            ✕
                        </button>
                    </div>
                </div>

                <!-- Expanded Cards List -->
                <div class="space-y-2 max-h-[60vh] overflow-y-auto custom-scrollbar pr-0.5">
                    @foreach($this->notifications as $notif)
                        @php 
                            $cleanTitle = Str::replace(['📢 ', '📢'], '', $notif->title);
                        @endphp
                        <div wire:key="mac-notif-{{ $notif->id }}" 
                             class="group relative text-white rounded-xl p-3 hover:bg-white/[0.08] transition-all duration-200 {{ $notif->read_at ? 'opacity-60' : '' }}"
                             style="background-color: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08);">

                            <div>
                                <!-- Top Bar: Status Bar on Left & Timestamp on Far Top-Right -->
                                <div class="flex items-center justify-between gap-1.5 mb-1.5">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        @if($notif->type === 'success' || str_contains(strtolower($notif->title), 'success'))
                                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-400 tracking-wide">
                                                <span class="size-1.5 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]"></span>
                                                <span>Success</span>
                                            </span>
                                        @elseif($notif->type === 'warning' || str_contains(strtolower($notif->title), 'warning'))
                                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-amber-400 tracking-wide">
                                                <span class="size-1.5 rounded-full bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.8)]"></span>
                                                <span>Warning</span>
                                            </span>
                                        @elseif($notif->type === 'danger' || str_contains(strtolower($notif->title), 'error') || str_contains(strtolower($notif->title), 'failed'))
                                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-rose-400 tracking-wide">
                                                <span class="size-1.5 rounded-full bg-rose-400 shadow-[0_0_8px_rgba(251,113,133,0.8)]"></span>
                                                <span>Alert</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-sky-400 tracking-wide">
                                                <span class="size-1.5 rounded-full bg-sky-400 shadow-[0_0_8px_rgba(56,189,248,0.8)]"></span>
                                                <span>Info</span>
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Notification Timestamp in Top Right -->
                                    <span class="text-[10px] text-zinc-400 font-normal shrink-0">{{ $notif->created_at->diffForHumans(null, true) }}</span>
                                </div>

                                <!-- Notification Title -->
                                <h4 class="font-semibold text-xs text-zinc-100 tracking-tight leading-snug">
                                    {{ $cleanTitle }}
                                </h4>

                                <!-- Markdown Message Body (Clean Left-aligned, No Indentation) -->
                                <div class="text-[11.5px] leading-relaxed text-zinc-300/90 mt-1 font-sans break-words text-left [&>p]:m-0 [&>p]:leading-relaxed [&>ul]:list-disc [&>ul]:pl-4 [&>ol]:list-decimal [&>ol]:pl-4 [&>h1]:text-xs [&>h1]:font-bold [&>h2]:text-xs [&>h2]:font-bold [&>h3]:text-xs [&>h3]:font-bold [&>code]:bg-white/10 [&>code]:px-1 [&>code]:rounded">
                                    {!! Str::markdown(trim($notif->body)) !!}
                                </div>

                                <!-- Bottom Row: Mark as read button on Bottom Right (No divider line) -->
                                <div class="flex items-center justify-end mt-1.5">
                                    @if(!$notif->read_at)
                                        <button type="button" 
                                                wire:click="markAsRead({{ $notif->id }})" 
                                                class="text-[10px] text-zinc-400 hover:text-emerald-400 font-medium flex items-center gap-1 transition-colors cursor-pointer"
                                                title="Mark as read">
                                            <span>Mark as read</span>
                                            <flux:icon name="check" class="size-2.5" />
                                        </button>
                                    @else
                                        <span class="text-[10px] text-zinc-500 font-medium flex items-center gap-1">
                                            <span>Read</span>
                                            <flux:icon name="check" class="size-2.5 opacity-60" />
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Footer Actions (No divider line) -->
                <div class="pt-1 flex items-center justify-between text-[10px] px-1">
                    <button type="button" wire:click="markAllAsRead" class="text-zinc-400 hover:text-zinc-200 font-medium transition-colors cursor-pointer">
                        Mark all as read
                    </button>
                    <button type="button" wire:click="clearAll" class="text-rose-400 hover:text-rose-300 font-medium transition-colors cursor-pointer">
                        Clear all
                    </button>
                </div>
            </div>

        @else
            <!-- Empty Notifications State (macOS Popup Glassmorphism - No divider line) -->
            <div class="rounded-2xl p-4 text-center text-white"
                 style="background-color: rgba(22, 22, 26, 0.68); backdrop-filter: blur(36px) saturate(190%); -webkit-backdrop-filter: blur(36px) saturate(190%); box-shadow: 0 16px 40px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.14);">
                <div class="flex items-center justify-between pb-1 mb-2">
                    <span class="text-xs font-bold tracking-wide text-zinc-300">Klakoan</span>
                    <button @click="open = false" class="size-4.5 rounded-full bg-white/10 hover:bg-white/20 text-zinc-400 hover:text-white flex items-center justify-center text-[9px] cursor-pointer">
                        ✕
                    </button>
                </div>
                <div class="py-4 flex flex-col items-center gap-1.5 text-zinc-400">
                    <flux:icon name="bell" class="size-5 text-zinc-500" />
                    <span class="text-xs font-semibold text-zinc-300">No Notifications</span>
                    <span class="text-[10px] text-zinc-500">You're all caught up for today.</span>
                </div>
            </div>
        @endif
    </div>
</div>





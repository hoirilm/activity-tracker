<?php

use Livewire\Component;
use App\Models\User;
use App\Models\Notification;

new class extends Component
{
    public $title = '';
    public $body = '';
    public $type = 'info';
    public $successMessage = '';

    public function broadcast()
    {
        if (!auth()->user()->is_admin) {
            abort(403);
        }

        $this->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'required|string|in:info,success,warning',
        ]);

        $users = User::all();
        $adminName = auth()->user()->name;

        foreach ($users as $user) {
            $user->notifications()->create([
                'title' => '📢 ' . $this->title,
                'body' => $this->body,
                'type' => $this->type,
            ]);
        }

        $count = $users->count();
        $this->reset(['title', 'body', 'type']);
        $this->successMessage = "Broadcast successfully sent to {$count} users!";
    }
};
?>

<div class="flex h-full w-full flex-col gap-6 p-3 sm:p-4 text-neutral-900 dark:text-neutral-100 max-w-6xl mx-auto mt-2 sm:mt-4 pb-16">
    <!-- Main Animated Content -->
    <div class="flex flex-col gap-6 animate-page-entrance">
        <!-- Header -->
    <div class="border-b border-zinc-200/80 dark:border-zinc-800/80 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                <div class="size-8.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                    <flux:icon name="speaker-wave" class="size-5" />
                </div>
                <span>Broadcast Center</span>
            </h2>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">Send mass announcements and system updates to all registered users.</p>
        </div>
    </div>

    <!-- Alert Status -->
    @if($successMessage)
        <div x-data="{ show: true }" x-show="show" class="p-3.5 bg-zinc-100 dark:bg-zinc-800/80 text-zinc-800 dark:text-zinc-200 rounded-2xl border border-zinc-200 dark:border-zinc-700 text-xs font-semibold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <flux:icon name="check-circle" class="size-4.5 text-emerald-500" />
                <span>{{ $successMessage }}</span>
            </div>
            <button @click="show = false" class="text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200 font-semibold text-xs cursor-pointer">Dismiss</button>
        </div>
    @endif

    <!-- Split Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Side: Form (Spans 2 columns) -->
        <div class="lg:col-span-2 border border-zinc-200/80 dark:border-zinc-800 rounded-2xl bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl p-5 sm:p-6 shadow-xs relative overflow-hidden group hover:border-zinc-400 dark:hover:border-zinc-700 transition-all">

            <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 mb-4 flex items-center gap-2 relative z-10">
                <div class="size-7 rounded-lg bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                    <flux:icon name="pencil-square" class="size-4 text-zinc-700 dark:text-zinc-300" />
                </div>
                <span>Compose Global Announcement</span>
            </h3>

            <form wire:submit="broadcast" class="space-y-4 relative z-10">
                <!-- Notification Type Selection -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Notification Priority / Type</label>
                    <div class="relative w-full">
                        <select wire:model.live="type" required
                                class="w-full h-10 pl-9 pr-8 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-zinc-600 focus:ring-2 focus:ring-zinc-600/20 shadow-2xs transition-all appearance-none cursor-pointer">
                            <option value="info">Info</option>
                            <option value="success">Success</option>
                            <option value="warning">Warning</option>
                        </select>
                        <flux:icon name="bell-alert" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-400 pointer-events-none" />
                        <flux:icon name="chevron-down" class="absolute right-3 top-1/2 -translate-y-1/2 size-3.5 text-zinc-400 pointer-events-none" />
                    </div>
                </div>

                <!-- Title -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Broadcast Title</label>
                    <div class="relative w-full">
                        <input type="text" 
                               wire:model.live="title" 
                               placeholder="e.g. System Maintenance / New Release" 
                               required 
                               autocomplete="off"
                               class="w-full h-10 pl-9 pr-3 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-zinc-600 focus:ring-2 focus:ring-zinc-600/20 shadow-2xs transition-all">
                        <flux:icon name="chat-bubble-left-ellipsis" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-400 pointer-events-none" />
                    </div>
                </div>

                <!-- Body Message -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Announcement Details</label>
                    <div class="relative w-full">
                        <textarea wire:model.live="body" 
                                  placeholder="Type your broadcast message content here..." 
                                  rows="4" 
                                  required
                                  class="w-full p-3 pl-9 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-zinc-600 focus:ring-2 focus:ring-zinc-600/20 shadow-2xs transition-all leading-relaxed"></textarea>
                        <flux:icon name="document-text" class="absolute left-3 top-3.5 size-4 text-zinc-400 pointer-events-none" />
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="flex justify-end pt-3 border-t border-zinc-200/50 dark:border-zinc-800/50">
                    <button type="submit" class="bg-violet-600 hover:bg-violet-500 text-white font-semibold text-xs px-6 py-2.5 rounded-xl border border-violet-500 active:scale-95 transition-all shadow-xs shadow-violet-500/25 flex items-center gap-2 cursor-pointer">
                        <flux:icon name="megaphone" class="size-4 text-white" />
                        <span>Send Broadcast Now</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Right Side: Live macOS Preview -->
        <div class="space-y-4 flex flex-col">
            <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <div class="size-7 rounded-lg bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                    <flux:icon name="eye" class="size-4 text-zinc-700 dark:text-zinc-300" />
                </div>
                <span>Live Card Preview</span>
            </h3>
            
            <div class="border border-dashed border-zinc-200 dark:border-zinc-800 rounded-2xl p-4 sm:p-5 bg-zinc-50/50 dark:bg-zinc-950/60 backdrop-blur-xl flex-1 flex flex-col justify-between gap-4">
                <!-- macOS Style Glass Banner Card -->
                <div class="w-full bg-white/90 dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 backdrop-blur-2xl rounded-2xl p-5 shadow-xl transition-all duration-300 relative overflow-hidden flex flex-col gap-3">
                    
                    <!-- Header -->
                    <div class="flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400 relative z-10">
                        <div class="flex items-center gap-2">
                            <div class="size-5 rounded-md bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-200 flex items-center justify-center font-bold text-[10px] shadow-xs">
                                K
                            </div>
                            <span class="font-bold text-zinc-700 dark:text-zinc-200 uppercase tracking-wider text-[9px]">Klakoan Tracker</span>
                            <span>&bull;</span>
                            <span class="text-[10px]">Just now</span>
                        </div>
                        
                        <!-- Indicator Type Badge -->
                        <div class="text-[9px] font-mono font-semibold uppercase tracking-wider">
                            @if($type === 'success')
                                <span class="bg-emerald-50 dark:bg-zinc-800 text-emerald-600 dark:text-emerald-400 px-2.5 py-0.5 rounded-md border border-emerald-200 dark:border-zinc-700/60">success</span>
                            @elseif($type === 'warning')
                                <span class="bg-amber-50 dark:bg-zinc-800 text-amber-600 dark:text-amber-400 px-2.5 py-0.5 rounded-md border border-amber-200 dark:border-zinc-700/60">warning</span>
                            @else
                                <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 px-2.5 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60">info</span>
                            @endif
                        </div>
                    </div>

                    <!-- Title -->
                    <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 leading-snug relative z-10 flex items-center gap-1.5">
                        <span>📢</span>
                        <span>{{ $title ?: 'Judul Pengumuman...' }}</span>
                    </h4>
                    
                    <!-- Message Body -->
                    <p class="text-xs leading-relaxed text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap break-words relative z-10 bg-zinc-50 dark:bg-zinc-950/60 p-3.5 rounded-xl border border-zinc-200/80 dark:border-zinc-800/80 min-h-[90px]">{{ trim($body) ?: 'Isi pesan pengumuman lengkap yang akan disiarkan ke semua pengguna terdaftar...' }}</p>
                </div>
                
                <p class="text-[10px] text-zinc-500 dark:text-zinc-500 text-center leading-relaxed">
                    Preview di atas mensimulasikan tampilan notifikasi real-time yang akan diterima pengguna di laci notifikasi.
                </p>
            </div>
        </div>

    </div>
    </div>
</div>

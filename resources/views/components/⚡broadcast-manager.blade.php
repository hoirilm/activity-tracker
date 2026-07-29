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
        $this->successMessage = "Broadcast berhasil dikirimkan ke {$count} user!";
    }
};
?>

<div class="flex h-full w-full flex-col gap-6 p-3 sm:p-4 text-neutral-900 dark:text-neutral-100 max-w-6xl mx-auto mt-2 sm:mt-4 pb-16" x-data="{ mounted: false }" x-init="setTimeout(() => mounted = true, 50)">
    <!-- Header -->
    <div class="border-b border-zinc-200/80 dark:border-zinc-800/80 pb-4 flex flex-col md:flex-row md:items-center justify-between gap-4 transition-all duration-700 ease-out"
         :class="mounted ? 'translate-y-0 opacity-100' : '-translate-y-4 opacity-0'">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                <div class="size-8.5 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-500 shrink-0">
                    <flux:icon name="megaphone" class="size-4.5" />
                </div>
                <span>System Broadcast Studio</span>
            </h2>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">Send real-time global notifications and announcements to all registered team members.</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-[11px] font-mono font-bold px-3 py-1 rounded-lg bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20 flex items-center gap-1.5">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-purple-500"></span>
                </span>
                <span>Broadcasting to {{ \App\Models\User::count() }} Active Users</span>
            </span>
        </div>
    </div>

    <!-- Alert success -->
    @if($successMessage)
        <div x-data="{ show: true }" x-show="show" class="p-3.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-2xl border border-emerald-500/20 text-xs font-semibold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <flux:icon name="check-circle" class="size-4.5 text-emerald-500" />
                <span>{{ $successMessage }}</span>
            </div>
            <button @click="show = false" class="text-zinc-400 hover:text-zinc-200 font-semibold text-xs cursor-pointer">Dismiss</button>
        </div>
    @endif

    <!-- Split Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 transition-all duration-700 ease-out delay-100"
         :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0'">
        
        <!-- Left Side: Form (Spans 2 columns) -->
        <div class="lg:col-span-2 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl bg-white/80 dark:bg-zinc-900/80 backdrop-blur-xl p-5 sm:p-6 shadow-xs relative overflow-hidden group hover:border-purple-500/40 transition-all">
            <!-- Background Ambient Glow -->
            <div class="absolute -right-10 -bottom-10 size-32 bg-purple-500/10 rounded-full blur-2xl pointer-events-none"></div>

            <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 mb-4 flex items-center gap-2 relative z-10">
                <div class="size-7 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0">
                    <flux:icon name="pencil-square" class="size-4" />
                </div>
                <span>Compose Global Announcement</span>
            </h3>

            <form wire:submit="broadcast" class="space-y-4 relative z-10">
                <!-- Notification Type Selection -->
                <div>
                    <flux:select wire:model.live="type" label="Notification Priority / Type" placeholder="Select type..." required>
                        <flux:select.option value="info">Info</flux:select.option>
                        <flux:select.option value="success">Success</flux:select.option>
                        <flux:select.option value="warning">Warning</flux:select.option>
                    </flux:select>
                </div>

                <!-- Title -->
                <div>
                    <flux:input wire:model.live="title" label="Broadcast Title" placeholder="e.g. System Maintenance / New Release" required />
                </div>

                <!-- Body Message -->
                <div>
                    <flux:textarea wire:model.live="body" label="Announcement Details" placeholder="Type your broadcast message content here..." rows="5" required />
                </div>

                <!-- Action buttons -->
                <div class="flex justify-end pt-3 border-t border-zinc-200/50 dark:border-zinc-800/50">
                    <button type="submit" class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-semibold text-xs px-6 py-2.5 rounded-xl active:scale-95 transition-all shadow-md shadow-purple-500/20 flex items-center gap-2 border-none cursor-pointer">
                        <flux:icon name="megaphone" class="size-4" />
                        <span>Send Broadcast Now</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Right Side: Live macOS Preview -->
        <div class="space-y-4">
            <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <div class="size-7 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0">
                    <flux:icon name="eye" class="size-4" />
                </div>
                <span>Live Card Preview</span>
            </h3>
            
            <div class="border border-dashed border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 bg-white/40 dark:bg-zinc-950/40 backdrop-blur-xl flex items-center justify-center min-h-[240px]">
                <!-- macOS Style Glass Banner Card -->
                <div class="w-full max-w-[300px] bg-white/90 dark:bg-zinc-900/90 border border-zinc-200/80 dark:border-zinc-800/80 backdrop-blur-2xl rounded-2xl p-4.5 shadow-xl transition-all duration-300 transform hover:scale-[1.02] relative overflow-hidden">
                    
                    <!-- Glow based on priority -->
                    @if($type === 'success')
                        <div class="absolute -right-6 -bottom-6 size-20 bg-emerald-500/10 rounded-full blur-xl pointer-events-none"></div>
                    @elseif($type === 'warning')
                        <div class="absolute -right-6 -bottom-6 size-20 bg-amber-500/10 rounded-full blur-xl pointer-events-none"></div>
                    @else
                        <div class="absolute -right-6 -bottom-6 size-20 bg-indigo-500/10 rounded-full blur-xl pointer-events-none"></div>
                    @endif

                    <!-- Header -->
                    <div class="flex items-center justify-between text-[10px] text-zinc-400 dark:text-zinc-500 mb-2.5 relative z-10">
                        <div class="flex items-center gap-1.5">
                            <div class="size-4.5 rounded-md bg-indigo-600 flex items-center justify-center text-white font-bold text-[9px] shadow-xs">
                                K
                            </div>
                            <span class="font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider text-[8px]">Klakoan</span>
                            <span>&bull;</span>
                            <span>now</span>
                        </div>
                        
                        <!-- Indicator Type Badge -->
                        <div class="text-[8px] font-mono font-bold uppercase tracking-wider">
                            @if($type === 'success')
                                <span class="bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 px-2 py-0.5 rounded-full border border-emerald-500/20">success</span>
                            @elseif($type === 'warning')
                                <span class="bg-amber-500/10 text-amber-600 dark:text-amber-400 px-2 py-0.5 rounded-full border border-amber-500/20">warning</span>
                            @else
                                <span class="bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 px-2 py-0.5 rounded-full border border-indigo-500/20">info</span>
                            @endif
                        </div>
                    </div>

                    <!-- Title -->
                    <h4 class="text-xs font-bold text-zinc-900 dark:text-zinc-100 mb-1.5 leading-snug relative z-10">
                        📢 {{ $title ?: 'Judul Pengumuman...' }}
                    </h4>
                    
                    <!-- Message Body -->
                    <p class="text-[11px] leading-relaxed text-zinc-500 dark:text-zinc-400 whitespace-pre-wrap break-words relative z-10 bg-zinc-50/50 dark:bg-zinc-950/30 p-2.5 rounded-xl border border-zinc-200/40 dark:border-zinc-800/40">{{ trim($body) ?: 'Isi pesan pengumuman lengkap yang akan disiarkan ke semua pengguna terdaftar...' }}</p>
                </div>
            </div>
            
            <p class="text-[10px] text-zinc-400 dark:text-zinc-500 text-center leading-relaxed">
                Preview di atas mensimulasikan tampilan notifikasi real-time yang akan diterima pengguna di laci notifikasi.
            </p>
        </div>

    </div>
</div>

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

<div class="flex h-full w-full flex-col gap-6 p-4 text-neutral-900 dark:text-neutral-100 max-w-5xl mx-auto mt-4">
    <!-- Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-800 pb-4">
        <h2 class="text-xl font-semibold tracking-tight">System Broadcast</h2>
        <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Send a global notification to all registered users in the workspace.</p>
    </div>

    <!-- Alert success -->
    @if($successMessage)
        <div x-data="{ show: true }" x-show="show" class="p-4 bg-emerald-50 text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400 rounded-xl border border-emerald-100 dark:border-emerald-900/30 text-sm font-medium flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <flux:icon name="check-circle" class="size-5 text-emerald-500" />
                <span>{{ $successMessage }}</span>
            </div>
            <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300 font-semibold text-xs cursor-pointer">Dismiss</button>
        </div>
    @endif

    <!-- Split Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Side: Form (Spans 2 columns) -->
        <div class="lg:col-span-2 border border-zinc-200 dark:border-zinc-800 rounded-xl bg-zinc-50 dark:bg-zinc-900 p-6 shadow-xs">
            <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mb-4 flex items-center gap-2">
                <flux:icon name="pencil-square" class="size-4 text-zinc-500" />
                <span>Compose Announcement</span>
            </h3>

            <form wire:submit="broadcast" class="space-y-5">
                <!-- Notification Type Selection -->
                <div>
                    <flux:select wire:model.live="type" label="Notification Type" placeholder="Select type..." required>
                        <flux:select.option value="info">Info</flux:select.option>
                        <flux:select.option value="success">Success</flux:select.option>
                        <flux:select.option value="warning">Warning</flux:select.option>
                    </flux:select>
                </div>

                <!-- Title -->
                <div>
                    <flux:input wire:model.live="title" label="Broadcast Title" placeholder="e.g. Pemeliharaan Sistem / Fitur Baru" required />
                </div>

                <!-- Body Message -->
                <div>
                    <flux:textarea wire:model.live="body" label="Message Content" placeholder="Type your announcement message here..." rows="6" required />
                </div>

                <!-- Action buttons -->
                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="filled" class="bg-indigo-600 hover:bg-indigo-700 text-white dark:bg-indigo-500 dark:hover:bg-indigo-600 border-none cursor-pointer px-4 py-2 text-xs font-semibold rounded-lg shadow-sm transition-all duration-150 transform active:scale-[0.98]">
                        Send Broadcast 📢
                    </flux:button>
                </div>
            </form>
        </div>

        <!-- Right Side: Live macOS Preview (Spans 1 column) -->
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 flex items-center gap-2">
                <flux:icon name="eye" class="size-4 text-zinc-500" />
                <span>Live Preview</span>
            </h3>
            
            <div class="border border-dashed border-zinc-300 dark:border-zinc-800 rounded-xl p-5 bg-zinc-50/50 dark:bg-zinc-950/20 flex items-center justify-center min-h-[220px]">
                <!-- macOS Style Glass Banner Card -->
                <div class="w-full max-w-[280px] bg-zinc-50/90 dark:bg-zinc-900/90 border border-zinc-200/50 dark:border-zinc-800/40 backdrop-blur-md rounded-2xl p-4 shadow-md transition-all duration-300 transform hover:scale-[1.02]">
                    <!-- Header -->
                    <div class="flex items-center justify-between text-[10px] text-zinc-400 dark:text-zinc-500 mb-2">
                        <div class="flex items-center gap-1.5">
                            <div class="size-4 rounded bg-zinc-800 dark:bg-zinc-250 flex items-center justify-center">
                                <span class="text-[9px] font-bold text-white dark:text-zinc-900">K</span>
                            </div>
                            <span class="font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider text-[8px]">Klakoan</span>
                            <span>•</span>
                            <span>now</span>
                        </div>
                        
                        <!-- Indicator Type Badge -->
                        <div class="text-[8px] font-bold uppercase tracking-wider">
                            @if($type === 'success')
                                <span class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 px-1.5 py-0.5 rounded">success</span>
                            @elseif($type === 'warning')
                                <span class="bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 px-1.5 py-0.5 rounded">warning</span>
                            @else
                                <span class="bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 px-1.5 py-0.5 rounded">info</span>
                            @endif
                        </div>
                    </div>

                    <!-- Title -->
                    <h4 class="text-xs font-bold text-zinc-850 dark:text-zinc-150 mb-1 leading-tight">
                        📢 {{ $title ?: 'Judul Pengumuman...' }}
                    </h4>
                    
                    <!-- Message Body -->
                    <p class="text-[11px] leading-relaxed text-zinc-500 dark:text-zinc-400 whitespace-pre-wrap break-words">{{ $body ?: 'Isi pesan pengumuman lengkap yang akan disiarkan ke semua pengguna terdaftar...' }}</p>
                </div>
            </div>
            
            <p class="text-[10px] text-zinc-400 dark:text-zinc-500 text-center leading-relaxed">
                Preview di atas mensimulasikan tampilan notifikasi yang akan diterima pengguna langsung di pojok kanan atas laci notifikasi mereka.
            </p>
        </div>

    </div>
</div>

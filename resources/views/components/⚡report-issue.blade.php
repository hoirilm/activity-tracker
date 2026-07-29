<?php

use Livewire\Component;
use App\Models\Issue;

new class extends Component
{
    public $title = '';
    public $description = '';
    public $success = false;

    public function submit()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $user = auth()->user();
        $issue = $user->issues()->create([
            'title' => $this->title,
            'description' => $this->description,
        ]);

        // Notify the user themselves
        $user->notifications()->create([
            'title' => 'Laporan Bug Diterima 🐛',
            'body' => "Terima kasih! Laporan bug Anda terkait '{$issue->formatted_title}' telah diterima oleh Administrator.",
            'type' => 'info',
        ]);

        // Notify all administrators
        $admins = \App\Models\User::where('is_admin', true)->get();
        foreach ($admins as $admin) {
            if ($admin->id !== $user->id) {
                $admin->notifications()->create([
                    'title' => 'Laporan Bug Baru ⚠️',
                    'body' => "Pengguna {$user->name} melaporkan bug baru: '{$issue->formatted_title}'.",
                    'type' => 'warning',
                ]);
            }
        }

        $this->reset(['title', 'description']);
        $this->success = true;
    }
};
?>

<div>
    <flux:modal name="report-issue-modal" class="w-[calc(100vw-2rem)] max-w-lg backdrop:backdrop-blur-md z-[200]" x-on:close="$wire.set('success', false)">
        <div class="space-y-6">
            @if($success)
                <div class="text-center py-6 space-y-4">
                    <div class="inline-flex items-center justify-center size-14 rounded-2xl bg-emerald-50 dark:bg-zinc-800 border border-emerald-200 dark:border-zinc-700/60 text-emerald-600 dark:text-emerald-400 mx-auto shadow-xs">
                        <flux:icon name="check" class="size-7 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div class="space-y-2">
                        <flux:heading size="lg" class="text-center font-bold tracking-tight">Report Submitted Successfully</flux:heading>
                        <flux:text class="text-center text-xs text-zinc-500 dark:text-zinc-400">
                            Your bug report has been successfully submitted. Thank you for helping us improve!
                        </flux:text>
                    </div>
                    <div class="flex justify-center mt-6">
                        <flux:modal.close>
                            <button type="button" x-on:click="window.location.reload()" class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs px-6 py-2.5 rounded-xl border border-emerald-500 active:scale-95 transition-all shadow-xs shadow-emerald-500/20 cursor-pointer">Done</button>
                        </flux:modal.close>
                    </div>
                </div>
            @else
                <div class="flex items-center gap-3">
                    <div class="size-10 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                        <flux:icon name="bug-ant" class="size-5 text-zinc-700 dark:text-zinc-300" />
                    </div>
                    <div>
                        <flux:heading size="lg" class="font-bold tracking-tight">Report a Bug / Issue</flux:heading>
                        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                            Please describe the problem you encountered or any feature requests you have.
                        </flux:text>
                    </div>
                </div>

                <form wire:submit="submit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Title</label>
                        <div class="relative w-full">
                            <input type="text" 
                                   wire:model="title" 
                                   placeholder="Brief summary of the issue" 
                                   required 
                                   autocomplete="off"
                                   class="w-full h-10 pl-9 pr-3 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 shadow-2xs transition-all">
                            <flux:icon name="pencil-square" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-400 dark:text-zinc-500 pointer-events-none" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Description</label>
                        <div class="relative w-full">
                            <textarea wire:model="description" 
                                      placeholder="Detailed description of what happened..." 
                                      rows="3" 
                                      required
                                      class="w-full p-3 pl-9 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 shadow-2xs transition-all leading-relaxed"></textarea>
                            <flux:icon name="document-text" class="absolute left-3 top-3.5 size-4 text-zinc-400 dark:text-zinc-500 pointer-events-none" />
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-2 mt-4 pt-2">
                        <flux:modal.close>
                            <flux:button variant="ghost" size="sm" class="rounded-xl">Cancel</flux:button>
                        </flux:modal.close>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs px-5 py-2.5 rounded-xl border border-indigo-500 active:scale-95 transition-all shadow-xs shadow-indigo-500/20 flex items-center gap-1.5 cursor-pointer">
                            <flux:icon name="paper-airplane" class="size-3.5 text-white" />
                            <span>Submit Report</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </flux:modal>
</div>

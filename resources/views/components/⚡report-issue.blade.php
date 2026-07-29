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
    <flux:modal name="report-issue-modal" class="min-w-[22rem] md:w-[32rem] backdrop:backdrop-blur-md z-[200]" x-on:close="$wire.set('success', false)">
        <div class="space-y-6">
            @if($success)
                <div class="text-center py-6 space-y-4">
                    <div class="inline-flex items-center justify-center size-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 mx-auto">
                        <flux:icon name="check" class="size-7" />
                    </div>
                    <div class="space-y-2">
                        <flux:heading size="lg" class="text-center font-bold tracking-tight">Report Submitted Successfully</flux:heading>
                        <flux:text class="text-center text-xs text-zinc-400">
                            Your bug report has been successfully submitted. Thank you for helping us improve!
                        </flux:text>
                    </div>
                    <div class="flex justify-center mt-6">
                        <flux:modal.close>
                            <flux:button variant="primary" size="sm" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium active:scale-95" x-on:click="window.location.reload()">Done</flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            @else
                <div class="flex items-center gap-3">
                    <div class="size-10 rounded-xl bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-500 shrink-0">
                        <flux:icon name="bug-ant" class="size-5" />
                    </div>
                    <div>
                        <flux:heading size="lg" class="font-bold tracking-tight">Report a Bug / Issue</flux:heading>
                        <flux:text class="text-xs text-zinc-400 mt-0.5">
                            Please describe the problem you encountered or any feature requests you have.
                        </flux:text>
                    </div>
                </div>

                <form wire:submit="submit" class="space-y-4">
                    <flux:input wire:model="title" label="Title" placeholder="Brief summary of the issue" required />
                    <flux:textarea wire:model="description" label="Description" placeholder="Detailed description of what happened..." required rows="4" />
                    
                    <div class="flex justify-end gap-2 mt-4 pt-2">
                        <flux:modal.close>
                            <flux:button variant="ghost" size="sm">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" size="sm" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl px-4 py-2 active:scale-95 border-none">Submit Report</flux:button>
                    </div>
                </form>
            @endif
        </div>
    </flux:modal>
</div>

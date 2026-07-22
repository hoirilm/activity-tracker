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
    <flux:modal name="report-issue-modal" class="min-w-[22rem] md:w-[32rem] backdrop:backdrop-blur-sm z-[200]" x-on:close="$wire.set('success', false)">
        <div class="space-y-6">
            @if($success)
                <div class="text-center py-6 space-y-4">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 mx-auto">
                        <flux:icon name="check" class="size-6" />
                    </div>
                    <div class="space-y-2">
                        <flux:heading size="lg" class="text-center">Report Submitted Successfully</flux:heading>
                        <flux:text class="text-center">
                            Your bug report has been successfully submitted. Thank you for helping us improve!
                        </flux:text>
                    </div>
                    <div class="flex justify-center mt-6">
                        <flux:modal.close>
                            <flux:button variant="primary" x-on:click="window.location.reload()">Done</flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            @else
                <div>
                    <flux:heading size="lg">Report a Bug / Issue</flux:heading>
                    <flux:text class="mt-2">
                        Please describe the problem you encountered or any feature requests you have.
                    </flux:text>
                </div>

                <form wire:submit="submit" class="space-y-4">
                    <flux:input wire:model="title" label="Title" placeholder="Brief summary of the issue" required />
                    <flux:textarea wire:model="description" label="Description" placeholder="Detailed description of what happened..." required rows="4" />
                    
                    <div class="flex justify-end gap-2 mt-4">
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button variant="primary" type="submit">Submit Report</flux:button>
                    </div>
                </form>
            @endif
        </div>
    </flux:modal>
</div>

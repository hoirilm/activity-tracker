<?php

use Livewire\Component;
use App\Models\Issue;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public function getIssuesProperty()
    {
        return Issue::with('user')->latest()->paginate(10);
    }

    public function toggleStatus($issueId)
    {
        if (!auth()->user()->is_admin) {
            abort(403);
        }

        $issue = Issue::findOrFail($issueId);
        $issue->update([
            'status' => $issue->status === 'open' ? 'closed' : 'open'
        ]);
        
        session()->flash('status_updated', 'Status updated to ' . $issue->status . '.');
        $this->redirect(route('issues'));
    }
};
?>

<div class="flex h-full w-full flex-col gap-6 p-4 text-neutral-900 dark:text-neutral-100 max-w-5xl mx-auto mt-4">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold tracking-tight">Issue Management</h2>
    </div>

    @if(session()->has('status_updated'))
        <div class="p-3 bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400 rounded-lg text-sm font-medium">
            {{ session('status_updated') }}
        </div>
    @endif

    <div class="grid gap-4">
        @forelse($this->issues as $issue)
            <div class="group relative overflow-hidden rounded-xl border {{ $issue->status === 'closed' ? 'border-neutral-200 bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900/50' : 'border-indigo-200 bg-white dark:border-indigo-900/50 dark:bg-neutral-900' }} p-5 shadow-sm transition-all flex flex-col md:flex-row justify-between items-start gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="font-medium text-lg {{ $issue->status === 'closed' ? 'line-through text-neutral-500' : '' }}">{{ $issue->title }}</div>
                        @if($issue->status === 'open')
                            <span class="bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-full font-semibold">Open</span>
                        @else
                            <span class="bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-full font-semibold">Closed</span>
                        @endif
                    </div>
                    <div class="text-sm text-neutral-600 dark:text-neutral-400 whitespace-pre-wrap">{{ $issue->description }}</div>
                    <div class="text-xs text-neutral-500 mt-4 flex items-center gap-2">
                        <span class="font-medium">Reporter:</span> {{ $issue->user->name }} &bull; {{ $issue->created_at->diffForHumans() }}
                    </div>
                </div>
                
                <div class="w-full md:w-auto flex justify-end">
                    @if($issue->status === 'open')
                        <flux:button variant="danger" wire:click="toggleStatus({{ $issue->id }})" size="sm">Mark as Closed</flux:button>
                    @else
                        <flux:button variant="subtle" wire:click="toggleStatus({{ $issue->id }})" size="sm">Re-open</flux:button>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-16 text-sm text-neutral-400 border border-dashed border-neutral-200 dark:border-neutral-700 rounded-xl">
                No issues reported yet.
            </div>
        @endforelse
    </div>
    
    <div>
        <flux:pagination :paginator="$this->issues" />
    </div>
</div>

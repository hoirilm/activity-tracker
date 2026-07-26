<?php

use Livewire\Component;
use App\Models\Issue;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all';

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function getIssuesProperty()
    {
        return Issue::with('user')
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->search, function ($query) {
                $term = '%' . strtolower($this->search) . '%';
                
                // Extract digits if searching for something like TKT-0005, TKT-5, or 0005
                $idSearch = null;
                if (preg_match('/(?:tkt-)?0*([1-9]\d*)/i', $this->search, $matches)) {
                    $idSearch = (int) $matches[1];
                }

                $query->where(function ($q) use ($term, $idSearch) {
                    $q->whereRaw('lower(title) like ?', [$term])
                      ->orWhereRaw('lower(description) like ?', [$term]);
                    
                    if ($idSearch) {
                        $q->orWhere('id', $idSearch);
                    }

                    $q->orWhereHas('user', function ($uq) use ($term) {
                        $uq->whereRaw('lower(name) like ?', [$term])
                          ->orWhereRaw('lower(email) like ?', [$term]);
                    });
                });
            })
            ->latest()
            ->paginate(10);
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

<div class="flex h-full w-full flex-col gap-6 p-4 text-neutral-900 dark:text-neutral-100 max-w-5xl mx-auto mt-4" x-data="{ mounted: false }" x-init="setTimeout(() => mounted = true, 50)">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-4 transition-all duration-700 ease-out"
         :class="mounted ? 'translate-y-0 opacity-100' : '-translate-y-4 opacity-0'">
        <div>
            <h2 class="text-xl font-semibold tracking-tight">Issue Management</h2>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Track, review, and manage bugs and feedback submitted by users.</p>
        </div>
        
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">
            <!-- Status Filter Segmented Control -->
            <div class="flex bg-zinc-100 dark:bg-zinc-800/80 rounded-lg p-0.5 shrink-0">
                <button type="button" wire:click="$set('statusFilter', 'all')" 
                        class="text-[10px] px-3 py-1.5 rounded-md font-bold uppercase tracking-wider transition-colors cursor-pointer {{ $statusFilter === 'all' ? 'bg-zinc-50 dark:bg-zinc-900 shadow-xs text-zinc-900 dark:text-zinc-100' : 'text-zinc-450 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                    All
                </button>
                <button type="button" wire:click="$set('statusFilter', 'open')" 
                        class="text-[10px] px-3 py-1.5 rounded-md font-bold uppercase tracking-wider transition-colors cursor-pointer {{ $statusFilter === 'open' ? 'bg-zinc-50 dark:bg-zinc-900 shadow-xs text-zinc-900 dark:text-zinc-100' : 'text-zinc-450 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                    Open
                </button>
                <button type="button" wire:click="$set('statusFilter', 'closed')" 
                        class="text-[10px] px-3 py-1.5 rounded-md font-bold uppercase tracking-wider transition-colors cursor-pointer {{ $statusFilter === 'closed' ? 'bg-zinc-50 dark:bg-zinc-900 shadow-xs text-zinc-900 dark:text-zinc-100' : 'text-zinc-450 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                    Solved
                </button>
            </div>
            
            <!-- Search bar -->
            <div class="flex-1 md:w-64 md:flex-none">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search issues..." icon="magnifying-glass" size="sm" autocomplete="off" />
            </div>
        </div>
    </div>

    @if(session()->has('status_updated'))
        <div x-data="{ show: true }" x-show="show" class="p-4 bg-emerald-50 text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400 rounded-xl border border-emerald-100 dark:border-emerald-900/30 text-sm font-medium flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <flux:icon name="check-circle" class="size-5 text-emerald-500" />
                <span>{{ session('status_updated') }}</span>
            </div>
            <button @click="show = false" class="text-red-500 hover:text-red-700 dark:hover:text-red-300 font-semibold text-xs cursor-pointer">Dismiss</button>
        </div>
    @endif

    <div class="grid gap-4 transition-all duration-700 ease-out delay-100"
         :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0'">
        @forelse($this->issues as $issue)
            <div wire:key="issue-{{ $issue->id }}" 
                 class="group relative overflow-hidden rounded-2xl border p-5 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex flex-col md:flex-row justify-between items-start md:items-center gap-4
                 {{ $issue->status === 'closed' ? 'border-zinc-200/60 bg-zinc-50/50 dark:border-zinc-800/40 dark:bg-zinc-950/10 opacity-75 hover:opacity-100' : 'border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 hover:border-zinc-300 dark:hover:border-zinc-700' }}
                 " wire:transition.slide.up>
                <div class="flex items-start gap-4 flex-1 min-w-0">
                    <!-- Icon container -->
                    @if($issue->status === 'open')
                        <div class="size-10 rounded-xl bg-red-50 dark:bg-red-950/30 border border-red-100/50 dark:border-red-900/20 flex items-center justify-center text-red-500 shrink-0">
                            <flux:icon name="bug-ant" class="size-5" />
                        </div>
                    @else
                        <div class="size-10 rounded-xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-200/50 dark:border-zinc-800/40 flex items-center justify-center text-zinc-400 dark:text-zinc-500 shrink-0">
                            <flux:icon name="check-circle" class="size-5" />
                        </div>
                    @endif

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2.5 mb-1.5 flex-wrap">
                            <div class="font-medium text-base text-zinc-850 dark:text-zinc-150 {{ $issue->status === 'closed' ? 'line-through text-zinc-450 dark:text-zinc-500' : '' }}">{{ $issue->formatted_title }}</div>
                            @if($issue->status === 'open')
                                <span class="bg-red-100 text-red-700 dark:bg-red-950/30 dark:text-red-400 text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded">Open</span>
                            @else
                                <span class="bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded">Closed</span>
                            @endif
                        </div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400 whitespace-pre-wrap leading-relaxed">{{ $issue->description }}</div>
                        <div class="text-[10px] text-zinc-400 dark:text-zinc-550 mt-3.5 flex items-center gap-1.5">
                            <flux:icon name="user" class="size-3.5" />
                            <span>Reporter: <strong class="text-zinc-500 dark:text-zinc-400">{{ $issue->user->name }}</strong> &bull; {{ $issue->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="w-full md:w-auto flex justify-end shrink-0">
                    @if($issue->status === 'open')
                        <flux:button variant="danger" wire:click="toggleStatus({{ $issue->id }})" size="sm" class="cursor-pointer active:scale-95 transition-all duration-200">Mark as Closed</flux:button>
                    @else
                        <flux:button variant="subtle" wire:click="toggleStatus({{ $issue->id }})" size="sm" class="cursor-pointer active:scale-95 transition-all duration-200">Re-open</flux:button>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-xs text-neutral-400 text-center py-16 border border-dashed border-neutral-200 dark:border-neutral-800 rounded-xl flex flex-col items-center gap-2 bg-zinc-50 dark:bg-zinc-900/50">
                <flux:icon name="bug-ant" class="size-8 text-neutral-300 dark:text-neutral-750" />
                <span>No issues found matching your search.</span>
            </div>
        @endforelse
    </div>
    
    <div class="mt-2">
        <flux:pagination :paginator="$this->issues" />
    </div>
</div>

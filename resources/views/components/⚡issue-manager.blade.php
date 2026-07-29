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

<div class="flex h-full w-full flex-col gap-6 p-3 sm:p-4 text-neutral-900 dark:text-neutral-100 max-w-6xl mx-auto mt-2 sm:mt-4 pb-16" x-data="{ mounted: false }" x-init="setTimeout(() => mounted = true, 50)">
    <!-- Header -->
    <div class="border-b border-zinc-200/80 dark:border-zinc-800/80 pb-4 flex flex-col md:flex-row md:items-center justify-between gap-4 transition-all duration-700 ease-out"
         :class="mounted ? 'translate-y-0 opacity-100' : '-translate-y-4 opacity-0'">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                <div class="size-8.5 rounded-xl bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-500 shrink-0">
                    <flux:icon name="flag" class="size-4.5" />
                </div>
                <span>Issue &amp; Feedback Tracking</span>
            </h2>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">Review, manage, and resolve bugs and feature feedback submitted by team members.</p>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">
            <!-- Status Filter Segmented Control -->
            <div class="flex bg-zinc-100 dark:bg-zinc-800/80 rounded-xl p-1 shrink-0 border border-zinc-200/50 dark:border-zinc-700/50">
                <button type="button" wire:click="$set('statusFilter', 'all')" 
                        class="text-[10px] px-3 py-1.5 rounded-lg font-bold uppercase tracking-wider transition-all duration-200 cursor-pointer {{ $statusFilter === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200' }}">
                    All
                </button>
                <button type="button" wire:click="$set('statusFilter', 'open')" 
                        class="text-[10px] px-3 py-1.5 rounded-lg font-bold uppercase tracking-wider transition-all duration-200 cursor-pointer {{ $statusFilter === 'open' ? 'bg-red-600 text-white shadow-xs' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200' }}">
                    Open ({{ \App\Models\Issue::where('status', 'open')->count() }})
                </button>
                <button type="button" wire:click="$set('statusFilter', 'closed')" 
                        class="text-[10px] px-3 py-1.5 rounded-lg font-bold uppercase tracking-wider transition-all duration-200 cursor-pointer {{ $statusFilter === 'closed' ? 'bg-emerald-600 text-white shadow-xs' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200' }}">
                    Solved ({{ \App\Models\Issue::where('status', 'closed')->count() }})
                </button>
            </div>
            
            <!-- Search bar -->
            <div class="relative flex-1 md:w-60 md:flex-none">
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Search ticket, title, user..." 
                       autocomplete="off"
                       class="w-full h-9 pl-9 pr-3 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 shadow-2xs transition-all">
                <flux:icon name="magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-indigo-500 pointer-events-none" />
            </div>
        </div>
    </div>

    <!-- Alert Status -->
    @if(session()->has('status_updated'))
        <div x-data="{ show: true }" x-show="show" class="p-3.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-2xl border border-emerald-500/20 text-xs font-semibold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <flux:icon name="check-circle" class="size-4.5 text-emerald-500" />
                <span>{{ session('status_updated') }}</span>
            </div>
            <button @click="show = false" class="text-zinc-400 hover:text-zinc-200 font-semibold text-xs cursor-pointer">Dismiss</button>
        </div>
    @endif

    <!-- Issue Cards Container -->
    <div class="space-y-4 transition-all duration-700 ease-out delay-100"
         :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0'">
        @forelse($this->issues as $issue)
            <div wire:key="issue-{{ $issue->id }}" 
                 class="bg-white/80 dark:bg-zinc-900/80 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-4 sm:p-5 shadow-xs hover:border-indigo-500/40 transition-all group relative overflow-hidden flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                
                <div class="flex items-start gap-4 flex-1 min-w-0">
                    <!-- Icon badge -->
                    @if($issue->status === 'open')
                        <div class="size-10 rounded-xl bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-500 shrink-0 group-hover:scale-105 transition-transform">
                            <flux:icon name="bug-ant" class="size-5" />
                        </div>
                    @else
                        <div class="size-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0 group-hover:scale-105 transition-transform">
                            <flux:icon name="check-circle" class="size-5" />
                        </div>
                    @endif

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            <span class="font-mono text-xs font-bold text-zinc-400 dark:text-zinc-500 bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded-md border border-zinc-200/50 dark:border-zinc-700/50">
                                TKT-{{ str_pad($issue->id, 4, '0', STR_PAD_LEFT) }}
                            </span>
                            <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-100 group-hover:text-indigo-400 transition-colors {{ $issue->status === 'closed' ? 'line-through opacity-60' : '' }}">
                                {{ $issue->title }}
                            </h3>
                            @if($issue->status === 'open')
                                <span class="bg-red-500/10 text-red-600 dark:text-red-400 text-[9px] font-mono font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border border-red-500/20">Open</span>
                            @else
                                <span class="bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-[9px] font-mono font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border border-emerald-500/20">Solved</span>
                            @endif
                        </div>
                        
                        <div class="text-xs text-zinc-600 dark:text-zinc-300 leading-relaxed bg-zinc-50/50 dark:bg-zinc-950/40 p-3 rounded-xl border border-zinc-200/40 dark:border-zinc-800/40 mt-2 whitespace-pre-wrap">
                            {{ $issue->description }}
                        </div>

                        <div class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-2.5 flex items-center gap-2 flex-wrap">
                            <span class="bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 font-medium px-2 py-0.5 rounded-md border border-indigo-500/20 flex items-center gap-1">
                                <flux:icon name="user" class="size-3 shrink-0" />
                                <span>Reporter: <strong>{{ $issue->user->name }}</strong></span>
                            </span>
                            <span>&bull;</span>
                            <span>Submitted {{ $issue->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="w-full md:w-auto flex justify-end shrink-0 pt-2 md:pt-0">
                    @if(auth()->user()->is_admin)
                        @if($issue->status === 'open')
                            <button type="button" wire:click="toggleStatus({{ $issue->id }})" 
                                    class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-xl px-4 py-2 text-xs active:scale-95 transition-all shadow-md shadow-emerald-500/20 flex items-center gap-1.5 border-none cursor-pointer">
                                <flux:icon name="check" class="size-3.5" />
                                <span>Mark Solved</span>
                            </button>
                        @else
                            <button type="button" wire:click="toggleStatus({{ $issue->id }})" 
                                    class="bg-zinc-800 hover:bg-zinc-700 text-zinc-300 font-semibold rounded-xl px-4 py-2 text-xs active:scale-95 transition-all border border-zinc-700/50 flex items-center gap-1.5 cursor-pointer">
                                <flux:icon name="arrow-path" class="size-3.5" />
                                <span>Re-open Issue</span>
                            </button>
                        @endif
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white/80 dark:bg-zinc-900/80 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-12 text-center flex flex-col items-center justify-center gap-3">
                <div class="size-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-500">
                    <flux:icon name="bug-ant" class="size-6" />
                </div>
                <div>
                    <h3 class="font-bold text-sm text-zinc-800 dark:text-zinc-200">No Issues Found</h3>
                    <p class="text-xs text-zinc-400 mt-1">No bug reports or feature requests matching your search filter criteria.</p>
                </div>
            </div>
        @endforelse
    </div>
    
    <div class="mt-2">
        <flux:pagination :paginator="$this->issues" />
    </div>
</div>

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
            ->when(trim($this->search), function ($query) {
                $term = '%' . mb_strtolower(trim($this->search)) . '%';
                
                // Extract digits if searching for something like TKT-0005, TKT-5, or 0005
                $idSearch = null;
                if (preg_match('/(?:tkt-)?0*([1-9]\d*)/i', $this->search, $matches)) {
                    $idSearch = (int) $matches[1];
                }

                $query->where(function ($q) use ($term, $idSearch) {
                    $q->whereRaw('LOWER(title) LIKE ?', [$term])
                      ->orWhereRaw('LOWER(description) LIKE ?', [$term]);
                    
                    if ($idSearch) {
                        $q->orWhere('id', $idSearch);
                    }

                    $q->orWhereHas('user', function ($uq) use ($term) {
                        $uq->whereRaw('LOWER(name) LIKE ?', [$term])
                          ->orWhereRaw('LOWER(email) LIKE ?', [$term]);
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

<div class="flex h-full w-full flex-col gap-6 p-3 sm:p-4 text-neutral-900 dark:text-neutral-100 max-w-6xl mx-auto mt-2 sm:mt-4 pb-16">
    <!-- Main Animated Content -->
    <div class="flex flex-col gap-6 animate-page-entrance">
        <!-- Header -->
    <div class="border-b border-zinc-200/80 dark:border-zinc-800/80 pb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                <div class="size-8.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                    <flux:icon name="flag" class="size-4.5 text-zinc-700 dark:text-zinc-300" />
                </div>
                <span>Issue &amp; Feedback Tracking</span>
            </h2>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">Review, manage, and resolve bugs and feature feedback submitted by team members.</p>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">
            <!-- Status Filter Segmented Control -->
            <div class="flex bg-zinc-100 dark:bg-zinc-900 rounded-xl p-1 shrink-0 border border-zinc-200/80 dark:border-zinc-800">
                <button type="button" wire:click="$set('statusFilter', 'all')" 
                        class="text-[10px] px-3 py-1.5 rounded-lg font-semibold uppercase tracking-wider transition-all duration-200 cursor-pointer {{ $statusFilter === 'all' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-xs border border-zinc-200 dark:border-zinc-600' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                    All
                </button>
                <button type="button" wire:click="$set('statusFilter', 'open')" 
                        class="text-[10px] px-3 py-1.5 rounded-lg font-semibold uppercase tracking-wider transition-all duration-200 cursor-pointer {{ $statusFilter === 'open' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-xs border border-zinc-200 dark:border-zinc-600' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                    Open ({{ \App\Models\Issue::where('status', 'open')->count() }})
                </button>
                <button type="button" wire:click="$set('statusFilter', 'closed')" 
                        class="text-[10px] px-3 py-1.5 rounded-lg font-semibold uppercase tracking-wider transition-all duration-200 cursor-pointer {{ $statusFilter === 'closed' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-xs border border-zinc-200 dark:border-zinc-600' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                    Solved ({{ \App\Models\Issue::where('status', 'closed')->count() }})
                </button>
            </div>
            
            <!-- Search bar -->
            <div class="relative flex-1 md:w-60 md:flex-none">
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Search ticket, title, user..." 
                       autocomplete="off"
                       class="w-full h-9 pl-9 pr-3 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-zinc-600 focus:ring-2 focus:ring-zinc-600/20 shadow-2xs transition-all">
                <flux:icon name="magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-400 pointer-events-none" />
            </div>
        </div>
    </div>

    <!-- Alert Status -->
    @if(session()->has('status_updated'))
        <div x-data="{ show: true }" x-show="show" class="p-3.5 bg-zinc-100 dark:bg-zinc-800/80 text-zinc-800 dark:text-zinc-200 rounded-2xl border border-zinc-200 dark:border-zinc-700 text-xs font-semibold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <flux:icon name="check-circle" class="size-4.5 text-emerald-500" />
                <span>{{ session('status_updated') }}</span>
            </div>
            <button @click="show = false" class="text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200 font-semibold text-xs cursor-pointer">Dismiss</button>
        </div>
    @endif

    <!-- Issue Cards Container -->
    <div class="space-y-4">
        @forelse($this->issues as $issue)
            <div wire:key="issue-{{ $issue->id }}" 
                 class="bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800 rounded-2xl p-4 sm:p-5 shadow-xs hover:border-zinc-400 dark:hover:border-zinc-700 transition-all group relative overflow-hidden flex flex-col gap-3.5">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-zinc-300/60 dark:via-zinc-600/40 to-transparent"></div>
                
                <!-- Card Header (Icon, Ticket ID, Title, Description & Status Badge) -->
                <div class="flex items-start justify-between gap-3 relative z-10">
                    <div class="flex items-start gap-3 min-w-0 flex-1">
                        <div class="size-9 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0 mt-0.5">
                            @if($issue->status === 'open')
                                <flux:icon name="bug-ant" class="size-4.5 text-zinc-700 dark:text-zinc-300" />
                            @else
                                <flux:icon name="check-circle" class="size-4.5 text-emerald-500 dark:text-emerald-400" />
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-mono text-[10px] font-semibold text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60 shrink-0">
                                    TKT-{{ str_pad($issue->id, 4, '0', STR_PAD_LEFT) }}
                                </span>
                                <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-100 group-hover:text-zinc-700 dark:group-hover:text-zinc-300 transition-colors {{ $issue->status === 'closed' ? 'line-through opacity-60' : '' }}">
                                    {{ $issue->title }}
                                </h3>
                            </div>
                            
                            <!-- Description Text Flow (Comfortable Spacing) -->
                            <div class="text-xs text-zinc-600 dark:text-zinc-300 leading-relaxed font-normal whitespace-pre-line mt-2.5 mb-1">{{ trim($issue->description) }}</div>
                        </div>
                    </div>

                    <div class="shrink-0">
                        @if($issue->status === 'open')
                            <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-[9px] font-mono font-medium uppercase tracking-wider px-2.5 py-1 rounded-md border border-zinc-200 dark:border-zinc-700/60 flex items-center gap-1.5">
                                <span class="size-1.5 rounded-full bg-amber-500 inline-block animate-pulse"></span>
                                <span>Open</span>
                            </span>
                        @else
                            <span class="bg-zinc-100/80 dark:bg-zinc-800/60 text-zinc-600 dark:text-zinc-400 text-[9px] font-mono font-medium uppercase tracking-wider px-2.5 py-1 rounded-md border border-zinc-200/80 dark:border-zinc-800 flex items-center gap-1.5">
                                <span class="size-1.5 rounded-full bg-emerald-500 inline-block"></span>
                                <span>Solved</span>
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Card Footer (Reporter metadata & Mark Solved action button) -->
                <div class="pt-3 border-t border-zinc-200/50 dark:border-zinc-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-[11px] relative z-10">
                    <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400 flex-wrap">
                        <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 font-medium px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60 flex items-center gap-1.5">
                            <flux:icon name="user" class="size-3 text-zinc-500 dark:text-zinc-400 shrink-0" />
                            <span>Reporter: <strong>{{ $issue->user->name }}</strong></span>
                        </span>
                        <span>&bull;</span>
                        <span>Submitted {{ $issue->created_at->diffForHumans() }}</span>
                    </div>

                    @if(auth()->user()->is_admin)
                        <div class="shrink-0 self-end sm:self-auto">
                            @if($issue->status === 'open')
                                <button type="button" wire:click="toggleStatus({{ $issue->id }})" 
                                        class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-xl px-3.5 py-1.5 text-xs border border-emerald-500 active:scale-95 transition-all shadow-xs shadow-emerald-500/20 flex items-center gap-1.5 cursor-pointer">
                                    <flux:icon name="check" class="size-3.5 text-white" />
                                    <span>Mark Solved</span>
                                </button>
                            @else
                                <button type="button" wire:click="toggleStatus({{ $issue->id }})" 
                                        class="bg-amber-500/10 dark:bg-amber-500/20 hover:bg-amber-500/20 text-amber-700 dark:text-amber-300 font-medium rounded-xl px-3.5 py-1.5 text-xs border border-amber-300/80 dark:border-amber-700/60 active:scale-95 transition-all flex items-center gap-1.5 cursor-pointer">
                                    <flux:icon name="arrow-path" class="size-3.5 text-amber-600 dark:text-amber-400" />
                                    <span>Re-open Issue</span>
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800 rounded-2xl p-12 text-center flex flex-col items-center justify-center gap-3">
                <div class="size-12 rounded-2xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300">
                    <flux:icon name="bug-ant" class="size-6 text-zinc-700 dark:text-zinc-300" />
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
</div>

<?php

use App\Models\Label;
use App\Models\Note;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    #[Url(as: 'selected')]
    public ?int $selectedNoteId = null;

    public string $search = '';

    public string $filterType = 'all'; // 'all', 'pinned', 'archived'

    public ?int $filterProjectId = null;

    public ?int $filterLabelId = null;

    public string $title = '';

    public ?string $content = '';

    public ?int $projectId = null;

    public ?int $taskId = null;

    public bool $isPinned = false;

    public array $selectedLabelIds = [];

    public string $viewMode = 'edit'; // 'edit', 'split', 'preview'

    public string $saveStatus = 'saved'; // 'saved', 'saving'

    public bool $isCreating = false;

    public ?int $noteToDeleteId = null;

    protected ?Note $cachedCurrentNote = null;

    public function mount(): void
    {
        if ($this->selectedNoteId) {
            $note = auth()->user()->notes()->with(['project', 'task', 'labels'])->find($this->selectedNoteId);
            if ($note) {
                $this->loadNote($note);
                return;
            }
        }

        $firstNote = auth()->user()->notes()->with(['project', 'task', 'labels'])->active()->orderByDesc('is_pinned')->orderByDesc('updated_at')->first();
        if ($firstNote) {
            $this->loadNote($firstNote);
        } else {
            $this->createNote();
        }
    }

    #[On('note-updated')]
    public function handleExternalNoteUpdated(int $id, string $source = ''): void
    {
        if ($source === 'notes') {
            return;
        }

        $this->cachedCurrentNote = null;

        if ($this->selectedNoteId === $id) {
            $note = auth()->user()->notes()->with(['project', 'task', 'labels'])->find($id);
            if ($note) {
                $this->loadNote($note);
            }
        }
    }

    #[On('note-created')]
    public function handleExternalNoteCreated(?int $id = null, string $source = ''): void
    {
        if ($source === 'notes') {
            return;
        }

        $this->cachedCurrentNote = null;
    }

    public function loadNote(Note $note): void
    {
        $this->isCreating = false;
        $this->cachedCurrentNote = $note;
        $this->selectedNoteId = $note->id;
        $this->title = $note->title ?? '';

        $raw = $note->content ?? '';
        if ($raw !== '' && ! str_contains($raw, '<p>') && ! str_contains($raw, '<div>') && ! str_contains($raw, '<h1') && ! str_contains($raw, '<h2') && ! str_contains($raw, '<ul') && ! str_contains($raw, '<table')) {
            if (preg_match('/(^#|\*\*|__|\*|_|~~|`|\[.*\]\(.*\)|\n- |\n\d+\. )/m', $raw)) {
                $raw = Str::markdown($raw);
            }
        }
        $this->content = $raw;

        $this->projectId = $note->project_id;
        $this->taskId = $note->task_id;
        $this->isPinned = (bool) $note->is_pinned;
        $this->selectedLabelIds = $note->relationLoaded('labels') 
            ? $note->labels->pluck('id')->toArray() 
            : $note->labels()->pluck('labels.id')->toArray();
        $this->saveStatus = 'saved';
        $this->dispatch('note-saved');
        $this->dispatch('note-loaded', content: $this->content);
    }

    public function updatedSelectedNoteId($id): void
    {
        if ($id && (! $this->cachedCurrentNote || $this->cachedCurrentNote->id !== (int) $id)) {
            $note = auth()->user()->notes()->with(['project', 'task', 'labels'])->find($id);
            if ($note) {
                $this->loadNote($note);
            }
        }
    }

    protected function hasChanges(): bool
    {
        $hasContent = trim(strip_tags($this->content ?? '')) !== '' || trim($this->content ?? '') !== '';
        $hasCustomTitle = trim($this->title ?? '') !== '' && trim($this->title ?? '') !== 'Untitled Note';

        return $hasContent || $hasCustomTitle;
    }

    public function selectNote(int $id): void
    {
        if (! $this->isCreating && $this->selectedNoteId === $id) {
            return;
        }

        // If user was creating a new note draft and had typed changes, save it first
        if ($this->isCreating) {
            if ($this->hasChanges()) {
                $this->saveNote();
            }
            $this->isCreating = false;
        }

        // Load the selected note from database
        $note = auth()->user()->notes()->with(['project', 'task', 'labels'])->find($id);
        if ($note) {
            $this->loadNote($note);
        }
    }

    public function createNote(): void
    {
        // If already in new note creation mode
        if ($this->isCreating) {
            if ($this->hasChanges()) {
                $this->saveNote();
            } else {
                // No changes typed, remain in clean draft mode without saving
                return;
            }
        } elseif ($this->selectedNoteId) {
            $this->saveNote();
        }

        // Enter new note draft mode (DO NOT save or insert row into database yet!)
        $this->isCreating = true;
        $this->selectedNoteId = null;
        $this->cachedCurrentNote = null;
        $this->title = '';
        $this->content = '';
        $this->projectId = $this->filterProjectId ?: null;
        $this->taskId = null;
        $this->selectedLabelIds = $this->filterLabelId ? [$this->filterLabelId] : [];
        $this->isPinned = false;
        $this->saveStatus = 'saved';
        $this->dispatch('note-saved');
        $this->dispatch('note-loaded', content: '');
    }

    public function updatedTitle(): void
    {
        $this->saveNote();
    }

    public function updatedContent(): void
    {
        $this->saveNote();
    }

    public function updatedProjectId(): void
    {
        if ($this->taskId) {
            $task = auth()->user()->tasks()->find($this->taskId);
            if (! $task || ! $this->projectId || $task->project_id != $this->projectId) {
                $this->taskId = null;
            }
        }

        $this->saveNote();
    }

    public function updatedTaskId(): void
    {
        $this->saveNote();
    }

    public function saveNote(): void
    {
        if ($this->isCreating || ! $this->selectedNoteId) {
            if (! $this->hasChanges()) {
                $this->saveStatus = 'saved';
                $this->dispatch('note-saved');
                return;
            }

            $this->saveStatus = 'saving';

            $note = auth()->user()->notes()->create([
                'title' => trim($this->title) !== '' ? $this->title : 'Untitled Note',
                'content' => $this->content ?? '',
                'project_id' => $this->projectId ?: null,
                'task_id' => $this->taskId ?: null,
                'is_pinned' => $this->isPinned,
                'is_archived' => false,
            ]);

            if (! empty($this->selectedLabelIds)) {
                $note->labels()->sync($this->selectedLabelIds);
            }

            $this->isCreating = false;
            $this->selectedNoteId = $note->id;
            $this->cachedCurrentNote = auth()->user()->notes()->with(['project', 'task', 'labels'])->find($note->id);

            $this->saveStatus = 'saved';
            $this->dispatch('note-saved');
            $this->dispatch('note-created', id: $note->id, source: 'notes');
            $this->dispatch('note-updated', id: $note->id, source: 'notes');
            return;
        }

        $note = auth()->user()->notes()->find($this->selectedNoteId);
        if (! $note) {
            return;
        }

        $this->saveStatus = 'saving';

        $note->update([
            'title' => trim($this->title) !== '' ? $this->title : 'Untitled Note',
            'content' => $this->content,
            'project_id' => $this->projectId ?: null,
            'task_id' => $this->taskId ?: null,
            'is_pinned' => $this->isPinned,
        ]);

        $note->labels()->sync($this->selectedLabelIds);

        $this->cachedCurrentNote = auth()->user()->notes()->with(['project', 'task', 'labels'])->find($this->selectedNoteId);

        $this->saveStatus = 'saved';
        $this->dispatch('note-saved');
        $this->dispatch('note-updated', id: $note->id, source: 'notes');
    }

    public function togglePin(): void
    {
        $this->isPinned = ! $this->isPinned;

        if ($this->selectedNoteId && ! $this->isCreating) {
            $note = auth()->user()->notes()->find($this->selectedNoteId);
            if ($note) {
                $note->update(['is_pinned' => $this->isPinned]);
            }
        }

        $this->dispatch('note-saved');
        if ($this->selectedNoteId) {
            $this->dispatch('note-updated', id: $this->selectedNoteId, source: 'notes');
        }
    }

    public function confirmDeleteNote(int $id): void
    {
        $this->noteToDeleteId = $id;
        $this->js("\$flux.modal('delete-note-modal').show()");
        $this->dispatch('modal-show', name: 'delete-note-modal');
        $this->dispatch('open-modal', name: 'delete-note-modal');
    }

    public function toggleArchive(?int $id = null): void
    {
        $targetId = $id ?: $this->selectedNoteId;

        if ($this->isCreating && ! $id) {
            $this->isCreating = false;
            $firstNote = auth()->user()->notes()->active()->orderByDesc('is_pinned')->orderByDesc('updated_at')->first();
            if ($firstNote) {
                $this->loadNote($firstNote);
            }
            return;
        }

        if (! $targetId) {
            return;
        }

        $note = auth()->user()->notes()->find($targetId);
        if ($note) {
            $newArchived = ! $note->is_archived;
            $note->update(['is_archived' => $newArchived]);

            if ($this->selectedNoteId === $targetId) {
                $nextNote = auth()->user()->notes()
                    ->when($this->filterType === 'archived', fn ($q) => $q->archived(), fn ($q) => $q->active())
                    ->orderByDesc('is_pinned')
                    ->orderByDesc('updated_at')
                    ->first();

                if ($nextNote) {
                    $this->loadNote($nextNote);
                } else {
                    $this->createNote();
                }
            }

            $this->dispatch('note-saved');
            $this->dispatch('note-updated', id: $targetId, source: 'notes');
        }
    }

    public function deleteNote(): void
    {
        $targetId = $this->noteToDeleteId ?: $this->selectedNoteId;

        if ($this->isCreating && ! $targetId) {
            $this->isCreating = false;
            $this->js("\$flux.modal('delete-note-modal').close()");
            $this->dispatch('modal-close', name: 'delete-note-modal');
            $this->dispatch('close-modal', name: 'delete-note-modal');
            $first = auth()->user()->notes()->active()->orderByDesc('is_pinned')->orderByDesc('updated_at')->first();
            if ($first) {
                $this->loadNote($first);
            } else {
                $this->createNote();
            }
            return;
        }

        if (! $targetId) {
            $this->js("\$flux.modal('delete-note-modal').close()");
            $this->dispatch('modal-close', name: 'delete-note-modal');
            $this->dispatch('close-modal', name: 'delete-note-modal');
            return;
        }

        $note = auth()->user()->notes()->find($targetId);
        if ($note) {
            $note->delete();

            if ($this->selectedNoteId === $targetId) {
                $nextNote = auth()->user()->notes()
                    ->when($this->filterType === 'archived', fn ($q) => $q->archived(), fn ($q) => $q->active())
                    ->orderByDesc('is_pinned')
                    ->orderByDesc('updated_at')
                    ->first();

                if ($nextNote) {
                    $this->loadNote($nextNote);
                } else {
                    $this->createNote();
                }
            }
        }

        $this->noteToDeleteId = null;
        $this->js("\$flux.modal('delete-note-modal').close()");
        $this->dispatch('modal-close', name: 'delete-note-modal');
        $this->dispatch('close-modal', name: 'delete-note-modal');
    }

    public function toggleLabel(int $labelId): void
    {
        if (in_array($labelId, $this->selectedLabelIds)) {
            $this->selectedLabelIds = array_values(array_diff($this->selectedLabelIds, [$labelId]));
        } else {
            $this->selectedLabelIds[] = $labelId;
        }

        $this->saveNote();
    }

    #[Computed]
    public function notesList()
    {
        return auth()->user()->notes()
            ->with(['project', 'labels', 'task'])
            ->when($this->filterType === 'archived', fn ($q) => $q->archived(), fn ($q) => $q->active())
            ->when($this->filterType === 'pinned', fn ($q) => $q->pinned())
            ->when($this->filterProjectId, fn ($q) => $q->where('project_id', $this->filterProjectId))
            ->when($this->filterLabelId, fn ($q) => $q->whereHas('labels', fn ($lq) => $lq->where('labels.id', $this->filterLabelId)))
            ->search($this->search)
            ->orderByDesc('is_pinned')
            ->orderByDesc('updated_at')
            ->get();
    }

    #[Computed]
    public function currentNote()
    {
        if ($this->selectedNoteId) {
            if ($this->cachedCurrentNote && $this->cachedCurrentNote->id === $this->selectedNoteId) {
                return $this->cachedCurrentNote;
            }

            return $this->cachedCurrentNote = auth()->user()->notes()->with(['project', 'task', 'labels'])->find($this->selectedNoteId);
        }

        if ($this->isCreating) {
            $dummy = new Note([
                'title' => $this->title,
                'content' => $this->content,
                'project_id' => $this->projectId,
                'task_id' => $this->taskId,
                'is_pinned' => $this->isPinned,
                'is_archived' => false,
            ]);
            $dummy->id = 0;
            $dummy->created_at = now();
            $dummy->updated_at = now();

            if ($this->projectId) {
                $dummy->setRelation('project', auth()->user()->projects()->find($this->projectId));
            }
            if ($this->taskId) {
                $dummy->setRelation('task', auth()->user()->tasks()->find($this->taskId));
            }

            return $dummy;
        }

        return null;
    }

    #[Computed]
    public function noteToDelete()
    {
        if ($this->noteToDeleteId) {
            return auth()->user()->notes()->find($this->noteToDeleteId);
        }

        return $this->currentNote;
    }

    #[Computed]
    public function projects()
    {
        return auth()->user()->projects()->orderBy('name')->get();
    }

    #[Computed]
    public function tasks()
    {
        if (! $this->projectId) {
            return collect();
        }

        return auth()->user()->tasks()
            ->where('project_id', $this->projectId)
            ->where(function ($q) {
                $q->where('status', '!=', Task::STATUS_DONE);
                if ($this->taskId) {
                    $q->orWhere('id', $this->taskId);
                }
            })
            ->orderBy('title')
            ->get();
    }

    #[Computed]
    public function allLabels()
    {
        return auth()->user()->labels()->orderBy('name')->get();
    }

    public function renderMarkdown(?string $text): string
    {
        if (! $text) {
            return '<p class="text-zinc-400 italic">No content yet...</p>';
        }

        return Str::markdown($text);
    }
};
?>

<!-- Identical outer container to Manage page, stretched to bottom on desktop -->
<div class="flex flex-1 h-full w-full flex-col gap-3.5 sm:gap-4 px-3 sm:px-4 py-2 text-neutral-900 dark:text-neutral-100 max-w-7xl mx-auto mt-1 sm:mt-3 pb-2 sm:pb-4 animate-page-entrance"
     x-data="{
         saveStatus: 'saved',
         copied: false,
         copyContent() {
             const el = document.getElementById('note-rich-editor');
             const text = el ? (el.innerText || el.textContent) : ($wire.content || '');
             navigator.clipboard.writeText(text);
             this.copied = true;
             setTimeout(() => this.copied = false, 2000);
         }
     }"
     @note-saved.window="saveStatus = 'saved'"
     @note-created.window="saveStatus = 'saved'"
     @note-saving.window="saveStatus = 'saving'"
     @keydown.window.cmd.n.prevent="$wire.createNote()"
     @keydown.window.ctrl.n.prevent="$wire.createNote()">

    <!-- Main Content Container (No re-animating class on Livewire updates) -->
    <div class="flex flex-1 flex-col gap-3.5 sm:gap-4">
        
        <!-- Top Header Studio Banner (Identical layout to Workspace Management Studio) -->
        <div class="border-b border-zinc-200/80 dark:border-zinc-800/80 pb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                    <div class="size-8.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                        <flux:icon name="document-text" class="size-4.5 text-zinc-700 dark:text-zinc-300" />
                    </div>
                    <span>Developer Notes &amp; Scratchpad</span>
                </h2>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">Write notes in Markdown, store snippets, and link them with your projects and tasks.</p>
            </div>
            
            <!-- Tab Navigation Switcher (Identical pill style to Manage) -->
            <div class="flex items-center p-1 bg-zinc-100 dark:bg-zinc-900 rounded-xl border border-zinc-200/80 dark:border-zinc-800 self-start md:self-auto overflow-x-auto max-w-full">
                <button wire:click="$set('filterType', 'all')" 
                        type="button"
                        class="px-3.5 py-1.5 rounded-lg text-xs transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap {{ $filterType === 'all' ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white shadow-xs font-semibold' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-200 font-medium' }}">
                    <flux:icon name="document-text" class="size-4 shrink-0" />
                    <span>All Notes</span>
                    <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-zinc-200/60 dark:bg-zinc-700/60 text-zinc-700 dark:text-zinc-300">
                        {{ auth()->user()->notes()->active()->count() }}
                    </span>
                </button>

                <button wire:click="$set('filterType', 'pinned')" 
                        type="button"
                        class="px-3.5 py-1.5 rounded-lg text-xs transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap {{ $filterType === 'pinned' ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white shadow-xs font-semibold' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-200 font-medium' }}">
                    <flux:icon name="bookmark" class="size-4 shrink-0 text-amber-500" />
                    <span>Pinned</span>
                    <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-zinc-200/60 dark:bg-zinc-700/60 text-zinc-700 dark:text-zinc-300">
                        {{ auth()->user()->notes()->active()->pinned()->count() }}
                    </span>
                </button>

                <button wire:click="$set('filterType', 'archived')" 
                        type="button"
                        class="px-3.5 py-1.5 rounded-lg text-xs transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap {{ $filterType === 'archived' ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white shadow-xs font-semibold' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-200 font-medium' }}">
                    <flux:icon name="archive-box" class="size-4 shrink-0" />
                    <span>Archived</span>
                    <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-zinc-200/60 dark:bg-zinc-700/60 text-zinc-700 dark:text-zinc-300">
                        {{ auth()->user()->notes()->archived()->count() }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Controls Bar (Identical to Manage's Controls Bar) -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-3 shadow-xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <!-- Left Controls: Search & Filters -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 flex-1 min-w-0">
                <!-- Search Input -->
                <div class="relative flex-1 min-w-[200px]">
                    <input type="text" 
                           wire:model.live.debounce.300ms="search" 
                           placeholder="Search notes or markdown..." 
                           autocomplete="off"
                           class="w-full h-9 pl-9 pr-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-amber-500 transition-all">
                    <flux:icon name="magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-400 pointer-events-none" />
                </div>

                <!-- Project Filter Dropdown -->
                <div class="w-full sm:w-auto">
                    <select wire:model.live="filterProjectId" class="h-9 px-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-amber-500 cursor-pointer">
                        <option value="">All Projects</option>
                        @foreach($this->projects as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Label Filter Dropdown -->
                <div class="w-full sm:w-auto">
                    <select wire:model.live="filterLabelId" class="h-9 px-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-amber-500 cursor-pointer">
                        <option value="">All Labels</option>
                        @foreach($this->allLabels as $l)
                            <option value="{{ $l->id }}">{{ $l->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Right Controls: Add Note Button (Amber, bold, h-9) -->
            <div class="flex items-center gap-2 shrink-0">
                <button wire:click="createNote" 
                        type="button" 
                        class="h-9 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs flex items-center gap-1.5 shadow-xs shadow-amber-500/20 active:scale-95 transition-all cursor-pointer">
                    <flux:icon name="plus" class="size-4 text-zinc-950" />
                    <span>New Note</span>
                </button>
            </div>
        </div>

        <!-- Main Body: 2-Panel Workstation Card (Stretches down to bottom of desktop screen) -->
        <div class="flex-1 min-h-[580px] h-[calc(100vh-14rem)] lg:h-[calc(100vh-13rem)] flex overflow-hidden bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl shadow-xs">
            
            <!-- LEFT PANEL: Notes List -->
            <aside class="w-72 sm:w-80 lg:w-88 border-r border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40 flex flex-col shrink-0">
                <div class="p-3 border-b border-zinc-200/80 dark:border-zinc-800 flex items-center justify-between">
                    <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider font-mono">Note List</span>
                    <span class="text-[11px] font-mono text-zinc-400">{{ count($this->notesList) }} total</span>
                </div>

                <!-- Notes List Cards (Scrollable) -->
                <div class="flex-1 overflow-y-auto p-2 space-y-1.5 custom-scrollbar">
                    @forelse($this->notesList as $noteItem)
                        @php
                            $isSelected = (! $isCreating && $selectedNoteId === $noteItem->id);
                        @endphp
                        <div wire:click="selectNote({{ $noteItem->id }})" 
                             wire:key="note-item-{{ $noteItem->id }}"
                             class="group relative p-3 rounded-xl cursor-pointer transition-all duration-150 border {{ $isSelected ? 'bg-amber-500/10 dark:bg-amber-500/[0.12] border-amber-500/70 dark:border-amber-500/60 shadow-[0_0_15px_rgba(245,158,11,0.2)] ring-1 ring-amber-500/40' : 'bg-white dark:bg-zinc-800/60 border-zinc-200/60 dark:border-zinc-800/80 hover:border-zinc-300 dark:hover:border-zinc-700 hover:bg-zinc-100/60 dark:hover:bg-zinc-800 shadow-none ring-0' }}">

                            <div class="flex items-start justify-between gap-2">
                                <h3 class="text-xs line-clamp-1 flex-1 min-w-0 {{ $isSelected ? 'text-amber-600 dark:text-amber-400 font-bold' : 'text-zinc-900 dark:text-zinc-100 font-semibold' }}">
                                    {{ $noteItem->title ?: 'Untitled Note' }}
                                </h3>

                                <div class="flex items-center gap-1 shrink-0 -mr-1 -mt-0.5">
                                    @if($noteItem->is_pinned)
                                        <flux:icon name="bookmark" class="size-3 text-amber-500 shrink-0" />
                                    @endif

                                    <!-- Card Action Kebab Menu -->
                                    <div class="relative" x-data="{ cardMenuOpen: false }">
                                        <button @click.stop="cardMenuOpen = !cardMenuOpen" 
                                                type="button" 
                                                title="Note Actions"
                                                class="size-6 rounded-md text-zinc-400 hover:text-zinc-900 dark:text-zinc-500 dark:hover:text-white hover:bg-zinc-200/70 dark:hover:bg-zinc-700/70 flex items-center justify-center transition-colors cursor-pointer opacity-0 group-hover:opacity-100 focus:opacity-100 {{ $isSelected ? 'opacity-100' : '' }}"
                                                :class="cardMenuOpen ? '!opacity-100 bg-zinc-200/70 dark:bg-zinc-700/70 text-zinc-900 dark:text-white' : ''">
                                            <flux:icon name="ellipsis-vertical" class="size-3.5" />
                                        </button>

                                        <div x-show="cardMenuOpen" 
                                             @click.away="cardMenuOpen = false" 
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="opacity-0 scale-95"
                                             x-transition:enter-end="opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-75"
                                             x-transition:leave-start="opacity-100 scale-100"
                                             x-transition:leave-end="opacity-0 scale-95"
                                             class="absolute right-0 mt-1 w-36 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xl shadow-zinc-950/10 dark:shadow-zinc-950/40 p-1 z-30 space-y-0.5 ring-1 ring-black/5 dark:ring-white/5"
                                             style="display: none;">
                                            
                                            <button wire:click.stop="toggleArchive({{ $noteItem->id }})" 
                                                    @click="cardMenuOpen = false"
                                                    type="button" 
                                                    class="note-dropdown-item w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-medium flex items-center gap-2 text-zinc-700 dark:text-zinc-200 cursor-pointer select-none">
                                                <flux:icon name="archive-box" class="size-3.5 text-zinc-400 shrink-0" />
                                                <span class="whitespace-nowrap">{{ $noteItem->is_archived ? 'Unarchive' : 'Archive' }}</span>
                                            </button>

                                            <button wire:click.stop="confirmDeleteNote({{ $noteItem->id }})" 
                                                    @click="cardMenuOpen = false"
                                                    type="button" 
                                                    class="note-dropdown-danger w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-medium flex items-center gap-2 text-rose-600 dark:text-rose-400 cursor-pointer select-none">
                                                <flux:icon name="trash" class="size-3.5 text-rose-500 shrink-0" />
                                                <span class="whitespace-nowrap">Delete Note</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 line-clamp-2 mt-1 font-sans leading-relaxed">
                                {{ $noteItem->excerpt ?: 'Empty note...' }}
                            </p>

                            <div class="flex items-center justify-between gap-2 mt-2 pt-1 border-t border-zinc-100 dark:border-zinc-800/60 text-[10px] text-zinc-400">
                                <span class="font-mono shrink-0">{{ $noteItem->updated_at->format('d M Y, H:i') }}</span>

                                <div class="flex items-center gap-1.5 overflow-hidden">
                                    @if($noteItem->project)
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 font-medium truncate max-w-[80px]" title="Project: {{ $noteItem->project->name }}">
                                            {{ $noteItem->project->name }}
                                        </span>
                                    @endif

                                    @if($noteItem->task)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded bg-amber-500/10 dark:bg-amber-500/15 text-amber-700 dark:text-amber-300 font-medium truncate max-w-[95px] border border-amber-500/20" title="Task: {{ $noteItem->task->title }}">
                                            <flux:icon name="clipboard-document-check" class="size-2.5 shrink-0 text-amber-500" />
                                            <span class="truncate">{{ $noteItem->task->title }}</span>
                                        </span>
                                    @endif

                                    @foreach($noteItem->labels->take(2) as $cardLabel)
                                        <span class="size-2 rounded-full shrink-0" style="background-color: var(--color-{{ $cardLabel->color }}-500, #a1a1aa);" title="{{ $cardLabel->name }}"></span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 px-4 text-center">
                            <flux:icon name="document" class="size-8 mx-auto text-zinc-300 dark:text-zinc-600 mb-2" />
                            <p class="text-xs font-medium text-zinc-600 dark:text-zinc-400">No notes found</p>
                            <p class="text-[11px] text-zinc-400 dark:text-zinc-500 mt-0.5">Try changing your search or filters.</p>
                            <button wire:click="createNote" class="mt-3 px-3 py-1.5 text-xs font-semibold text-amber-600 dark:text-amber-400 hover:underline">
                                + Create Note
                            </button>
                        </div>
                    @endforelse
                </div>
            </aside>

            <!-- RIGHT PANEL: Editor & Preview Area -->
            <main class="flex-1 flex flex-col bg-white dark:bg-zinc-900 overflow-hidden"
                  x-data="notesRichEditor($wire)"
                  x-init="initEditor()"
                  @note-loaded.window="handleNoteLoaded($event)">
                @if($this->currentNote)
<style global>
    .note-dropdown-item {
        transition: background-color 0.12s ease, color 0.12s ease;
    }
    .note-dropdown-item:hover {
        background-color: #f4f4f5 !important;
        color: #18181b !important;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) .note-dropdown-item:hover,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & .note-dropdown-item:hover {
        background-color: #3f3f46 !important;
        color: #ffffff !important;
    }
    .note-dropdown-danger {
        transition: background-color 0.12s ease, color 0.12s ease;
    }
    .note-dropdown-danger:hover {
        background-color: #fff1f2 !important;
        color: #e11d48 !important;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) .note-dropdown-danger:hover,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & .note-dropdown-danger:hover {
        background-color: rgba(244, 63, 94, 0.2) !important;
        color: #fb7185 !important;
    }
    #note-rich-editor:empty:before {
        content: attr(data-placeholder);
        color: #a1a1aa;
        pointer-events: none;
        display: block;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #note-rich-editor:empty:before,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #note-rich-editor:empty:before {
        color: #525252;
    }

    /* Headings */
    #note-rich-editor h1 {
        font-size: 1.625rem;
        font-weight: 700;
        line-height: 1.25;
        margin-top: 1.25rem;
        margin-bottom: 0.5rem;
        color: #18181b;
        letter-spacing: -0.02em;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #note-rich-editor h1,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #note-rich-editor h1 {
        color: #fafafa;
    }
    #note-rich-editor h2 {
        font-size: 1.25rem;
        font-weight: 600;
        line-height: 1.3;
        margin-top: 1rem;
        margin-bottom: 0.375rem;
        color: #27272a;
        letter-spacing: -0.015em;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #note-rich-editor h2,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #note-rich-editor h2 {
        color: #f4f4f5;
    }
    #note-rich-editor h3 {
        font-size: 1.05rem;
        font-weight: 600;
        line-height: 1.35;
        margin-top: 0.75rem;
        margin-bottom: 0.25rem;
        color: #3f3f46;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #note-rich-editor h3,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #note-rich-editor h3 {
        color: #e4e4e7;
    }

    /* Paragraphs */
    #note-rich-editor p {
        margin-top: 0.25rem;
        margin-bottom: 0.5rem;
        line-height: 1.65;
    }

    /* Lists: Bulleted & Numbered */
    #note-rich-editor ul {
        list-style-type: disc !important;
        margin-top: 0.375rem !important;
        margin-bottom: 0.375rem !important;
        padding-left: 1.5rem !important;
    }
    #note-rich-editor ul ul {
        list-style-type: circle !important;
    }
    #note-rich-editor ul ul ul {
        list-style-type: square !important;
    }
    #note-rich-editor ul > li {
        list-style-type: disc !important;
        display: list-item !important;
        margin-top: 0.2rem;
        margin-bottom: 0.2rem;
        line-height: 1.55;
    }

    #note-rich-editor ol {
        list-style-type: decimal !important;
        margin-top: 0.375rem !important;
        margin-bottom: 0.375rem !important;
        padding-left: 1.5rem !important;
    }
    #note-rich-editor ol ol {
        list-style-type: lower-alpha !important;
    }
    #note-rich-editor ol ol ol {
        list-style-type: lower-roman !important;
    }
    #note-rich-editor ol > li {
        list-style-type: decimal !important;
        display: list-item !important;
        margin-top: 0.2rem;
        margin-bottom: 0.2rem;
        line-height: 1.55;
    }

    /* Code and Preformatted blocks */
    #note-rich-editor pre {
        background-color: #f4f4f5;
        border: 1px solid #e4e4e7;
        border-radius: 0.625rem;
        padding: 0.75rem 1rem;
        margin: 0.75rem 0;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 0.8125rem;
        line-height: 1.55;
        white-space: pre-wrap;
        word-break: break-word;
        color: #18181b;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #note-rich-editor pre,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #note-rich-editor pre {
        background-color: #18181b;
        border-color: #27272a;
        color: #e4e4e7;
    }
    #note-rich-editor code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 0.85em;
        background-color: rgba(0, 0, 0, 0.05);
        padding: 0.15rem 0.35rem;
        border-radius: 0.25rem;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #note-rich-editor code,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #note-rich-editor code {
        background-color: rgba(255, 255, 255, 0.1);
    }

    /* Tables: Balanced Light & Dark Theme Adaptation */
    #note-rich-editor table {
        width: 100% !important;
        border-collapse: collapse !important;
        margin: 1.25rem 0 !important;
        font-size: 0.8125rem !important;
        border: 1px solid #e4e4e7 !important;
        border-radius: 0.375rem !important;
    }
    #note-rich-editor thead {
        border-bottom: 2px solid #e4e4e7 !important;
    }
    #note-rich-editor thead tr,
    #note-rich-editor thead tr th,
    #note-rich-editor th {
        background-color: #f4f4f5 !important;
        color: #18181b !important;
        font-weight: 600 !important;
        text-align: left !important;
        border: 1px solid #e4e4e7 !important;
        padding: 0.5rem 0.75rem !important;
        letter-spacing: -0.01em !important;
    }
    #note-rich-editor td {
        background-color: transparent !important;
        border: 1px solid #e4e4e7 !important;
        padding: 0.5rem 0.75rem !important;
        color: #27272a !important;
        line-height: 1.5 !important;
    }
    #note-rich-editor tbody tr:hover td {
        background-color: rgba(0, 0, 0, 0.02) !important;
    }

    /* Table: Dark Mode */
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #note-rich-editor table,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #note-rich-editor table {
        border-color: #27272a !important;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #note-rich-editor thead,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #note-rich-editor thead {
        border-bottom-color: #3f3f46 !important;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #note-rich-editor thead tr,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #note-rich-editor thead tr th,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #note-rich-editor th,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #note-rich-editor thead tr,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #note-rich-editor thead tr th,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #note-rich-editor th {
        background-color: #27272a !important;
        color: #f4f4f5 !important;
        border-color: #3f3f46 !important;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #note-rich-editor td,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #note-rich-editor td {
        background-color: transparent !important;
        color: #d4d4d8 !important;
        border-color: #27272a !important;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #note-rich-editor tbody tr:hover td,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #note-rich-editor tbody tr:hover td {
        background-color: rgba(255, 255, 255, 0.03) !important;
    }

    /* Blockquote */
    #note-rich-editor blockquote {
        border-left: 3px solid #f59e0b;
        padding-left: 1rem;
        margin: 0.75rem 0;
        color: #71717a;
        font-style: italic;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #note-rich-editor blockquote,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #note-rich-editor blockquote {
        color: #a1a1aa;
        border-left-color: #d97706;
    }

    /* Horizontal Divider */
    #note-rich-editor hr {
        border: none;
        border-top: 1px solid #e4e4e7;
        margin: 1.25rem 0;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #note-rich-editor hr,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #note-rich-editor hr {
        border-top-color: #27272a;
    }

    /* Checklist Items */
    #note-rich-editor .note-checklist-item,
    #note-rich-editor [data-checklist="true"],
    #note-rich-editor div:has(> input.note-checkbox),
    #note-rich-editor div:has(> input[type="checkbox"]) {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: wrap !important;
        align-items: flex-start !important;
        gap: 0.625rem !important;
        margin: 0.375rem 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
    }
    #note-rich-editor .note-checklist-item > .note-checklist-text,
    #note-rich-editor [data-checklist="true"] > .note-checklist-text,
    #note-rich-editor .note-checklist-item > span,
    #note-rich-editor [data-checklist="true"] > span {
        display: block !important;
        flex: 1 1 calc(100% - 2.5rem) !important;
        min-width: 0 !important;
        outline: none !important;
        word-break: break-word;
    }
    #note-rich-editor input.note-checkbox,
    #note-rich-editor input[type="checkbox"] {
        margin-top: 0.25rem !important;
        cursor: pointer !important;
        flex-shrink: 0 !important;
        user-select: none !important;
        -webkit-user-select: none !important;
        pointer-events: auto !important;
    }
    #note-rich-editor input[type="checkbox"]:checked + span,
    #note-rich-editor input[type="checkbox"]:checked + div,
    #note-rich-editor input[type="checkbox"]:checked ~ .note-checklist-text {
        text-decoration: line-through !important;
        opacity: 0.5 !important;
    }
</style>

                    <!-- Top Toolbar inside Editor -->
                    <div class="p-3 sm:px-6 py-2.5 border-b border-zinc-200/80 dark:border-zinc-800 flex items-center justify-between gap-3 bg-zinc-50/40 dark:bg-zinc-900/40 shrink-0">
                        <!-- Left Controls: Project, Task, Labels -->
                        <div class="flex items-center flex-wrap gap-2">
                            <!-- Project Selector (Clean Native Select) -->
                            <select wire:model.live="projectId" 
                                    @change="saveStatus = 'saving'"
                                    class="h-8 py-1 pl-2.5 pr-7 text-xs font-medium rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500/20 cursor-pointer min-w-[125px] max-w-[190px] truncate shadow-2xs">
                                <option value="">No Project</option>
                                @foreach($this->projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>

                            <!-- Task Selector (Filtered by Selected Project) -->
                            <div class="hidden md:block">
                                <select wire:model.live="taskId" 
                                        @change="saveStatus = 'saving'"
                                        @disabled(! $projectId)
                                        class="h-8 py-1 pl-2.5 pr-7 text-xs font-medium rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500/20 cursor-pointer min-w-[135px] max-w-[210px] truncate disabled:opacity-50 disabled:cursor-not-allowed shadow-2xs">
                                    @if(! $projectId)
                                        <option value="">Select Project First</option>
                                    @else
                                        <option value="">No Task</option>
                                        @forelse($this->tasks as $task)
                                            <option value="{{ $task->id }}">{{ $task->title }}</option>
                                        @empty
                                            <option value="" disabled>No tasks in project</option>
                                        @endforelse
                                    @endif
                                </select>
                            </div>

                            <!-- Labels Picker -->
                            <div class="relative" x-data="{ openLabels: false }">
                                <button @click="openLabels = !openLabels" 
                                        type="button" 
                                        class="h-8 px-2.5 text-xs font-medium rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:border-zinc-300 dark:hover:border-zinc-600 flex items-center gap-1.5 cursor-pointer shadow-2xs transition-colors focus:outline-none"
                                        :class="openLabels ? 'text-amber-600 dark:text-amber-400 border-amber-500/50' : ''">
                                    <flux:icon name="tag" class="size-3.5 text-zinc-400" />
                                    <span>Labels</span>
                                    @if(count($selectedLabelIds) > 0)
                                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono font-bold bg-amber-500/20 text-amber-600 dark:text-amber-400">{{ count($selectedLabelIds) }}</span>
                                    @endif
                                    <flux:icon name="chevron-down" class="size-3 text-zinc-400 transition-transform duration-150" ::class="openLabels ? 'rotate-180' : ''" />
                                </button>

                                <div x-show="openLabels" 
                                     @click.away="openLabels = false" 
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     class="absolute left-0 mt-1.5 w-64 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xl shadow-zinc-950/10 dark:shadow-zinc-950/40 p-1.5 z-50 ring-1 ring-black/5 dark:ring-white/5"
                                     style="display: none;">
                                    <div class="px-2.5 py-1.5 border-b border-zinc-100 dark:border-zinc-700/60 mb-1 flex items-center justify-between">
                                        <span class="text-[10px] font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Attach Labels</span>
                                        @if(count($selectedLabelIds) > 0)
                                            <span class="text-[10px] font-mono text-amber-600 dark:text-amber-400 font-semibold">{{ count($selectedLabelIds) }} active</span>
                                        @endif
                                    </div>
                                    <div class="max-h-60 overflow-y-auto space-y-0.5 pr-0.5 custom-scrollbar">
                                        @forelse($this->allLabels as $lbl)
                                            <button wire:click="toggleLabel({{ $lbl->id }})" 
                                                    type="button"
                                                    class="note-dropdown-item w-full text-left px-2.5 py-1.5 rounded-lg text-xs flex items-center justify-between gap-3 text-zinc-700 dark:text-zinc-200 cursor-pointer select-none">
                                                <span class="flex items-center gap-2.5 min-w-0">
                                                    <span class="size-2.5 rounded-full shrink-0 shadow-xs ring-2 ring-white dark:ring-zinc-800" style="background-color: var(--color-{{ $lbl->color }}-500, #a1a1aa);"></span>
                                                    <span class="font-medium whitespace-nowrap truncate">{{ $lbl->name }}</span>
                                                </span>
                                                @if(in_array($lbl->id, $selectedLabelIds))
                                                    <div class="size-4.5 rounded-full bg-amber-500/15 dark:bg-amber-500/20 flex items-center justify-center shrink-0">
                                                        <flux:icon name="check" class="size-3 text-amber-600 dark:text-amber-400 stroke-[2.5]" />
                                                    </div>
                                                @endif
                                            </button>
                                        @empty
                                            <p class="text-xs text-zinc-400 p-2 text-center">No labels yet.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Actions: Autosave Status, Copy, Pin, Menu -->
                        <div class="flex items-center gap-2">
                            <!-- Autosave status: in-place text & icon swap without double-text or layout blink -->
                            <div class="hidden sm:flex items-center gap-1.5 font-mono text-[11px] text-zinc-400 dark:text-zinc-500 select-none pr-1">
                                <svg x-show="saveStatus === 'saving'" class="animate-spin size-3.5 text-zinc-400 dark:text-zinc-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="display: none;">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                								</svg>
                                <svg x-show="saveStatus === 'saved'" class="size-3.5 text-zinc-400 dark:text-zinc-500 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                </svg>
                                <span x-text="saveStatus === 'saving' ? 'Saving...' : 'Saved'"></span>
                            </div>

                            <div class="hidden sm:block h-3.5 w-px bg-zinc-200 dark:bg-zinc-800"></div>

                            <!-- Copy Content Button -->
                            <button @click="copyContent()" 
                                    type="button" 
                                    title="Copy Note Content"
                                    class="size-8 rounded-lg text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 flex items-center justify-center transition-colors cursor-pointer relative focus:outline-none">
                                <flux:icon name="clipboard" class="size-4" x-show="!copied" />
                                <flux:icon name="check" class="size-4 text-emerald-500" x-show="copied" style="display: none;" />
                            </button>

                            <!-- Pin Button -->
                            <button wire:click="togglePin" 
                                    type="button" 
                                    title="{{ $isPinned ? 'Unpin Note' : 'Pin Note' }}"
                                    class="size-8 rounded-lg flex items-center justify-center transition-colors cursor-pointer focus:outline-none {{ $isPinned ? 'text-amber-500 bg-amber-500/10' : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                <flux:icon name="bookmark" class="size-4" />
                            </button>
                        </div>
                    </div>

                    <!-- macOS Notes Style Text Formatting Toolbar -->
                    <div class="px-4 sm:px-8 py-1.5 border-b border-zinc-200/70 dark:border-zinc-800/70 bg-zinc-50/50 dark:bg-zinc-900/40 flex items-center flex-wrap gap-1 text-zinc-600 dark:text-zinc-300 select-none shrink-0">
                        <!-- Aa Typography Dropdown -->
                        <div class="relative" @click.away="styleMenuOpen = false">
                            <button @click="styleMenuOpen = !styleMenuOpen" 
                                    @mousedown.prevent
                                    type="button" 
                                    title="Text Styles (Title, Heading, Body)"
                                    class="h-7 px-2 rounded-lg text-xs font-semibold flex items-center gap-1.5 hover:bg-zinc-200/60 dark:hover:bg-zinc-800 transition-colors cursor-pointer border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 shadow-2xs text-zinc-800 dark:text-zinc-200">
                                <span class="font-serif text-sm leading-none font-bold">Aa</span>
                                <flux:icon name="chevron-down" class="size-2.5 text-zinc-400" />
                            </button>
                            <div x-show="styleMenuOpen" 
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute left-0 mt-1 w-48 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xl p-1 z-50 ring-1 ring-black/5"
                                 style="display: none;">
                                <button @mousedown.prevent @click="exec('formatBlock', '<h1>'); styleMenuOpen = false" type="button" class="w-full text-left px-3 py-1.5 rounded-lg text-sm font-bold hover:bg-zinc-100 dark:hover:bg-zinc-700 flex items-center justify-between text-zinc-900 dark:text-zinc-100 cursor-pointer">
                                    <span>Title</span>
                                    <span class="text-[10px] font-mono text-zinc-400 font-normal">H1</span>
                                </button>
                                <button @mousedown.prevent @click="exec('formatBlock', '<h2>'); styleMenuOpen = false" type="button" class="w-full text-left px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-700 flex items-center justify-between text-zinc-800 dark:text-zinc-200 cursor-pointer">
                                    <span>Heading</span>
                                    <span class="text-[10px] font-mono text-zinc-400 font-normal">H2</span>
                                </button>
                                <button @mousedown.prevent @click="exec('formatBlock', '<h3>'); styleMenuOpen = false" type="button" class="w-full text-left px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-zinc-100 dark:hover:bg-zinc-700 flex items-center justify-between text-zinc-700 dark:text-zinc-300 cursor-pointer">
                                    <span>Subheading</span>
                                    <span class="text-[10px] font-mono text-zinc-400 font-normal">H3</span>
                                </button>
                                <button @mousedown.prevent @click="exec('formatBlock', '<p>'); styleMenuOpen = false" type="button" class="w-full text-left px-3 py-1.5 rounded-lg text-xs hover:bg-zinc-100 dark:hover:bg-zinc-700 flex items-center justify-between text-zinc-600 dark:text-zinc-400 cursor-pointer">
                                    <span>Body</span>
                                    <span class="text-[10px] font-mono text-zinc-400 font-normal">Paragraph</span>
                                </button>
                                <div class="h-px bg-zinc-100 dark:bg-zinc-700 my-1"></div>
                                <button @mousedown.prevent @click="exec('formatBlock', '<pre>'); styleMenuOpen = false" type="button" class="w-full text-left px-3 py-1.5 rounded-lg text-xs font-mono hover:bg-zinc-100 dark:hover:bg-zinc-700 flex items-center justify-between text-zinc-700 dark:text-zinc-300 cursor-pointer">
                                    <span>Monospaced</span>
                                    <span class="text-[10px] font-mono text-zinc-400 font-normal">Code block</span>
                                </button>
                            </div>
                        </div>

                        <!-- Checklist Button (Mac Notes style) -->
                        <button @click="insertChecklist()" 
                                @mousedown.prevent
                                type="button" 
                                title="Checklist (⌘⇧L)"
                                class="h-7 px-2 rounded-lg text-xs font-medium flex items-center gap-1.5 text-zinc-700 dark:text-zinc-300 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-amber-500/10 dark:hover:bg-amber-500/15 border border-transparent hover:border-amber-500/30 transition-colors cursor-pointer">
                            <div class="size-4 rounded-full border border-amber-500/70 dark:border-amber-400/70 bg-amber-500/15 dark:bg-amber-500/25 flex items-center justify-center shrink-0">
                                <flux:icon name="check" class="size-2.5 text-amber-600 dark:text-amber-400 stroke-[3]" />
                            </div>
                            <span class="hidden sm:inline font-medium">Checklist</span>
                        </button>

                        <!-- Table Button -->
                        <button @click="insertTable()" 
                                @mousedown.prevent
                                type="button" 
                                title="Table"
                                class="size-7 rounded-lg flex items-center justify-center hover:bg-zinc-200/60 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-300 transition-colors cursor-pointer">
                            <flux:icon name="table-cells" class="size-3.5" />
                        </button>

                        <div class="h-3.5 w-px bg-zinc-200 dark:bg-zinc-700/80 mx-1"></div>

                        <!-- Bold Button -->
                        <button @click="exec('bold')" 
                                @mousedown.prevent
                                type="button" 
                                title="Bold (⌘B)"
                                class="size-7 rounded-lg flex items-center justify-center hover:bg-zinc-200/60 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition-colors cursor-pointer font-bold text-xs">
                            B
                        </button>

                        <!-- Italic Button -->
                        <button @click="exec('italic')" 
                                @mousedown.prevent
                                type="button" 
                                title="Italic (⌘I)"
                                class="size-7 rounded-lg flex items-center justify-center hover:bg-zinc-200/60 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition-colors cursor-pointer italic font-serif font-bold text-xs">
                            I
                        </button>

                        <!-- Strikethrough Button -->
                        <button @click="exec('strikeThrough')" 
                                @mousedown.prevent
                                type="button" 
                                title="Strikethrough (⌘⇧X)"
                                class="size-7 rounded-lg flex items-center justify-center hover:bg-zinc-200/60 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition-colors cursor-pointer line-through text-xs font-semibold">
                            S
                        </button>

                        <div class="h-3.5 w-px bg-zinc-200 dark:bg-zinc-700/80 mx-1"></div>

                        <!-- Bullet List Button -->
                        <button @click="exec('insertUnorderedList')" 
                                @mousedown.prevent
                                type="button" 
                                title="Bulleted List"
                                class="size-7 rounded-lg flex items-center justify-center hover:bg-zinc-200/60 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-300 transition-colors cursor-pointer">
                            <flux:icon name="bars-3-bottom-left" class="size-3.5" />
                        </button>

                        <!-- Numbered List Button -->
                        <button @click="exec('insertOrderedList')" 
                                @mousedown.prevent
                                type="button" 
                                title="Numbered List"
                                class="size-7 rounded-lg flex items-center justify-center hover:bg-zinc-200/60 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-300 transition-colors cursor-pointer font-mono text-[11px] font-bold">
                            1.
                        </button>

                        <div class="h-3.5 w-px bg-zinc-200 dark:bg-zinc-700/80 mx-1"></div>

                        <!-- Quote Block Button -->
                        <button @click="toggleBlockquote()" 
                                @mousedown.prevent
                                type="button" 
                                title="Quote Block"
                                class="size-7 rounded-lg flex items-center justify-center hover:bg-zinc-200/60 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-300 transition-colors cursor-pointer">
                            <flux:icon name="chat-bubble-bottom-center-text" class="size-3.5" />
                        </button>

                        <!-- Code Button -->
                        <button @click="toggleCodeBlock()" 
                                @mousedown.prevent
                                type="button" 
                                title="Code Block"
                                class="size-7 rounded-lg flex items-center justify-center hover:bg-zinc-200/60 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-300 transition-colors cursor-pointer">
                            <flux:icon name="code-bracket" class="size-3.5" />
                        </button>

                        <!-- Horizontal Divider Button -->
                        <button @click="exec('insertHorizontalRule')" 
                                @mousedown.prevent
                                type="button" 
                                title="Horizontal Divider"
                                class="size-7 rounded-lg flex items-center justify-center hover:bg-zinc-200/60 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-300 transition-colors cursor-pointer">
                            <flux:icon name="minus" class="size-3.5" />
                        </button>
                    </div>

                    <!-- Title Bar -->
                    <div class="px-4 sm:px-8 pt-4 pb-2 shrink-0">
                        <input type="text" 
                               wire:key="note-title-{{ $selectedNoteId ?? 'draft' }}"
                               wire:model.live.debounce.500ms="title" 
                               @input="saveStatus = 'saving'"
                               placeholder="Untitled Note" 
                               class="w-full text-xl sm:text-2xl font-bold bg-transparent border-0 text-zinc-900 dark:text-white placeholder-zinc-300 dark:placeholder-zinc-600 focus:outline-none focus:ring-0 p-0 tracking-tight">
                    </div>

                    <!-- Note Editor Content Body (WYSIWYG Rich Text Canvas) -->
                    <div class="flex-1 overflow-hidden flex flex-col relative">
                        <div id="note-rich-editor"
                             wire:ignore
                             x-ref="editor"
                             contenteditable="true"
                             data-placeholder="Write your note here..."
                             @input="onInput()"
                             @blur="flushSave()"
                             @keydown="onKeydown($event)"
                             @change="handleCheckboxChange($event)"
                             class="w-full h-full p-4 sm:px-8 py-3 bg-transparent border-0 outline-none text-sm text-zinc-800 dark:text-zinc-200 focus:outline-none font-sans leading-relaxed custom-scrollbar overflow-y-auto prose dark:prose-invert max-w-none">{!! $content !!}</div>
                    </div>

                    <!-- Status Footer Bar -->
                    <div class="px-4 sm:px-8 py-2 border-t border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 flex items-center justify-between text-[11px] font-mono text-zinc-400 shrink-0">
                        <div class="flex items-center gap-3">
                            <span>{{ $this->currentNote->word_count }} words</span>
                            <span>&bull;</span>
                            <span>{{ strlen(strip_tags($content ?? '')) }} chars</span>
                            <span>&bull;</span>
                            <span>{{ $this->currentNote->reading_time }} read</span>
                        </div>

                        <div>
                            <span>Updated {{ $this->currentNote->updated_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>
                @else
                    <div class="flex-1 flex flex-col items-center justify-center p-8 text-center">
                        <div class="size-16 rounded-2xl bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-400 mb-4 border border-zinc-200 dark:border-zinc-700">
                            <flux:icon name="document-text" class="size-8" />
                        </div>
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white">No Note Selected</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 max-w-sm leading-relaxed">
                            Select a note from the left panel to start editing.
                        </p>
                    </div>
                @endif
            </main>
        </div>
    </div>

    <!-- Delete Note Verification Modal -->
    <flux:modal name="delete-note-modal" class="w-[calc(100vw-2rem)] max-w-md z-[200]">
        <div class="space-y-4 text-left">
            <div class="flex items-start gap-3.5">
                <div class="size-10 rounded-xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 flex items-center justify-center text-rose-600 dark:text-rose-500 shrink-0 mt-0.5">
                    <flux:icon name="trash" class="size-5" />
                </div>
                <div class="space-y-1">
                    <flux:heading size="lg" class="font-bold text-sm">Delete Note?</flux:heading>
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
                        Are you sure you want to delete <strong class="text-zinc-900 dark:text-zinc-100">{{ $this->noteToDelete?->title ?: 'this note' }}</strong>? This action cannot be undone.
                    </flux:text>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="ghost" size="sm">Cancel</flux:button>
                </flux:modal.close>
                <flux:modal.close>
                    <flux:button variant="danger" size="sm" wire:click="deleteNote">Delete Note</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <script>
        (function() {
            function registerNotesEditor() {
                if (window._notesRichEditorRegistered) return;
                window._notesRichEditorRegistered = true;

                Alpine.data('notesRichEditor', (wire) => ({
                    styleMenuOpen: false,
                    saveTimer: null,

                    initEditor() {
                        const w = wire || this.$wire;
                        if (this.$refs.editor && !this.$refs.editor.innerHTML.trim() && w?.content) {
                            this.setHtml(w.content);
                        }
                        try {
                            document.execCommand('defaultParagraphSeparator', false, 'p');
                        } catch (e) {}
                    },

                    escapeHtml(str) {
                        return (str || '')
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;');
                    },

                    setHtml(html) {
                        if (this.$refs.editor) {
                            const normalized = this.normalizeEditorHtml(html);
                            if (this.$refs.editor.innerHTML !== normalized) {
                                this.$refs.editor.innerHTML = normalized;
                            }
                        }
                    },

                    createChecklistRow(content = '<br>') {
                        const row = document.createElement('div');
                        row.className = 'note-checklist-item my-1.5 flex items-start gap-2.5 w-full';
                        row.setAttribute('data-checklist', 'true');
                        row.innerHTML = `<input type='checkbox' contenteditable='false' class='note-checkbox mt-1 size-4 rounded accent-amber-500 cursor-pointer shrink-0' /><span class='note-checklist-text flex-1 outline-none leading-normal text-zinc-900 dark:text-zinc-100'>${content}</span>`;
                        return row;
                    },

                    normalizeEditorHtml(html) {
                        if (!html || !html.trim() || html.trim() === '<br>') {
                            return '<p><br></p>';
                        }
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(`<div>${html}</div>`, 'text/html');
                        const root = doc.body.firstElementChild;

                        // 1. Flatten ANY nested checklist items (defense in depth)
                        let iterations = 0;
                        while (iterations < 20) {
                            iterations++;
                            const nestedChecklists = root.querySelectorAll('.note-checklist-item .note-checklist-item, [data-checklist="true"] [data-checklist="true"], .note-checklist-item div:has(> input[type="checkbox"]), div:has(> input[type="checkbox"]) div:has(> input[type="checkbox"])');
                            if (!nestedChecklists.length) break;
                            nestedChecklists.forEach(item => {
                                let parent = item.parentElement;
                                while (parent && parent !== root && !parent.classList?.contains('note-checklist-item') && parent.getAttribute('data-checklist') !== 'true' && !parent.querySelector(':scope > input[type="checkbox"]')) {
                                    parent = parent.parentElement;
                                }
                                if (parent && parent !== root) {
                                    parent.after(item);
                                } else {
                                    root.appendChild(item);
                                }
                            });
                        }

                        // 2. Standardize all checklist items
                        const allChecklists = root.querySelectorAll('.note-checklist-item, [data-checklist="true"], div:has(> input.note-checkbox), div:has(> input[type="checkbox"])');
                        allChecklists.forEach(item => {
                            // Extract any nested paragraphs / divs that shouldn't be inside checklist
                            const strayBlocks = item.querySelectorAll('p, blockquote, table, pre');
                            strayBlocks.forEach(sb => {
                                if (!sb.textContent.trim()) {
                                    sb.remove();
                                } else {
                                    item.after(sb);
                                }
                            });

                            item.className = 'note-checklist-item my-1.5 flex items-start gap-2.5 w-full';
                            item.setAttribute('data-checklist', 'true');
                            item.removeAttribute('contenteditable');

                            let cb = item.querySelector('input[type="checkbox"]');
                            if (!cb) {
                                cb = doc.createElement('input');
                                cb.type = 'checkbox';
                                item.prepend(cb);
                            }
                            cb.setAttribute('contenteditable', 'false');
                            cb.className = 'note-checkbox mt-1 size-4 rounded accent-amber-500 cursor-pointer shrink-0';

                            let span = item.querySelector('.note-checklist-text') || item.querySelector('span');
                            if (!span) {
                                span = doc.createElement('span');
                                const nodes = Array.from(item.childNodes).filter(n => n !== cb);
                                nodes.forEach(n => span.appendChild(n));
                                item.appendChild(span);
                            }
                            span.className = 'note-checklist-text flex-1 outline-none leading-normal text-zinc-900 dark:text-zinc-100';
                            if (!span.innerHTML.trim()) {
                                span.innerHTML = '<br>';
                            }
                        });

                        // 3. Ensure last element is not a table without a following paragraph
                        if (root.lastElementChild && root.lastElementChild.tagName === 'TABLE') {
                            const p = doc.createElement('p');
                            p.innerHTML = '<br>';
                            root.appendChild(p);
                        }

                        return root.innerHTML;
                    },

                    handleNoteLoaded(event) {
                        let content = '';
                        if (event?.detail !== undefined && event?.detail !== null) {
                            if (typeof event.detail === 'string') {
                                content = event.detail;
                            } else if (event.detail?.content !== undefined) {
                                content = event.detail.content;
                            } else if (Array.isArray(event.detail) && event.detail[0]?.content !== undefined) {
                                content = event.detail[0].content;
                            } else if (Array.isArray(event.detail) && typeof event.detail[0] === 'string') {
                                content = event.detail[0];
                            }
                        }

                        // If the editor is currently focused by the user, do not clobber caret
                        const isFocused = this.$refs.editor && (document.activeElement === this.$refs.editor || this.$refs.editor.contains(document.activeElement));
                        if (isFocused) {
                            return;
                        }

                        this.setHtml(content);
                    },

                    onInput() {
                        this.$dispatch('note-saving');
                        clearTimeout(this.saveTimer);
                        this.saveTimer = setTimeout(() => {
                            this.flushSave();
                        }, 300);
                    },

                    flushSave() {
                        if (this.saveTimer) {
                            clearTimeout(this.saveTimer);
                            this.saveTimer = null;
                        }
                        const w = wire || this.$wire;
                        if (w && this.$refs.editor) {
                            w.set('content', this.$refs.editor.innerHTML);
                        }
                    },

                    focusElement(el, atEnd = false) {
                        if (!el) return;
                        el.focus();
                        const sel = window.getSelection();
                        if (!sel) return;
                        const range = document.createRange();
                        if (atEnd) {
                            range.selectNodeContents(el);
                            range.collapse(false);
                        } else {
                            range.selectNodeContents(el);
                            range.collapse(true);
                        }
                        sel.removeAllRanges();
                        sel.addRange(range);
                    },

                    exec(cmd, val = null) {
                        this.$refs.editor.focus();
                        if (cmd === 'formatBlock' && val) {
                            const valWith = val.startsWith('<') ? val : `<${val}>`;
                            const valWithout = val.replace(/[<>]/g, '');
                            let res = false;
                            try {
                                res = document.execCommand('formatBlock', false, valWith);
                            } catch (e) {}
                            if (!res) {
                                try {
                                    document.execCommand('formatBlock', false, valWithout);
                                } catch (e) {}
                            }
                        } else {
                            document.execCommand(cmd, false, val);
                        }
                        this.onInput();
                    },

                    toggleBlockquote() {
                        this.$refs.editor.focus();
                        const sel = window.getSelection();
                        let isQuote = false;
                        if (sel && sel.rangeCount > 0) {
                            let node = sel.anchorNode;
                            while (node && node !== this.$refs.editor) {
                                if (node.nodeName === 'BLOCKQUOTE') {
                                    isQuote = true;
                                    break;
                                }
                                node = node.parentNode;
                            }
                        }
                        if (isQuote) {
                            this.exec('formatBlock', '<p>');
                        } else {
                            this.exec('formatBlock', '<blockquote>');
                        }
                    },

                    toggleCodeBlock() {
                        this.$refs.editor.focus();
                        const sel = window.getSelection();
                        let isPre = false;
                        if (sel && sel.rangeCount > 0) {
                            let node = sel.anchorNode;
                            while (node && node !== this.$refs.editor) {
                                if (node.nodeName === 'PRE') {
                                    isPre = true;
                                    break;
                                }
                                node = node.parentNode;
                            }
                        }
                        if (isPre) {
                            this.exec('formatBlock', '<p>');
                        } else {
                            this.exec('formatBlock', '<pre>');
                        }
                    },

                    handleCheckboxChange(e) {
                        if (e.target && e.target.type === 'checkbox') {
                            const cb = e.target;
                            if (cb.checked) {
                                cb.setAttribute('checked', 'checked');
                                if (cb.nextElementSibling) {
                                    cb.nextElementSibling.style.textDecoration = 'line-through';
                                    cb.nextElementSibling.style.opacity = '0.5';
                                }
                            } else {
                                cb.removeAttribute('checked');
                                if (cb.nextElementSibling) {
                                    cb.nextElementSibling.style.textDecoration = 'none';
                                    cb.nextElementSibling.style.opacity = '1';
                                }
                            }
                            this.onInput();
                        }
                    },

                    insertChecklist() {
                        this.$refs.editor.focus();
                        const sel = window.getSelection();
                        let anchor = sel && sel.rangeCount > 0 ? sel.anchorNode : null;
                        if (anchor && anchor.nodeType === Node.TEXT_NODE) anchor = anchor.parentElement;

                        if (!anchor || !this.$refs.editor.contains(anchor)) {
                            // If caret was not inside editor, append a new empty checklist item
                            const newRow = this.createChecklistRow('<br>');
                            const p = document.createElement('p');
                            p.innerHTML = '<br>';
                            this.$refs.editor.appendChild(newRow);
                            this.$refs.editor.appendChild(p);
                            this.focusElement(newRow.querySelector('.note-checklist-text'), false);
                            this.onInput();
                            return;
                        }

                        // Check if current block is ALREADY a checklist item -> TOGGLE OFF (Apple Notes style)
                        const existingChecklist = anchor.closest('.note-checklist-item, [data-checklist="true"], div:has(> input.note-checkbox), div:has(> input[type="checkbox"])');
                        if (existingChecklist && this.$refs.editor.contains(existingChecklist)) {
                            // Find the outermost checklist item if nested
                            let topRow = existingChecklist;
                            while (topRow.parentElement && topRow.parentElement !== this.$refs.editor) {
                                const p = topRow.parentElement.closest('.note-checklist-item, [data-checklist="true"], div:has(> input.note-checkbox), div:has(> input[type="checkbox"])');
                                if (p && this.$refs.editor.contains(p)) {
                                    topRow = p;
                                } else {
                                    break;
                                }
                            }
                            const span = existingChecklist.querySelector('.note-checklist-text') || existingChecklist.querySelector('span');
                            const html = span ? span.innerHTML : '<br>';
                            const p = document.createElement('p');
                            p.innerHTML = html.trim() ? html : '<br>';
                            topRow.replaceWith(p);
                            this.focusElement(p, true);
                            this.onInput();
                            return;
                        }

                        // Otherwise find current top-level block inside editor
                        let block = anchor;
                        while (block && block.parentElement && block.parentElement !== this.$refs.editor) {
                            block = block.parentElement;
                        }
                        if (!block || block === this.$refs.editor) block = anchor;

                        if (block && block !== this.$refs.editor && block.tagName !== 'TABLE') {
                            const html = block.innerHTML;
                            const newRow = this.createChecklistRow(html && html.trim() && html !== '<br>' ? html : '<br>');
                            block.replaceWith(newRow);
                            this.focusElement(newRow.querySelector('.note-checklist-text'), true);
                        } else {
                            const newRow = this.createChecklistRow('<br>');
                            this.$refs.editor.appendChild(newRow);
                            this.focusElement(newRow.querySelector('.note-checklist-text'), false);
                        }

                        this.onInput();
                    },

                    insertTable() {
                        this.$refs.editor.focus();
                        const sel = window.getSelection();
                        let currentBlock = null;

                        if (sel && sel.rangeCount > 0) {
                            let node = sel.anchorNode;
                            while (node && node !== this.$refs.editor) {
                                if (node.parentElement === this.$refs.editor) {
                                    currentBlock = node;
                                    break;
                                }
                                node = node.parentNode;
                            }
                        }

                        const tableWrapper = document.createElement('div');
                        tableWrapper.innerHTML = `<table class='not-prose my-3 w-full border-collapse text-xs text-left'><thead><tr><th>Column 1</th><th>Column 2</th><th>Column 3</th></tr></thead><tbody><tr><td>Item 1</td><td>Item 2</td><td>Item 3</td></tr><tr><td>Item 4</td><td>Item 5</td><td>Item 6</td></tr></tbody></table><p><br></p>`;

                        const tableEl = tableWrapper.firstElementChild;
                        const pEl = tableWrapper.lastElementChild;

                        if (currentBlock && currentBlock !== this.$refs.editor) {
                            currentBlock.after(pEl);
                            currentBlock.after(tableEl);
                        } else {
                            this.$refs.editor.appendChild(tableEl);
                            this.$refs.editor.appendChild(pEl);
                        }

                        // Focus first cell
                        const firstTd = tableEl.querySelector('tbody td');
                        if (firstTd) {
                            this.focusElement(firstTd, false);
                        }

                        this.onInput();
                    },

                    handleChecklistKeydown(e) {
                        const sel = window.getSelection();
                        if (!sel || sel.rangeCount === 0) return false;

                        let anchor = sel.anchorNode;
                        if (!anchor) return false;
                        if (anchor.nodeType === Node.TEXT_NODE) anchor = anchor.parentElement;
                        if (!anchor || !this.$refs.editor.contains(anchor)) return false;

                        const checklistRow = anchor.closest('.note-checklist-item, [data-checklist="true"], div:has(> input.note-checkbox), div:has(> input[type="checkbox"])');
                        if (!checklistRow || !this.$refs.editor.contains(checklistRow)) return false;

                        // Find top-level checklist row so siblings are never inserted inside a parent checklist
                        let topRow = checklistRow;
                        while (topRow.parentElement && topRow.parentElement !== this.$refs.editor) {
                            const p = topRow.parentElement.closest('.note-checklist-item, [data-checklist="true"], div:has(> input.note-checkbox), div:has(> input[type="checkbox"])');
                            if (p && this.$refs.editor.contains(p)) {
                                topRow = p;
                            } else {
                                break;
                            }
                        }

                        const span = checklistRow.querySelector('.note-checklist-text') || checklistRow.querySelector('span') || checklistRow;
                        const rawText = span.innerText || span.textContent || '';
                        const cleanText = rawText.replace(/[\u200B-\u200D\uFEFF\n\r]/g, '').trim();

                        // Tab / Shift+Tab for Indent / Outdent
                        if (e.key === 'Tab') {
                            e.preventDefault();
                            const currentMargin = parseInt(topRow.style.marginLeft || '0', 10) || 0;
                            if (e.shiftKey) {
                                const newMargin = Math.max(0, currentMargin - 24);
                                topRow.style.marginLeft = newMargin ? `${newMargin}px` : '';
                            } else {
                                const newMargin = Math.min(96, currentMargin + 24);
                                topRow.style.marginLeft = `${newMargin}px`;
                            }
                            this.onInput();
                            return true;
                        }

                        // Backspace / Delete
                        if (e.key === 'Backspace' || e.key === 'Delete') {
                            if (cleanText === '') {
                                e.preventDefault();
                                const prev = topRow.previousElementSibling;
                                const next = topRow.nextElementSibling;
                                topRow.remove();

                                if (!this.$refs.editor.innerHTML.trim() || this.$refs.editor.innerHTML === '<br>') {
                                    this.$refs.editor.innerHTML = '<p><br></p>';
                                    this.focusElement(this.$refs.editor.querySelector('p'), false);
                                } else if (prev) {
                                    this.focusElement(prev, true);
                                } else if (next) {
                                    this.focusElement(next, false);
                                } else {
                                    const p = document.createElement('p');
                                    p.innerHTML = '<br>';
                                    this.$refs.editor.appendChild(p);
                                    this.focusElement(p, false);
                                }
                                this.onInput();
                                return true;
                            }

                            if (e.key === 'Backspace') {
                                const range = sel.getRangeAt(0);
                                let isAtStart = false;
                                if (range.collapsed) {
                                    if (range.startOffset === 0 && (range.startContainer === span || range.startContainer === span.firstChild || range.startContainer.parentNode === span)) {
                                        isAtStart = true;
                                    }
                                }
                                if (isAtStart) {
                                    e.preventDefault();
                                    const p = document.createElement('p');
                                    p.innerHTML = span.innerHTML || '<br>';
                                    topRow.replaceWith(p);
                                    this.focusElement(p, false);
                                    this.onInput();
                                    return true;
                                }
                            }
                        }

                        // Enter
                        if (e.key === 'Enter') {
                            e.preventDefault();

                            // Enter on empty checklist item -> exit checklist mode to body paragraph
                            if (cleanText === '') {
                                const p = document.createElement('p');
                                p.innerHTML = '<br>';
                                topRow.replaceWith(p);
                                this.focusElement(p, false);
                                this.onInput();
                                return true;
                            }

                            // Enter with text -> split text at caret
                            let preHtml = '';
                            let postHtml = '';
                            try {
                                const range = sel.getRangeAt(0);
                                const preRange = range.cloneRange();
                                preRange.selectNodeContents(span);
                                preRange.setEnd(range.startContainer, range.startOffset);
                                const preFrag = preRange.cloneContents();
                                const divPre = document.createElement('div');
                                divPre.appendChild(preFrag);
                                preHtml = divPre.innerHTML;

                                const postRange = range.cloneRange();
                                postRange.selectNodeContents(span);
                                postRange.setStart(range.endContainer, range.endOffset);
                                const postFrag = postRange.cloneContents();
                                const divPost = document.createElement('div');
                                divPost.appendChild(postFrag);
                                postHtml = divPost.innerHTML;
                            } catch (err) {
                                preHtml = span.innerHTML;
                                postHtml = '';
                            }

                            span.innerHTML = preHtml.trim() ? preHtml : '<br>';

                            const newRow = this.createChecklistRow(postHtml.trim() ? postHtml : '<br>');
                            if (topRow.style.marginLeft) {
                                newRow.style.marginLeft = topRow.style.marginLeft;
                            }

                            topRow.after(newRow);
                            const newSpan = newRow.querySelector('.note-checklist-text');
                            this.focusElement(newSpan, false);

                            this.onInput();
                            return true;
                        }

                        return false;
                    },

                    handleListKeydown(e) {
                        const sel = window.getSelection();
                        if (!sel || sel.rangeCount === 0) return false;

                        let anchor = sel.anchorNode;
                        if (!anchor) return false;
                        if (anchor.nodeType === Node.TEXT_NODE) anchor = anchor.parentElement;
                        if (!anchor || !this.$refs.editor.contains(anchor)) return false;

                        const li = anchor.closest('li');
                        if (!li) return false;

                        const list = li.closest('ul, ol');
                        if (!list) return false;

                        // Tab / Shift+Tab inside list -> Indent / Outdent
                        if (e.key === 'Tab') {
                            e.preventDefault();
                            if (e.shiftKey) {
                                document.execCommand('outdent', false, null);
                            } else {
                                document.execCommand('indent', false, null);
                            }
                            this.onInput();
                            return true;
                        }

                        // Enter on empty <li> -> exit list to normal paragraph below
                        if (e.key === 'Enter' && !e.shiftKey) {
                            const text = li.innerText?.replace(/[\u200B-\u200D\uFEFF\n\r]/g, '').trim() || '';
                            if (text === '') {
                                e.preventDefault();
                                li.remove();
                                if (!list.querySelectorAll('li').length) {
                                    const p = document.createElement('p');
                                    p.innerHTML = '<br>';
                                    list.replaceWith(p);
                                    this.focusElement(p, false);
                                } else {
                                    const p = document.createElement('p');
                                    p.innerHTML = '<br>';
                                    list.after(p);
                                    this.focusElement(p, false);
                                }
                                this.onInput();
                                return true;
                            }
                        }

                        return false;
                    },

                    handleTableKeydown(e) {
                        const sel = window.getSelection();
                        if (!sel || sel.rangeCount === 0) return false;

                        let node = sel.anchorNode;
                        let cell = null;

                        while (node && node !== this.$refs.editor) {
                            if (node.nodeName === 'TD' || node.nodeName === 'TH') {
                                cell = node;
                                break;
                            }
                            node = node.parentNode;
                        }

                        if (!cell) return false;

                        const table = cell.closest('table');
                        if (!table) return false;

                        const tr = cell.parentElement;
                        const colIdx = Array.from(tr.children).indexOf(cell);

                        // Enter inside table
                        if (e.key === 'Enter') {
                            // Shift+Enter / Alt+Enter: Insert line break inside the cell
                            if (e.shiftKey || e.altKey) {
                                e.preventDefault();
                                const range = sel.getRangeAt(0);
                                range.deleteContents();
                                const br = document.createElement('br');
                                range.insertNode(br);
                                range.setStartAfter(br);
                                range.collapse(true);
                                sel.removeAllRanges();
                                sel.addRange(range);
                                this.onInput();
                                return true;
                            }

                            // Cmd+Enter / Ctrl+Enter: Exit table to paragraph below
                            if (e.metaKey || e.ctrlKey) {
                                e.preventDefault();
                                let nextEl = table.nextElementSibling;
                                if (!nextEl || nextEl.tagName === 'TABLE') {
                                    nextEl = document.createElement('p');
                                    nextEl.innerHTML = '<br>';
                                    table.after(nextEl);
                                }
                                this.focusElement(nextEl, false);
                                return true;
                            }

                            // Normal Enter: move to cell directly below (same column)!
                            e.preventDefault();
                            const tbody = table.querySelector('tbody') || table;
                            const rows = Array.from(tbody.querySelectorAll('tr'));

                            if (cell.nodeName === 'TH') {
                                // From header row, move down to first row of tbody
                                if (rows.length > 0 && rows[0].children[colIdx]) {
                                    this.focusElement(rows[0].children[colIdx], false);
                                }
                            } else {
                                // Inside tbody
                                const currentIdx = rows.indexOf(tr);
                                if (currentIdx < rows.length - 1) {
                                    // Move to next existing row
                                    const nextRow = rows[currentIdx + 1];
                                    if (nextRow.children[colIdx]) {
                                        this.focusElement(nextRow.children[colIdx], false);
                                    }
                                } else {
                                    // On last row -> ADD A NEW ROW! (Apple Notes style)
                                    const newTr = document.createElement('tr');
                                    const colCount = tr.children.length;
                                    for (let i = 0; i < colCount; i++) {
                                        const newTd = document.createElement('td');
                                        newTd.innerHTML = '<br>';
                                        newTr.appendChild(newTd);
                                    }
                                    tbody.appendChild(newTr);
                                    this.focusElement(newTr.children[colIdx] || newTr.children[0], false);
                                    this.onInput();
                                }
                            }
                            return true;
                        }

                        // Tab key inside table: navigate cells horizontally
                        if (e.key === 'Tab') {
                            e.preventDefault();
                            const allCells = Array.from(table.querySelectorAll('th, td'));
                            const currentIdx = allCells.indexOf(cell);

                            if (e.shiftKey) {
                                // Move backwards
                                if (currentIdx > 0) {
                                    this.focusElement(allCells[currentIdx - 1], true);
                                }
                            } else {
                                // Move forwards
                                if (currentIdx < allCells.length - 1) {
                                    this.focusElement(allCells[currentIdx + 1], false);
                                } else {
                                    // At very last cell: ADD A NEW ROW!
                                    const tbody = table.querySelector('tbody') || table;
                                    const lastTr = tbody.querySelector('tr:last-child') || tr;
                                    const colCount = lastTr.children.length;
                                    const newTr = document.createElement('tr');
                                    for (let i = 0; i < colCount; i++) {
                                        const newTd = document.createElement('td');
                                        newTd.innerHTML = '<br>';
                                        newTr.appendChild(newTd);
                                    }
                                    tbody.appendChild(newTr);
                                    this.focusElement(newTr.firstElementChild, false);
                                    this.onInput();
                                }
                            }
                            return true;
                        }

                        return false;
                    },

                    handleHeadingKeydown(e) {
                        if (e.key !== 'Enter' || e.shiftKey) return false;
                        const sel = window.getSelection();
                        if (!sel || sel.rangeCount === 0) return false;

                        let node = sel.anchorNode;
                        let heading = null;

                        while (node && node !== this.$refs.editor) {
                            if (node.nodeName === 'H1' || node.nodeName === 'H2' || node.nodeName === 'H3') {
                                heading = node;
                                break;
                            }
                            node = node.parentNode;
                        }

                        if (!heading) return false;

                        e.preventDefault();
                        const text = heading.textContent.trim();

                        if (text === '') {
                            // Empty heading: convert to <p><br></p>
                            const p = document.createElement('p');
                            p.innerHTML = '<br>';
                            heading.replaceWith(p);
                            this.focusElement(p, false);
                        } else {
                            // Split or insert paragraph after heading
                            let postText = '';
                            try {
                                const range = sel.getRangeAt(0);
                                const postRange = range.cloneRange();
                                postRange.selectNodeContents(heading);
                                postRange.setStart(range.startContainer, range.startOffset);
                                postText = postRange.toString();

                                const preRange = range.cloneRange();
                                preRange.selectNodeContents(heading);
                                preRange.setEnd(range.endContainer, range.endOffset);
                                heading.innerHTML = preRange.toString() ? this.escapeHtml(preRange.toString()) : '<br>';
                            } catch (err) {
                                postText = '';
                            }

                            const p = document.createElement('p');
                            p.innerHTML = postText ? this.escapeHtml(postText) : '<br>';
                            heading.after(p);
                            this.focusElement(p, false);
                        }

                        this.onInput();
                        return true;
                    },

                    handleCodeKeydown(e) {
                        const sel = window.getSelection();
                        if (!sel || sel.rangeCount === 0) return false;

                        let node = sel.anchorNode;
                        let pre = null;

                        while (node && node !== this.$refs.editor) {
                            if (node.nodeName === 'PRE') {
                                pre = node;
                                break;
                            }
                            node = node.parentNode;
                        }

                        if (!pre) return false;

                        if (e.key === 'Tab') {
                            e.preventDefault();
                            const range = sel.getRangeAt(0);
                            const tabNode = document.createTextNode('  ');
                            range.insertNode(tabNode);
                            range.setStartAfter(tabNode);
                            range.collapse(true);
                            sel.removeAllRanges();
                            sel.addRange(range);
                            this.onInput();
                            return true;
                        }

                        if (e.key === 'Enter') {
                            if (e.shiftKey) {
                                // Shift+Enter: exit code block
                                e.preventDefault();
                                const p = document.createElement('p');
                                p.innerHTML = '<br>';
                                pre.after(p);
                                this.focusElement(p, false);
                                this.onInput();
                                return true;
                            }

                            e.preventDefault();
                            const range = sel.getRangeAt(0);
                            range.deleteContents();
                            const newline = document.createTextNode('\n');
                            range.insertNode(newline);
                            range.setStartAfter(newline);
                            range.collapse(true);
                            sel.removeAllRanges();
                            sel.addRange(range);
                            this.onInput();
                            return true;
                        }

                        return false;
                    },

                    handleBlockquoteKeydown(e) {
                        if (e.key !== 'Enter' || e.shiftKey) return false;
                        const sel = window.getSelection();
                        if (!sel || sel.rangeCount === 0) return false;

                        let node = sel.anchorNode;
                        let bq = null;

                        while (node && node !== this.$refs.editor) {
                            if (node.nodeName === 'BLOCKQUOTE') {
                                bq = node;
                                break;
                            }
                            node = node.parentNode;
                        }

                        if (!bq) return false;

                        const text = bq.innerText?.trim() || bq.textContent?.trim() || '';
                        if (text === '') {
                            e.preventDefault();
                            const p = document.createElement('p');
                            p.innerHTML = '<br>';
                            bq.replaceWith(p);
                            this.focusElement(p, false);
                            this.onInput();
                            return true;
                        }

                        return false;
                    },

                    onKeydown(e) {
                        // 1. Checklist handler (Enter, Backspace, Delete, Tab)
                        if (e.key === 'Backspace' || e.key === 'Delete' || e.key === 'Enter' || e.key === 'Tab') {
                            if (this.handleChecklistKeydown(e)) {
                                return;
                            }
                        }

                        // 2. Table handler (Enter, Tab, Shift+Tab)
                        if (e.key === 'Enter' || e.key === 'Tab') {
                            if (this.handleTableKeydown(e)) {
                                return;
                            }
                        }

                        // 3. List handler (Enter on empty <li>, Tab indents <li>)
                        if (e.key === 'Enter' || e.key === 'Tab') {
                            if (this.handleListKeydown(e)) {
                                return;
                            }
                        }

                        // 4. Heading handler (Enter -> converts to normal body paragraph)
                        if (e.key === 'Enter') {
                            if (this.handleHeadingKeydown(e)) {
                                return;
                            }
                        }

                        // 5. Code block handler (Tab, Enter)
                        if (e.key === 'Tab' || e.key === 'Enter') {
                            if (this.handleCodeKeydown(e)) {
                                return;
                            }
                        }

                        // 6. Blockquote handler (Enter on empty)
                        if (e.key === 'Enter') {
                            if (this.handleBlockquoteKeydown(e)) {
                                return;
                            }
                        }

                        // 7. Keyboard shortcuts
                        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'b') {
                            e.preventDefault();
                            this.exec('bold');
                        } else if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'i') {
                            e.preventDefault();
                            this.exec('italic');
                        } else if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'u') {
                            e.preventDefault();
                            this.exec('underline');
                        } else if ((e.metaKey || e.ctrlKey) && e.shiftKey && e.key.toLowerCase() === 'x') {
                            e.preventDefault();
                            this.exec('strikeThrough');
                        } else if ((e.metaKey || e.ctrlKey) && e.shiftKey && e.key.toLowerCase() === 'l') {
                            e.preventDefault();
                            this.insertChecklist();
                        } else if (e.key === 'Tab') {
                            e.preventDefault();
                            document.execCommand('insertHTML', false, '&nbsp;&nbsp;&nbsp;&nbsp;');
                            this.onInput();
                        }
                    }
                }));
            }

            if (window.Alpine) {
                registerNotesEditor();
            } else {
                document.addEventListener('alpine:init', registerNotesEditor);
            }
        })();
    </script>
</div>

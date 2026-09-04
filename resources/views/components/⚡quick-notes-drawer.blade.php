<?php

use App\Models\Note;
use App\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public bool $isOpen = false;

    public ?int $selectedNoteId = null;

    public string $title = '';

    public ?string $content = '';

    public ?int $projectId = null;

    public string $saveStatus = 'saved';

    public bool $isCreating = false;

    public function mount(): void
    {
        // Don't eagerly load to keep initial page render fast
    }

    public function openDrawer(?int $noteId = null): void
    {
        $this->isOpen = true;

        if ($noteId) {
            $note = auth()->user()->notes()->find($noteId);
            if ($note) {
                $this->loadNote($note);
                return;
            }
        }

        // Default to latest active note, or enter draft mode if none exists
        $latest = auth()->user()->notes()->active()->orderByDesc('updated_at')->first();
        if ($latest) {
            $this->loadNote($latest);
        } else {
            $this->createQuickNote();
        }
    }

    protected function hasChanges(): bool
    {
        $hasContent = trim(strip_tags($this->content ?? '')) !== '' || trim($this->content ?? '') !== '';
        $hasCustomTitle = trim($this->title ?? '') !== '' 
            && trim($this->title ?? '') !== 'Quick Scratchpad' 
            && trim($this->title ?? '') !== 'Untitled Note';

        return $hasContent || $hasCustomTitle;
    }

    public function closeDrawer(): void
    {
        if ($this->isCreating) {
            if ($this->hasChanges()) {
                $this->saveNote();
            }
            $this->isCreating = false;
        } elseif ($this->selectedNoteId) {
            $this->saveNote();
        }

        $this->isOpen = false;
    }

    public function loadNote(Note $note): void
    {
        $this->isCreating = false;
        $this->selectedNoteId = $note->id;
        $this->title = $note->title ?? '';
        $this->content = $note->content ?? '';
        $this->projectId = $note->project_id;
        $this->saveStatus = 'saved';
        $this->dispatch('note-saved');
    }

    public function createQuickNote(): void
    {
        // If already in creation mode
        if ($this->isCreating) {
            if ($this->hasChanges()) {
                $this->saveNote();
            } else {
                // No changes, keep drafting without saving
                return;
            }
        } elseif ($this->selectedNoteId) {
            $this->saveNote();
        }

        // Enter new draft mode - DO NOT insert row into database!
        $this->isCreating = true;
        $this->selectedNoteId = null;
        $this->title = '';
        $this->content = '';
        $this->projectId = null;
        $this->saveStatus = 'saved';
        $this->dispatch('note-saved');
    }

    public function selectNote(int $id): void
    {
        if (! $this->isCreating && $this->selectedNoteId === $id) {
            return;
        }

        if ($this->isCreating) {
            if ($this->hasChanges()) {
                $this->saveNote();
            }
            $this->isCreating = false;
        }

        $note = auth()->user()->notes()->find($id);
        if ($note) {
            $this->loadNote($note);
        }
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
                'title' => trim($this->title) !== '' ? $this->title : 'Quick Scratchpad',
                'content' => $this->content ?? '',
                'project_id' => $this->projectId ?: null,
                'is_pinned' => false,
                'is_archived' => false,
            ]);

            $this->isCreating = false;
            $this->selectedNoteId = $note->id;
            $this->saveStatus = 'saved';
            $this->dispatch('note-saved');
            $this->dispatch('note-created');
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
        ]);

        $this->saveStatus = 'saved';
        $this->dispatch('note-saved');
    }

    #[Computed]
    public function recentNotes()
    {
        return auth()->user()->notes()->active()->orderByDesc('updated_at')->take(10)->get();
    }

    #[Computed]
    public function projects()
    {
        return auth()->user()->projects()->orderBy('name')->get();
    }
};
?>

<div x-data="{
        isOpen: @entangle('isOpen'),
        saveStatus: 'saved',
        saveTimer: null,
        setSaving() {
            this.saveStatus = 'saving';
            clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => {
                this.saveStatus = 'saved';
            }, 1500);
        },
        handleShortcut(e) {
            // Cmd+Shift+N or Ctrl+Shift+N
            if ((e.metaKey || e.ctrlKey) && e.shiftKey && (e.key === 'N' || e.key === 'n')) {
                e.preventDefault();
                if (this.isOpen) {
                    $wire.closeDrawer();
                } else {
                    $wire.openDrawer();
                }
            }
            // Escape to close if open
            if (e.key === 'Escape' && this.isOpen) {
                e.preventDefault();
                $wire.closeDrawer();
            }
        }
     }"
     @note-saved.window="saveStatus = 'saved'; clearTimeout(saveTimer);"
     @keydown.window="handleShortcut($event)">

    <!-- Floating Quick Scratchpad Button (Bottom Right) -->
    <div class="fixed bottom-5 right-5 sm:bottom-6 sm:right-6 z-40 hidden sm:block">
        <button @click="$wire.openDrawer()" 
                type="button" 
                title="Quick Scratchpad (Cmd+Shift+N / Ctrl+Shift+N)"
                class="flex items-center gap-2 px-3 py-2 rounded-full bg-white/90 dark:bg-zinc-800/90 hover:bg-amber-500 hover:text-zinc-950 dark:hover:bg-amber-500 dark:hover:text-zinc-950 text-zinc-700 dark:text-zinc-200 border border-zinc-200/80 dark:border-zinc-700/80 shadow-lg backdrop-blur-md transition-all duration-200 group active:scale-95 cursor-pointer">
            <div class="size-6 rounded-full bg-amber-500/10 group-hover:bg-zinc-950/10 flex items-center justify-center text-amber-500 group-hover:text-zinc-950 transition-colors">
                <flux:icon name="pencil-square" class="size-3.5" />
            </div>
            <span class="text-xs font-semibold">Scratchpad</span>
            <kbd class="text-[10px] font-mono px-1.5 py-0.5 bg-zinc-100 dark:bg-zinc-700/60 group-hover:bg-amber-600/30 rounded text-zinc-500 dark:text-zinc-400 group-hover:text-zinc-950 transition-colors">⌘⇧N</kbd>
        </button>
    </div>

    <!-- Backdrop Overlay (z-50) -->
    <div x-show="isOpen" 
         @click="$wire.closeDrawer()"
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/40 dark:bg-black/60 backdrop-blur-xs z-50"
         style="display: none;"></div>

    <!-- Slide-over Drawer Panel (z-50) -->
    <aside x-show="isOpen" 
           x-transition:enter="transition ease-out duration-300 transform"
           x-transition:enter-start="translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200 transform"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="translate-x-full"
           class="fixed inset-y-0 right-0 w-full sm:w-[450px] md:w-[500px] bg-white dark:bg-zinc-900 border-l border-zinc-200 dark:border-zinc-800 shadow-2xl z-50 flex flex-col justify-between overflow-hidden"
           style="display: none;">

        <!-- Drawer Header -->
        <div class="p-3 sm:p-4 border-b border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/70 dark:bg-zinc-900/60 flex items-center justify-between gap-2 shrink-0">
            <div class="flex items-center gap-2 flex-1 min-w-0">
                <!-- Note Switcher Dropdown -->
                <select wire:change="selectNote($event.target.value)" 
                        class="text-xs font-semibold py-1 pl-2.5 pr-7 rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-1 focus:ring-amber-500 max-w-[200px] truncate">
                    @if($isCreating)
                        <option value="" selected disabled>+ New Scratchpad</option>
                    @endif
                    @foreach($this->recentNotes as $recentNote)
                        <option value="{{ $recentNote->id }}" {{ (! $isCreating && $selectedNoteId === $recentNote->id) ? 'selected' : '' }}>
                            {{ $recentNote->title ?: 'Untitled Note' }}
                        </option>
                    @endforeach
                </select>

                <!-- New Quick Note Button -->
                <button wire:click="createQuickNote" 
                        type="button" 
                        title="Create new scratchpad note"
                        class="p-1 rounded-lg text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white hover:bg-zinc-200/60 dark:hover:bg-zinc-800 transition-colors cursor-pointer shrink-0">
                    <flux:icon name="plus" class="size-4" />
                </button>

                <!-- Autosave State: in-place text & icon swap without double-text or layout blink -->
                <div class="flex items-center gap-1 pl-1 font-mono text-[10px] text-zinc-400 dark:text-zinc-500 select-none shrink-0">
                    <svg x-show="saveStatus === 'saving'" class="animate-spin size-3 text-zinc-400 dark:text-zinc-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="display: none;">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg x-show="saveStatus === 'saved'" class="size-3 text-zinc-400 dark:text-zinc-500 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                    </svg>
                    <span x-text="saveStatus === 'saving' ? 'Saving...' : 'Saved'"></span>
                </div>
            </div>

            <!-- Right Controls: Open in Full Notes & Close -->
            <div class="flex items-center gap-1.5 shrink-0">
                @if($selectedNoteId)
                    <a href="{{ route('notes', ['selected' => $selectedNoteId]) }}" 
                       wire:navigate 
                       @click="$wire.closeDrawer()"
                       title="Open full page in Notes"
                       class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-medium text-amber-600 dark:text-amber-400 hover:bg-amber-500/10 transition-colors">
                        <span>Open Full</span>
                        <flux:icon name="arrow-up-right" class="size-3" />
                    </a>
                @endif

                <button @click="$wire.closeDrawer()" 
                        type="button" 
                        class="p-1.5 rounded-lg text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer">
                    <flux:icon name="x-mark" class="size-4.5" />
                </button>
            </div>
        </div>

        <!-- Project link toolbar -->
        <div class="px-4 py-2 border-b border-zinc-100 dark:border-zinc-800/80 bg-zinc-50/30 dark:bg-zinc-900/30 flex items-center justify-between text-xs shrink-0">
            <div class="flex items-center gap-2">
                <span class="text-[11px] text-zinc-400">Project:</span>
                <select wire:model.live="projectId" 
                        @change="setSaving()"
                        class="py-0.5 pl-2 pr-6 text-[11px] rounded-md bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 focus:ring-1 focus:ring-amber-500">
                    <option value="">None (Personal)</option>
                    @foreach($this->projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <span class="text-[10px] font-mono text-zinc-400">Markdown enabled</span>
        </div>

        <!-- Title Input -->
        <div class="px-4 sm:px-6 pt-4 pb-1 shrink-0">
            <input type="text" 
                   wire:key="quick-title-{{ $selectedNoteId ?? 'draft' }}"
                   wire:model.live.debounce.500ms="title" 
                   @input="setSaving()"
                   placeholder="Scratchpad Title" 
                   class="w-full text-lg font-bold bg-transparent border-0 text-zinc-900 dark:text-white placeholder-zinc-300 dark:placeholder-zinc-600 focus:outline-none focus:ring-0 p-0 tracking-tight">
        </div>

        <!-- Scratchpad Textarea -->
        <div class="flex-1 p-4 sm:px-6 py-2 overflow-hidden flex flex-col">
            <textarea wire:key="quick-content-{{ $selectedNoteId ?? 'draft' }}"
                      wire:model.live.debounce.500ms="content" 
                      @input="setSaving()"
                      placeholder="Write your note here..."
                      class="w-full h-full bg-transparent border-0 resize-none text-xs sm:text-sm text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 dark:placeholder-zinc-600 focus:outline-none focus:ring-0 font-sans leading-relaxed custom-scrollbar"></textarea>
        </div>

        <!-- Drawer Footer Status -->
        <div class="p-3 px-4 sm:px-6 border-t border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-900/60 flex items-center justify-between text-[11px] text-zinc-400 font-mono shrink-0">
            <div class="flex items-center gap-2">
                <span>{{ str_word_count(strip_tags($content ?? '')) }} words</span>
                <span>&bull;</span>
                <span>{{ strlen($content ?? '') }} chars</span>
            </div>
            <div class="flex items-center gap-1.5">
                <kbd class="px-1 py-0.2 bg-zinc-200/70 dark:bg-zinc-800 rounded text-[10px]">Esc</kbd>
                <span class="text-[10px]">to dismiss</span>
            </div>
        </div>
    </aside>
</div>

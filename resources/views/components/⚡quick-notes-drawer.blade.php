<?php

use App\Models\Note;
use App\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
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

    #[On('note-updated')]
    public function handleExternalNoteUpdated(int $id, string $source = ''): void
    {
        if ($source === 'drawer') {
            return;
        }

        if ($this->selectedNoteId === $id && ! $this->isCreating) {
            $note = auth()->user()->notes()->find($id);
            if ($note) {
                $this->loadNote($note);
            }
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

    public function openFull(): mixed
    {
        if ($this->isCreating) {
            if ($this->hasChanges()) {
                $this->saveNote();
            }
        } elseif ($this->selectedNoteId) {
            $this->saveNote();
        }

        $this->isOpen = false;

        $targetId = $this->selectedNoteId;
        if ($targetId) {
            return $this->redirect(route('notes', ['selected' => $targetId]), navigate: true);
        }

        return $this->redirect(route('notes'), navigate: true);
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
        $this->dispatch('scratchpad-note-loaded', content: $this->content);
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
        $this->dispatch('scratchpad-note-loaded', content: '');
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
        } elseif ($this->selectedNoteId) {
            $this->saveNote();
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
            $this->dispatch('note-created', id: $note->id, source: 'drawer');
            $this->dispatch('note-updated', id: $note->id, source: 'drawer');
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
        $this->dispatch('note-updated', id: $note->id, source: 'drawer');
    }

    #[Computed]
    public function recentNotes()
    {
        return auth()->user()->notes()->select(['id', 'title', 'updated_at'])->active()->orderByDesc('updated_at')->take(10)->get();
    }

    #[Computed]
    public function projects()
    {
        return auth()->user()->projects()->select(['id', 'name'])->orderBy('name')->get();
    }
};
?>

<style global>
    #scratchpad-rich-editor:empty:before {
        content: attr(data-placeholder);
        color: #a1a1aa;
        pointer-events: none;
        display: block;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #scratchpad-rich-editor:empty:before,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #scratchpad-rich-editor:empty:before {
        color: #525252;
    }

    #scratchpad-rich-editor h1 {
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1.25;
        margin-top: 1rem;
        margin-bottom: 0.375rem;
        color: #18181b;
        letter-spacing: -0.02em;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #scratchpad-rich-editor h1,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #scratchpad-rich-editor h1 {
        color: #fafafa;
    }
    #scratchpad-rich-editor h2 {
        font-size: 1.15rem;
        font-weight: 600;
        line-height: 1.3;
        margin-top: 0.875rem;
        margin-bottom: 0.25rem;
        color: #27272a;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #scratchpad-rich-editor h2,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #scratchpad-rich-editor h2 {
        color: #f4f4f5;
    }
    #scratchpad-rich-editor h3 {
        font-size: 1rem;
        font-weight: 600;
        line-height: 1.35;
        margin-top: 0.625rem;
        margin-bottom: 0.25rem;
        color: #3f3f46;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #scratchpad-rich-editor h3,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #scratchpad-rich-editor h3 {
        color: #e4e4e7;
    }

    #scratchpad-rich-editor p {
        margin-top: 0.25rem;
        margin-bottom: 0.5rem;
        line-height: 1.6;
    }

    #scratchpad-rich-editor ul {
        list-style-type: disc !important;
        padding-left: 1.5rem !important;
        margin: 0.5rem 0 !important;
    }
    #scratchpad-rich-editor ol {
        list-style-type: decimal !important;
        padding-left: 1.5rem !important;
        margin: 0.5rem 0 !important;
    }
    #scratchpad-rich-editor li {
        margin-top: 0.15rem;
        margin-bottom: 0.15rem;
        padding-left: 0.25rem;
    }

    #scratchpad-rich-editor pre {
        background: #f4f4f5;
        color: #18181b;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.8rem;
        overflow-x: auto;
        margin: 0.75rem 0;
        border: 1px solid #e4e4e7;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #scratchpad-rich-editor pre,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #scratchpad-rich-editor pre {
        background: #18181b;
        color: #f4f4f5;
        border-color: #27272a;
    }

    #scratchpad-rich-editor blockquote {
        border-left: 3px solid #f59e0b;
        padding-left: 0.75rem;
        margin: 0.5rem 0;
        font-style: italic;
        color: #71717a;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #scratchpad-rich-editor blockquote,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #scratchpad-rich-editor blockquote {
        color: #a1a1aa;
    }

    /* Tables: Balanced Light & Dark Theme Adaptation */
    #scratchpad-rich-editor table {
        width: 100% !important;
        border-collapse: collapse !important;
        margin: 0.75rem 0 !important;
        font-size: 0.75rem !important;
        border: 1px solid #e4e4e7 !important;
        border-radius: 0.375rem !important;
    }
    #scratchpad-rich-editor thead {
        border-bottom: 2px solid #e4e4e7 !important;
    }
    #scratchpad-rich-editor thead tr,
    #scratchpad-rich-editor thead tr th,
    #scratchpad-rich-editor th {
        background-color: #f4f4f5 !important;
        color: #18181b !important;
        font-weight: 600 !important;
        text-align: left !important;
        border: 1px solid #e4e4e7 !important;
        padding: 0.375rem 0.5rem !important;
    }
    #scratchpad-rich-editor td {
        background-color: transparent !important;
        border: 1px solid #e4e4e7 !important;
        padding: 0.375rem 0.5rem !important;
        color: #27272a !important;
        line-height: 1.4 !important;
    }
    #scratchpad-rich-editor tbody tr:hover td {
        background-color: rgba(0, 0, 0, 0.02) !important;
    }

    /* Table: Dark Mode */
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #scratchpad-rich-editor table,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #scratchpad-rich-editor table {
        border-color: #27272a !important;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #scratchpad-rich-editor thead,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #scratchpad-rich-editor thead {
        border-bottom-color: #3f3f46 !important;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #scratchpad-rich-editor thead tr,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #scratchpad-rich-editor thead tr th,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #scratchpad-rich-editor th,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #scratchpad-rich-editor thead tr,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #scratchpad-rich-editor thead tr th,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #scratchpad-rich-editor th {
        background-color: #27272a !important;
        color: #f4f4f5 !important;
        border-color: #3f3f46 !important;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #scratchpad-rich-editor td,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #scratchpad-rich-editor td {
        background-color: transparent !important;
        color: #d4d4d8 !important;
        border-color: #27272a !important;
    }
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) #scratchpad-rich-editor tbody tr:hover td,
    :is(html.dark, .dark, [data-theme="dark"], [data-flux-appearance="dark"]) & #scratchpad-rich-editor tbody tr:hover td {
        background-color: rgba(255, 255, 255, 0.03) !important;
    }

    /* Checklist Items */
    #scratchpad-rich-editor .note-checklist-item,
    #scratchpad-rich-editor [data-checklist="true"],
    #scratchpad-rich-editor div:has(> input.note-checkbox),
    #scratchpad-rich-editor div:has(> input[type="checkbox"]) {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: wrap !important;
        align-items: flex-start !important;
        gap: 0.5rem !important;
        margin: 0.25rem 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
    }
    #scratchpad-rich-editor .note-checklist-item > .note-checklist-text,
    #scratchpad-rich-editor [data-checklist="true"] > .note-checklist-text,
    #scratchpad-rich-editor .note-checklist-item > span,
    #scratchpad-rich-editor [data-checklist="true"] > span {
        display: block !important;
        flex: 1 1 calc(100% - 2rem) !important;
        min-width: 0 !important;
        outline: none !important;
        word-break: break-word;
    }
    #scratchpad-rich-editor input.note-checkbox,
    #scratchpad-rich-editor input[type="checkbox"] {
        margin-top: 0.2rem !important;
        cursor: pointer !important;
        flex-shrink: 0 !important;
        user-select: none !important;
        -webkit-user-select: none !important;
        pointer-events: auto !important;
    }
    #scratchpad-rich-editor input[type="checkbox"]:checked + span,
    #scratchpad-rich-editor input[type="checkbox"]:checked + div,
    #scratchpad-rich-editor input[type="checkbox"]:checked ~ .note-checklist-text {
        text-decoration: line-through !important;
        opacity: 0.5 !important;
    }
</style>

<div x-data="quickScratchpadDrawer($wire)"
     @note-saved.window="saveStatus = 'saved'; clearTimeout(saveTimer);"
     @scratchpad-note-loaded.window="handleNoteLoaded($event)"
     @scratchpad-note-loaded="handleNoteLoaded($event)"
     @keydown.window="handleShortcut($event)">

    <!-- Floating Quick Scratchpad Button (Bottom Right) -->
    <div class="fixed bottom-5 right-5 sm:bottom-6 sm:right-6 z-40 hidden sm:block">
        <button @click="openDrawer()" 
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
         @click="closeDrawer()"
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
                <select @change="flushSave()"
                        wire:change="selectNote($event.target.value)" 
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
                <button @click="flushSave()"
                        wire:click="createQuickNote" 
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
                    <button wire:click="openFull" 
                            @click="flushSave()"
                            type="button"
                            title="Open full page in Notes"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-medium text-amber-600 dark:text-amber-400 hover:bg-amber-500/10 transition-colors cursor-pointer">
                        <span>Open Full</span>
                        <flux:icon name="arrow-up-right" class="size-3" />
                    </button>
                @endif

                <button @click="closeDrawer()" 
                        type="button" 
                        class="p-1.5 rounded-lg text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer">
                    <flux:icon name="x-mark" class="size-4.5" />
                </button>
            </div>
        </div>

        <!-- Project link toolbar & Quick formatting controls -->
        <div class="px-3 sm:px-4 py-1.5 border-b border-zinc-100 dark:border-zinc-800/80 bg-zinc-50/40 dark:bg-zinc-900/40 flex items-center justify-between gap-2 text-xs shrink-0 flex-wrap">
            <div class="flex items-center gap-2">
                <span class="text-[11px] text-zinc-400">Project:</span>
                <select wire:model.live="projectId" 
                        @change="saveStatus = 'saving'"
                        class="py-0.5 pl-2 pr-6 text-[11px] rounded-md bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 focus:ring-1 focus:ring-amber-500">
                    <option value="">None (Personal)</option>
                    @foreach($this->projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Quick Rich Text Formatting Toolbar -->
            <div class="flex items-center gap-0.5 bg-zinc-100 dark:bg-zinc-800/70 p-0.5 rounded-lg border border-zinc-200/60 dark:border-zinc-700/60">
                <button @mousedown.prevent @click="insertChecklist()" type="button" title="Checklist (⌘⇧L)" class="p-1 rounded hover:bg-white dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:text-amber-600 dark:hover:text-amber-400 transition-colors cursor-pointer">
                    <flux:icon name="check-circle" class="size-3.5" />
                </button>
                <button @mousedown.prevent @click="exec('bold')" type="button" title="Bold (⌘B)" class="p-1 rounded hover:bg-white dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300 transition-colors font-bold text-xs cursor-pointer w-5 text-center leading-none">
                    B
                </button>
                <button @mousedown.prevent @click="exec('italic')" type="button" title="Italic (⌘I)" class="p-1 rounded hover:bg-white dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300 transition-colors italic font-serif text-xs cursor-pointer w-5 text-center leading-none">
                    I
                </button>
                <button @mousedown.prevent @click="exec('strikeThrough')" type="button" title="Strikethrough (⌘⇧X)" class="p-1 rounded hover:bg-white dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300 transition-colors line-through text-xs cursor-pointer w-5 text-center leading-none">
                    S
                </button>
                <div class="h-3 w-px bg-zinc-200 dark:bg-zinc-700 mx-0.5"></div>
                <button @mousedown.prevent @click="exec('insertUnorderedList')" type="button" title="Bulleted List" class="p-1 rounded hover:bg-white dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300 transition-colors cursor-pointer">
                    <flux:icon name="bars-3-bottom-left" class="size-3.5" />
                </button>
                <button @mousedown.prevent @click="exec('insertOrderedList')" type="button" title="Numbered List" class="p-1 rounded hover:bg-white dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300 transition-colors font-mono text-[10px] font-bold cursor-pointer w-5 text-center leading-none">
                    1.
                </button>
            </div>
        </div>

        <!-- Title Input -->
        <div class="px-4 sm:px-6 pt-4 pb-1 shrink-0">
            <input type="text" 
                   wire:key="quick-title-{{ $selectedNoteId ?? 'draft' }}"
                   wire:model.live.debounce.500ms="title" 
                   @input="saveStatus = 'saving'"
                   placeholder="Scratchpad Title" 
                   class="w-full text-lg font-bold bg-transparent border-0 text-zinc-900 dark:text-white placeholder-zinc-300 dark:placeholder-zinc-600 focus:outline-none focus:ring-0 p-0 tracking-tight">
        </div>

        <!-- Scratchpad WYSIWYG Rich Text Editor Canvas -->
        <div class="flex-1 p-4 sm:px-6 py-2 overflow-hidden flex flex-col relative">
            <div id="scratchpad-rich-editor"
                 wire:ignore
                 x-ref="scratchpadEditor"
                 contenteditable="true"
                 data-placeholder="Write your note here..."
                 @input="onEditorInput()"
                 @blur="flushSave()"
                 @keydown="onEditorKeydown($event)"
                 @change="handleCheckboxChange($event)"
                 class="w-full h-full bg-transparent border-0 outline-none text-xs sm:text-sm text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 dark:placeholder-zinc-600 focus:outline-none font-sans leading-relaxed custom-scrollbar overflow-y-auto prose dark:prose-invert max-w-none">{!! $content !!}</div>
        </div>

        <!-- Drawer Footer Status -->
        <div class="p-3 px-4 sm:px-6 border-t border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-900/60 flex items-center justify-between text-[11px] text-zinc-400 font-mono shrink-0">
            <div class="flex items-center gap-2">
                <span x-text="`${wordCount} words`">{{ str_word_count(strip_tags($content ?? '')) }} words</span>
                <span>&bull;</span>
                <span x-text="`${charCount} chars`">{{ strlen(strip_tags($content ?? '')) }} chars</span>
            </div>
            <div class="flex items-center gap-1.5">
                <kbd class="px-1 py-0.2 bg-zinc-200/70 dark:bg-zinc-800 rounded text-[10px]">Esc</kbd>
                <span class="text-[10px]">to dismiss</span>
            </div>
        </div>
    </aside>

    <script>
(function() {
    function registerQuickScratchpad() {
        if (window._quickScratchpadRegistered) return;
        window._quickScratchpadRegistered = true;

        Alpine.data('quickScratchpadDrawer', (wire) => ({
            isOpen: false,
            saveStatus: 'saved',
            saveTimer: null,
            isDirty: false,
            wordCount: {{ str_word_count(strip_tags($content ?? '')) }},
            charCount: {{ strlen(strip_tags($content ?? '')) }},

            init() {
                const w = wire || this.$wire;
                if (w) {
                    this.isOpen = !!w.isOpen;
                }

                // Sync with Livewire server state
                this.$watch('$wire.isOpen', (val) => {
                    this.isOpen = !!val;
                });

                this.$watch('isOpen', (open) => {
                    if (open) {
                        this.$nextTick(() => {
                            const w = wire || this.$wire;
                            if (w?.content && (!this.$refs.scratchpadEditor || !this.$refs.scratchpadEditor.innerHTML.trim())) {
                                this.setHtml(w.content);
                                this.updateStats();
                            }
                        });
                    }
                });

                if (this.isOpen && this.$refs.scratchpadEditor) {
                    const w = wire || this.$wire;
                    if (w?.content) {
                        this.setHtml(w.content);
                        this.updateStats();
                    }
                }

                try {
                    document.execCommand('defaultParagraphSeparator', false, 'p');
                } catch (e) {}
            },

            openDrawer(noteId = null) {
                this.isOpen = true;
                const w = wire || this.$wire;
                if (w) {
                    if (!noteId) {
                        const urlParams = new URLSearchParams(window.location.search);
                        const selectedFromUrl = urlParams.get('selected');
                        if (selectedFromUrl) {
                            noteId = parseInt(selectedFromUrl, 10);
                        }
                    }
                    w.openDrawer(noteId || null);
                }
            },

            closeDrawer() {
                this.flushSave();
                this.isOpen = false;
                const w = wire || this.$wire;
                if (w) {
                    w.closeDrawer();
                }
            },

            updateStats() {
                const text = this.$refs.scratchpadEditor ? (this.$refs.scratchpadEditor.innerText || '') : '';
                const clean = text.replace(/[\u200B-\u200D\uFEFF\n\r]/g, ' ').trim();
                this.wordCount = clean ? clean.split(/\s+/).filter(Boolean).length : 0;
                this.charCount = clean.length;
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

                // If the scratchpad editor is currently focused by user, do not clobber caret
                const isFocused = this.$refs.scratchpadEditor && (document.activeElement === this.$refs.scratchpadEditor || this.$refs.scratchpadEditor.contains(document.activeElement));
                if (isFocused) {
                    return;
                }

                this.isDirty = false;
                this.setHtml(content);
                this.updateStats();
            },

            setHtml(html) {
                if (this.$refs.scratchpadEditor) {
                    const normalized = this.normalizeEditorHtml(html);
                    if (this.$refs.scratchpadEditor.innerHTML !== normalized) {
                        this.$refs.scratchpadEditor.innerHTML = normalized;
                    }
                }
            },

            normalizeEditorHtml(html) {
                if (!html || !html.trim() || html.trim() === '<br>') {
                    return '<p><br></p>';
                }
                const parser = new DOMParser();
                const doc = parser.parseFromString(`<div>${html}</div>`, 'text/html');
                const root = doc.body.firstElementChild;

                // Flatten nested checklist items
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

                // Standardize all checklist items
                const allChecklists = root.querySelectorAll('.note-checklist-item, [data-checklist="true"], div:has(> input.note-checkbox), div:has(> input[type="checkbox"])');
                allChecklists.forEach(item => {
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

                return root.innerHTML;
            },

            createChecklistRow(content = '<br>') {
                const row = document.createElement('div');
                row.className = 'note-checklist-item my-1.5 flex items-start gap-2.5 w-full';
                row.setAttribute('data-checklist', 'true');
                row.innerHTML = `<input type='checkbox' contenteditable='false' class='note-checkbox mt-1 size-4 rounded accent-amber-500 cursor-pointer shrink-0' /><span class='note-checklist-text flex-1 outline-none leading-normal text-zinc-900 dark:text-zinc-100'>${content}</span>`;
                return row;
            },

            insertChecklist() {
                this.$refs.scratchpadEditor.focus();
                const sel = window.getSelection();
                let anchor = sel && sel.rangeCount > 0 ? sel.anchorNode : null;
                if (anchor && anchor.nodeType === Node.TEXT_NODE) anchor = anchor.parentElement;

                if (!anchor || !this.$refs.scratchpadEditor.contains(anchor)) {
                    const newRow = this.createChecklistRow('<br>');
                    const p = document.createElement('p');
                    p.innerHTML = '<br>';
                    this.$refs.scratchpadEditor.appendChild(newRow);
                    this.$refs.scratchpadEditor.appendChild(p);
                    this.focusElement(newRow.querySelector('.note-checklist-text'), false);
                    this.onEditorInput();
                    return;
                }

                const existingChecklist = anchor.closest('.note-checklist-item, [data-checklist="true"], div:has(> input.note-checkbox), div:has(> input[type="checkbox"])');
                if (existingChecklist && this.$refs.scratchpadEditor.contains(existingChecklist)) {
                    let topRow = existingChecklist;
                    while (topRow.parentElement && topRow.parentElement !== this.$refs.scratchpadEditor) {
                        const p = topRow.parentElement.closest('.note-checklist-item, [data-checklist="true"], div:has(> input.note-checkbox), div:has(> input[type="checkbox"])');
                        if (p && this.$refs.scratchpadEditor.contains(p)) {
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
                    this.onEditorInput();
                    return;
                }

                let block = anchor;
                while (block && block.parentElement && block.parentElement !== this.$refs.scratchpadEditor) {
                    block = block.parentElement;
                }
                if (!block || block === this.$refs.scratchpadEditor) block = anchor;

                if (block && block !== this.$refs.scratchpadEditor && block.tagName !== 'TABLE') {
                    const html = block.innerHTML;
                    const newRow = this.createChecklistRow(html && html.trim() && html !== '<br>' ? html : '<br>');
                    block.replaceWith(newRow);
                    this.focusElement(newRow.querySelector('.note-checklist-text'), true);
                } else {
                    const newRow = this.createChecklistRow('<br>');
                    this.$refs.scratchpadEditor.appendChild(newRow);
                    this.focusElement(newRow.querySelector('.note-checklist-text'), false);
                }

                this.onEditorInput();
            },

            exec(cmd, val = null) {
                this.$refs.scratchpadEditor.focus();
                document.execCommand(cmd, false, val);
                this.onEditorInput();
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

            onEditorInput() {
                this.isDirty = true;
                this.updateStats();
                this.saveStatus = 'saving';
                clearTimeout(this.saveTimer);
                this.saveTimer = setTimeout(() => {
                    this.flushSave();
                }, 400);
            },

            flushSave() {
                if (this.saveTimer) {
                    clearTimeout(this.saveTimer);
                    this.saveTimer = null;
                }
                if (!this.isDirty) return;
                if (this.$refs.scratchpadEditor) {
                    const w = wire || this.$wire;
                    if (w) {
                        this.isDirty = false;
                        w.set('content', this.$refs.scratchpadEditor.innerHTML);
                    }
                    this.saveStatus = 'saved';
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
                    this.onEditorInput();
                }
            },

            onEditorKeydown(e) {
                // Checklist handler (Enter, Backspace, Delete, Tab)
                if (e.key === 'Backspace' || e.key === 'Delete' || e.key === 'Enter' || e.key === 'Tab') {
                    if (this.handleChecklistKeydown(e)) {
                        return;
                    }
                }

                // List handler (Enter on empty <li>, Tab in <li>)
                if (e.key === 'Enter' || e.key === 'Tab') {
                    if (this.handleListKeydown(e)) {
                        return;
                    }
                }

                // Shortcuts
                if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'b') {
                    e.preventDefault();
                    this.exec('bold');
                } else if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'i') {
                    e.preventDefault();
                    this.exec('italic');
                } else if ((e.metaKey || e.ctrlKey) && e.shiftKey && e.key.toLowerCase() === 'x') {
                    e.preventDefault();
                    this.exec('strikeThrough');
                } else if ((e.metaKey || e.ctrlKey) && e.shiftKey && e.key.toLowerCase() === 'l') {
                    e.preventDefault();
                    this.insertChecklist();
                } else if (e.key === 'Tab') {
                    e.preventDefault();
                    document.execCommand('insertHTML', false, '&nbsp;&nbsp;&nbsp;&nbsp;');
                    this.onEditorInput();
                }
            },

            handleChecklistKeydown(e) {
                const sel = window.getSelection();
                if (!sel || sel.rangeCount === 0) return false;

                let anchor = sel.anchorNode;
                if (!anchor) return false;
                if (anchor.nodeType === Node.TEXT_NODE) anchor = anchor.parentElement;
                if (!anchor || !this.$refs.scratchpadEditor.contains(anchor)) return false;

                const checklistRow = anchor.closest('.note-checklist-item, [data-checklist="true"], div:has(> input.note-checkbox), div:has(> input[type="checkbox"])');
                if (!checklistRow || !this.$refs.scratchpadEditor.contains(checklistRow)) return false;

                let topRow = checklistRow;
                while (topRow.parentElement && topRow.parentElement !== this.$refs.scratchpadEditor) {
                    const p = topRow.parentElement.closest('.note-checklist-item, [data-checklist="true"], div:has(> input.note-checkbox), div:has(> input[type="checkbox"])');
                    if (p && this.$refs.scratchpadEditor.contains(p)) {
                        topRow = p;
                    } else {
                        break;
                    }
                }

                const span = checklistRow.querySelector('.note-checklist-text') || checklistRow.querySelector('span') || checklistRow;
                const rawText = span.innerText || span.textContent || '';
                const cleanText = rawText.replace(/[\u200B-\u200D\uFEFF\n\r]/g, '').trim();

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
                    this.onEditorInput();
                    return true;
                }

                if (e.key === 'Backspace' || e.key === 'Delete') {
                    if (cleanText === '') {
                        e.preventDefault();
                        const prev = topRow.previousElementSibling;
                        const next = topRow.nextElementSibling;
                        topRow.remove();

                        if (!this.$refs.scratchpadEditor.innerHTML.trim() || this.$refs.scratchpadEditor.innerHTML === '<br>') {
                            this.$refs.scratchpadEditor.innerHTML = '<p><br></p>';
                            this.focusElement(this.$refs.scratchpadEditor.querySelector('p'), false);
                        } else if (prev) {
                            this.focusElement(prev, true);
                        } else if (next) {
                            this.focusElement(next, false);
                        } else {
                            const p = document.createElement('p');
                            p.innerHTML = '<br>';
                            this.$refs.scratchpadEditor.appendChild(p);
                            this.focusElement(p, false);
                        }
                        this.onEditorInput();
                        return true;
                    }

                    if (e.key === 'Backspace') {
                        const range = sel.getRangeAt(0);
                        let isAtStart = false;
                        if (range.collapsed && range.startOffset === 0) {
                            if (range.startContainer === span || range.startContainer === span.firstChild || range.startContainer.parentNode === span) {
                                isAtStart = true;
                            }
                        }
                        if (isAtStart) {
                            e.preventDefault();
                            const p = document.createElement('p');
                            p.innerHTML = span.innerHTML || '<br>';
                            topRow.replaceWith(p);
                            this.focusElement(p, false);
                            this.onEditorInput();
                            return true;
                        }
                    }
                }

                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (cleanText === '') {
                        const p = document.createElement('p');
                        p.innerHTML = '<br>';
                        topRow.replaceWith(p);
                        this.focusElement(p, false);
                        this.onEditorInput();
                        return true;
                    }

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

                    this.onEditorInput();
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
                if (!anchor || !this.$refs.scratchpadEditor.contains(anchor)) return false;

                const li = anchor.closest('li');
                if (!li) return false;

                const list = li.closest('ul, ol');
                if (!list) return false;

                if (e.key === 'Tab') {
                    e.preventDefault();
                    if (e.shiftKey) {
                        document.execCommand('outdent', false, null);
                    } else {
                        document.execCommand('indent', false, null);
                    }
                    this.onEditorInput();
                    return true;
                }

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
                        this.onEditorInput();
                        return true;
                    }
                }

                return false;
            },

            handleShortcut(e) {
                // Cmd+Shift+N or Ctrl+Shift+N
                if ((e.metaKey || e.ctrlKey) && e.shiftKey && (e.key === 'N' || e.key === 'n')) {
                    e.preventDefault();
                    if (this.isOpen) {
                        this.closeDrawer();
                    } else {
                        this.openDrawer();
                    }
                }
                // Escape to close if open
                if (e.key === 'Escape' && this.isOpen) {
                    e.preventDefault();
                    this.closeDrawer();
                }
            }
        }));
    }

    if (window.Alpine) {
        registerQuickScratchpad();
    } else {
        document.addEventListener('alpine:init', registerQuickScratchpad);
    }
})();
    </script>
</div>

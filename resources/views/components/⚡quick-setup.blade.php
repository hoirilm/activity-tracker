<?php

use Livewire\Component;
use App\Models\Project;
use App\Models\Category;
use App\Models\Task;
use App\Models\Label;
use Flux\Flux;

new class extends Component
{
    public bool $showModal = false;
    public string $activeTab = 'starter'; // 'starter', 'project', 'category', 'task', 'label'

    // Form inputs
    public string $projectName = '';
    public string $projectClient = '';

    public string $categoryName = '';

    public string $taskTitle = '';
    public string $taskDescription = '';
    public ?int $taskProjectId = null;

    public string $labelName = '';
    public string $labelColor = 'amber';

    protected $listeners = [
        'open-quick-setup' => 'openModal',
        'close-quick-setup' => 'closeModal',
        'quick-setup-updated' => '$refresh',
    ];

    public function mount()
    {
        if (auth()->check()) {
            $hasProjects = auth()->user()->projects()->exists();
            $hasCategories = auth()->user()->categories()->exists();
            if (!$hasProjects || !$hasCategories) {
                $this->showModal = true;
            }
        }
    }

    public function openModal()
    {
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        if ($this->has_project && $this->has_category) {
            $this->dispatch('start-onboarding-tour');
        }
    }

    public function getProjectsProperty()
    {
        return auth()->check() ? auth()->user()->projects()->get() : collect();
    }

    public function getCategoriesProperty()
    {
        return auth()->check() ? auth()->user()->categories()->get() : collect();
    }

    public function getTasksProperty()
    {
        return auth()->check() ? auth()->user()->tasks()->with('project')->get() : collect();
    }

    public function getLabelsProperty()
    {
        return auth()->check() ? auth()->user()->labels()->get() : collect();
    }

    public function getHasProjectProperty(): bool
    {
        return $this->projects->count() > 0;
    }

    public function getHasCategoryProperty(): bool
    {
        return $this->categories->count() > 0;
    }

    public function getHasTaskProperty(): bool
    {
        return $this->tasks->count() > 0;
    }

    public function getHasLabelProperty(): bool
    {
        return $this->labels->count() > 0;
    }

    public function getProgressPercentProperty(): int
    {
        $score = 0;
        if ($this->has_project) $score += 35;
        if ($this->has_category) $score += 35;
        if ($this->has_task) $score += 15;
        if ($this->has_label) $score += 15;
        return $score;
    }

    public function applyStarterPack()
    {
        $user = auth()->user();
        if (!$user) return;

        // 1. Project
        $project = $user->projects()->first();
        if (!$project) {
            $project = $user->projects()->create([
                'name' => 'General Project',
                'client_name' => 'Internal',
            ]);
        }

        // 2. Categories
        if (!$user->categories()->exists()) {
            $user->categories()->createMany([
                ['name' => 'Feature Development'],
                ['name' => 'Bug Fix & Maintenance'],
                ['name' => 'Meeting & Discussion'],
            ]);
        }

        // 3. Task
        if (!$user->tasks()->exists()) {
            $user->tasks()->create([
                'title' => 'Setup & Initial Activity Tracker',
                'description' => 'Initial task to start daily activity tracking.',
                'project_id' => $project->id ?? null,
                'status' => Task::STATUS_NEW,
            ]);
        }

        // 4. Label
        if (!$user->labels()->exists()) {
            $user->labels()->create([
                'name' => 'High Priority',
                'color' => 'amber',
            ]);
        }

        $this->dispatch('quick-setup-updated');
        $this->dispatch('project-created');
        $this->dispatch('category-created');
        $this->dispatch('toast', title: 'Starter Pack activated successfully!', category: 'STARTER PACK', type: 'success');
    }

    public function createProject()
    {
        $this->validate([
            'projectName' => 'required|string|max:255',
            'projectClient' => 'nullable|string|max:255',
        ]);

        auth()->user()->projects()->create([
            'name' => trim($this->projectName),
            'client_name' => trim($this->projectClient) ?: null,
        ]);

        $this->projectName = '';
        $this->projectClient = '';

        $this->dispatch('quick-setup-updated');
        $this->dispatch('project-created');
        $this->dispatch('toast', title: 'Project created successfully!', category: 'PROJECT', type: 'success');
    }

    public function createCategory()
    {
        $this->validate([
            'categoryName' => 'required|string|max:255',
        ]);

        auth()->user()->categories()->create([
            'name' => trim($this->categoryName),
        ]);

        $this->categoryName = '';

        $this->dispatch('quick-setup-updated');
        $this->dispatch('category-created');
        $this->dispatch('toast', title: 'Category created successfully!', category: 'CATEGORY', type: 'success');
    }

    public function createTask()
    {
        $this->validate([
            'taskTitle' => 'required|string|max:255',
            'taskDescription' => 'nullable|string',
            'taskProjectId' => 'nullable|exists:projects,id',
        ]);

        auth()->user()->tasks()->create([
            'title' => trim($this->taskTitle),
            'description' => trim($this->taskDescription) ?: null,
            'project_id' => $this->taskProjectId ?: null,
            'status' => Task::STATUS_NEW,
        ]);

        $this->taskTitle = '';
        $this->taskDescription = '';
        $this->taskProjectId = null;

        $this->dispatch('quick-setup-updated');
        $this->dispatch('toast', title: 'Task created successfully!', category: 'TASK', type: 'success');
    }

    public function createLabel()
    {
        $this->validate([
            'labelName' => 'required|string|max:255',
            'labelColor' => 'required|string',
        ]);

        auth()->user()->labels()->create([
            'name' => trim($this->labelName),
            'color' => $this->labelColor,
        ]);

        $this->labelName = '';

        $this->dispatch('quick-setup-updated');
        $this->dispatch('toast', title: 'Label created successfully!', category: 'LABEL', type: 'success');
    }
};
?>

<div>
    <!-- Floating Setup Reminder when mandatory setup is incomplete and modal is closed -->
    @if(auth()->check() && (!$this->has_project || !$this->has_category) && !$showModal)
        <div class="fixed bottom-4 left-4 z-40">
            <button @click="$wire.openModal()" type="button" class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 shadow-lg text-xs font-semibold hover:border-amber-500/50 active:scale-95 transition-all cursor-pointer">
                <span class="size-2 rounded-full bg-amber-500"></span>
                <flux:icon name="sparkles" class="size-3.5 text-amber-500 shrink-0" />
                <span>Quick Setup ({{ $this->progress_percent }}%)</span>
            </button>
        </div>
    @endif

    <!-- Quick Setup Modal Overlay -->
    @if($showModal)
        <div data-quick-setup-modal
             class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 overflow-y-auto bg-black/60"
             x-data
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            <div class="relative w-full max-w-2xl bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden my-auto">
                
                <!-- Modal Header banner -->
                <div class="bg-zinc-950 p-6 sm:p-8 text-white relative overflow-hidden border-b border-zinc-200/80 dark:border-zinc-800">
                    <div class="flex items-start justify-between gap-4 relative z-10">
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-zinc-900 text-xs font-semibold text-zinc-300 mb-3 border border-zinc-800">
                                <flux:icon name="sparkles" class="size-3.5 text-amber-400" />
                                <span>New User Onboarding</span>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-white">Welcome to Activity Tracker! 🚀</h2>
                            <p class="mt-1.5 text-xs sm:text-sm text-zinc-400 max-w-lg leading-relaxed">
                                Let's set up your initial data. At least <strong class="font-bold text-amber-400 bg-amber-500/10 border border-amber-500/25 px-2 py-0.5 rounded-md">1 Project</strong> and <strong class="font-bold text-amber-400 bg-amber-500/10 border border-amber-500/25 px-2 py-0.5 rounded-md">1 Category</strong> are required so you can start tracking activities.
                            </p>
                        </div>
                        <button @click="$wire.closeModal()" type="button" class="p-2 rounded-full text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors cursor-pointer shrink-0">
                            <flux:icon name="x-mark" class="size-5" />
                        </button>
                    </div>

                    <!-- Progress Bar Header -->
                    <div class="mt-6 pt-4 border-t border-zinc-800/80">
                        <div class="flex items-center justify-between text-xs font-semibold mb-2">
                            <span class="text-zinc-300">Initial Setup Progress</span>
                            <span class="font-mono font-bold text-amber-400">{{ $this->progress_percent }}% Completed</span>
                        </div>
                        <div class="w-full bg-zinc-900 rounded-full h-2.5 overflow-hidden p-0.5 border border-zinc-800">
                            <div class="bg-gradient-to-r from-amber-500 to-amber-400 h-full rounded-full transition-all duration-500 shadow-xs shadow-amber-500/30" style="width: {{ $this->progress_percent }}%"></div>
                        </div>
                    </div>
                </div>

                <!-- Checkpoints Status Grid -->
                <div class="p-6 sm:p-8 space-y-6">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <!-- Checkpoint 1: Project -->
                        <div class="p-3.5 rounded-2xl border transition-all bg-zinc-50 dark:bg-zinc-900/60 border-zinc-200/80 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                    <flux:icon name="folder" class="size-3.5 text-zinc-500 dark:text-zinc-400" />
                                    <span>Project</span>
                                </span>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-md {{ $this->has_project ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20 animate-pulse' }}">
                                    {{ $this->has_project ? 'Done' : 'Required' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-zinc-600 dark:text-zinc-400 font-medium">
                                @if($this->has_project)
                                    <flux:icon name="check-circle" class="size-3.5 text-emerald-500 shrink-0" />
                                    <span class="truncate">{{ $this->projects->count() }} created</span>
                                @else
                                    <flux:icon name="exclamation-circle" class="size-3.5 text-amber-500 shrink-0" />
                                    <span>None created</span>
                                @endif
                            </div>
                        </div>

                        <!-- Checkpoint 2: Category -->
                        <div class="p-3.5 rounded-2xl border transition-all bg-zinc-50 dark:bg-zinc-900/60 border-zinc-200/80 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                    <flux:icon name="tag" class="size-3.5 text-zinc-500 dark:text-zinc-400" />
                                    <span>Category</span>
                                </span>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-md {{ $this->has_category ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20 animate-pulse' }}">
                                    {{ $this->has_category ? 'Done' : 'Required' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-zinc-600 dark:text-zinc-400 font-medium">
                                @if($this->has_category)
                                    <flux:icon name="check-circle" class="size-3.5 text-emerald-500 shrink-0" />
                                    <span class="truncate">{{ $this->categories->count() }} created</span>
                                @else
                                    <flux:icon name="exclamation-circle" class="size-3.5 text-amber-500 shrink-0" />
                                    <span>None created</span>
                                @endif
                            </div>
                        </div>

                        <!-- Checkpoint 3: Task -->
                        <div class="p-3.5 rounded-2xl border transition-all bg-zinc-50 dark:bg-zinc-900/60 border-zinc-200/80 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                    <flux:icon name="clipboard-document-list" class="size-3.5 text-zinc-500 dark:text-zinc-400" />
                                    <span>Task</span>
                                </span>
                                <span class="px-2 py-0.5 text-[10px] font-medium rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700/60">
                                    Optional
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-zinc-600 dark:text-zinc-400 font-medium">
                                @if($this->has_task)
                                    <flux:icon name="check-circle" class="size-3.5 text-emerald-500 shrink-0" />
                                    <span class="truncate">{{ $this->tasks->count() }} created</span>
                                @else
                                    <flux:icon name="minus-circle" class="size-3.5 text-zinc-400 shrink-0" />
                                    <span>None created</span>
                                @endif
                            </div>
                        </div>

                        <!-- Checkpoint 4: Label -->
                        <div class="p-3.5 rounded-2xl border transition-all bg-zinc-50 dark:bg-zinc-900/60 border-zinc-200/80 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                    <flux:icon name="bookmark" class="size-3.5 text-zinc-500 dark:text-zinc-400" />
                                    <span>Label</span>
                                </span>
                                <span class="px-2 py-0.5 text-[10px] font-medium rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700/60">
                                    Optional
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-zinc-600 dark:text-zinc-400 font-medium">
                                @if($this->has_label)
                                    <flux:icon name="check-circle" class="size-3.5 text-emerald-500 shrink-0" />
                                    <span class="truncate">{{ $this->labels->count() }} created</span>
                                @else
                                    <flux:icon name="minus-circle" class="size-3.5 text-zinc-400 shrink-0" />
                                    <span>None created</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Tabs Selector -->
                    <div class="flex items-center border-b border-zinc-200 dark:border-zinc-800 overflow-x-auto gap-2">
                        <button @click="$wire.set('activeTab', 'starter')" type="button" class="pb-2.5 px-3 text-xs font-bold border-b-2 transition-all cursor-pointer whitespace-nowrap flex items-center gap-1.5 {{ $activeTab === 'starter' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-zinc-500 hover:text-zinc-900 dark:hover:text-white' }}">
                            <flux:icon name="sparkles" class="size-3.5 text-amber-500" />
                            <span>1-Click Starter Pack</span>
                        </button>
                        <button @click="$wire.set('activeTab', 'project')" type="button" class="pb-2.5 px-3 text-xs font-bold border-b-2 transition-all cursor-pointer whitespace-nowrap flex items-center gap-1.5 {{ $activeTab === 'project' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-zinc-500 hover:text-zinc-900 dark:hover:text-white' }}">
                            <flux:icon name="folder" class="size-3.5 text-zinc-400" />
                            <span>Project</span>
                            @if($this->has_project)
                                <flux:icon name="check" class="size-3 text-emerald-500" />
                            @endif
                        </button>
                        <button @click="$wire.set('activeTab', 'category')" type="button" class="pb-2.5 px-3 text-xs font-bold border-b-2 transition-all cursor-pointer whitespace-nowrap flex items-center gap-1.5 {{ $activeTab === 'category' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-zinc-500 hover:text-zinc-900 dark:hover:text-white' }}">
                            <flux:icon name="tag" class="size-3.5 text-zinc-400" />
                            <span>Category</span>
                            @if($this->has_category)
                                <flux:icon name="check" class="size-3 text-emerald-500" />
                            @endif
                        </button>
                        <button @click="$wire.set('activeTab', 'task')" type="button" class="pb-2.5 px-3 text-xs font-bold border-b-2 transition-all cursor-pointer whitespace-nowrap flex items-center gap-1.5 {{ $activeTab === 'task' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-zinc-500 hover:text-zinc-900 dark:hover:text-white' }}">
                            <flux:icon name="clipboard-document-list" class="size-3.5 text-zinc-400" />
                            <span>Task</span>
                            @if($this->has_task)
                                <flux:icon name="check" class="size-3 text-emerald-500" />
                            @endif
                        </button>
                        <button @click="$wire.set('activeTab', 'label')" type="button" class="pb-2.5 px-3 text-xs font-bold border-b-2 transition-all cursor-pointer whitespace-nowrap flex items-center gap-1.5 {{ $activeTab === 'label' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-zinc-500 hover:text-zinc-900 dark:hover:text-white' }}">
                            <flux:icon name="bookmark" class="size-3.5 text-zinc-400" />
                            <span>Label</span>
                            @if($this->has_label)
                                <flux:icon name="check" class="size-3 text-emerald-500" />
                            @endif
                        </button>
                    </div>

                    <!-- TAB 1: Starter Pack (Recommended Fast Track) -->
                    @if($activeTab === 'starter')
                        <div class="p-5 rounded-2xl bg-zinc-50 dark:bg-zinc-900/80 border border-zinc-200/80 dark:border-zinc-800 space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="p-2.5 rounded-xl bg-amber-500 text-zinc-950 shrink-0 shadow-xs font-bold">
                                    <flux:icon name="bolt" class="size-5 text-zinc-950" />
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-zinc-900 dark:text-white">Fastest Option: Activate Default Starter Pack</h4>
                                    <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1 leading-relaxed">
                                        Want to start tracking activities right away without hassle? The system will automatically generate a standard template for you:
                                    </p>
                                    <ul class="mt-2.5 space-y-1 text-xs text-zinc-700 dark:text-zinc-300">
                                        <li class="flex items-center gap-2">
                                            <flux:icon name="check-circle" class="size-3.5 text-emerald-500 shrink-0" />
                                            <span><strong>Project:</strong> "General Project" (Client: Internal)</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <flux:icon name="check-circle" class="size-3.5 text-emerald-500 shrink-0" />
                                            <span><strong>Category:</strong> Feature Development, Bug Fix & Maintenance, Meeting</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <flux:icon name="check-circle" class="size-3.5 text-emerald-500 shrink-0" />
                                            <span><strong>Task:</strong> Setup & Initial Activity Tracker</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <flux:icon name="check-circle" class="size-3.5 text-emerald-500 shrink-0" />
                                            <span><strong>Label:</strong> High Priority</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="pt-2 flex items-center justify-end">
                                <button wire:click="applyStarterPack" wire:loading.attr="disabled" type="button" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 font-semibold text-xs border border-zinc-200 dark:border-zinc-700/80 active:scale-95 transition-all cursor-pointer">
                                    <flux:icon name="sparkles" class="size-3.5 text-amber-500 dark:text-amber-400" />
                                    <span>Activate Starter Pack Now</span>
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- TAB 2: Custom Project Form -->
                    @if($activeTab === 'project')
                        <div class="space-y-4">
                            <form wire:submit.prevent="createProject" class="space-y-3 bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-2xl border border-zinc-200/80 dark:border-zinc-700/60">
                                <h4 class="text-xs font-bold text-zinc-900 dark:text-white uppercase tracking-wider">Add New Project</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Project Name <span class="text-red-500">*</span></label>
                                        <input wire:model="projectName" type="text" placeholder="e.g. Website Revamp" class="w-full px-3 py-2 text-xs rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 focus:outline-hidden" />
                                        @error('projectName') <span class="text-[10px] text-red-500 mt-0.5 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Client Name (Optional)</label>
                                        <input wire:model="projectClient" type="text" placeholder="e.g. Acme Corp" class="w-full px-3 py-2 text-xs rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 focus:outline-hidden" />
                                    </div>
                                </div>
                                <div class="flex justify-end pt-1">
                                    <button type="submit" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-semibold text-xs border border-amber-400/80 shadow-xs shadow-amber-500/20 transition-all cursor-pointer">
                                        + Save Project
                                    </button>
                                </div>
                            </form>

                            <!-- List existing projects -->
                            <div>
                                <h5 class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-2">Your Projects ({{ $this->projects->count() }}):</h5>
                                @if($this->has_project)
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($this->projects as $p)
                                             <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 text-zinc-700 dark:text-zinc-300 text-xs font-medium">
                                                 📁 {{ $p->name }}
                                                 @if($p->client_name)
                                                     <span class="text-[10px] opacity-75">({{ $p->client_name }})</span>
                                                 @endif
                                             </span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-xs text-zinc-400 italic">No projects created yet.</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- TAB 3: Custom Category Form -->
                    @if($activeTab === 'category')
                        <div class="space-y-4">
                            <form wire:submit.prevent="createCategory" class="space-y-3 bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-2xl border border-zinc-200/80 dark:border-zinc-700/60">
                                <h4 class="text-xs font-bold text-zinc-900 dark:text-white uppercase tracking-wider">Add New Category</h4>
                                <div>
                                    <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Category Name <span class="text-red-500">*</span></label>
                                    <input wire:model="categoryName" type="text" placeholder="e.g. Development, Design, Meeting" class="w-full px-3 py-2 text-xs rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 focus:outline-hidden" />
                                    @error('categoryName') <span class="text-[10px] text-red-500 mt-0.5 block">{{ $message }}</span> @enderror
                                </div>
                                <div class="flex justify-end pt-1">
                                    <button type="submit" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-semibold text-xs border border-amber-400/80 shadow-xs shadow-amber-500/20 transition-all cursor-pointer">
                                        + Save Category
                                    </button>
                                </div>
                            </form>

                            <!-- List existing categories -->
                            <div>
                                <h5 class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-2">Your Categories ({{ $this->categories->count() }}):</h5>
                                @if($this->has_category)
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($this->categories as $c)
                                             <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 text-zinc-700 dark:text-zinc-300 text-xs font-medium">
                                                 🏷️ {{ $c->name }}
                                             </span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-xs text-zinc-400 italic">No categories created yet.</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- TAB 4: Custom Task Form -->
                    @if($activeTab === 'task')
                        <div class="space-y-4">
                            <form wire:submit.prevent="createTask" class="space-y-3 bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-2xl border border-zinc-200/80 dark:border-zinc-700/60">
                                <h4 class="text-xs font-bold text-zinc-900 dark:text-white uppercase tracking-wider">Add New Task (Optional)</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Task Title <span class="text-red-500">*</span></label>
                                        <input wire:model="taskTitle" type="text" placeholder="e.g. Slice UI Landing Page" class="w-full px-3 py-2 text-xs rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 focus:outline-hidden" />
                                        @error('taskTitle') <span class="text-[10px] text-red-500 mt-0.5 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Select Project</label>
                                        <select wire:model="taskProjectId" class="w-full px-3 py-2 text-xs rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 focus:outline-hidden">
                                            <option value="">-- No Project --</option>
                                            @foreach($this->projects as $p)
                                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1 flex items-center gap-1.5">
                                        <flux:icon name="document-text" class="size-3.5 text-zinc-400 dark:text-zinc-500" />
                                        <span>Description (Optional)</span>
                                    </label>
                                    <textarea wire:model="taskDescription" rows="2" placeholder="Add task description or notes..." class="w-full px-3 py-2 text-xs rounded-xl border border-zinc-200/80 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 focus:outline-hidden font-sans leading-relaxed"></textarea>
                                </div>
                                <div class="flex justify-end pt-1">
                                    <button type="submit" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-semibold text-xs border border-amber-400/80 shadow-xs shadow-amber-500/20 transition-all cursor-pointer">
                                        + Save Task
                                    </button>
                                </div>
                            </form>

                            <!-- List existing tasks -->
                            <div>
                                <h5 class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-2">Your Tasks ({{ $this->tasks->count() }}):</h5>
                                @if($this->has_task)
                                    <div class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
                                        @foreach($this->tasks as $t)
                                            <div class="flex items-center justify-between p-2 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/60 dark:border-zinc-700/50 text-xs">
                                                <span class="font-medium text-zinc-800 dark:text-zinc-200">📝 {{ $t->title }}</span>
                                                @if($t->project)
                                                    <span class="text-[10px] px-2 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700/60">
                                                        {{ $t->project->name }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-xs text-zinc-400 italic">No tasks created yet.</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- TAB 5: Custom Label Form -->
                    @if($activeTab === 'label')
                        <div class="space-y-4">
                            <form wire:submit.prevent="createLabel" class="space-y-3 bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-2xl border border-zinc-200/80 dark:border-zinc-700/60">
                                <h4 class="text-xs font-bold text-zinc-900 dark:text-white uppercase tracking-wider">Add New Label (Optional)</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Label Name <span class="text-red-500">*</span></label>
                                        <input wire:model="labelName" type="text" placeholder="e.g. Urgent, Frontend" class="w-full px-3 py-2 text-xs rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 focus:outline-hidden" />
                                        @error('labelName') <span class="text-[10px] text-red-500 mt-0.5 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Label Color</label>
                                        <select wire:model="labelColor" class="w-full px-3 py-2 text-xs rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 focus:outline-hidden">
                                            <option value="amber">Amber / Orange</option>
                                            <option value="emerald">Emerald / Green</option>
                                            <option value="rose">Rose / Red</option>
                                            <option value="zinc">Zinc / Neutral</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="flex justify-end pt-1">
                                    <button type="submit" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-semibold text-xs border border-amber-400/80 shadow-xs shadow-amber-500/20 transition-all cursor-pointer">
                                        + Save Label
                                    </button>
                                </div>
                            </form>

                            <!-- List existing labels -->
                            <div>
                                <h5 class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-2">Your Labels ({{ $this->labels->count() }}):</h5>
                                @if($this->has_label)
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($this->labels as $l)
                                             <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 text-zinc-700 dark:text-zinc-300 text-xs font-medium">
                                                 🏷️ {{ $l->name }}
                                             </span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-xs text-zinc-400 italic">No labels created yet.</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="p-6 bg-zinc-50 dark:bg-zinc-950/60 border-t border-zinc-200/80 dark:border-zinc-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-xs text-zinc-600 dark:text-zinc-400">
                        @if($this->has_project && $this->has_category)
                            <span class="text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1">
                                <flux:icon name="check-circle" class="size-4 shrink-0 text-emerald-500" />
                                <span>Required steps completed! You are ready to use the Tracker.</span>
                            </span>
                        @else
                            <span class="text-amber-600 dark:text-amber-400 font-medium flex items-center gap-1">
                                <flux:icon name="exclamation-circle" class="size-4 shrink-0 text-amber-500" />
                                <span>Create at least 1 Project & 1 Category to complete setup.</span>
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        @if($this->has_project && $this->has_category)
                            <a href="{{ route('tracker') }}" wire:navigate @click="$wire.closeModal()" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-semibold text-xs shadow-xs shadow-amber-500/20 border border-amber-400/80 active:scale-95 transition-all cursor-pointer">
                                <span>Start Using Tracker 🚀</span>
                            </a>
                        @else
                            <button @click="$wire.closeModal()" type="button" class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-zinc-300 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-xs font-medium transition-colors cursor-pointer text-center">
                                Close For Now
                            </button>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    @endif
</div>

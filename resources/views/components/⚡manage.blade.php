<?php

use Livewire\Component;
use App\Models\Project;
use App\Models\Category;

new class extends Component
{
    public $projectName = '';
    public $projectClient = '';
    public $categoryName = '';

    public $editingProjectId = null;
    public $editingProjectName = '';
    public $editingProjectClient = '';

    public $editingCategoryId = null;
    public $editingCategoryName = '';

    public function getProjectsProperty()
    {
        return auth()->user()->projects()->get();
    }

    public function getCategoriesProperty()
    {
        return auth()->user()->categories()->get();
    }

    public function addProject()
    {
        $this->validate([
            'projectName' => 'required|string|max:255',
            'projectClient' => 'nullable|string|max:255',
        ]);

        auth()->user()->projects()->create([
            'name' => $this->projectName,
            'client_name' => $this->projectClient ?: null,
        ]);

        $this->reset(['projectName', 'projectClient']);
        session()->flash('project_message', 'Project added successfully.');
    }

    public function addCategory()
    {
        $this->validate([
            'categoryName' => 'required|string|max:255',
        ]);

        auth()->user()->categories()->create([
            'name' => $this->categoryName,
        ]);

        $this->reset('categoryName');
        session()->flash('category_message', 'Category added successfully.');
    }

    public function editProject($id)
    {
        $project = auth()->user()->projects()->find($id);
        if ($project) {
            $this->editingProjectId = $id;
            $this->editingProjectName = $project->name;
            $this->editingProjectClient = $project->client_name;
        }
    }

    public function updateProject()
    {
        $this->validate([
            'editingProjectName' => 'required|string|max:255',
            'editingProjectClient' => 'nullable|string|max:255',
        ]);

        auth()->user()->projects()->find($this->editingProjectId)?->update([
            'name' => $this->editingProjectName,
            'client_name' => $this->editingProjectClient ?: null,
        ]);

        $this->editingProjectId = null;
        session()->flash('project_message', 'Project updated successfully.');
    }

    public function cancelEditProject()
    {
        $this->editingProjectId = null;
    }

    public function editCategory($id)
    {
        $category = auth()->user()->categories()->find($id);
        if ($category) {
            $this->editingCategoryId = $id;
            $this->editingCategoryName = $category->name;
        }
    }

    public function updateCategory()
    {
        $this->validate([
            'editingCategoryName' => 'required|string|max:255',
        ]);

        auth()->user()->categories()->find($this->editingCategoryId)?->update([
            'name' => $this->editingCategoryName,
        ]);

        $this->editingCategoryId = null;
        session()->flash('category_message', 'Category updated successfully.');
    }

    public function cancelEditCategory()
    {
        $this->editingCategoryId = null;
    }

    public function deleteProject($id)
    {
        auth()->user()->projects()->find($id)?->delete();
    }

    public function deleteCategory($id)
    {
        auth()->user()->categories()->find($id)?->delete();
    }
};
?>

<div class="flex h-full w-full flex-col gap-6 p-3 sm:p-4 text-neutral-900 dark:text-neutral-100 max-w-6xl mx-auto mt-2 sm:mt-4 pb-16" x-data="{ mounted: false }" x-init="setTimeout(() => mounted = true, 50)">
    <!-- Header -->
    <div class="border-b border-zinc-200/80 dark:border-zinc-800/80 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 transition-all duration-700 ease-out"
         :class="mounted ? 'translate-y-0 opacity-100' : '-translate-y-4 opacity-0'">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                <div class="size-8 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                    <flux:icon name="cog-8-tooth" class="size-4.5 text-zinc-700 dark:text-zinc-300" />
                </div>
                <span>Workspace Management</span>
            </h2>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">Organize and manage your projects, clients, and categories for tracking.</p>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-auto">
            <span class="text-[11px] font-mono font-semibold px-3 py-1 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700/60">
                {{ $this->projects->count() }} Projects &bull; {{ $this->categories->count() }} Categories
            </span>
        </div>
    </div>

    <!-- Grid Container (2-Column Command Center) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 transition-all duration-700 ease-out delay-100"
         :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0'">
        
        <!-- Projects Section -->
        <div class="flex flex-col gap-4">
            <!-- Section Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="size-7 rounded-lg bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                        <flux:icon name="briefcase" class="size-4 text-zinc-700 dark:text-zinc-300" />
                    </div>
                    <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-100">Projects &amp; Clients</h3>
                    <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-[10px] font-mono font-medium px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60">
                        {{ $this->projects->count() }} Total
                    </span>
                </div>
            </div>

            <!-- Add Project Card (Modern Glassmorphism) -->
            <div class="border border-zinc-200/80 dark:border-zinc-800 rounded-2xl bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl p-4.5 shadow-xs relative overflow-hidden group hover:border-zinc-400 dark:hover:border-zinc-700 transition-all">

                <form wire:submit.prevent="addProject" class="space-y-3 relative z-10">
                    <div class="relative w-full">
                        <input type="text" wire:model="projectName" placeholder="Project Name" required autocomplete="off"
                               class="w-full h-10 pl-9 pr-3 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-zinc-600 focus:ring-2 focus:ring-zinc-600/20 shadow-2xs transition-all">
                        <flux:icon name="briefcase" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-400 pointer-events-none" />
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center">
                        <div class="flex-1 w-full">
                            <div class="relative w-full">
                                <input type="text" wire:model="projectClient" placeholder="Client Name (Optional)" autocomplete="off"
                                       class="w-full h-10 pl-9 pr-3 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-zinc-600 focus:ring-2 focus:ring-zinc-600/20 shadow-2xs transition-all">
                                <flux:icon name="user" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-400 pointer-events-none" />
                            </div>
                        </div>
                        <button type="submit" class="w-full sm:w-auto cursor-pointer bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl px-4 py-2.5 text-xs border border-indigo-500/80 active:scale-95 transition-all shadow-xs shadow-indigo-500/20 flex items-center justify-center gap-1.5 shrink-0">
                            <flux:icon name="plus" class="size-3.5 text-white" />
                            <span>Add Project</span>
                        </button>
                    </div>
                </form>

                @if(session()->has('project_message'))
                    <div class="mt-3 text-xs font-semibold text-emerald-600 dark:text-emerald-500 flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-500/10 p-2 rounded-lg border border-emerald-200 dark:border-emerald-500/20">
                        <flux:icon name="check-circle" class="size-4" />
                        <span>{{ session('project_message') }}</span>
                    </div>
                @endif
            </div>

            <!-- Projects Grouped List Card -->
            <div class="bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800 rounded-2xl overflow-hidden shadow-xs divide-y divide-zinc-200/50 dark:divide-zinc-800/50">
                @forelse($this->projects as $project)
                    <div wire:key="project-{{ $project->id }}" class="p-3.5 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors group relative">
                        @if($this->editingProjectId === $project->id)
                            <form wire:submit.prevent="updateProject" class="space-y-2.5 p-1">
                                <div class="relative w-full">
                                    <input type="text" wire:model="editingProjectName" placeholder="Project Name" required autocomplete="off"
                                           class="w-full h-9 pl-8 pr-3 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-zinc-600">
                                    <flux:icon name="briefcase" class="absolute left-2.5 top-1/2 -translate-y-1/2 size-3.5 text-zinc-400 pointer-events-none" />
                                </div>
                                <div class="relative w-full">
                                    <input type="text" wire:model="editingProjectClient" placeholder="Client Name (Optional)" autocomplete="off"
                                           class="w-full h-9 pl-8 pr-3 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-zinc-600">
                                    <flux:icon name="user" class="absolute left-2.5 top-1/2 -translate-y-1/2 size-3.5 text-zinc-400 pointer-events-none" />
                                </div>
                                <div class="flex gap-2 justify-end mt-1">
                                    <flux:button variant="ghost" wire:click="cancelEditProject" size="xs">Cancel</flux:button>
                                    <flux:button type="submit" size="xs" class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold border border-emerald-500 cursor-pointer px-3">Save</flux:button>
                                </div>
                            </form>
                        @else
                            <div class="flex justify-between items-center gap-3">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <div class="size-9 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0 group-hover:scale-105 transition-transform">
                                        <flux:icon name="folder" class="size-4 text-zinc-700 dark:text-zinc-300" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-sm text-zinc-900 dark:text-zinc-100 truncate group-hover:text-zinc-700 dark:group-hover:text-zinc-300 transition-colors">{{ $project->name }}</div>
                                        @if($project->client_name)
                                            <div class="text-[10px] font-mono font-medium text-zinc-700 dark:text-zinc-300 mt-1 truncate flex items-center gap-1 inline-flex bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60">
                                                <flux:icon name="user" class="size-3 shrink-0 text-zinc-500 dark:text-zinc-400" />
                                                <span>{{ $project->client_name }}</span>
                                            </div>
                                        @else
                                            <div class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-0.5">Internal Project</div>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-150 flex items-center gap-1 shrink-0">
                                    <flux:button wire:click="editProject({{ $project->id }})" variant="ghost" size="xs" icon="pencil" square class="cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800/60 text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200 active:scale-95" />
                                    <flux:modal.trigger name="delete-project-{{ $project->id }}">
                                        <flux:button variant="ghost" size="xs" icon="trash" square class="text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/20 cursor-pointer active:scale-95" />
                                    </flux:modal.trigger>
                                </div>
                            </div>
                        @endif

                        <flux:modal name="delete-project-{{ $project->id }}" class="w-[calc(100vw-2rem)] max-w-md backdrop:backdrop-blur-md z-[200]">
                            <div class="space-y-5">
                                <div class="flex items-center gap-3">
                                    <div class="size-10 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 flex items-center justify-center text-red-600 dark:text-red-500 shrink-0">
                                        <flux:icon name="trash" class="size-5" />
                                    </div>
                                    <div>
                                        <flux:heading size="lg" class="font-bold">Delete Project?</flux:heading>
                                        <flux:text class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                            Are you sure you want to delete <strong>{{ $project->name }}</strong>? All linked activity logs will be removed.
                                        </flux:text>
                                    </div>
                                </div>
                                <div class="flex justify-end gap-2 pt-2">
                                    <flux:modal.close>
                                        <flux:button variant="ghost" size="sm">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:modal.close>
                                        <flux:button variant="danger" size="sm" wire:click="deleteProject({{ $project->id }})">Delete</flux:button>
                                    </flux:modal.close>
                                </div>
                            </div>
                        </flux:modal>
                    </div>
                @empty
                    <div class="text-xs text-neutral-400 text-center py-10 flex flex-col items-center gap-2 bg-zinc-50/50 dark:bg-zinc-950/20">
                        <div class="size-10 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-500 dark:text-zinc-400">
                            <flux:icon name="folder" class="size-5" />
                        </div>
                        <span class="font-semibold text-zinc-700 dark:text-zinc-300">No projects created yet.</span>
                        <span class="text-[11px] text-zinc-500 dark:text-zinc-400">Add a new project using the form above.</span>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Categories Section -->
        <div class="flex flex-col gap-4">
            <!-- Section Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="size-7 rounded-lg bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                        <flux:icon name="tag" class="size-4 text-zinc-700 dark:text-zinc-300" />
                    </div>
                    <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-100">Categories</h3>
                    <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-[10px] font-mono font-medium px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60">
                        {{ $this->categories->count() }} Total
                    </span>
                </div>
            </div>

            <!-- Add Category Card (Modern Glassmorphism) -->
            <div class="border border-zinc-200/80 dark:border-zinc-800 rounded-2xl bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl p-4.5 shadow-xs relative overflow-hidden group hover:border-zinc-400 dark:hover:border-zinc-700 transition-all">

                <form wire:submit.prevent="addCategory" class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center relative z-10">
                    <div class="flex-1 w-full">
                        <div class="relative w-full">
                            <input type="text" wire:model="categoryName" placeholder="Category Name" required autocomplete="off"
                                   class="w-full h-10 pl-9 pr-3 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-zinc-600 focus:ring-2 focus:ring-zinc-600/20 shadow-2xs transition-all">
                            <flux:icon name="tag" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-400 pointer-events-none" />
                        </div>
                    </div>
                    <button type="submit" class="w-full sm:w-auto cursor-pointer bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl px-4 py-2.5 text-xs border border-indigo-500/80 active:scale-95 transition-all shadow-xs shadow-indigo-500/20 flex items-center justify-center gap-1.5 shrink-0">
                        <flux:icon name="plus" class="size-3.5 text-white" />
                        <span>Add Category</span>
                    </button>
                </form>

                @if(session()->has('category_message'))
                    <div class="mt-3 text-xs font-semibold text-emerald-600 dark:text-emerald-500 flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-500/10 p-2 rounded-lg border border-emerald-200 dark:border-emerald-500/20">
                        <flux:icon name="check-circle" class="size-4" />
                        <span>{{ session('category_message') }}</span>
                    </div>
                @endif
            </div>

            <!-- Categories Grouped List Card -->
            <div class="bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800 rounded-2xl overflow-hidden shadow-xs divide-y divide-zinc-200/50 dark:divide-zinc-800/50">
                @forelse($this->categories as $category)
                    <div wire:key="category-{{ $category->id }}" class="p-3.5 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors group relative">
                        @if($this->editingCategoryId === $category->id)
                            <form wire:submit.prevent="updateCategory" class="space-y-2.5 p-1">
                                <div class="relative w-full">
                                    <input type="text" wire:model="editingCategoryName" placeholder="Category Name" required autocomplete="off"
                                           class="w-full h-9 pl-8 pr-3 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-zinc-600">
                                    <flux:icon name="tag" class="absolute left-2.5 top-1/2 -translate-y-1/2 size-3.5 text-zinc-400 pointer-events-none" />
                                </div>
                                <div class="flex gap-2 justify-end mt-1">
                                    <flux:button variant="ghost" wire:click="cancelEditCategory" size="xs">Cancel</flux:button>
                                    <flux:button type="submit" size="xs" class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold border border-emerald-500 cursor-pointer px-3">Save</flux:button>
                                </div>
                            </form>
                        @else
                            <div class="flex justify-between items-center gap-3">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <div class="size-9 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0 group-hover:scale-105 transition-transform">
                                        <flux:icon name="tag" class="size-4 text-zinc-700 dark:text-zinc-300" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-sm text-zinc-900 dark:text-zinc-100 truncate group-hover:text-zinc-700 dark:group-hover:text-zinc-300 transition-colors">{{ $category->name }}</div>
                                        <div class="text-[10px] font-mono font-medium text-zinc-600 dark:text-zinc-400 mt-1 inline-flex bg-zinc-100 dark:bg-zinc-800/60 px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-800">
                                            Category Tag
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-150 flex items-center gap-1 shrink-0">
                                    <flux:button wire:click="editCategory({{ $category->id }})" variant="ghost" size="xs" icon="pencil" square class="cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800/60 text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200 active:scale-95" />
                                    <flux:modal.trigger name="delete-category-{{ $category->id }}">
                                        <flux:button variant="ghost" size="xs" icon="trash" square class="text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/20 cursor-pointer active:scale-95" />
                                    </flux:modal.trigger>
                                </div>
                            </div>
                        @endif

                        <flux:modal name="delete-category-{{ $category->id }}" class="w-[calc(100vw-2rem)] max-w-md backdrop:backdrop-blur-md z-[200]">
                            <div class="space-y-5">
                                <div class="flex items-center gap-3">
                                    <div class="size-10 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 flex items-center justify-center text-red-600 dark:text-red-500 shrink-0">
                                        <flux:icon name="trash" class="size-5" />
                                    </div>
                                    <div>
                                        <flux:heading size="lg" class="font-bold">Delete Category?</flux:heading>
                                        <flux:text class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                            Are you sure you want to delete <strong>{{ $category->name }}</strong>? All linked activity logs will be removed.
                                        </flux:text>
                                    </div>
                                </div>
                                <div class="flex justify-end gap-2 pt-2">
                                    <flux:modal.close>
                                        <flux:button variant="ghost" size="sm">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:modal.close>
                                        <flux:button variant="danger" size="sm" wire:click="deleteCategory({{ $category->id }})">Delete</flux:button>
                                    </flux:modal.close>
                                </div>
                            </div>
                        </flux:modal>
                    </div>
                @empty
                    <div class="text-xs text-neutral-400 text-center py-10 flex flex-col items-center gap-2 bg-zinc-50/50 dark:bg-zinc-950/20">
                        <div class="size-10 rounded-xl bg-purple-50 dark:bg-purple-500/10 border border-purple-200 dark:border-purple-500/20 flex items-center justify-center text-purple-600 dark:text-purple-500">
                            <flux:icon name="tag" class="size-5" />
                        </div>
                        <span class="font-semibold text-zinc-700 dark:text-zinc-300">No categories created yet.</span>
                        <span class="text-[11px] text-zinc-500 dark:text-zinc-400">Add a new category using the form above.</span>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>

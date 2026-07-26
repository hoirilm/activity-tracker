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

<div class="flex h-full w-full flex-col gap-6 p-4 text-neutral-900 dark:text-neutral-100 max-w-5xl mx-auto mt-4" x-data="{ mounted: false }" x-init="setTimeout(() => mounted = true, 50)">
    <!-- Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-800 pb-4 transition-all duration-700 ease-out"
         :class="mounted ? 'translate-y-0 opacity-100' : '-translate-y-4 opacity-0'">
        <h2 class="text-xl font-semibold tracking-tight">Workspace Management</h2>
        <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Organize and manage your projects, clients, and categories for tracking.</p>
    </div>

    <!-- Grid Container -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 transition-all duration-700 ease-out delay-100"
         :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0'">
        
        <!-- Projects Section -->
        <div class="flex flex-col gap-4">
            <!-- Section Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <flux:icon name="briefcase" class="size-5 text-zinc-500" />
                    <h3 class="font-semibold text-zinc-850 dark:text-zinc-150">Projects</h3>
                    <span class="bg-zinc-100 text-zinc-650 dark:bg-zinc-805 dark:text-zinc-400 text-[10px] font-bold px-2 py-0.5 rounded-full">
                        {{ $this->projects->count() }}
                    </span>
                </div>
            </div>

            <!-- Add Project Card -->
            <div class="border border-zinc-200 dark:border-zinc-800 rounded-2xl bg-zinc-50 dark:bg-zinc-900 p-5 shadow-xs hover:shadow-md transition-shadow duration-300">
                <form wire:submit.prevent="addProject" class="space-y-3">
                    <flux:input wire:model="projectName" placeholder="Project Name" icon="briefcase" required size="sm" autocomplete="off" />
                    <div class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center">
                        <div class="flex-1 w-full">
                            <flux:input wire:model="projectClient" placeholder="Client Name (Optional)" icon="user" size="sm" autocomplete="off" />
                        </div>
                        <flux:button variant="primary" type="submit" size="sm" class="w-full sm:w-auto cursor-pointer bg-indigo-600 hover:bg-indigo-700 text-white dark:bg-indigo-500 dark:hover:bg-indigo-600 border-none px-4 active:scale-95 transition-all duration-200">
                            Add
                        </flux:button>
                    </div>
                </form>

                @if(session()->has('project_message'))
                    <div class="mt-3 text-xs font-medium text-emerald-600 dark:text-emerald-450 flex items-center gap-1.5">
                        <flux:icon name="check-circle" class="size-4" />
                        <span>{{ session('project_message') }}</span>
                    </div>
                @endif
            </div>

            <!-- Projects List -->
            <div class="space-y-3">
                @forelse($this->projects as $project)
                    <div wire:key="project-{{ $project->id }}" class="group relative overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 p-4 shadow-xs hover:shadow-md hover:border-zinc-300 dark:hover:border-zinc-700 hover:-translate-y-0.5 transition-all duration-300" wire:transition.slide.up>
                        @if($this->editingProjectId === $project->id)
                            <form wire:submit.prevent="updateProject" class="space-y-3">
                                <flux:input wire:model="editingProjectName" placeholder="Project Name" required size="sm" autocomplete="off" />
                                <flux:input wire:model="editingProjectClient" placeholder="Client Name (Optional)" size="sm" autocomplete="off" />
                                <div class="flex gap-2 justify-end mt-1">
                                    <flux:button variant="ghost" wire:click="cancelEditProject" size="xs">Cancel</flux:button>
                                    <flux:button variant="primary" type="submit" size="xs" class="bg-indigo-600 text-white hover:bg-indigo-700 border-none cursor-pointer">Save</flux:button>
                                </div>
                            </form>
                        @else
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <div class="size-9 rounded-lg bg-zinc-50 dark:bg-zinc-950 border border-zinc-200/50 dark:border-zinc-800/40 flex items-center justify-center text-zinc-500 dark:text-zinc-400 shrink-0">
                                        <flux:icon name="folder" class="size-4.5" />
                                    </div>
                                    <div class="truncate">
                                        <div class="font-medium text-sm text-zinc-850 dark:text-zinc-150 truncate">{{ $project->name }}</div>
                                        @if($project->client_name)
                                            <div class="text-[11px] text-zinc-400 dark:text-zinc-505 mt-0.5 truncate flex items-center gap-1">
                                                <flux:icon name="user" class="size-3 shrink-0" />
                                                <span>{{ $project->client_name }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Hover Actions -->
                                <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-150 flex items-center gap-1 shrink-0">
                                    <flux:button wire:click="editProject({{ $project->id }})" variant="ghost" size="xs" icon="pencil" square class="cursor-pointer" />
                                    <flux:modal.trigger name="delete-project-{{ $project->id }}">
                                        <flux:button variant="ghost" size="xs" icon="trash" square class="text-red-500 hover:text-red-650 dark:hover:text-red-405 cursor-pointer" />
                                    </flux:modal.trigger>
                                </div>
                            </div>
                        @endif

                        <!-- Delete Project Modal -->
                        <flux:modal name="delete-project-{{ $project->id }}" class="min-w-[22rem] backdrop:backdrop-blur-sm z-[200]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Project?</flux:heading>
                                    <flux:text class="mt-2 text-xs">
                                        Are you sure you want to delete <strong>{{ $project->name }}</strong>? <br>
                                        All activities linked to this project will also be deleted. This action cannot be undone.
                                    </flux:text>
                                </div>
                                <div class="flex justify-end gap-2">
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:modal.close>
                                        <flux:button variant="danger" wire:click="deleteProject({{ $project->id }})">Delete</flux:button>
                                    </flux:modal.close>
                                </div>
                            </div>
                        </flux:modal>
                    </div>
                @empty
                    <div class="text-xs text-neutral-400 text-center py-10 border border-dashed border-neutral-200 dark:border-neutral-800 rounded-xl flex flex-col items-center gap-2 bg-zinc-50 dark:bg-zinc-900/50">
                        <flux:icon name="folder" class="size-8 text-neutral-350 dark:text-neutral-600" />
                        <span>No projects found. Create one above.</span>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Categories Section -->
        <div class="flex flex-col gap-4">
            <!-- Section Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <flux:icon name="tag" class="size-5 text-zinc-500" />
                    <h3 class="font-semibold text-zinc-850 dark:text-zinc-150">Categories</h3>
                    <span class="bg-zinc-100 text-zinc-650 dark:bg-zinc-805 dark:text-zinc-400 text-[10px] font-bold px-2 py-0.5 rounded-full">
                        {{ $this->categories->count() }}
                    </span>
                </div>
            </div>

            <!-- Add Category Card -->
            <div class="border border-zinc-200 dark:border-zinc-800 rounded-2xl bg-zinc-50 dark:bg-zinc-900 p-5 shadow-xs hover:shadow-md transition-shadow duration-300">
                <form wire:submit.prevent="addCategory" class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center">
                    <div class="flex-1 w-full">
                        <flux:input wire:model="categoryName" placeholder="Category Name" icon="tag" required size="sm" autocomplete="off" />
                    </div>
                    <flux:button variant="primary" type="submit" size="sm" class="w-full sm:w-auto cursor-pointer bg-indigo-600 hover:bg-indigo-700 text-white dark:bg-indigo-500 dark:hover:bg-indigo-600 border-none px-4 active:scale-95 transition-all duration-200">
                        Add
                    </flux:button>
                </form>

                @if(session()->has('category_message'))
                    <div class="mt-3 text-xs font-medium text-emerald-600 dark:text-emerald-450 flex items-center gap-1.5">
                        <flux:icon name="check-circle" class="size-4" />
                        <span>{{ session('category_message') }}</span>
                    </div>
                @endif
            </div>

            <!-- Categories List -->
            <div class="space-y-3">
                @forelse($this->categories as $category)
                    <div wire:key="category-{{ $category->id }}" class="group relative overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 p-4 shadow-xs hover:shadow-md hover:border-zinc-300 dark:hover:border-zinc-700 hover:-translate-y-0.5 transition-all duration-300" wire:transition.slide.up>
                        @if($this->editingCategoryId === $category->id)
                            <form wire:submit.prevent="updateCategory" class="space-y-3">
                                <flux:input wire:model="editingCategoryName" placeholder="Category Name" required size="sm" autocomplete="off" />
                                <div class="flex gap-2 justify-end mt-1">
                                    <flux:button variant="ghost" wire:click="cancelEditCategory" size="xs">Cancel</flux:button>
                                    <flux:button variant="primary" type="submit" size="xs" class="bg-indigo-600 text-white hover:bg-indigo-700 border-none cursor-pointer">Save</flux:button>
                                </div>
                            </form>
                        @else
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <div class="size-9 rounded-lg bg-zinc-50 dark:bg-zinc-950 border border-zinc-200/50 dark:border-zinc-800/40 flex items-center justify-center text-zinc-500 dark:text-zinc-400 shrink-0">
                                        <flux:icon name="tag" class="size-4.5" />
                                    </div>
                                    <div class="truncate">
                                        <div class="font-medium text-sm text-zinc-850 dark:text-zinc-150 truncate">{{ $category->name }}</div>
                                    </div>
                                </div>
                                
                                <!-- Hover Actions -->
                                <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-150 flex items-center gap-1 shrink-0">
                                    <flux:button wire:click="editCategory({{ $category->id }})" variant="ghost" size="xs" icon="pencil" square class="cursor-pointer" />
                                    <flux:modal.trigger name="delete-category-{{ $category->id }}">
                                        <flux:button variant="ghost" size="xs" icon="trash" square class="text-red-500 hover:text-red-650 dark:hover:text-red-405 cursor-pointer" />
                                    </flux:modal.trigger>
                                </div>
                            </div>
                        @endif

                        <!-- Delete Category Modal -->
                        <flux:modal name="delete-category-{{ $category->id }}" class="min-w-[22rem] backdrop:backdrop-blur-sm z-[200]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Category?</flux:heading>
                                    <flux:text class="mt-2 text-xs">
                                        Are you sure you want to delete <strong>{{ $category->name }}</strong>? <br>
                                        All activities linked to this category will also be deleted. This action cannot be undone.
                                    </flux:text>
                                </div>
                                <div class="flex justify-end gap-2">
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:modal.close>
                                        <flux:button variant="danger" wire:click="deleteCategory({{ $category->id }})">Delete</flux:button>
                                    </flux:modal.close>
                                </div>
                            </div>
                        </flux:modal>
                    </div>
                @empty
                    <div class="text-xs text-neutral-400 text-center py-10 border border-dashed border-neutral-200 dark:border-neutral-800 rounded-xl flex flex-col items-center gap-2 bg-zinc-50 dark:bg-zinc-900/50">
                        <flux:icon name="tag" class="size-8 text-neutral-350 dark:text-neutral-600" />
                        <span>No categories found. Create one above.</span>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>

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
        return Project::all();
    }

    public function getCategoriesProperty()
    {
        return Category::all();
    }

    public function addProject()
    {
        $this->validate([
            'projectName' => 'required|string|max:255',
            'projectClient' => 'nullable|string|max:255',
        ]);

        Project::create([
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

        Category::create([
            'name' => $this->categoryName,
        ]);

        $this->reset('categoryName');
        session()->flash('category_message', 'Category added successfully.');
    }

    public function editProject($id)
    {
        $project = Project::find($id);
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

        Project::find($this->editingProjectId)?->update([
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
        $category = Category::find($id);
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

        Category::find($this->editingCategoryId)?->update([
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
        Project::find($id)?->delete();
    }

    public function deleteCategory($id)
    {
        Category::find($id)?->delete();
    }
};
?>

<div class="flex h-full w-full flex-col gap-8 md:flex-row p-4 text-neutral-900 dark:text-neutral-100 max-w-5xl mx-auto mt-4">
    
    <!-- Projects Section -->
    <div class="flex-1 flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold tracking-tight">Projects</h2>
        </div>
        
        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900 p-5 shadow-sm">
            <form wire:submit.prevent="addProject" class="flex flex-col gap-3">
                <flux:input wire:model="projectName" placeholder="Project Name" required />
                <div class="flex gap-2 items-start">
                    <div class="flex-1">
                        <flux:input wire:model="projectClient" placeholder="Client (Optional)" />
                    </div>
                    <flux:button variant="primary" type="submit">Add</flux:button>
                </div>
            </form>

            @if(session()->has('project_message'))
                <div class="mt-3 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                    {{ session('project_message') }}
                </div>
            @endif
        </div>

        <div class="flex flex-col gap-3 mt-2">
            @forelse($this->projects as $project)
                <div class="group relative overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900 p-4 shadow-sm hover:border-neutral-300 dark:hover:border-neutral-600 transition-all">
                    @if($this->editingProjectId === $project->id)
                        <form wire:submit.prevent="updateProject" class="flex flex-col gap-3">
                            <flux:input wire:model="editingProjectName" placeholder="Project Name" required />
                            <flux:input wire:model="editingProjectClient" placeholder="Client (Optional)" />
                            <div class="flex gap-2 justify-end mt-1">
                                <flux:button variant="subtle" wire:click="cancelEditProject" size="sm">Cancel</flux:button>
                                <flux:button variant="primary" type="submit" size="sm">Save</flux:button>
                            </div>
                        </form>
                    @else
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="font-medium text-base">{{ $project->name }}</div>
                                @if($project->client_name)
                                    <div class="text-sm text-neutral-500 mt-0.5">{{ $project->client_name }}</div>
                                @endif
                            </div>
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-2 bg-white dark:bg-neutral-900 pl-2">
                                <button wire:click="editProject({{ $project->id }})" class="text-sm font-medium text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-100 transition-colors">Edit</button>
                                <flux:modal.trigger name="delete-project-{{ $project->id }}">
                                    <button class="text-sm font-medium text-red-500 hover:text-red-600 transition-colors">Delete</button>
                                </flux:modal.trigger>
                            </div>
                        </div>
                    @endif

                    <flux:modal name="delete-project-{{ $project->id }}" class="min-w-[22rem] backdrop:backdrop-blur-sm">
                        <div class="space-y-6">
                            <div>
                                <flux:heading size="lg">Delete Project?</flux:heading>
                                <flux:text class="mt-2">
                                    Are you sure you want to delete <strong>{{ $project->name }}</strong>? <br>
                                    All activities linked to this project will also be deleted.
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
                <div class="text-sm text-neutral-400 text-center py-8 border border-dashed border-neutral-200 dark:border-neutral-700 rounded-xl">No projects found.</div>
            @endforelse
        </div>
    </div>

    <!-- Categories Section -->
    <div class="flex-1 flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold tracking-tight">Categories</h2>
        </div>
        
        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900 p-5 shadow-sm">
            <form wire:submit.prevent="addCategory" class="flex gap-2 items-start">
                <div class="flex-1">
                    <flux:input wire:model="categoryName" placeholder="Category Name" required />
                </div>
                <flux:button variant="primary" type="submit">Add</flux:button>
            </form>

            @if(session()->has('category_message'))
                <div class="mt-3 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                    {{ session('category_message') }}
                </div>
            @endif
        </div>

        <div class="flex flex-col gap-3 mt-2">
            @forelse($this->categories as $category)
                <div class="group relative overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900 p-4 shadow-sm hover:border-neutral-300 dark:hover:border-neutral-600 transition-all">
                    @if($this->editingCategoryId === $category->id)
                        <form wire:submit.prevent="updateCategory" class="flex flex-col gap-3">
                            <flux:input wire:model="editingCategoryName" placeholder="Category Name" required />
                            <div class="flex gap-2 justify-end mt-1">
                                <flux:button variant="subtle" wire:click="cancelEditCategory" size="sm">Cancel</flux:button>
                                <flux:button variant="primary" type="submit" size="sm">Save</flux:button>
                            </div>
                        </form>
                    @else
                        <div class="flex justify-between items-center">
                            <div class="font-medium text-base">{{ $category->name }}</div>
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-2">
                                <button wire:click="editCategory({{ $category->id }})" class="text-sm font-medium text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-100 transition-colors">Edit</button>
                                <flux:modal.trigger name="delete-category-{{ $category->id }}">
                                    <button class="text-sm font-medium text-red-500 hover:text-red-600 transition-colors">Delete</button>
                                </flux:modal.trigger>
                            </div>
                        </div>
                    @endif

                    <flux:modal name="delete-category-{{ $category->id }}" class="min-w-[22rem] backdrop:backdrop-blur-sm">
                        <div class="space-y-6">
                            <div>
                                <flux:heading size="lg">Delete Category?</flux:heading>
                                <flux:text class="mt-2">
                                    Are you sure you want to delete <strong>{{ $category->name }}</strong>? <br>
                                    All activities linked to this category will also be deleted.
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
                <div class="text-sm text-neutral-400 text-center py-8 border border-dashed border-neutral-200 dark:border-neutral-700 rounded-xl">No categories found.</div>
            @endforelse
        </div>
    </div>

</div>

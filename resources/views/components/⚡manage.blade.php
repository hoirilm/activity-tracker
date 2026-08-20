<?php

use Livewire\Component;
use App\Models\Project;
use App\Models\Category;
use App\Models\Task;
use App\Models\Label;
use Illuminate\Support\Str;
use League\CommonMark\GithubFlavoredMarkdownConverter;

new class extends Component
{
    public string $activeTab = 'tasks';

    // Task Form Properties
    public string $taskTitle = '';
    public string $taskDescription = '';
    public ?int $taskProjectId = null;
    public string $taskStatus = Task::STATUS_NEW;
    public ?string $taskDueAt = null;
    public array $taskLabelIds = [];

    // Task Editing Properties
    public ?int $editingTaskId = null;
    public string $editingTaskTitle = '';
    public string $editingTaskDescription = '';
    public ?int $editingTaskProjectId = null;
    public string $editingTaskStatus = Task::STATUS_NEW;
    public ?string $editingTaskDueAt = null;
    public array $editingTaskLabelIds = [];

    // Task Filters
    public string $filterProject = 'all'; // 'all', 'non_project', or project ID
    public string $filterStatus = 'all';  // 'all', 'new', 'on_progress', 'done', 'on_hold', 'archived'
    public string $filterDeadline = 'all';// 'all', 'overdue', 'due_today', 'due_this_week', 'no_deadline'
    public string $searchTask = '';
    public string $archiveSearchQuery = '';
    public string $viewMode = 'kanban';    // 'kanban' or 'list'
    public bool $showArchived = false;

    // Task Viewing Properties
    public ?int $viewingTaskId = null;

    // Project Form & Editing Properties
    public string $projectName = '';
    public string $projectClient = '';
    public ?int $editingProjectId = null;
    public string $editingProjectName = '';
    public string $editingProjectClient = '';

    // Category Form & Editing Properties
    public string $categoryName = '';
    public ?int $editingCategoryId = null;
    public string $editingCategoryName = '';

    // Label Form Properties
    public string $labelName = '';
    public string $labelColor = 'amber';

    // Checklist Properties
    public array $newTaskChecklists = [];
    public string $newTaskChecklistInput = '';
    public string $newDetailChecklistInput = '';
    public bool $hideCheckedItems = false;

    public function addChecklistItemToNewTask()
    {
        $title = trim($this->newTaskChecklistInput);
        if ($title !== '') {
            $this->newTaskChecklists[] = $title;
            $this->newTaskChecklistInput = '';
        }
    }

    public function removeNewTaskChecklistItem(int $index)
    {
        if (isset($this->newTaskChecklists[$index])) {
            array_splice($this->newTaskChecklists, $index, 1);
        }
    }

    public function addChecklistItemToDetail()
    {
        $title = trim($this->newDetailChecklistInput);
        if ($title !== '' && $this->viewingTaskId) {
            $task = auth()->user()->tasks()->find($this->viewingTaskId);
            if ($task) {
                $task->checklists()->create([
                    'title' => $title,
                    'position' => $task->checklists()->count(),
                ]);
                $this->newDetailChecklistInput = '';
            }
        }
    }

    public function toggleChecklistItem(int $checklistId)
    {
        $checklist = \App\Models\TaskChecklist::whereHas('task', function ($q) {
            $q->where('user_id', auth()->id());
        })->find($checklistId);

        if ($checklist) {
            $checklist->update([
                'is_completed' => ! $checklist->is_completed,
            ]);
        }
    }

    public function deleteChecklistItem(int $checklistId)
    {
        $checklist = \App\Models\TaskChecklist::whereHas('task', function ($q) {
            $q->where('user_id', auth()->id());
        })->find($checklistId);

        if ($checklist) {
            $checklist->delete();
        }
    }

    public function reorderNewTaskChecklists(int $fromIndex, int $toIndex)
    {
        if (! isset($this->newTaskChecklists[$fromIndex]) || ! isset($this->newTaskChecklists[$toIndex])) {
            return;
        }
        $item = array_splice($this->newTaskChecklists, $fromIndex, 1);
        array_splice($this->newTaskChecklists, $toIndex, 0, $item);
    }

    public function reorderDetailChecklistItems(int $fromId, int $toId)
    {
        if (! $this->viewingTaskId) return;

        $items = \App\Models\TaskChecklist::where('task_id', $this->viewingTaskId)->orderBy('position')->orderBy('id')->get();
        
        $fromIndex = $items->search(fn($item) => $item->id == $fromId);
        $toIndex = $items->search(fn($item) => $item->id == $toId);

        if ($fromIndex === false || $toIndex === false || $fromIndex === $toIndex) return;

        $movedItem = $items->splice($fromIndex, 1)->first();
        $items->splice($toIndex, 0, [$movedItem]);

        foreach ($items as $pos => $item) {
            $item->update(['position' => $pos]);
        }
    }

    public function saveChecklistOrder(array $orderedIds)
    {
        if (! $this->viewingTaskId) return;

        foreach ($orderedIds as $position => $id) {
            \App\Models\TaskChecklist::where('id', $id)
                ->where('task_id', $this->viewingTaskId)
                ->update(['position' => $position]);
        }
    }

    public function mount()
    {
        if (request()->query('tab')) {
            $tab = request()->query('tab');
            if (in_array($tab, ['tasks', 'projects', 'categories'])) {
                $this->activeTab = $tab;
            }
        }
    }

    public function getProjectsProperty()
    {
        return auth()->user()->projects()->withCount('tasks')->get();
    }

    public function getCategoriesProperty()
    {
        return auth()->user()->categories()->get();
    }

    public function getLabelsProperty()
    {
        return auth()->user()->labels()->withCount('tasks')->get();
    }

    public function getArchivedCountProperty()
    {
        return auth()->user()->tasks()->where('status', Task::STATUS_ARCHIVED)->count();
    }

    public function getArchivedTasksProperty()
    {
        $query = auth()->user()->tasks()->with(['project', 'labels', 'checklists'])->where('status', Task::STATUS_ARCHIVED)->latest();

        if ($this->filterProject === 'non_project') {
            $query->whereNull('project_id');
        } elseif ($this->filterProject !== 'all' && is_numeric($this->filterProject)) {
            $query->where('project_id', $this->filterProject);
        }

        if (!empty(trim($this->archiveSearchQuery))) {
            $term = '%' . mb_strtolower(trim($this->archiveSearchQuery)) . '%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(title) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(description) LIKE ?', [$term])
                  ->orWhereHas('labels', function ($lq) use ($term) {
                      $lq->whereRaw('LOWER(name) LIKE ?', [$term]);
                  })
                  ->orWhereHas('project', function ($pq) use ($term) {
                      $pq->whereRaw('LOWER(name) LIKE ?', [$term]);
                  });
            });
        }

        return $query->get();
    }

    public function getTasksQueryProperty()
    {
        $query = auth()->user()->tasks()->with(['project', 'labels', 'checklists'])->latest();

        if ($this->filterProject === 'non_project') {
            $query->whereNull('project_id');
        } elseif ($this->filterProject !== 'all' && is_numeric($this->filterProject)) {
            $query->where('project_id', $this->filterProject);
        }

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        } elseif (!$this->showArchived) {
            $query->where('status', '!=', Task::STATUS_ARCHIVED);
        }

        if ($this->filterDeadline === 'overdue') {
            $query->whereNotNull('due_at')
                  ->where('due_at', '<', now())
                  ->where('status', '!=', Task::STATUS_DONE);
        } elseif ($this->filterDeadline === 'due_today') {
            $query->whereNotNull('due_at')
                  ->whereDate('due_at', \Carbon\Carbon::today())
                  ->where('status', '!=', Task::STATUS_DONE);
        } elseif ($this->filterDeadline === 'due_this_week') {
            $query->whereNotNull('due_at')
                  ->whereBetween('due_at', [\Carbon\Carbon::now()->startOfWeek(), \Carbon\Carbon::now()->endOfWeek()])
                  ->where('status', '!=', Task::STATUS_DONE);
        } elseif ($this->filterDeadline === 'no_deadline') {
            $query->whereNull('due_at');
        }

        if (!empty(trim($this->searchTask))) {
            $term = '%' . mb_strtolower(trim($this->searchTask)) . '%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(title) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(description) LIKE ?', [$term])
                  ->orWhereHas('labels', function ($lq) use ($term) {
                      $lq->whereRaw('LOWER(name) LIKE ?', [$term]);
                  })
                  ->orWhereHas('project', function ($pq) use ($term) {
                      $pq->whereRaw('LOWER(name) LIKE ?', [$term]);
                  });
            });
        }

        return $query;
    }

    public function getTasksProperty()
    {
        return $this->getTasksQueryProperty()->get();
    }

    public function setTaskDuePreset(string $preset, bool $isEditing = false)
    {
        $date = null;
        if ($preset === 'today') {
            $date = now()->endOfDay()->format('Y-m-d\TH:i');
        } elseif ($preset === 'tomorrow') {
            $date = now()->addDay()->endOfDay()->format('Y-m-d\TH:i');
        } elseif ($preset === 'next_week') {
            $date = now()->addWeek()->startOfDay()->format('Y-m-d\TH:i');
        }

        if ($isEditing) {
            $this->editingTaskDueAt = $date;
        } else {
            $this->taskDueAt = $date;
        }
    }

    public function getActiveDuePreset(?string $dueAt): ?string
    {
        if (! $dueAt) return null;
        try {
            $date = \Carbon\Carbon::parse($dueAt);
            if ($date->isToday()) return 'today';
            if ($date->isTomorrow()) return 'tomorrow';
            if ($date->isSameDay(now()->addWeek()->startOfDay())) return 'next_week';
        } catch (\Throwable $e) {}
        return null;
    }

    // --- Task Actions ---

    public function addTask()
    {
        $this->validate([
            'taskTitle' => 'required|string|max:255',
            'taskDescription' => 'nullable|string',
            'taskProjectId' => 'nullable|exists:projects,id',
            'taskStatus' => 'required|in:new,on_progress,done,on_hold,archived',
            'taskDueAt' => 'nullable|date',
            'taskLabelIds' => 'array',
            'taskLabelIds.*' => 'exists:labels,id',
        ]);

        // Ensure project belongs to auth user if provided
        $projectId = null;
        if ($this->taskProjectId) {
            $project = auth()->user()->projects()->find($this->taskProjectId);
            if ($project) {
                $projectId = $project->id;
            }
        }

        $task = auth()->user()->tasks()->create([
            'title' => $this->taskTitle,
            'description' => $this->taskDescription ?: null,
            'project_id' => $projectId,
            'status' => $this->taskStatus,
            'due_at' => $this->taskDueAt ?: null,
        ]);

        if (!empty($this->taskLabelIds)) {
            $userLabelIds = auth()->user()->labels()->whereIn('id', $this->taskLabelIds)->pluck('id')->toArray();
            $task->labels()->sync($userLabelIds);
        }

        if ($task->isOverdue()) {
            auth()->user()->notifications()->create([
                'title' => "⚠️ Task Overdue: {$task->title}",
                'body' => "Task '{$task->title}' was due on " . $task->due_at->format('d M Y H:i') . " and is currently overdue.",
                'type' => 'danger',
            ]);
        } elseif ($task->isDueToday()) {
            $timeStr = $task->due_at->format('H:i') !== '00:00' ? " at {$task->due_at->format('H:i')}" : '';
            auth()->user()->notifications()->create([
                'title' => "⏰ Task Due Today: {$task->title}",
                'body' => "Task '{$task->title}' is due today{$timeStr}. Don't forget to complete it!",
                'type' => 'warning',
            ]);
        }

        if ($task->status === Task::STATUS_DONE) {
            $this->dispatch('task-completed', title: $task->title);
            $this->js("window.dispatchEvent(new CustomEvent('task-completed', { detail: { title: " . json_encode($task->title) . " } }))");
        }

        if (!empty($this->newTaskChecklists)) {
            foreach ($this->newTaskChecklists as $index => $itemTitle) {
                $task->checklists()->create([
                    'title' => $itemTitle,
                    'position' => $index,
                ]);
            }
        }

        $this->reset(['taskTitle', 'taskDescription', 'taskProjectId', 'taskLabelIds', 'taskDueAt', 'newTaskChecklists', 'newTaskChecklistInput']);
        $this->taskStatus = Task::STATUS_NEW;

        $this->dispatch('close-modal', name: 'create-task-modal');
        $this->js("\$flux.modal('create-task-modal').close()");
        session()->flash('task_message', 'Task added successfully.');
    }

    public function updateTaskStatus(int $taskId, string $status)
    {
        if (!in_array($status, [Task::STATUS_NEW, Task::STATUS_ON_PROGRESS, Task::STATUS_DONE, Task::STATUS_ON_HOLD, Task::STATUS_ARCHIVED])) {
            return;
        }

        $task = auth()->user()->tasks()->find($taskId);
        if (!$task) {
            return;
        }

        $oldStatus = $task->status;

        $task->update([
            'status' => $status,
            'updated_at' => now(),
        ]);

        if ($status === Task::STATUS_DONE && $oldStatus !== Task::STATUS_DONE) {
            $this->dispatch('task-completed', title: $task->title);
            $this->js("window.dispatchEvent(new CustomEvent('task-completed', { detail: { title: " . json_encode($task->title) . " } }))");
        }
    }

    public function editTask(int $id)
    {
        $task = auth()->user()->tasks()->with('labels')->find($id);
        if ($task) {
            $this->editingTaskId = $id;
            $this->editingTaskTitle = $task->title;
            $this->editingTaskDescription = $task->description ?? '';
            $this->editingTaskProjectId = $task->project_id;
            $this->editingTaskStatus = $task->status;
            $this->editingTaskDueAt = $task->due_at ? $task->due_at->format('Y-m-d\TH:i') : null;
            $this->editingTaskLabelIds = $task->labels->pluck('id')->toArray();
            
            $this->dispatch('open-modal', name: 'edit-task-modal');
        }
    }

    public function updateTask()
    {
        $this->validate([
            'editingTaskTitle' => 'required|string|max:255',
            'editingTaskDescription' => 'nullable|string',
            'editingTaskProjectId' => 'nullable|exists:projects,id',
            'editingTaskStatus' => 'required|in:new,on_progress,done,on_hold,archived',
            'editingTaskDueAt' => 'nullable|date',
            'editingTaskLabelIds' => 'array',
            'editingTaskLabelIds.*' => 'exists:labels,id',
        ]);

        $task = auth()->user()->tasks()->find($this->editingTaskId);
        if ($task) {
            $oldStatus = $task->status;

            $projectId = null;
            if ($this->editingTaskProjectId) {
                $project = auth()->user()->projects()->find($this->editingTaskProjectId);
                if ($project) {
                    $projectId = $project->id;
                }
            }

            $task->update([
                'title' => $this->editingTaskTitle,
                'description' => $this->editingTaskDescription ?: null,
                'project_id' => $projectId,
                'status' => $this->editingTaskStatus,
                'due_at' => $this->editingTaskDueAt ?: null,
            ]);

            $task->refresh();

            if ($task->isOverdue()) {
                auth()->user()->notifications()->create([
                    'title' => "⚠️ Task Overdue: {$task->title}",
                    'body' => "Task '{$task->title}' was due on " . $task->due_at->format('d M Y H:i') . " and is currently overdue.",
                    'type' => 'danger',
                ]);
            } elseif ($task->isDueToday()) {
                $timeStr = $task->due_at->format('H:i') !== '00:00' ? " at {$task->due_at->format('H:i')}" : '';
                auth()->user()->notifications()->create([
                    'title' => "⏰ Task Due Today: {$task->title}",
                    'body' => "Task '{$task->title}' is due today{$timeStr}. Don't forget to complete it!",
                    'type' => 'warning',
                ]);
            }

            if ($this->editingTaskStatus === Task::STATUS_DONE && $oldStatus !== Task::STATUS_DONE) {
                $this->dispatch('task-completed', title: $task->title);
                $this->js("window.dispatchEvent(new CustomEvent('task-completed', { detail: { title: " . json_encode($task->title) . " } }))");
            }

            $userLabelIds = auth()->user()->labels()->whereIn('id', $this->editingTaskLabelIds)->pluck('id')->toArray();
            $task->labels()->sync($userLabelIds);

            $this->editingTaskId = null;
            $this->dispatch('close-modal', name: 'edit-task-modal');
            $this->js("\$flux.modal('edit-task-modal').close()");
            session()->flash('task_message', 'Task updated successfully.');
        }
    }

    public function deleteTask(int $id)
    {
        auth()->user()->tasks()->find($id)?->delete();
        session()->flash('task_message', 'Task deleted successfully.');
    }

    public function showTaskDetail(int $id)
    {
        $task = auth()->user()->tasks()->find($id);
        if ($task) {
            $this->viewingTaskId = $id;
            $this->js("\$flux.modal('detail-task-modal').show()");
        }
    }

    public function editTaskFromDetail()
    {
        if ($this->viewingTaskId) {
            $id = $this->viewingTaskId;
            $this->dispatch('close-modal', name: 'detail-task-modal');
            $this->js("\$flux.modal('detail-task-modal').close()");
            $this->editTask($id);
            $this->js("\$flux.modal('edit-task-modal').show()");
        }
    }

    public function getViewingTaskProperty()
    {
        if (! $this->viewingTaskId) {
            return null;
        }

        return auth()->user()->tasks()->with(['project', 'labels', 'checklists'])->find($this->viewingTaskId);
    }

    // --- Label Actions ---

    public function addLabel()
    {
        $this->validate([
            'labelName' => 'required|string|max:255',
            'labelColor' => 'required|string|in:amber,indigo,emerald,rose,sky,purple,zinc',
        ]);

        auth()->user()->labels()->create([
            'name' => $this->labelName,
            'color' => $this->labelColor,
        ]);

        $this->reset(['labelName']);
        $this->labelColor = 'amber';
        session()->flash('label_message', 'Label added successfully.');
    }

    public function addPresetLabel(string $name, string $color = 'amber')
    {
        $existing = auth()->user()->labels()->where('name', $name)->first();
        if (!$existing) {
            auth()->user()->labels()->create([
                'name' => $name,
                'color' => $color,
            ]);
            session()->flash('label_message', "Label '{$name}' added.");
        }
    }

    public function deleteLabel(int $id)
    {
        auth()->user()->labels()->find($id)?->delete();
        session()->flash('label_message', 'Label deleted successfully.');
    }

    // --- Project Actions ---

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

    public function deleteProject($id)
    {
        auth()->user()->projects()->find($id)?->delete();
    }

    // --- Category Actions ---

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

    public function deleteCategory($id)
    {
        auth()->user()->categories()->find($id)?->delete();
    }

    // --- Helper color mappings ---
    public function getLabelBgClass(string $color): string
    {
        $classes = [
            'amber' => 'bg-amber-500/10 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300/90 border-amber-200/80 dark:border-amber-900/40',
            'emerald' => 'bg-emerald-500/10 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300/90 border-emerald-200/80 dark:border-emerald-900/40',
            'rose' => 'bg-rose-500/10 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300/90 border-rose-200/80 dark:border-rose-900/40',
            'sky' => 'bg-sky-500/10 text-sky-800 dark:bg-sky-950/40 dark:text-sky-300/90 border-sky-200/80 dark:border-sky-900/40',
            'purple' => 'bg-purple-500/10 text-purple-800 dark:bg-purple-950/40 dark:text-purple-300/90 border-purple-200/80 dark:border-purple-900/40',
            'zinc' => 'bg-zinc-500/10 text-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-300/90 border-zinc-200/80 dark:border-zinc-700/60',
        ];

        return $classes[$color] ?? 'bg-indigo-500/10 text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300/90 border-indigo-200/80 dark:border-indigo-900/40';
    }

    public function renderFormattedDescription(?string $text): string
    {
        if (!$text) {
            return '';
        }

        return Str::markdown($text);
    }

    public function getCleanDescription(?string $text): string
    {
        if (!$text) return '';
        $clean = strip_tags($text);
        $clean = preg_replace('/[#\*\_~`>]/', '', $clean);
        return trim(html_entity_decode($clean));
    }
};
?>

<script>
    window.renderTaskMarkdown = function(text) {
        if (!text) return '';
        let html = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        // Multiline Code Blocks: ``` ... ```
        html = html.replace(/```(?:[a-zA-Z0-9_\-]*\n)?([\s\S]*?)```/g, '<pre><code>$1</code></pre>');

        // Inline Code: `code`
        html = html.replace(/`([^`]+)`/g, '<code>$1</code>');

        // Parse Markdown Tables: | Header | ... | \n | --- | ... | \n | Cell | ... |
        html = html.replace(/((?:^\|.*?\|\s*\n)+)/gm, function(match) {
            let lines = match.trim().split('\n').map(l => l.trim()).filter(l => l);
            if (lines.length >= 2 && /^\|[\s:\-|\+]+\|$/.test(lines[1])) {
                let tableHtml = '<table>';
                let headers = lines[0].split('|').slice(1, -1).map(h => h.trim());
                tableHtml += '<thead><tr>' + headers.map(h => '<th>' + h + '</th>').join('') + '</tr></thead>';
                tableHtml += '<tbody>';
                for (let i = 2; i < lines.length; i++) {
                    let cells = lines[i].split('|').slice(1, -1).map(c => c.trim());
                    tableHtml += '<tr>' + cells.map(c => '<td>' + c + '</td>').join('') + '</tr>';
                }
                tableHtml += '</tbody></table>';
                return tableHtml;
            }
            return match;
        });

        // Horizontal Rule / Divider: ---, ***, ___
        html = html.replace(/^(---|[*]{3,}|_{3,})$/gm, '<hr>');

        // Headings (Must run BEFORE lists)
        html = html.replace(/^### (.*)$/gm, '<h3>$1</h3>');
        html = html.replace(/^## (.*)$/gm, '<h2>$1</h2>');
        html = html.replace(/^# (.*)$/gm, '<h1>$1</h1>');
        
        // Blockquote: > text
        html = html.replace(/^&gt;\s?(.*)$/gm, '<blockquote>$1</blockquote>');
        
        // Task List Checkboxes: - [x] or - [ ]
        html = html.replace(/^\s*[\-\*]\s+\[[xX]\]\s+(.*)$/gm, '<li><input type="checkbox" checked disabled> <del>$1</del></li>');
        html = html.replace(/^\s*[\-\*]\s+\[\s\]\s+(.*)$/gm, '<li><input type="checkbox" disabled> $1</li>');

        // Nested & Root Lists (Must run AFTER headings)
        html = html.replace(/^\s{2,}[\-\*]\s+(.*)$/gm, '<li style="margin-left: 1rem;">$1</li>');
        html = html.replace(/^[\-\*]\s+(.*)$/gm, '<li>$1</li>');
        html = html.replace(/^\s{2,}\d+\.\s+(.*)$/gm, '<li style="margin-left: 1rem;">$1</li>');
        html = html.replace(/^\d+\.\s+(.*)$/gm, '<li>$1</li>');
        
        // Bold & Italic
        html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/__(.*?)__/g, '<strong>$1</strong>');
        html = html.replace(/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');
        html = html.replace(/&lt;u&gt;(.*?)&lt;\/u&gt;/gi, '<u>$1</u>');
        html = html.replace(/~~(.*?)~~/g, '<del>$1</del>');

        // Wrap consecutive <li> items in <ul>
        html = html.replace(/(<li>.*?<\/li>\s*)+/gs, '<ul>$&</ul>');

        html = html.replace(/\n/g, '<br>');
        
        // Strip ALL <br> tags surrounding <hr>, <table>, and block elements
        html = html.replace(/(<br\s*\/?>\s*)+<hr>/gi, '<hr>');
        html = html.replace(/<hr>(\s*<br\s*\/?>)+/gi, '<hr>');
        html = html.replace(/(<\/h[1-3]>|<\/blockquote>|<\/ul>|<\/ol>|<\/li>|<hr>|<\/pre>|<\/table>)\s*<br\s*\/?>/gi, '$1');

        return html;
    };
</script>

<style>
    .rich-editor-content {
        line-height: 1.6;
        font-size: 0.875rem;
    }
    .rich-editor-content p {
        margin-top: 0;
        margin-bottom: 0.5rem;
    }
    .rich-editor-content h1 {
        font-size: 1.25rem !important;
        font-weight: 700 !important;
        padding-bottom: 0.25rem !important;
        border-bottom: 1px solid rgba(161, 161, 170, 0.25) !important;
        margin-top: 1rem !important;
        margin-bottom: 0.5rem !important;
    }
    .rich-editor-content h2 {
        font-size: 1.1rem !important;
        font-weight: 700 !important;
        padding-bottom: 0.2rem !important;
        border-bottom: 1px solid rgba(161, 161, 170, 0.2) !important;
        margin-top: 0.85rem !important;
        margin-bottom: 0.4rem !important;
    }
    .rich-editor-content h3 {
        font-size: 0.95rem !important;
        font-weight: 700 !important;
        margin-top: 0.75rem !important;
        margin-bottom: 0.35rem !important;
    }
    .rich-editor-content ul {
        list-style-type: disc !important;
        padding-left: 1.5rem !important;
        margin-top: 0 !important;
        margin-bottom: 0.5rem !important;
    }
    .rich-editor-content ul ul {
        list-style-type: circle !important;
        margin-top: 0 !important;
        margin-bottom: 0 !important;
    }
    .rich-editor-content ol {
        list-style-type: decimal !important;
        padding-left: 1.5rem !important;
        margin-top: 0 !important;
        margin-bottom: 0.5rem !important;
    }
    .rich-editor-content ol ol {
        list-style-type: lower-alpha !important;
        margin-top: 0 !important;
        margin-bottom: 0 !important;
    }
    .rich-editor-content li {
        display: list-item !important;
        line-height: 1.6;
    }
    .rich-editor-content li input[type="checkbox"] {
        margin-right: 0.35rem;
        accent-color: #6366f1;
    }
    .rich-editor-content hr {
        border: 0 !important;
        border-top: 1px solid rgba(161, 161, 170, 0.25) !important;
        margin-top: 0.75rem !important;
        margin-bottom: 0.75rem !important;
    }
    .rich-editor-content blockquote {
        border-left: 3px solid #6366f1 !important;
        padding-left: 0.75rem !important;
        margin-top: 0.5rem !important;
        margin-bottom: 0.5rem !important;
        font-style: italic !important;
        opacity: 0.95;
        background-color: rgba(99, 102, 241, 0.08) !important;
        border-radius: 0 6px 6px 0 !important;
    }
    .rich-editor-content code {
        background-color: rgba(99, 102, 241, 0.18) !important;
        color: #6366f1 !important;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
        padding: 2px 6px !important;
        border-radius: 4px !important;
        font-size: 11px !important;
        border: 1px solid rgba(99, 102, 241, 0.3) !important;
    }
    .dark .rich-editor-content code {
        color: #a5b4fc !important;
        background-color: rgba(99, 102, 241, 0.25) !important;
    }
    .rich-editor-content pre {
        background-color: #18181b !important;
        padding: 0.75rem !important;
        border-radius: 0.5rem !important;
        overflow-x: auto !important;
        margin-bottom: 0.5rem !important;
    }
    .rich-editor-content pre code {
        background-color: transparent !important;
        border: none !important;
        padding: 0 !important;
        color: #f4f4f5 !important;
    }
    .rich-editor-content table {
        width: 100% !important;
        border-collapse: collapse !important;
        margin-bottom: 0.75rem !important;
    }
    .rich-editor-content th, .rich-editor-content td {
        border: 1px solid rgba(161, 161, 170, 0.25) !important;
        padding: 0.4rem 0.6rem !important;
        font-size: 0.8rem !important;
    }
    .rich-editor-content th {
        background-color: rgba(161, 161, 170, 0.1) !important;
        font-weight: 600 !important;
    }
    .rich-editor-content a {
        color: #6366f1 !important;
        text-decoration: underline !important;
        font-weight: 500 !important;
    }
    .rich-editor-content img {
        max-height: 200px !important;
        max-width: 100% !important;
        border-radius: 10px !important;
        margin: 8px 0 !important;
        display: block !important;
        object-fit: cover !important;
    }
</style>

<div class="flex h-full w-full flex-col gap-5 sm:gap-6 px-3 sm:px-4 py-2 text-neutral-900 dark:text-neutral-100 max-w-7xl mx-auto mt-1 sm:mt-4 pb-20"
     x-data="{ activeTab: @entangle('activeTab') }">
    
    <!-- Main Animated Content -->
    <div class="flex flex-col gap-6 animate-page-entrance">
        <!-- Top Header Studio Banner -->
    <div class="border-b border-zinc-200/80 dark:border-zinc-800/80 pb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                <div class="size-8.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                    <flux:icon name="cog-8-tooth" class="size-4.5 text-zinc-700 dark:text-zinc-300" />
                </div>
                <span>Workspace Management Studio</span>
            </h2>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">Manage tasks, projects, clients, tracker categories, and dynamic task labels all in one place.</p>
        </div>
        
        <!-- Tab Navigation Switcher -->
        <div class="flex items-center p-1 bg-zinc-100 dark:bg-zinc-900 rounded-xl border border-zinc-200/80 dark:border-zinc-800 self-start md:self-auto overflow-x-auto max-w-full">
            <button @click="activeTab = 'tasks'" 
                    type="button"
                    :class="activeTab === 'tasks' ? 'bg-white dark:bg-zinc-800 text-indigo-600 dark:text-indigo-400 shadow-xs font-semibold' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-200 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap">
                <flux:icon name="clipboard-document-list" class="size-4 shrink-0" />
                <span>Tasks</span>
                <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-zinc-200/60 dark:bg-zinc-700/60 text-zinc-700 dark:text-zinc-300">
                    {{ $this->tasks->count() }}
                </span>
            </button>

            <button @click="activeTab = 'projects'" 
                    type="button"
                    :class="activeTab === 'projects' ? 'bg-white dark:bg-zinc-800 text-indigo-600 dark:text-indigo-400 shadow-xs font-semibold' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-200 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap">
                <flux:icon name="briefcase" class="size-4 shrink-0" />
                <span>Projects</span>
                <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-zinc-200/60 dark:bg-zinc-700/60 text-zinc-700 dark:text-zinc-300">
                    {{ $this->projects->count() }}
                </span>
            </button>

            <button @click="activeTab = 'categories'" 
                    type="button"
                    :class="activeTab === 'categories' ? 'bg-white dark:bg-zinc-800 text-indigo-600 dark:text-indigo-400 shadow-xs font-semibold' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-200 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap">
                <flux:icon name="tag" class="size-4 shrink-0" />
                <span>Categories &amp; Labels</span>
                <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-zinc-200/60 dark:bg-zinc-700/60 text-zinc-700 dark:text-zinc-300">
                    {{ $this->categories->count() + $this->labels->count() }}
                </span>
            </button>
        </div>
    </div>

    <!-- Global Notification Flash -->
    @if(session()->has('task_message'))
        <div class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-2 bg-emerald-50 dark:bg-emerald-500/10 p-3 rounded-xl border border-emerald-200 dark:border-emerald-500/20 shadow-2xs">
            <flux:icon name="check-circle" class="size-4 shrink-0" />
            <span>{{ session('task_message') }}</span>
        </div>
    @endif

    <!-- TAB 1: TASKS MANAGEMENT -->
    <div x-show="activeTab === 'tasks'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-4" x-data="{ showArchived: false }">
        <!-- Controls Bar: Search, Project Filter, Status Filter, View Mode & Add Task -->
        <div class="bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800 rounded-2xl p-3 shadow-xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <!-- Left Controls: Search & Filters -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 flex-1 min-w-0">
                <!-- Search Input -->
                <div class="relative flex-1 min-w-[200px]">
                    <input type="text" 
                           wire:model.live.debounce.300ms="searchTask" 
                           placeholder="Search task title or label..." 
                           autocomplete="off"
                           class="w-full h-9 pl-9 pr-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500 transition-all">
                    <flux:icon name="magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-400 pointer-events-none" />
                </div>

                <!-- Project Filter Dropdown -->
                <div class="w-full sm:w-auto">
                    <select wire:model.live="filterProject" class="h-9 px-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500 cursor-pointer">
                        <option value="all">All Projects</option>
                        <option value="non_project">Non-Project Only</option>
                        @foreach($this->projects as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="w-full sm:w-auto">
                    <select wire:model.live="filterStatus" class="h-9 px-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500 cursor-pointer">
                        <option value="all">Active Statuses</option>
                        <option value="on_hold">On Hold 🟠</option>
                        <option value="new">New 🔵</option>
                        <option value="on_progress">On Progress 🟡</option>
                        <option value="done">Done 🟢</option>
                        <option value="archived">Archived 📦 ({{ $this->archivedCount }})</option>
                    </select>
                </div>

                <!-- Deadline Filter -->
                <div class="w-full sm:w-auto">
                    <select wire:model.live="filterDeadline" class="h-9 px-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500 cursor-pointer">
                        <option value="all">All Deadlines 📅</option>
                        <option value="overdue">Overdue ⚠️</option>
                        <option value="due_today">Due Today ⏰</option>
                        <option value="due_this_week">Due This Week 📆</option>
                        <option value="no_deadline">No Deadline ♾️</option>
                    </select>
                </div>
            </div>

            <!-- Right Controls: View Switcher, Archive Repository, & Add Task Button -->
            <div class="flex items-center gap-2 shrink-0">
                <!-- View Mode Switcher -->
                <div class="flex items-center p-0.5 bg-zinc-100 dark:bg-zinc-800/80 rounded-xl border border-zinc-200 dark:border-zinc-700/60">
                    <button wire:click="$set('viewMode', 'kanban')" 
                            class="p-1.5 rounded-lg text-xs transition-all cursor-pointer {{ $viewMode === 'kanban' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200' }}"
                            title="Kanban Board View">
                        <flux:icon name="rectangle-stack" class="size-4" />
                    </button>
                    <button wire:click="$set('viewMode', 'list')" 
                            class="p-1.5 rounded-lg text-xs transition-all cursor-pointer {{ $viewMode === 'list' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200' }}"
                            title="List Table View">
                        <flux:icon name="bars-3-bottom-left" class="size-4" />
                    </button>
                </div>

                <!-- Archive Repository Modal Trigger -->
                @php $archivedCount = $this->archivedCount; @endphp
                <flux:modal.trigger name="archived-tasks-modal">
                    <button type="button" class="cursor-pointer bg-purple-500/10 hover:bg-purple-500/20 text-purple-700 dark:text-purple-300 font-semibold rounded-xl px-3 py-2 text-xs border border-purple-200/80 dark:border-purple-900/40 active:scale-95 transition-all flex items-center gap-1.5 shrink-0">
                        <flux:icon name="archive-box" class="size-3.5 text-purple-600 dark:text-purple-400" />
                        <span>Archived</span>
                        @if($archivedCount > 0)
                            <span class="px-1.5 py-0.2 text-[10px] font-mono font-bold rounded-full bg-purple-500/20 text-purple-800 dark:text-purple-200">
                                {{ $archivedCount }}
                            </span>
                        @endif
                    </button>
                </flux:modal.trigger>

                <!-- Add Task Button Modal Trigger -->
                <flux:modal.trigger name="create-task-modal">
                    <button type="button" class="cursor-pointer bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl px-3.5 py-2 text-xs border border-indigo-500/80 active:scale-95 transition-all shadow-xs shadow-indigo-500/20 flex items-center gap-1.5">
                        <flux:icon name="plus" class="size-3.5 text-white" />
                        <span>Add Task</span>
                    </button>
                </flux:modal.trigger>
            </div>
        </div>

        <!-- KANBAN VIEW -->
        @if($viewMode === 'kanban')
            @php
                $columns = [
                    ['id' => 'on_hold', 'label' => 'On Hold', 'color' => 'rose', 'icon' => 'pause-circle', 'bg' => 'bg-rose-500/10 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300/90 border-rose-200/80 dark:border-rose-900/40'],
                    ['id' => 'new', 'label' => 'New', 'color' => 'sky', 'icon' => 'sparkles', 'bg' => 'bg-sky-500/10 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300/90 border-sky-200/80 dark:border-sky-900/40'],
                    ['id' => 'on_progress', 'label' => 'On Progress', 'color' => 'amber', 'icon' => 'arrow-path', 'bg' => 'bg-amber-500/10 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300/90 border-amber-200/80 dark:border-amber-900/40'],
                    ['id' => 'done', 'label' => 'Done', 'color' => 'emerald', 'icon' => 'check-circle', 'bg' => 'bg-emerald-500/10 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300/90 border-emerald-200/80 dark:border-emerald-900/40'],
                ];
            @endphp

            <!-- 4 Main Kanban Columns -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-start">
                @foreach($columns as $col)
                    @php
                        $colTasks = $this->tasks->where('status', $col['id']);
                    @endphp
                    <div x-data="{ isOver: false }"
                         x-on:dragover.prevent="isOver = true"
                         x-on:dragleave.prevent="isOver = false"
                         x-on:drop.prevent="
                             isOver = false;
                             let taskId = event.dataTransfer.getData('text/plain');
                             if (taskId) {
                                 $wire.updateTaskStatus(parseInt(taskId), '{{ $col['id'] }}');
                             }
                         "
                         class="bg-zinc-50/70 dark:bg-zinc-900/60 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 p-3 sm:p-3.5 flex flex-col gap-3 min-h-[140px] md:min-h-[400px] transition-all duration-200"
                         :class="isOver ? 'ring-2 ring-indigo-500/50 bg-indigo-50/30 dark:bg-indigo-950/30 border-indigo-400 dark:border-indigo-500/60 shadow-lg' : ''">
                        <!-- Column Header -->
                        <div class="flex items-center justify-between pb-2 border-b border-zinc-200/60 dark:border-zinc-800">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-lg text-xs font-semibold border flex items-center gap-1.5 {{ $col['bg'] }}">
                                    <flux:icon name="{{ $col['icon'] }}" class="size-3.5" />
                                    <span>{{ $col['label'] }}</span>
                                </span>
                            </div>
                            <span class="text-[11px] font-mono font-medium text-zinc-500 dark:text-zinc-400 bg-zinc-200/60 dark:bg-zinc-800 px-2 py-0.5 rounded-md">
                                {{ $colTasks->count() }}
                            </span>
                        </div>

                        <!-- Column Tasks Card List -->
                        <div class="flex flex-col gap-2.5 flex-1">
                            @forelse($colTasks as $task)
                                <flux:modal.trigger name="detail-task-modal">
                                    <div wire:key="task-kanban-{{ $task->id }}" 
                                         x-data="{ isDragging: false }"
                                         draggable="true"
                                         x-on:dragstart="
                                             event.dataTransfer.setData('text/plain', '{{ $task->id }}');
                                             event.dataTransfer.effectAllowed = 'move';
                                             isDragging = true;
                                         "
                                         x-on:dragend="isDragging = false"
                                         wire:loading.class="opacity-50 scale-[0.98] pointer-events-none ring-2 ring-indigo-500/40 transition-all duration-200"
                                         wire:target="updateTaskStatus({{ $task->id }}, 'on_hold'), updateTaskStatus({{ $task->id }}, 'new'), updateTaskStatus({{ $task->id }}, 'on_progress'), updateTaskStatus({{ $task->id }}, 'done'), updateTaskStatus({{ $task->id }}, 'archived')"
                                         wire:click="showTaskDetail({{ $task->id }})"
                                         class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 rounded-xl p-3 shadow-2xs hover:border-zinc-300 dark:hover:border-zinc-700 transition-all group flex flex-col gap-2 relative overflow-hidden cursor-grab active:cursor-grabbing"
                                         :class="isDragging ? 'opacity-40 border-dashed border-indigo-500 scale-[0.97]' : ''">
                                    
                                    <!-- Processing Loading Progress Bar -->
                                    <div wire:loading 
                                         wire:target="updateTaskStatus({{ $task->id }}, 'on_hold'), updateTaskStatus({{ $task->id }}, 'new'), updateTaskStatus({{ $task->id }}, 'on_progress'), updateTaskStatus({{ $task->id }}, 'done'), updateTaskStatus({{ $task->id }}, 'archived')" 
                                         class="absolute top-0 left-0 right-0 h-1 bg-indigo-500 rounded-t-xl animate-pulse"></div>
                                    <!-- Title & Actions -->
                                    <div class="flex items-start justify-between gap-2">
                                        <h4 class="text-xs font-bold text-zinc-900 dark:text-zinc-100 leading-snug line-clamp-2">{{ $task->title }}</h4>
                                        
                                        <div class="opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity flex items-center gap-0.5 shrink-0" @click.stop>
                                            <flux:modal.trigger name="edit-task-modal">
                                                <button wire:click="editTask({{ $task->id }})" class="p-1 rounded text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 cursor-pointer">
                                                    <flux:icon name="pencil" class="size-3" />
                                                </button>
                                            </flux:modal.trigger>
                                            <flux:modal.trigger name="delete-task-{{ $task->id }}">
                                                <button class="p-1 rounded text-red-400 hover:text-red-600 cursor-pointer">
                                                    <flux:icon name="trash" class="size-3" />
                                                </button>
                                            </flux:modal.trigger>
                                        </div>
                                    </div>

                                    <!-- Description preview if exists -->
                                    @if($task->description)
                                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 line-clamp-2 leading-tight">{{ $this->getCleanDescription($task->description) }}</p>
                                    @endif

                                    <!-- Project & Labels row -->
                                    <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                        <!-- Project Badge -->
                                        @if($task->project)
                                            <span class="inline-flex items-center gap-1 text-[10px] font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 px-1.5 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60 max-w-[150px] truncate" title="{{ $task->project->name }}">
                                                <flux:icon name="folder" class="size-2.5 text-zinc-500 shrink-0" />
                                                <span class="truncate">{{ $task->project->name }}</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center text-[10px] font-medium bg-zinc-100/60 dark:bg-zinc-800/40 text-zinc-500 dark:text-zinc-400 px-1.5 py-0.5 rounded-md border border-zinc-200/50 dark:border-zinc-800/80">
                                                Non-Project
                                            </span>
                                        @endif

                                        <!-- Deadline Badge -->
                                        @if($task->due_badge)
                                            @php $badge = $task->due_badge; @endphp
                                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold px-1.5 py-0.5 rounded-md border
                                                {{ $badge['color'] === 'rose' ? 'bg-rose-500/10 text-rose-700 dark:text-rose-300 border-rose-200/80 dark:border-rose-900/40 font-bold' : '' }}
                                                {{ $badge['color'] === 'amber' ? 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-200/80 dark:border-amber-900/40 font-bold' : '' }}
                                                {{ $badge['color'] === 'indigo' ? 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border-indigo-200/80 dark:border-indigo-900/40' : '' }}
                                                {{ $badge['color'] === 'sky' ? 'bg-sky-500/10 text-sky-700 dark:text-sky-300 border-sky-200/80 dark:border-sky-900/40' : '' }}
                                                {{ $badge['color'] === 'zinc' ? 'bg-zinc-100/60 dark:bg-zinc-800/40 text-zinc-500 dark:text-zinc-400 border-zinc-200/50 dark:border-zinc-800/80' : '' }}">
                                                <flux:icon name="{{ $badge['icon'] }}" class="size-2.5 shrink-0" />
                                                <span>{{ $badge['label'] }}</span>
                                            </span>
                                        @endif

                                        <!-- Dynamic Labels -->
                                        @foreach($task->labels as $label)
                                            <span class="inline-flex items-center text-[10px] font-semibold px-1.5 py-0.5 rounded-md border {{ $this->getLabelBgClass($label->color) }}">
                                                {{ $label->name }}
                                            </span>
                                        @endforeach

                                        <!-- Checklist Progress Badge -->
                                        @if($task->checklist_stats)
                                            @php $cStats = $task->checklist_stats; @endphp
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-1.5 py-0.5 rounded-md border
                                                  {{ $cStats['is_all_completed'] ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-500/30' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700/60' }}"
                                                  title="Checklist: {{ $cStats['completed'] }} of {{ $cStats['total'] }} completed">
                                                <flux:icon name="clipboard-document-check" class="size-2.5 shrink-0 {{ $cStats['is_all_completed'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-500' }}" />
                                                <span>{{ $cStats['completed'] }}/{{ $cStats['total'] }}</span>
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Status Quick Action Controls -->
                                    <div class="flex items-center justify-between pt-2 border-t border-zinc-100 dark:border-zinc-800/60 mt-1">
                                        <span class="text-[10px] text-zinc-400 font-mono">{{ $task->created_at->diffForHumans() }}</span>

                                        <div class="flex items-center gap-1" @click.stop>
                                            @if($col['id'] !== 'on_hold')
                                                <button wire:click="updateTaskStatus({{ $task->id }}, 'on_hold')" 
                                                        wire:loading.attr="disabled"
                                                        class="p-1 rounded hover:bg-rose-500/10 text-rose-500 hover:scale-110 active:scale-95 transition-all cursor-pointer disabled:opacity-50" 
                                                        title="Move to On Hold">
                                                    <flux:icon name="pause-circle" class="size-3" wire:loading.class="animate-spin" wire:target="updateTaskStatus({{ $task->id }}, 'on_hold')" />
                                                </button>
                                            @endif
                                            @if($col['id'] !== 'new')
                                                <button wire:click="updateTaskStatus({{ $task->id }}, 'new')" 
                                                        wire:loading.attr="disabled"
                                                        class="p-1 rounded hover:bg-sky-500/10 text-sky-500 hover:scale-110 active:scale-95 transition-all cursor-pointer disabled:opacity-50" 
                                                        title="Move to New">
                                                    <flux:icon name="sparkles" class="size-3" wire:loading.class="animate-spin" wire:target="updateTaskStatus({{ $task->id }}, 'new')" />
                                                </button>
                                            @endif
                                            @if($col['id'] !== 'on_progress')
                                                <button wire:click="updateTaskStatus({{ $task->id }}, 'on_progress')" 
                                                        wire:loading.attr="disabled"
                                                        class="p-1 rounded hover:bg-amber-500/10 text-amber-500 hover:scale-110 active:scale-95 transition-all cursor-pointer disabled:opacity-50" 
                                                        title="Move to On Progress">
                                                    <flux:icon name="arrow-path" class="size-3" wire:loading.class="animate-spin" wire:target="updateTaskStatus({{ $task->id }}, 'on_progress')" />
                                                </button>
                                            @endif
                                            @if($col['id'] !== 'done')
                                                <button wire:click="updateTaskStatus({{ $task->id }}, 'done')" 
                                                        wire:loading.attr="disabled"
                                                        class="p-1 rounded hover:bg-emerald-500/10 text-emerald-500 hover:scale-110 active:scale-95 transition-all cursor-pointer disabled:opacity-50" 
                                                        title="Move to Done">
                                                    <flux:icon name="check-circle" class="size-3" wire:loading.class="animate-spin" wire:target="updateTaskStatus({{ $task->id }}, 'done')" />
                                                </button>
                                            @endif
                                            <button wire:click="updateTaskStatus({{ $task->id }}, 'archived')" 
                                                    wire:loading.attr="disabled"
                                                    class="p-1 rounded hover:bg-purple-500/10 text-purple-500 hover:scale-110 active:scale-95 transition-all cursor-pointer disabled:opacity-50" 
                                                    title="Archive Task">
                                                <flux:icon name="archive-box" class="size-3" wire:loading.class="animate-spin" wire:target="updateTaskStatus({{ $task->id }}, 'archived')" />
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Delete Modal -->
                                    <flux:modal name="delete-task-{{ $task->id }}" class="w-[calc(100vw-2rem)] max-w-md backdrop:backdrop-blur-md z-[200]">
                                        <div class="space-y-4">
                                            <div class="flex items-center gap-3">
                                                <div class="size-9 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 flex items-center justify-center text-red-600 dark:text-red-500 shrink-0">
                                                    <flux:icon name="trash" class="size-4.5" />
                                                </div>
                                                <div>
                                                    <flux:heading size="lg" class="font-bold text-sm">Delete Task?</flux:heading>
                                                    <flux:text class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                                        Are you sure you want to delete <strong>{{ $task->title }}</strong>?
                                                    </flux:text>
                                                </div>
                                            </div>
                                            <div class="flex justify-end gap-2 pt-2">
                                                <flux:modal.close>
                                                    <flux:button variant="ghost" size="xs">Cancel</flux:button>
                                                </flux:modal.close>
                                                <flux:modal.close>
                                                    <flux:button variant="danger" size="xs" wire:click="deleteTask({{ $task->id }})">Delete</flux:button>
                                                </flux:modal.close>
                                            </div>
                                        </div>
                                    </flux:modal>
                                </div>
                                </flux:modal.trigger>
                            @empty
                                <div class="text-[11px] text-zinc-400 text-center py-8 border border-dashed border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col items-center justify-center gap-1 my-auto">
                                    <span>No tasks in {{ $col['label'] }}</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>



        <!-- LIST VIEW -->
        @else
            <div class="bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800 rounded-2xl overflow-hidden shadow-xs divide-y divide-zinc-200/50 dark:divide-zinc-800/50">
                @forelse($this->tasks as $task)
                    <flux:modal.trigger name="detail-task-modal">
                        <div wire:key="task-list-{{ $task->id }}" 
                             wire:click="showTaskDetail({{ $task->id }})" 
                             class="p-3.5 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors group flex flex-col sm:flex-row sm:items-center justify-between gap-3 cursor-pointer">
                        <div class="flex items-start sm:items-center gap-3 flex-1 min-w-0">
                            <!-- Status Selector Dropdown -->
                            <div @click.stop>
                                <select wire:change="updateTaskStatus({{ $task->id }}, $event.target.value)"
                                    class="h-8 px-2 rounded-lg text-xs font-semibold cursor-pointer border focus:outline-none transition-all shrink-0
                                           {{ $task->status === 'new' ? 'bg-sky-500/10 text-sky-800 dark:bg-sky-950/40 dark:text-sky-300/90 border-sky-200/80 dark:border-sky-900/40' : '' }}
                                           {{ $task->status === 'on_progress' ? 'bg-amber-500/10 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300/90 border-amber-200/80 dark:border-amber-900/40' : '' }}
                                           {{ $task->status === 'on_hold' ? 'bg-rose-500/10 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300/90 border-rose-200/80 dark:border-rose-900/40' : '' }}
                                           {{ $task->status === 'done' ? 'bg-emerald-500/10 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300/90 border-emerald-200/80 dark:border-emerald-900/40' : '' }}
                                           {{ $task->status === 'archived' ? 'bg-purple-500/10 text-purple-800 dark:bg-purple-950/40 dark:text-purple-300/90 border-purple-200/80 dark:border-purple-900/40' : '' }}">
                                <option value="on_hold" {{ $task->status === 'on_hold' ? 'selected' : '' }}>On Hold 🟠</option>
                                <option value="new" {{ $task->status === 'new' ? 'selected' : '' }}>New 🔵</option>
                                <option value="on_progress" {{ $task->status === 'on_progress' ? 'selected' : '' }}>On Progress 🟡</option>
                                <option value="done" {{ $task->status === 'done' ? 'selected' : '' }}>Done 🟢</option>
                                <option value="archived" {{ $task->status === 'archived' ? 'selected' : '' }}>Archived 📦</option>
                            </select>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-sm text-zinc-900 dark:text-zinc-100 flex items-center gap-2 flex-wrap">
                                    <span>{{ $task->title }}</span>
                                    
                                    <!-- Project Tag -->
                                    @if($task->project)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-mono font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60">
                                            <flux:icon name="folder" class="size-3 text-zinc-500" />
                                            <span>{{ $task->project->name }}</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center text-[10px] font-mono text-zinc-400 bg-zinc-100/60 dark:bg-zinc-800/40 px-2 py-0.5 rounded-md border border-zinc-200/50 dark:border-zinc-800/80">
                                            Non-Project
                                        </span>
                                    @endif

                                    <!-- Deadline Badge -->
                                    @if($task->due_badge)
                                        @php $badge = $task->due_badge; @endphp
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-md border
                                            {{ $badge['color'] === 'rose' ? 'bg-rose-500/10 text-rose-700 dark:text-rose-300 border-rose-200/80 dark:border-rose-900/40 font-bold' : '' }}
                                            {{ $badge['color'] === 'amber' ? 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-200/80 dark:border-amber-900/40 font-bold' : '' }}
                                            {{ $badge['color'] === 'indigo' ? 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border-indigo-200/80 dark:border-indigo-900/40' : '' }}
                                            {{ $badge['color'] === 'sky' ? 'bg-sky-500/10 text-sky-700 dark:text-sky-300 border-sky-200/80 dark:border-sky-900/40' : '' }}
                                            {{ $badge['color'] === 'zinc' ? 'bg-zinc-100/60 dark:bg-zinc-800/40 text-zinc-500 dark:text-zinc-400 border-zinc-200/50 dark:border-zinc-800/80' : '' }}">
                                            <flux:icon name="{{ $badge['icon'] }}" class="size-3 shrink-0" />
                                            <span>{{ $badge['label'] }}</span>
                                        </span>
                                    @endif

                                    <!-- Labels -->
                                    @foreach($task->labels as $label)
                                        <span class="inline-flex items-center text-[10px] font-semibold px-2 py-0.5 rounded-md border {{ $this->getLabelBgClass($label->color) }}">
                                            {{ $label->name }}
                                        </span>
                                    @endforeach
                                </div>
                                @if($task->description)
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 truncate">{{ $this->getCleanDescription($task->description) }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-3 shrink-0 self-end sm:self-center" @click.stop>
                            <span class="text-[10px] text-zinc-400 font-mono">{{ $task->created_at->format('d M Y') }}</span>
                            <div class="flex items-center gap-1">
                                <flux:modal.trigger name="edit-task-modal">
                                    <flux:button wire:click="editTask({{ $task->id }})" variant="ghost" size="xs" icon="pencil" square class="cursor-pointer text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200" />
                                </flux:modal.trigger>
                                <flux:modal.trigger name="delete-task-list-{{ $task->id }}">
                                    <flux:button variant="ghost" size="xs" icon="trash" square class="text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/20 cursor-pointer" />
                                </flux:modal.trigger>
                            </div>
                        </div>

                        <flux:modal name="delete-task-list-{{ $task->id }}" class="w-[calc(100vw-2rem)] max-w-md backdrop:backdrop-blur-md z-[200]">
                            <div class="space-y-4">
                                <div class="flex items-center gap-3">
                                    <div class="size-9 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 flex items-center justify-center text-red-600 dark:text-red-500 shrink-0">
                                        <flux:icon name="trash" class="size-4.5" />
                                    </div>
                                    <div>
                                        <flux:heading size="lg" class="font-bold">Delete Task?</flux:heading>
                                        <flux:text class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                            Are you sure you want to delete <strong>{{ $task->title }}</strong>?
                                        </flux:text>
                                    </div>
                                </div>
                                <div class="flex justify-end gap-2 pt-2">
                                    <flux:modal.close>
                                        <flux:button variant="ghost" size="xs">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:modal.close>
                                        <flux:button variant="danger" size="xs" wire:click="deleteTask({{ $task->id }})">Delete</flux:button>
                                    </flux:modal.close>
                                </div>
                            </div>
                        </flux:modal>
                    </div>
                    </flux:modal.trigger>
                @empty
                    <div class="text-xs text-neutral-400 text-center py-12 flex flex-col items-center gap-2 bg-zinc-50/50 dark:bg-zinc-950/20">
                        <div class="size-10 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-500 dark:text-zinc-400">
                            <flux:icon name="clipboard-document-list" class="size-5" />
                        </div>
                        <span class="font-semibold text-zinc-700 dark:text-zinc-300">No tasks found.</span>
                        <span class="text-[11px] text-zinc-500 dark:text-zinc-400">Click "Add Task" to create your first task.</span>
                    </div>
                @endforelse
            </div>
        @endif

        <!-- CREATE TASK MODAL -->
        <flux:modal name="create-task-modal" class="w-[calc(100vw-2rem)] max-w-lg backdrop:backdrop-blur-md z-[200]">
            <form wire:submit.prevent="addTask" class="space-y-4" x-data="{ showChecklist: false, showDeadline: false, showLabels: false, selectedLabels: $wire.entangle('taskLabelIds') }">
                <div class="flex items-center justify-between pb-2 border-b border-zinc-200 dark:border-zinc-800">
                    <div class="flex items-center gap-2">
                        <div class="size-7 rounded-lg bg-indigo-50 dark:bg-indigo-500/20 border border-indigo-200 dark:border-indigo-500/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <flux:icon name="plus" class="size-4" />
                        </div>
                        <flux:heading size="lg" class="font-bold">Create New Task</flux:heading>
                    </div>
                </div>

                <!-- Task Title -->
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                        <flux:icon name="pencil-square" class="size-3.5 text-indigo-500" />
                        <span>Task Title <span class="text-red-500">*</span></span>
                    </label>
                    <input type="text" wire:model="taskTitle" placeholder="What needs to be done?" required autocomplete="off"
                           class="w-full h-9 px-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500">
                    @error('taskTitle') <span class="text-[10px] text-red-500"></span> @enderror
                </div>

                <!-- Task Description with Write / Preview Tabs (Create Modal) -->
                <div x-data="{ tab: 'write' }" class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                            <flux:icon name="document-text" class="size-3.5 text-indigo-500" />
                            <span>Description (Optional)</span>
                        </label>
                        
                        <!-- Write / Preview Tab Switcher -->
                        <div class="flex items-center p-0.5 rounded-lg bg-zinc-200/60 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700/60 text-[11px] font-medium">
                            <button type="button" 
                                    @click="tab = 'write'" 
                                    :class="tab === 'write' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 font-bold shadow-2xs' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200'"
                                    class="px-2.5 py-0.5 rounded-md transition-all cursor-pointer">
                                Write
                            </button>
                            <button type="button" 
                                    @click="tab = 'preview'" 
                                    :class="tab === 'preview' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 font-bold shadow-2xs' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200'"
                                    class="px-2.5 py-0.5 rounded-md transition-all cursor-pointer">
                                Preview
                            </button>
                        </div>
                    </div>

                    <!-- WRITE TAB CONTENT -->
                    <div x-show="tab === 'write'">
                        <textarea wire:model.live="taskDescription"
                                  placeholder="Write task details using GitHub Flavored Markdown (e.g. ## Title, **bold**, - list, | table |)..." 
                                  rows="10"
                                  class="w-full p-3 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all leading-relaxed font-mono resize-y min-h-[220px]"></textarea>
                    </div>

                    <!-- PREVIEW TAB CONTENT -->
                    <div x-show="tab === 'preview'" class="p-3.5 rounded-xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-200/80 dark:border-zinc-800 min-h-[220px] max-h-[420px] overflow-y-auto">
                        @if(trim($taskDescription))
                            <div class="rich-editor-content text-xs text-zinc-800 dark:text-zinc-200 leading-relaxed font-mono">
                                {!! $this->renderFormattedDescription($taskDescription) !!}
                            </div>
                        @else
                            <div class="text-zinc-400 text-xs italic font-mono">
                                <span>Nothing to preview yet. Write some Markdown content in the Write tab first!</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Grid Row: Project & Initial Status -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Project Selector -->
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                            <flux:icon name="folder" class="size-3.5 text-indigo-500" />
                            <span>Project Reference</span>
                        </label>
                        <select wire:model="taskProjectId" class="w-full h-9 px-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500 cursor-pointer">
                            <option value="">Non-Project (Standalone)</option>
                            @foreach($this->projects as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Initial Status -->
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                            <flux:icon name="flag" class="size-3.5 text-indigo-500" />
                            <span>Status</span>
                        </label>
                        <select wire:model="taskStatus" class="w-full h-9 px-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500 cursor-pointer">
                            <option value="new">New 🔵 (Default)</option>
                            <option value="on_progress">On Progress 🟡</option>
                            <option value="on_hold">On Hold 🟠</option>
                            <option value="done">Done 🟢</option>
                            <option value="archived">Archived 📦</option>
                        </select>
                    </div>
                </div>

                <!-- Quick Optional Section Toggles -->
                <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-b border-zinc-200/60 dark:border-zinc-800/60 py-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Add Optional:</span>
                    
                    <button type="button" 
                            @click="showChecklist = !showChecklist"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold border transition-all cursor-pointer flex items-center gap-1.5 active:scale-95"
                            :class="showChecklist || (Array.isArray($wire.newTaskChecklists) && $wire.newTaskChecklists.length > 0)
                                ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/30' 
                                : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700/60 hover:bg-zinc-200 dark:hover:bg-zinc-700'">
                        <flux:icon name="clipboard-document-check" class="size-3.5" />
                        <span>Checklist</span>
                    </button>

                    <button type="button" 
                            @click="showDeadline = !showDeadline"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold border transition-all cursor-pointer flex items-center gap-1.5 active:scale-95"
                            :class="showDeadline || $wire.taskDueAt !== null
                                ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/30' 
                                : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700/60 hover:bg-zinc-200 dark:hover:bg-zinc-700'">
                        <flux:icon name="calendar-days" class="size-3.5" />
                        <span>Deadline</span>
                    </button>

                    <button type="button" 
                            @click="showLabels = !showLabels"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold border transition-all cursor-pointer flex items-center gap-1.5 active:scale-95"
                            :class="showLabels || (Array.isArray(selectedLabels) && selectedLabels.length > 0)
                                ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/30' 
                                : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700/60 hover:bg-zinc-200 dark:hover:bg-zinc-700'">
                        <flux:icon name="tag" class="size-3.5" />
                        <span>Labels</span>
                    </button>
                </div>

                <!-- Checklist Section (Add Sub-tasks) -->
                <div class="space-y-2" x-show="showChecklist || (Array.isArray($wire.newTaskChecklists) && $wire.newTaskChecklists.length > 0)" x-transition>
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                            <flux:icon name="clipboard-document-check" class="size-3.5 text-indigo-500" />
                            <span>Checklist (Sub-tasks)</span>
                        </label>
                        <button type="button" @click="showChecklist = false" class="text-[10px] text-zinc-400 hover:text-red-500 transition-colors cursor-pointer">Hide</button>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="text" 
                               wire:model="newTaskChecklistInput" 
                               wire:keydown.enter.prevent="addChecklistItemToNewTask"
                               placeholder="Add a checklist point (e.g. update db)..." 
                               autocomplete="off"
                               class="h-8 px-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500 flex-1">
                        
                        <button type="button" 
                                wire:click="addChecklistItemToNewTask"
                                class="h-8 px-3 rounded-xl bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-xs font-semibold cursor-pointer transition-all flex items-center gap-1 shrink-0">
                            <flux:icon name="plus" class="size-3" />
                            <span>Add Point</span>
                        </button>
                    </div>

                    @if(!empty($newTaskChecklists))
                        <div wire:key="new-task-checklist-container-{{ count($newTaskChecklists) }}"
                             wire:ignore
                             class="space-y-1.5 max-h-40 overflow-y-auto p-1 select-none"
                             x-data="{ 
                                 init() {
                                     let el = $el;
                                     if (window.Sortable && !el._sortable) {
                                         el._sortable = new Sortable(el, {
                                             animation: 150,
                                             handle: '.drag-handle',
                                             ghostClass: 'opacity-30',
                                             chosenClass: 'border-indigo-500',
                                             onEnd: (evt) => {
                                                 if (evt.oldIndex !== undefined && evt.newIndex !== undefined && evt.oldIndex !== evt.newIndex) {
                                                     $wire.reorderNewTaskChecklists(evt.oldIndex, evt.newIndex);
                                                 }
                                             }
                                         });
                                     }
                                 }
                             }">
                            @foreach($newTaskChecklists as $idx => $pointTitle)
                                <div wire:key="new-checklist-point-{{ md5($pointTitle) }}"
                                     class="group flex items-center justify-between gap-2 p-2.5 rounded-xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-200/80 dark:border-zinc-800/80 text-xs transition-all hover:border-indigo-500/50">
                                    <div class="flex items-center gap-2 min-w-0 flex-1">
                                        <flux:icon name="bars-3" class="drag-handle size-3.5 text-zinc-400 opacity-60 group-hover:opacity-100 shrink-0 cursor-grab active:cursor-grabbing" />
                                        <span class="text-zinc-800 dark:text-zinc-200 truncate font-medium select-none">{{ $pointTitle }}</span>
                                    </div>
                                    <button type="button" 
                                            wire:click="removeNewTaskChecklistItem({{ $idx }})"
                                            class="text-zinc-400 hover:text-red-500 p-1 rounded-md hover:bg-red-500/10 transition-colors cursor-pointer shrink-0">
                                        <flux:icon name="x-mark" class="size-3.5" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Deadline (Due Date) Input -->
                <div class="space-y-1.5" x-show="showDeadline || $wire.taskDueAt !== null" x-transition>
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                            <flux:icon name="calendar-days" class="size-3.5 text-indigo-500" />
                            <span>Deadline (Due Date)</span>
                        </label>
                        <button type="button" @click="showDeadline = false; $wire.set('taskDueAt', null)" class="text-[10px] text-zinc-400 hover:text-red-500 transition-colors cursor-pointer">Hide</button>
                    </div>

                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                        <input type="datetime-local" 
                               wire:model="taskDueAt" 
                               class="h-9 px-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500 flex-1">
                        
                        @php $activePreset = $this->getActiveDuePreset($taskDueAt); @endphp
                        <div class="flex items-center gap-1 overflow-x-auto shrink-0" 
                             x-data="{ preset: '{{ $activePreset }}' }">
                            <button type="button" 
                                    @click="preset = 'today'"
                                    wire:click="setTaskDuePreset('today')" 
                                    class="px-2.5 py-1.5 rounded-lg text-[10px] font-semibold transition-all cursor-pointer whitespace-nowrap flex items-center gap-1 active:scale-95"
                                    :class="preset === 'today' ? 'bg-indigo-600 text-white font-bold shadow-2xs ring-2 ring-indigo-500/50' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'">
                                <template x-if="preset === 'today'">
                                    <flux:icon name="check" class="size-3 text-white" />
                                </template>
                                <span>Today</span>
                            </button>
                            <button type="button" 
                                    @click="preset = 'tomorrow'"
                                    wire:click="setTaskDuePreset('tomorrow')" 
                                    class="px-2.5 py-1.5 rounded-lg text-[10px] font-semibold transition-all cursor-pointer whitespace-nowrap flex items-center gap-1 active:scale-95"
                                    :class="preset === 'tomorrow' ? 'bg-indigo-600 text-white font-bold shadow-2xs ring-2 ring-indigo-500/50' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'">
                                <template x-if="preset === 'tomorrow'">
                                    <flux:icon name="check" class="size-3 text-white" />
                                </template>
                                <span>Tomorrow</span>
                            </button>
                            <button type="button" 
                                    @click="preset = 'next_week'"
                                    wire:click="setTaskDuePreset('next_week')" 
                                    class="px-2.5 py-1.5 rounded-lg text-[10px] font-semibold transition-all cursor-pointer whitespace-nowrap flex items-center gap-1 active:scale-95"
                                    :class="preset === 'next_week' ? 'bg-indigo-600 text-white font-bold shadow-2xs ring-2 ring-indigo-500/50' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'">
                                <template x-if="preset === 'next_week'">
                                    <flux:icon name="check" class="size-3 text-white" />
                                </template>
                                <span>Next Week</span>
                            </button>
                            <button type="button" 
                                    @click="preset = ''"
                                    wire:click="$set('taskDueAt', null)" 
                                    class="px-2 py-1.5 rounded-lg text-[10px] font-semibold bg-red-500/10 text-red-500 hover:bg-red-500/20 transition-colors cursor-pointer whitespace-nowrap">
                                Clear
                            </button>
                        </div>
                    </div>
                    @error('taskDueAt') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <!-- Labels Picker -->
                <div class="space-y-1.5" 
                     x-show="showLabels || (Array.isArray(selectedLabels) && selectedLabels.length > 0)" 
                     x-transition>
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                            <flux:icon name="tag" class="size-3.5 text-indigo-500" />
                            <span>Attach Labels</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-zinc-400">Select multiple</span>
                            <button type="button" @click="showLabels = false; selectedLabels = []" class="text-[10px] text-zinc-400 hover:text-red-500 transition-colors cursor-pointer">Hide</button>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-1.5 p-2 bg-zinc-50 dark:bg-zinc-950 border border-zinc-200/80 dark:border-zinc-800 rounded-xl min-h-[42px] max-h-32 overflow-y-auto">
                        @forelse($this->labels as $label)
                            @php $bgColorClass = $this->getLabelBgClass($label->color); @endphp
                            <button type="button" 
                                    wire:key="task-label-chip-{{ $label->id }}"
                                    @click="
                                        let id = {{ $label->id }};
                                        if (!Array.isArray(selectedLabels)) selectedLabels = [];
                                        let idx = selectedLabels.map(Number).indexOf(id);
                                        if (idx > -1) { selectedLabels.splice(idx, 1); } 
                                        else { selectedLabels.push(id); }
                                    "
                                    class="cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border text-xs font-semibold transition-all active:scale-95"
                                    :class="(Array.isArray(selectedLabels) && selectedLabels.map(Number).includes({{ $label->id }}))
                                        ? '{{ $bgColorClass }} ring-2 ring-indigo-500 shadow-2xs' 
                                        : 'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700'">
                                <template x-if="Array.isArray(selectedLabels) && selectedLabels.map(Number).includes({{ $label->id }})">
                                    <flux:icon name="check" class="size-3 shrink-0" />
                                </template>
                                <span>{{ $label->name }}</span>
                            </button>
                        @empty
                            <div class="text-[11px] text-zinc-400 py-1 px-1">
                                No custom labels created yet. Add labels in the "Categories &amp; Labels" tab.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Form Footer -->
                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-zinc-200/80 dark:border-zinc-800/80">
                    <flux:modal.close>
                        <button type="button" 
                                class="h-9 px-4 rounded-xl text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 bg-zinc-100 dark:bg-zinc-800/60 hover:bg-zinc-200 dark:hover:bg-zinc-700/80 border border-zinc-200/80 dark:border-zinc-700/60 transition-all cursor-pointer active:scale-95">
                            Cancel
                        </button>
                    </flux:modal.close>
                    
                    <button type="submit" 
                            class="h-9 px-5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-indigo-600 via-indigo-500 to-indigo-600 hover:from-indigo-500 hover:to-indigo-400 shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 cursor-pointer flex items-center gap-1.5 active:scale-95">
                        <flux:icon name="plus-circle" class="size-4 text-white/90" />
                        <span>Save Task</span>
                    </button>
                </div>
            </form>
        </flux:modal>

        <!-- EDIT TASK MODAL -->
        <flux:modal name="edit-task-modal" class="w-[calc(100vw-2rem)] max-w-lg backdrop:backdrop-blur-md z-[200]">
            <form wire:submit.prevent="updateTask" class="space-y-4" x-data="{ showDeadline: false, showLabels: false, selectedLabels: $wire.entangle('editingTaskLabelIds') }">
                <div class="flex items-center justify-between pb-2 border-b border-zinc-200 dark:border-zinc-800">
                    <div class="flex items-center gap-2">
                        <div class="size-7 rounded-lg bg-indigo-50 dark:bg-indigo-500/20 border border-indigo-200 dark:border-indigo-500/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <flux:icon name="pencil" class="size-4" />
                        </div>
                        <flux:heading size="lg" class="font-bold">Edit Task</flux:heading>
                    </div>
                </div>

                <!-- Task Title -->
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                        <flux:icon name="pencil-square" class="size-3.5 text-indigo-500" />
                        <span>Task Title <span class="text-red-500">*</span></span>
                    </label>
                    <input type="text" wire:model="editingTaskTitle" placeholder="Task Title" required autocomplete="off"
                           class="w-full h-9 px-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500">
                </div>

                <!-- Task Description with Write / Preview Tabs (Edit Modal) -->
                <div x-data="{ tab: 'write' }" class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                            <flux:icon name="document-text" class="size-3.5 text-indigo-500" />
                            <span>Description (Optional)</span>
                        </label>
                        
                        <!-- Write / Preview Tab Switcher -->
                        <div class="flex items-center p-0.5 rounded-lg bg-zinc-200/60 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700/60 text-[11px] font-medium">
                            <button type="button" 
                                    @click="tab = 'write'" 
                                    :class="tab === 'write' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 font-bold shadow-2xs' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200'"
                                    class="px-2.5 py-0.5 rounded-md transition-all cursor-pointer">
                                Write
                            </button>
                            <button type="button" 
                                    @click="tab = 'preview'" 
                                    :class="tab === 'preview' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 font-bold shadow-2xs' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200'"
                                    class="px-2.5 py-0.5 rounded-md transition-all cursor-pointer">
                                Preview
                            </button>
                        </div>
                    </div>

                    <!-- WRITE TAB CONTENT -->
                    <div x-show="tab === 'write'">
                        <textarea wire:model.live="editingTaskDescription"
                                  placeholder="Write task details using GitHub Flavored Markdown (e.g. ## Title, **bold**, - list, | table |)..." 
                                  rows="10"
                                  class="w-full p-3 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all leading-relaxed font-mono resize-y min-h-[220px]"></textarea>
                    </div>

                    <!-- PREVIEW TAB CONTENT -->
                    <div x-show="tab === 'preview'" class="p-3.5 rounded-xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-200/80 dark:border-zinc-800 min-h-[220px] max-h-[420px] overflow-y-auto">
                        @if(trim($editingTaskDescription))
                            <div class="rich-editor-content text-xs text-zinc-800 dark:text-zinc-200 leading-relaxed font-mono">
                                {!! $this->renderFormattedDescription($editingTaskDescription) !!}
                            </div>
                        @else
                            <div class="text-zinc-400 text-xs italic font-mono">
                                <span>Nothing to preview yet. Write some Markdown content in the Write tab first!</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Grid Row: Project & Status -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                            <flux:icon name="folder" class="size-3.5 text-indigo-500" />
                            <span>Project Reference</span>
                        </label>
                        <select wire:model="editingTaskProjectId" class="w-full h-9 px-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500 cursor-pointer">
                            <option value="">Non-Project (Standalone)</option>
                            @foreach($this->projects as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                            <flux:icon name="flag" class="size-3.5 text-indigo-500" />
                            <span>Status</span>
                        </label>
                        <select wire:model="editingTaskStatus" class="w-full h-9 px-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500 cursor-pointer">
                            <option value="new">New 🔵</option>
                            <option value="on_progress">On Progress 🟡</option>
                            <option value="on_hold">On Hold 🟠</option>
                            <option value="done">Done 🟢</option>
                            <option value="archived">Archived 📦</option>
                        </select>
                    </div>
                </div>

                <!-- Quick Optional Section Toggles -->
                <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-b border-zinc-200/60 dark:border-zinc-800/60 py-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Add Optional:</span>

                    <button type="button" 
                            @click="showDeadline = !showDeadline"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold border transition-all cursor-pointer flex items-center gap-1.5 active:scale-95"
                            :class="showDeadline || $wire.editingTaskDueAt !== null
                                ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/30' 
                                : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700/60 hover:bg-zinc-200 dark:hover:bg-zinc-700'">
                        <flux:icon name="calendar-days" class="size-3.5" />
                        <span>Deadline</span>
                    </button>

                    <button type="button" 
                            @click="showLabels = !showLabels"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold border transition-all cursor-pointer flex items-center gap-1.5 active:scale-95"
                            :class="showLabels || (Array.isArray(selectedLabels) && selectedLabels.length > 0)
                                ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/30' 
                                : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700/60 hover:bg-zinc-200 dark:hover:bg-zinc-700'">
                        <flux:icon name="tag" class="size-3.5" />
                        <span>Labels</span>
                    </button>
                </div>

                <!-- Deadline (Due Date) Input -->
                <div class="space-y-1.5" x-show="showDeadline || $wire.editingTaskDueAt !== null" x-transition>
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                            <flux:icon name="calendar-days" class="size-3.5 text-indigo-500" />
                            <span>Deadline (Due Date)</span>
                        </label>
                        <button type="button" @click="showDeadline = false; $wire.set('editingTaskDueAt', null)" class="text-[10px] text-zinc-400 hover:text-red-500 transition-colors cursor-pointer">Hide</button>
                    </div>

                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                        <input type="datetime-local" 
                               wire:model="editingTaskDueAt" 
                               class="h-9 px-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500 flex-1">
                        
                        @php $editActivePreset = $this->getActiveDuePreset($editingTaskDueAt); @endphp
                        <div class="flex items-center gap-1 overflow-x-auto shrink-0" 
                             x-data="{ preset: '{{ $editActivePreset }}' }">
                            <button type="button" 
                                    @click="preset = 'today'"
                                    wire:click="setTaskDuePreset('today', true)" 
                                    class="px-2.5 py-1.5 rounded-lg text-[10px] font-semibold transition-all cursor-pointer whitespace-nowrap flex items-center gap-1 active:scale-95"
                                    :class="preset === 'today' ? 'bg-indigo-600 text-white font-bold shadow-2xs ring-2 ring-indigo-500/50' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'">
                                <template x-if="preset === 'today'">
                                    <flux:icon name="check" class="size-3 text-white" />
                                </template>
                                <span>Today</span>
                            </button>
                            <button type="button" 
                                    @click="preset = 'tomorrow'"
                                    wire:click="setTaskDuePreset('tomorrow', true)" 
                                    class="px-2.5 py-1.5 rounded-lg text-[10px] font-semibold transition-all cursor-pointer whitespace-nowrap flex items-center gap-1 active:scale-95"
                                    :class="preset === 'tomorrow' ? 'bg-indigo-600 text-white font-bold shadow-2xs ring-2 ring-indigo-500/50' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'">
                                <template x-if="preset === 'tomorrow'">
                                    <flux:icon name="check" class="size-3 text-white" />
                                </template>
                                <span>Tomorrow</span>
                            </button>
                            <button type="button" 
                                    @click="preset = 'next_week'"
                                    wire:click="setTaskDuePreset('next_week', true)" 
                                    class="px-2.5 py-1.5 rounded-lg text-[10px] font-semibold transition-all cursor-pointer whitespace-nowrap flex items-center gap-1 active:scale-95"
                                    :class="preset === 'next_week' ? 'bg-indigo-600 text-white font-bold shadow-2xs ring-2 ring-indigo-500/50' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'">
                                <template x-if="preset === 'next_week'">
                                    <flux:icon name="check" class="size-3 text-white" />
                                </template>
                                <span>Next Week</span>
                            </button>
                            <button type="button" 
                                    @click="preset = ''"
                                    wire:click="$set('editingTaskDueAt', null)" 
                                    class="px-2 py-1.5 rounded-lg text-[10px] font-semibold bg-red-500/10 text-red-500 hover:bg-red-500/20 transition-colors cursor-pointer whitespace-nowrap">
                                Clear
                            </button>
                        </div>
                    </div>
                    @error('editingTaskDueAt') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <!-- Labels Picker -->
                <div class="space-y-1.5" x-show="showLabels || (Array.isArray(selectedLabels) && selectedLabels.length > 0)" x-transition>
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                            <flux:icon name="tag" class="size-3.5 text-indigo-500" />
                            <span>Attach Labels</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-zinc-400">Select multiple</span>
                            <button type="button" @click="showLabels = false; selectedLabels = []" class="text-[10px] text-zinc-400 hover:text-red-500 transition-colors cursor-pointer">Hide</button>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-1.5 p-2 bg-zinc-50 dark:bg-zinc-950 border border-zinc-200/80 dark:border-zinc-800 rounded-xl min-h-[42px] max-h-32 overflow-y-auto">
                        @forelse($this->labels as $label)
                            @php $bgColorClass = $this->getLabelBgClass($label->color); @endphp
                            <button type="button" 
                                    wire:key="edit-task-label-chip-{{ $label->id }}"
                                    @click="
                                        let id = {{ $label->id }};
                                        if (!Array.isArray(selectedLabels)) selectedLabels = [];
                                        let idx = selectedLabels.map(Number).indexOf(id);
                                        if (idx > -1) { selectedLabels.splice(idx, 1); } 
                                        else { selectedLabels.push(id); }
                                    "
                                    class="cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border text-xs font-semibold transition-all active:scale-95"
                                    :class="(Array.isArray(selectedLabels) && selectedLabels.map(Number).includes({{ $label->id }}))
                                        ? '{{ $bgColorClass }} ring-2 ring-indigo-500 shadow-2xs' 
                                        : 'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700'">
                                <template x-if="Array.isArray(selectedLabels) && selectedLabels.map(Number).includes({{ $label->id }})">
                                    <flux:icon name="check" class="size-3 shrink-0" />
                                </template>
                                <span>{{ $label->name }}</span>
                            </button>
                        @empty
                            <div class="text-[11px] text-zinc-400 py-1 px-1">
                                No labels available.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Form Footer -->
                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-zinc-200/80 dark:border-zinc-800/80">
                    <flux:modal.close>
                        <button type="button" 
                                class="h-9 px-4 rounded-xl text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 bg-zinc-100 dark:bg-zinc-800/60 hover:bg-zinc-200 dark:hover:bg-zinc-700/80 border border-zinc-200/80 dark:border-zinc-700/60 transition-all cursor-pointer active:scale-95">
                            Cancel
                        </button>
                    </flux:modal.close>
                    
                    <button type="submit" 
                            class="h-9 px-5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-indigo-600 via-indigo-500 to-indigo-600 hover:from-indigo-500 hover:to-indigo-400 shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 cursor-pointer flex items-center gap-1.5 active:scale-95">
                        <flux:icon name="check-circle" class="size-4 text-white/90" />
                        <span>Update Task</span>
                    </button>
                </div>
            </form>
        </flux:modal>

        <!-- DETAIL TASK MODAL -->
        <flux:modal name="detail-task-modal" class="w-[calc(100vw-2rem)] max-w-xl backdrop:backdrop-blur-md z-[200]" x-on:close="$wire.set('viewingTaskId', null)">
            @if($this->viewingTask)
                @php
                    $task = $this->viewingTask;
                @endphp
                <div class="space-y-5 text-left">
                    <!-- Modal Header -->
                    <div class="flex items-start justify-between gap-4 pb-3 border-b border-zinc-200 dark:border-zinc-800 pr-6">
                        <div class="space-y-1.5 flex-1 min-w-0 text-left">
                            <!-- Status & Due Badges -->
                            <div class="flex flex-wrap items-center gap-2">
                                <!-- Status Badge -->
                                @if($task->status === 'new')
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-md bg-sky-500/10 text-sky-700 dark:text-sky-300 border border-sky-200/80 dark:border-sky-900/40">
                                        <span class="size-1.5 rounded-full bg-sky-500"></span>
                                        New Task
                                    </span>
                                @elseif($task->status === 'on_progress')
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-md bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-200/80 dark:border-amber-900/40">
                                        <span class="size-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        On Progress
                                    </span>
                                @elseif($task->status === 'on_hold')
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-md bg-rose-500/10 text-rose-700 dark:text-rose-300 border border-rose-200/80 dark:border-rose-900/40">
                                        <span class="size-1.5 rounded-full bg-rose-500"></span>
                                        On Hold
                                    </span>
                                @elseif($task->status === 'done')
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-md bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-900/40">
                                        <flux:icon name="check-circle" class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                                        Completed
                                    </span>
                                @elseif($task->status === 'archived')
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-md bg-purple-500/10 text-purple-700 dark:text-purple-300 border border-purple-200/80 dark:border-purple-900/40">
                                        <flux:icon name="archive-box" class="size-3.5 text-purple-600 dark:text-purple-400" />
                                        Archived
                                    </span>
                                @endif

                                <!-- Deadline Badge -->
                                @if($task->due_badge)
                                    @php $badge = $task->due_badge; @endphp
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-0.5 rounded-md border
                                        {{ $badge['color'] === 'rose' ? 'bg-rose-500/10 text-rose-700 dark:text-rose-300 border-rose-200/80 dark:border-rose-900/40 font-bold' : '' }}
                                        {{ $badge['color'] === 'amber' ? 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-200/80 dark:border-amber-900/40 font-bold' : '' }}
                                        {{ $badge['color'] === 'indigo' ? 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border-indigo-200/80 dark:border-indigo-900/40' : '' }}
                                        {{ $badge['color'] === 'sky' ? 'bg-sky-500/10 text-sky-700 dark:text-sky-300 border-sky-200/80 dark:border-sky-900/40' : '' }}
                                        {{ $badge['color'] === 'zinc' ? 'bg-zinc-100/60 dark:bg-zinc-800/40 text-zinc-500 dark:text-zinc-400 border-zinc-200/50 dark:border-zinc-800/80' : '' }}">
                                        <flux:icon name="{{ $badge['icon'] }}" class="size-3.5 shrink-0" />
                                        <span>{{ $badge['label'] }}</span>
                                    </span>
                                @endif
                            </div>

                            <!-- Title -->
                            <flux:heading size="xl" class="font-extrabold text-zinc-900 dark:text-zinc-100 leading-snug break-words text-left">
                                {{ $task->title }}
                            </flux:heading>
                        </div>
                    </div>

                    <!-- Meta Information Cards Grid (Project & Timeline) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-left">
                        <!-- Project Ref -->
                        <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-200/80 dark:border-zinc-800/80 space-y-1 text-left">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 block text-left">Project Reference</span>
                            <div class="flex items-center gap-2">
                                @if($task->project)
                                    <div class="size-7 rounded-lg bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
                                        <flux:icon name="folder" class="size-3.5" />
                                    </div>
                                    <div class="min-w-0 text-left">
                                        <p class="text-xs font-bold text-zinc-900 dark:text-zinc-100 truncate text-left">{{ $task->project->name }}</p>
                                        @if($task->project->client_name)
                                            <p class="text-[10px] text-zinc-500 truncate text-left">Client: {{ $task->project->client_name }}</p>
                                        @endif
                                    </div>
                                @else
                                    <div class="size-7 rounded-lg bg-zinc-200/50 dark:bg-zinc-800 border border-zinc-300/50 dark:border-zinc-700/50 flex items-center justify-center text-zinc-400 shrink-0">
                                        <flux:icon name="folder-minus" class="size-3.5" />
                                    </div>
                                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 italic text-left">Non-Project (Standalone)</p>
                                @endif
                            </div>
                        </div>

                        <!-- Due Date / Timeline -->
                        <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-200/80 dark:border-zinc-800/80 space-y-1 text-left">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 block text-left">Deadline &amp; Timeline</span>
                            <div class="flex items-center gap-2">
                                <div class="size-7 rounded-lg bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
                                    <flux:icon name="calendar-days" class="size-3.5" />
                                </div>
                                <div class="min-w-0 text-left">
                                    @if($task->due_at)
                                        <p class="text-xs font-bold text-zinc-900 dark:text-zinc-100 text-left">{{ $task->due_at->format('d M Y, H:i') }}</p>
                                        <p class="text-[10px] text-zinc-500 text-left">{{ $task->due_at->diffForHumans() }}</p>
                                    @else
                                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 italic text-left">No deadline set</p>
                                        <p class="text-[10px] text-zinc-400 text-left">Created: {{ $task->created_at->format('d M Y') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description Section Card -->
                    <div class="p-3.5 sm:p-4 rounded-2xl bg-zinc-50/70 dark:bg-zinc-950/70 border border-zinc-200/80 dark:border-zinc-800/80 space-y-2 !text-left text-left" style="text-align: left !important;">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 block !text-left text-left" style="text-align: left !important;">Description</span>
                        @if(trim($task->description))
                            <div class="rich-editor-content text-xs text-zinc-800 dark:text-zinc-200 leading-relaxed !text-left text-left w-full max-h-60 overflow-y-auto" style="text-align: left !important;">
                                {!! $this->renderFormattedDescription($task->description) !!}
                            </div>
                        @else
                            <p class="text-xs text-zinc-400 italic !text-left text-left" style="text-align: left !important;">No description provided for this task.</p>
                        @endif
                    </div>

                    <!-- Labels Section Card -->
                    <div class="p-3.5 sm:p-4 rounded-2xl bg-zinc-50/70 dark:bg-zinc-950/70 border border-zinc-200/80 dark:border-zinc-800/80 space-y-2 text-left">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 block text-left">Attached Labels</span>
                        @if($task->labels->count() > 0)
                            <div class="flex flex-wrap gap-1.5 pt-0.5">
                                @foreach($task->labels as $label)
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-lg border {{ $this->getLabelBgClass($label->color) }}">
                                        <flux:icon name="tag" class="size-3 opacity-70" />
                                        <span>{{ $label->name }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-zinc-400 italic text-left">No labels attached to this task.</p>
                        @endif
                    </div>

                    <!-- Trello-Style Checklist Section Card -->
                    @php
                        $checklists = $task->checklists()->orderBy('position')->orderBy('id')->get();
                        $stats = $task->checklist_stats;
                    @endphp
                    <div class="p-3.5 sm:p-4 rounded-2xl bg-zinc-50/70 dark:bg-zinc-950/70 border border-zinc-200/80 dark:border-zinc-800/80 space-y-3 text-left">
                        <!-- Checklist Header -->
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 block text-left">Checklist</span>
                                @if($stats)
                                    <span class="text-[11px] font-mono font-bold px-2 py-0.5 rounded-md border
                                          {{ $stats['is_all_completed'] ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30' : 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/30' }}">
                                        {{ $stats['completed'] }}/{{ $stats['total'] }} ({{ $stats['percent'] }}%)
                                    </span>
                                @endif
                            </div>

                            @if($stats && $stats['completed'] > 0)
                                <button type="button" 
                                        wire:click="$toggle('hideCheckedItems')"
                                        class="text-[11px] font-medium text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200 px-2 py-1 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer flex items-center gap-1">
                                    <flux:icon name="{{ $hideCheckedItems ? 'eye' : 'eye-slash' }}" class="size-3.5 opacity-70" />
                                    <span>{{ $hideCheckedItems ? 'Show checked items' : 'Hide checked items' }}</span>
                                </button>
                            @endif
                        </div>

                        <!-- Progress Bar (Trello Style - Smooth Height & Opacity Transition) -->
                        @if($stats)
                            <div class="w-full bg-zinc-200/80 dark:bg-zinc-800/80 rounded-full overflow-hidden transition-all duration-300 ease-out {{ $stats['percent'] > 0 ? 'h-2 p-0.5 opacity-100' : 'h-0 p-0 opacity-0' }}">
                                <div class="h-full rounded-full bg-emerald-500 transition-all duration-500 ease-out {{ $stats['is_all_completed'] ? 'shadow-emerald-500/50 shadow-xs' : '' }}"
                                     style="width: {{ $stats['percent'] }}%;"></div>
                            </div>
                        @endif

                        <!-- Checklist Items List -->
                        <div wire:key="detail-checklist-container-{{ $task->id }}-{{ $checklists->count() }}"
                             wire:ignore
                             class="space-y-1.5 pt-1 select-none"
                             x-data="{
                                 init() {
                                     let el = $el;
                                     if (window.Sortable && !el._sortable) {
                                         el._sortable = new Sortable(el, {
                                             animation: 150,
                                             handle: '.drag-handle',
                                             ghostClass: 'opacity-30',
                                             chosenClass: 'border-indigo-500',
                                             onEnd: (evt) => {
                                                 if (evt.oldIndex !== undefined && evt.newIndex !== undefined && evt.oldIndex !== evt.newIndex) {
                                                     let order = Array.from(el.querySelectorAll('[data-checklist-id]')).map(item => parseInt(item.getAttribute('data-checklist-id')));
                                                     if (order.length > 0) {
                                                         $wire.saveChecklistOrder(order);
                                                     }
                                                 }
                                             }
                                         });
                                     }
                                 }
                             }">
                            @forelse($checklists as $item)
                                @if(! $hideCheckedItems || ! $item->is_completed)
                                    <div wire:key="checklist-item-{{ $item->id }}"
                                         data-checklist-id="{{ $item->id }}"
                                         class="group flex items-center justify-between gap-3 p-2.5 rounded-xl border transition-all duration-200 hover:border-indigo-500/50
                                                {{ $item->is_completed ? 'bg-zinc-100/60 dark:bg-zinc-900/40 border-zinc-200/60 dark:border-zinc-800/60' : 'bg-white dark:bg-zinc-950 border-zinc-200/80 dark:border-zinc-800/80' }}">
                                        
                                        <div class="flex items-center gap-2.5 flex-1 min-w-0">
                                            <flux:icon name="bars-3" class="drag-handle size-3.5 text-zinc-400 opacity-40 group-hover:opacity-100 shrink-0 cursor-grab active:cursor-grabbing" />
                                            
                                            <label class="flex items-center gap-2.5 flex-1 min-w-0 cursor-pointer select-none">
                                                <input type="checkbox"
                                                       wire:click="toggleChecklistItem({{ $item->id }})"
                                                       {{ $item->is_completed ? 'checked' : '' }}
                                                       class="size-4 rounded border-zinc-300 dark:border-zinc-700 text-emerald-600 focus:ring-emerald-500 cursor-pointer transition-all">
                                                
                                                <span class="text-xs transition-all duration-200 break-words flex-1
                                                             {{ $item->is_completed ? 'line-through text-zinc-400 dark:text-zinc-500 font-normal' : 'text-zinc-800 dark:text-zinc-200 font-medium' }}">
                                                    {{ $item->title }}
                                                </span>
                                            </label>
                                        </div>

                                        <button type="button" 
                                                wire:click="deleteChecklistItem({{ $item->id }})"
                                                class="opacity-100 lg:opacity-0 lg:group-hover:opacity-100 p-1 rounded-md text-zinc-400 hover:text-red-500 hover:bg-red-500/10 transition-all cursor-pointer shrink-0"
                                                title="Delete point">
                                            <flux:icon name="x-mark" class="size-3.5" />
                                        </button>
                                    </div>
                                @endif
                            @empty
                                <p class="text-xs text-zinc-400 italic py-1 text-left">No sub-task checklist items added yet.</p>
                            @endforelse
                        </div>

                        <!-- Add Item Form inside Detail Modal -->
                        <form wire:submit.prevent="addChecklistItemToDetail" class="flex items-center gap-2 pt-1">
                            <input type="text" 
                                   wire:model="newDetailChecklistInput" 
                                   placeholder="Add a checklist point (e.g. update db)..." 
                                   autocomplete="off"
                                   class="h-8 px-3 rounded-xl bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500 flex-1">
                            
                            <button type="submit" 
                                    class="h-8 px-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold cursor-pointer active:scale-95 transition-all flex items-center gap-1 shrink-0">
                                <flux:icon name="plus" class="size-3 text-white" />
                                <span>Add Point</span>
                            </button>
                        </form>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex items-center justify-between pt-4 border-t border-zinc-200/80 dark:border-zinc-800/80">
                        <span class="text-[10px] text-zinc-400 font-mono">Created {{ $task->created_at->format('d M Y H:i') }}</span>

                        <div class="flex items-center gap-2.5">
                            <flux:modal.close>
                                <button type="button" 
                                        class="h-9 px-4 rounded-xl text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 bg-zinc-100 dark:bg-zinc-800/60 hover:bg-zinc-200 dark:hover:bg-zinc-700/80 border border-zinc-200/80 dark:border-zinc-700/60 transition-all cursor-pointer active:scale-95">
                                    Close
                                </button>
                            </flux:modal.close>
                            
                            <button type="button"
                                    wire:click="editTaskFromDetail"
                                    class="h-9 px-5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-indigo-600 via-indigo-500 to-indigo-600 hover:from-indigo-500 hover:to-indigo-400 shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 cursor-pointer flex items-center gap-1.5 active:scale-95">
                                <flux:icon name="pencil-square" class="size-4 text-white/90" />
                                <span>Edit Task</span>
                            </button>
                        </div>
                    </div>
                </div>
            @else
                <div class="py-12 text-center text-zinc-400 dark:text-zinc-500 flex flex-col items-center justify-center gap-3">
                    <flux:icon name="arrow-path" class="size-7 animate-spin text-indigo-500" />
                    <span class="text-xs font-semibold text-zinc-600 dark:text-zinc-300">Loading task details...</span>
                </div>
            @endif
        </flux:modal>

        <!-- ARCHIVED TASKS MODAL -->
        <flux:modal name="archived-tasks-modal" class="w-[calc(100vw-2rem)] max-w-4xl backdrop:backdrop-blur-md z-[200]" x-on:close="$wire.set('archiveSearchQuery', '')">
            <div class="space-y-4 text-left">
                <!-- Modal Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-purple-200/60 dark:border-purple-900/30 pr-6">
                    <div class="flex items-center gap-3">
                        <div class="size-9 rounded-xl bg-purple-500/10 border border-purple-200/60 dark:border-purple-900/40 flex items-center justify-center text-purple-600 dark:text-purple-400 shrink-0">
                            <flux:icon name="archive-box" class="size-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <flux:heading size="lg" class="font-bold">Archived Tasks Repository</flux:heading>
                                <span class="text-xs font-mono font-bold px-2.5 py-0.5 rounded-full bg-purple-500/10 text-purple-700 dark:text-purple-300 border border-purple-200/80 dark:border-purple-900/40">
                                    {{ $this->archivedTasks->count() }} Tasks
                                </span>
                            </div>
                            <flux:subheading size="sm" class="text-zinc-500 dark:text-zinc-400">
                                Repository for inactive tasks. You can restore them to active status anytime.
                            </flux:subheading>
                        </div>
                    </div>

                    <!-- Dedicated Archive Search Input (With spacing from modal close button) -->
                    <div class="relative min-w-[220px] sm:mr-4">
                        <flux:icon name="magnifying-glass" class="size-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400" />
                        <input type="text" 
                               wire:model.live.debounce.200ms="archiveSearchQuery" 
                               placeholder="Search archived tasks..." 
                               class="h-8 pl-8 pr-7 w-full rounded-xl bg-zinc-100 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-purple-500 transition-all">
                        @if(!empty($archiveSearchQuery))
                            <button type="button" 
                                    wire:click="$set('archiveSearchQuery', '')" 
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-0.5 cursor-pointer"
                                    title="Clear search">
                                <flux:icon name="x-mark" class="size-3" />
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Archived Grid -->
                <div class="max-h-[60vh] overflow-y-auto pr-1">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
                        @forelse($this->archivedTasks as $task)
                            <div wire:key="archived-modal-card-{{ $task->id }}" 
                                 class="bg-zinc-50/80 dark:bg-zinc-950/80 border border-zinc-200/80 dark:border-zinc-800 rounded-2xl p-4 flex flex-col justify-between gap-3 group relative overflow-hidden">
                                
                                <div class="space-y-2.5">
                                    <div class="flex items-start justify-between gap-2">
                                        <h4 class="text-xs font-bold text-zinc-900 dark:text-zinc-100 leading-snug line-clamp-2">{{ $task->title }}</h4>
                                        
                                        <div class="flex items-center gap-1 shrink-0 opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity">
                                            <flux:modal.trigger name="edit-task-modal">
                                                <button wire:click="editTask({{ $task->id }})" class="p-1 rounded-md text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-200/60 dark:hover:bg-zinc-800 transition-colors cursor-pointer" title="Edit Task">
                                                    <flux:icon name="pencil" class="size-3.5" />
                                                </button>
                                            </flux:modal.trigger>
                                            <flux:modal.trigger name="delete-task-{{ $task->id }}">
                                                <button class="p-1 rounded-md text-red-400 hover:text-red-600 hover:bg-red-500/10 transition-colors cursor-pointer" title="Delete Task">
                                                    <flux:icon name="trash" class="size-3.5" />
                                                </button>
                                            </flux:modal.trigger>
                                        </div>
                                    </div>

                                    @if($task->description)
                                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 line-clamp-2 leading-relaxed">{{ $this->getCleanDescription($task->description) }}</p>
                                    @endif

                                    <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                                        @if($task->project)
                                            <span class="inline-flex items-center gap-1 text-[10px] font-medium bg-zinc-100 dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-800 max-w-[140px] truncate">
                                                <flux:icon name="folder" class="size-2.5 text-zinc-400 shrink-0" />
                                                <span class="truncate">{{ $task->project->name }}</span>
                                            </span>
                                        @endif

                                        @foreach($task->labels as $label)
                                            <span class="inline-flex items-center text-[10px] font-semibold px-2 py-0.5 rounded-md border {{ $this->getLabelBgClass($label->color) }}">
                                                {{ $label->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Restore Controls -->
                                <div class="flex flex-col gap-1.5 pt-3 border-t border-zinc-200/60 dark:border-zinc-800/60 mt-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] text-purple-600 dark:text-purple-400 font-mono font-bold">Restore to:</span>
                                        <flux:modal.trigger name="detail-task-modal">
                                            <button wire:click="showTaskDetail({{ $task->id }})" 
                                                    class="text-[10px] text-indigo-500 hover:underline cursor-pointer">
                                                View Details
                                            </button>
                                        </flux:modal.trigger>
                                    </div>
                                    <div class="grid grid-cols-2 gap-1.5">
                                        <button wire:click="updateTaskStatus({{ $task->id }}, 'on_hold')" 
                                                wire:loading.attr="disabled"
                                                class="px-2 py-1 rounded-lg text-[10px] font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 hover:bg-rose-500/20 border border-rose-500/20 transition-all cursor-pointer flex items-center justify-center gap-1 active:scale-95 disabled:opacity-50" 
                                                title="Restore to On Hold">
                                            <span>On Hold 🟠</span>
                                        </button>
                                        <button wire:click="updateTaskStatus({{ $task->id }}, 'new')" 
                                                wire:loading.attr="disabled"
                                                class="px-2 py-1 rounded-lg text-[10px] font-bold bg-sky-500/10 text-sky-600 dark:text-sky-400 hover:bg-sky-500/20 border border-sky-500/20 transition-all cursor-pointer flex items-center justify-center gap-1 active:scale-95 disabled:opacity-50" 
                                                title="Restore to New">
                                            <span>New 🔵</span>
                                        </button>
                                        <button wire:click="updateTaskStatus({{ $task->id }}, 'on_progress')" 
                                                wire:loading.attr="disabled"
                                                class="px-2 py-1 rounded-lg text-[10px] font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 border border-amber-500/20 transition-all cursor-pointer flex items-center justify-center gap-1 active:scale-95 disabled:opacity-50" 
                                                title="Restore to On Progress">
                                            <span>Progress 🟡</span>
                                        </button>
                                        <button wire:click="updateTaskStatus({{ $task->id }}, 'done')" 
                                                wire:loading.attr="disabled"
                                                class="px-2 py-1 rounded-lg text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/20 border border-emerald-500/20 transition-all cursor-pointer flex items-center justify-center gap-1 active:scale-95 disabled:opacity-50" 
                                                title="Restore to Done">
                                            <span>Done 🟢</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full py-12 text-center text-xs text-zinc-400 dark:text-zinc-500 border border-dashed border-purple-500/20 rounded-2xl flex flex-col items-center justify-center gap-2">
                                <flux:icon name="archive-box" class="size-8 opacity-40 text-purple-500" />
                                <p class="font-medium">No archived tasks found in repository.</p>
                                <p class="text-[11px] text-zinc-400">Tasks you archive will be safely stored here.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end pt-4 border-t border-zinc-200/80 dark:border-zinc-800/80">
                    <flux:modal.close>
                        <button type="button" 
                                wire:click="$set('archiveSearchQuery', '')"
                                class="h-9 px-4 rounded-xl text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 bg-zinc-100 dark:bg-zinc-800/60 hover:bg-zinc-200 dark:hover:bg-zinc-700/80 border border-zinc-200/80 dark:border-zinc-700/60 transition-all cursor-pointer active:scale-95">
                            Close Repository
                        </button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>

    </div>

    <!-- TAB 2: PROJECTS & CLIENTS -->
    <div x-show="activeTab === 'projects'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="size-7 rounded-lg bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                    <flux:icon name="briefcase" class="size-4 text-zinc-700 dark:text-zinc-300" />
                </div>
                <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-100">Projects &amp; Clients Portfolio</h3>
                <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-[10px] font-mono font-medium px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60">
                    {{ $this->projects->count() }} Total
                </span>
            </div>
        </div>

        <!-- Add Project Card -->
        <div class="border border-zinc-200/80 dark:border-zinc-800 rounded-2xl bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl p-4.5 shadow-xs relative overflow-hidden group hover:border-zinc-400 dark:hover:border-zinc-700 transition-all">
            <form wire:submit.prevent="addProject" class="space-y-3 relative z-10">
                <div class="relative w-full">
                    <input type="text" wire:model="projectName" placeholder="Project Name" required autocomplete="off"
                           class="w-full h-10 pl-9 pr-3 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500 shadow-2xs transition-all">
                    <flux:icon name="briefcase" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-400 pointer-events-none" />
                </div>
                <div class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center">
                    <div class="flex-1 w-full">
                        <div class="relative w-full">
                            <input type="text" wire:model="projectClient" placeholder="Client Name (Optional)" autocomplete="off"
                                   class="w-full h-10 pl-9 pr-3 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500 shadow-2xs transition-all">
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
                                       class="w-full h-9 pl-8 pr-3 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500">
                                <flux:icon name="briefcase" class="absolute left-2.5 top-1/2 -translate-y-1/2 size-3.5 text-zinc-400 pointer-events-none" />
                            </div>
                            <div class="relative w-full">
                                <input type="text" wire:model="editingProjectClient" placeholder="Client Name (Optional)" autocomplete="off"
                                       class="w-full h-9 pl-8 pr-3 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500">
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
                                    <div class="font-bold text-sm text-zinc-900 dark:text-zinc-100 truncate flex items-center gap-2">
                                        <span>{{ $project->name }}</span>
                                        <span class="text-[10px] font-mono font-medium px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20">
                                            {{ $project->tasks_count }} Tasks
                                        </span>
                                    </div>
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
                            
                            <div class="opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity duration-150 flex items-center gap-1 shrink-0">
                                <flux:button wire:click="editProject({{ $project->id }})" variant="ghost" size="xs" icon="pencil" square class="cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800/60 text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200 active:scale-95" />
                                <flux:modal.trigger name="delete-project-{{ $project->id }}">
                                    <flux:button variant="ghost" size="xs" icon="trash" square class="text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/20 cursor-pointer active:scale-95" />
                                </flux:modal.trigger>
                            </div>
                        </div>
                    @endif

                    <flux:modal name="delete-project-{{ $project->id }}" class="w-[calc(100vw-2rem)] max-w-md backdrop:backdrop-blur-md z-[200]">
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="size-10 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 flex items-center justify-center text-red-600 dark:text-red-500 shrink-0">
                                    <flux:icon name="trash" class="size-5" />
                                </div>
                                <div>
                                    <flux:heading size="lg" class="font-bold">Delete Project?</flux:heading>
                                    <flux:text class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                        Are you sure you want to delete <strong>{{ $project->name }}</strong>? All linked activity logs and task references will be removed.
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

    <!-- TAB 3: CATEGORIES & TASK LABELS -->
    <div x-show="activeTab === 'categories'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Tracker Categories Section -->
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="size-7 rounded-lg bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                        <flux:icon name="tag" class="size-4 text-zinc-700 dark:text-zinc-300" />
                    </div>
                    <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-100">Tracker Categories</h3>
                    <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-[10px] font-mono font-medium px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60">
                        {{ $this->categories->count() }} Total
                    </span>
                </div>
            </div>

            <!-- Add Category Card -->
            <div class="border border-zinc-200/80 dark:border-zinc-800 rounded-2xl bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl p-4.5 shadow-xs relative overflow-hidden group hover:border-zinc-400 dark:hover:border-zinc-700 transition-all">
                <form wire:submit.prevent="addCategory" class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center relative z-10">
                    <div class="flex-1 w-full">
                        <div class="relative w-full">
                            <input type="text" wire:model="categoryName" placeholder="Category Name" required autocomplete="off"
                                   class="w-full h-10 pl-9 pr-3 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500 shadow-2xs transition-all">
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

            <!-- Categories List Card -->
            <div class="bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800 rounded-2xl overflow-hidden shadow-xs divide-y divide-zinc-200/50 dark:divide-zinc-800/50">
                @forelse($this->categories as $category)
                    <div wire:key="category-{{ $category->id }}" class="p-3.5 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors group relative">
                        @if($this->editingCategoryId === $category->id)
                            <form wire:submit.prevent="updateCategory" class="space-y-2.5 p-1">
                                <div class="relative w-full">
                                    <input type="text" wire:model="editingCategoryName" placeholder="Category Name" required autocomplete="off"
                                           class="w-full h-9 pl-8 pr-3 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500">
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
                                        <div class="font-bold text-sm text-zinc-900 dark:text-zinc-100 truncate">{{ $category->name }}</div>
                                        <div class="text-[10px] font-mono font-medium text-zinc-600 dark:text-zinc-400 mt-0.5 inline-flex bg-zinc-100 dark:bg-zinc-800/60 px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-800">
                                            Activity Tracker Tag
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity duration-150 flex items-center gap-1 shrink-0">
                                    <flux:button wire:click="editCategory({{ $category->id }})" variant="ghost" size="xs" icon="pencil" square class="cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800/60 text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200" />
                                    <flux:modal.trigger name="delete-category-{{ $category->id }}">
                                        <flux:button variant="ghost" size="xs" icon="trash" square class="text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/20 cursor-pointer" />
                                    </flux:modal.trigger>
                                </div>
                            </div>
                        @endif

                        <flux:modal name="delete-category-{{ $category->id }}" class="w-[calc(100vw-2rem)] max-w-md backdrop:backdrop-blur-md z-[200]">
                            <div class="space-y-4">
                                <div class="flex items-center gap-3">
                                    <div class="size-10 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 flex items-center justify-center text-red-600 dark:text-red-500 shrink-0">
                                        <flux:icon name="trash" class="size-5" />
                                    </div>
                                    <div>
                                        <flux:heading size="lg" class="font-bold">Delete Category?</flux:heading>
                                        <flux:text class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                            Are you sure you want to delete <strong>{{ $category->name }}</strong>?
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
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Task Dynamic Labels Section -->
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="size-7 rounded-lg bg-amber-500/10 dark:bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
                        <flux:icon name="bookmark" class="size-4 text-amber-600 dark:text-amber-400" />
                    </div>
                    <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-100">Dynamic Task Labels</h3>
                    <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-[10px] font-mono font-medium px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60">
                        {{ $this->labels->count() }} Total
                    </span>
                </div>

                <!-- Quick Add Suggested Label -->
                @if(!$this->labels->pluck('name')->contains('belum ada open project'))
                    <button wire:click="addPresetLabel('belum ada open project', 'amber')" type="button" class="text-[11px] font-semibold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-500/10 px-2.5 py-1 rounded-lg border border-amber-200 dark:border-amber-500/30 hover:bg-amber-100 dark:hover:bg-amber-500/20 transition-all flex items-center gap-1 cursor-pointer">
                        <flux:icon name="plus" class="size-3" />
                        <span>+ Add "belum ada open project"</span>
                    </button>
                @endif
            </div>

            <!-- Add Label Form Card -->
            <div class="border border-zinc-200/80 dark:border-zinc-800 rounded-2xl bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl p-4.5 shadow-xs relative overflow-hidden group hover:border-zinc-400 dark:hover:border-zinc-700 transition-all">
                <form wire:submit.prevent="addLabel" class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center relative z-10">
                    <div class="flex-1">
                        <input type="text" wire:model="labelName" placeholder="Label Name (e.g. belum ada open project)" required autocomplete="off"
                               class="w-full h-10 px-3 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-amber-500 shadow-2xs transition-all">
                    </div>
                    
                    <!-- Color Picker -->
                    <select wire:model="labelColor" class="h-10 px-2.5 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-amber-500 cursor-pointer shrink-0">
                        <option value="amber">Amber 🟡</option>
                        <option value="indigo">Indigo 🔵</option>
                        <option value="emerald">Emerald 🟢</option>
                        <option value="rose">Rose 🔴</option>
                        <option value="sky">Sky 🩵</option>
                        <option value="purple">Purple 🟣</option>
                        <option value="zinc">Zinc ⚪</option>
                    </select>

                    <button type="submit" class="w-full sm:w-auto cursor-pointer bg-amber-600 hover:bg-amber-500 text-white font-semibold rounded-xl px-4 py-2.5 text-xs border border-amber-500/80 active:scale-95 transition-all shadow-xs shadow-amber-500/20 flex items-center justify-center gap-1.5 shrink-0">
                        <flux:icon name="plus" class="size-3.5 text-white" />
                        <span>Add Label</span>
                    </button>
                </form>

                @if(session()->has('label_message'))
                    <div class="mt-3 text-xs font-semibold text-amber-700 dark:text-amber-400 flex items-center gap-1.5 bg-amber-50 dark:bg-amber-500/10 p-2 rounded-lg border border-amber-200 dark:border-amber-500/20">
                        <flux:icon name="check-circle" class="size-4" />
                        <span>{{ session('label_message') }}</span>
                    </div>
                @endif
            </div>

            <!-- Labels Grid List -->
            <div class="bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800 rounded-2xl p-4 shadow-xs">
                <div class="flex flex-wrap gap-2">
                    @forelse($this->labels as $label)
                        <div wire:key="label-tag-{{ $label->id }}" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border text-xs font-semibold transition-all group {{ $this->getLabelBgClass($label->color) }}">
                            <span>{{ $label->name }}</span>
                            <span class="text-[10px] font-mono opacity-80">({{ $label->tasks_count }})</span>
                            <button wire:click="deleteLabel({{ $label->id }})" class="opacity-100 lg:opacity-60 lg:group-hover:opacity-100 hover:text-red-600 transition-opacity ml-1 cursor-pointer" title="Delete label">
                                <flux:icon name="x-mark" class="size-3.5" />
                            </button>
                        </div>
                    @empty
                        <div class="w-full text-xs text-neutral-400 text-center py-8 flex flex-col items-center gap-2 bg-zinc-50/50 dark:bg-zinc-950/20 rounded-xl">
                            <div class="size-10 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-500">
                                <flux:icon name="bookmark" class="size-5" />
                            </div>
                            <span class="font-semibold text-zinc-700 dark:text-zinc-300">No task labels created yet.</span>
                            <span class="text-[11px] text-zinc-500 dark:text-zinc-400">Add custom labels like "belum ada open project" using the form above.</span>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
    </div>
</div>

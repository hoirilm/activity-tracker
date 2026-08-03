<?php

use Livewire\Component;
use App\Models\Activity;
use App\Models\Project;
use App\Models\Category;
use App\Models\Task;
use Carbon\Carbon;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ActivitiesExport;
use App\Imports\ActivitiesImport;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    use WithFileUploads;

    public $detail = '';
    public $project_id;
    public $category_id;
    public $task_id = null;
    public $is_parallel = false;
    public $importFile;
    public $startDate;
    public $endDate;
    public $searchQuery = '';
    public $daysLimit = 7;
    
    public $editingActivityId = null;
    public $editDetail;
    public $editTaskId = null;
    public $editStartTime;
    public $editEndTime;
    public $showEditModal = false;

    public function mount()
    {
        $this->project_id = Project::where('user_id', auth()->id())->first()->id ?? null;
        $this->category_id = Category::where('user_id', auth()->id())->first()->id ?? null;
    }

    public function updatedProjectId($val)
    {
        if ($this->task_id) {
            $task = auth()->user()->tasks()->find($this->task_id);
            if ($task && $task->project_id && $task->project_id != $val) {
                $this->task_id = null;
            }
        }
    }

    public function updatedTaskId($val)
    {
        if ($val) {
            $task = auth()->user()->tasks()->find($val);
            if ($task) {
                if ($task->project_id) {
                    $this->project_id = $task->project_id;
                }
                if (empty(trim($this->detail))) {
                    $this->detail = $task->title;
                }
            }
        }
    }

    public function getProjectsProperty()
    {
        return Project::where('user_id', auth()->id())->get();
    }

    public function getCategoriesProperty()
    {
        return Category::where('user_id', auth()->id())->get();
    }

    public function getTasksProperty()
    {
        $query = auth()->user()->tasks()
            ->with('project')
            ->whereIn('status', [Task::STATUS_NEW, Task::STATUS_ON_PROGRESS, Task::STATUS_ON_HOLD]);

        if ($this->project_id) {
            $query->where(function ($q) {
                $q->where('project_id', $this->project_id)
                  ->orWhereNull('project_id');
            });
        }

        return $query->latest()->get();
    }

    public function loadMore()
    {
        $this->daysLimit += 7;
    }

    public function getActivitiesDataProperty()
    {
        $query = auth()->user()->activities()
            ->whereNotNull('end_time');

        if ($this->startDate) {
            $query->whereDate('start_time', '>=', $this->startDate);
        }
        
        if ($this->endDate) {
            $query->whereDate('start_time', '<=', $this->endDate);
        }

        if (!empty($this->searchQuery)) {
            $query->where('detail', 'ilike', '%' . $this->searchQuery . '%');
        }

        $dateQuery = clone $query;
        $dates = $dateQuery->selectRaw('DATE(start_time) as date')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit($this->daysLimit + 1)
            ->pluck('date');

        $hasMore = $dates->count() > $this->daysLimit;
        
        $datesToFetch = $dates->take($this->daysLimit);

        if ($datesToFetch->isEmpty()) {
            return [
                'activities' => collect(),
                'hasMore' => false
            ];
        }

        $activitiesQuery = clone $query;
        $activities = $activitiesQuery->with(['project', 'category', 'task'])
            ->whereIn(DB::raw('DATE(start_time)'), $datesToFetch)
            ->orderBy('start_time', 'desc')
            ->get()
            ->groupBy(function($activity) {
                return $activity->start_time->format('Y-m-d');
            });

        return [
            'activities' => $activities,
            'hasMore' => $hasMore
        ];
    }

    public function getRunningActivitiesProperty()
    {
        return auth()->user()->activities()->with(['project', 'category', 'task'])
            ->whereNull('end_time')
            ->orderBy('start_time', 'desc')
            ->get();
    }

    public function startActivity()
    {
        $this->validate([
            'detail' => 'required|string',
            'project_id' => 'required|exists:projects,id',
            'category_id' => 'required|exists:categories,id',
            'task_id' => 'nullable|exists:tasks,id',
        ]);

        if (!$this->is_parallel) {
            // Stop all non-parallel running activities
            auth()->user()->activities()->whereNull('end_time')
                ->where('is_parallel', false)
                ->update(['end_time' => now()]);
        }

        auth()->user()->activities()->create([
            'project_id' => $this->project_id,
            'category_id' => $this->category_id,
            'task_id' => $this->task_id ?: null,
            'detail' => $this->detail,
            'is_parallel' => $this->is_parallel,
            'start_time' => now(),
        ]);

        // Auto update task status to on_progress if currently new or on_hold
        if ($this->task_id) {
            $task = auth()->user()->tasks()->find($this->task_id);
            if ($task && in_array($task->status, [Task::STATUS_NEW, Task::STATUS_ON_HOLD])) {
                $task->update(['status' => Task::STATUS_ON_PROGRESS]);
            }
        }

        $this->reset(['detail', 'task_id']);
    }

    public function stopActivity($id)
    {
        $activity = auth()->user()->activities()->find($id);
        if ($activity && !$activity->end_time) {
            $activity->update(['end_time' => now()]);
        }
    }

    public function export($start = null, $end = null)
    {
        $userName = \Illuminate\Support\Str::slug(auth()->user()->name, '_');
        $date = now()->format('Ymd');
        $filename = "backup_activity_{$userName}_{$date}.xlsx";
        
        return Excel::download(new ActivitiesExport($start, $end), $filename);
    }

    public function import()
    {
        try {
            $this->validate([
                'importFile' => 'required|file|mimes:xlsx,csv,xls|max:10240',
            ], [
                'importFile.required' => 'Please select an Excel (.xlsx or .csv) file first.',
                'importFile.mimes' => 'Unsupported file format. Please upload a .xlsx or .csv file.',
                'importFile.max' => 'File size is too large. Maximum 10 MB.',
            ]);

            $fileName = $this->importFile ? $this->importFile->getClientOriginalName() : 'Excel';

            Excel::import(new ActivitiesImport, $this->importFile);
            $this->importFile = null;

            session()->flash('import_status', [
                'type' => 'success',
                'title' => 'Import Successful! 🎉',
                'message' => "File '{$fileName}' has been successfully imported and added to your activity history.",
            ]);

            auth()->user()->notifications()->create([
                'title' => '📥 Activity Import Successful',
                'body' => "Activity file '{$fileName}' has been successfully imported.",
                'type' => 'success',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errorMsg = $e->validator->errors()->first('importFile');
            session()->flash('import_status', [
                'type' => 'error',
                'title' => 'Invalid File Format ⚠️',
                'message' => $errorMsg ?: 'Please double-check your uploaded file.',
            ]);
        } catch (\Throwable $e) {
            $this->importFile = null;
            session()->flash('import_status', [
                'type' => 'error',
                'title' => 'Import Failed ❌',
                'message' => 'An error occurred while processing the Excel file. Please ensure column headers match (project, category, detail, start_time, end_time).',
            ]);

            auth()->user()->notifications()->create([
                'title' => '⚠️ Activity Import Failed',
                'body' => 'Excel file import failed. Please ensure the column formats match requirements.',
                'type' => 'danger',
            ]);
        }
    }

    public function editActivity($id)
    {
        $activity = auth()->user()->activities()->find($id);
        if ($activity) {
            $this->editingActivityId = $activity->id;
            $this->editDetail = $activity->detail;
            $this->editTaskId = $activity->task_id;
            $this->editStartTime = $activity->start_time->format('Y-m-d\TH:i');
            $this->editEndTime = $activity->end_time ? $activity->end_time->format('Y-m-d\TH:i') : null;
            $this->showEditModal = true;
        }
    }

    public function updateActivity()
    {
        $this->validate([
            'editDetail' => 'required|string',
            'editTaskId' => 'nullable|exists:tasks,id',
            'editStartTime' => 'required|date',
            'editEndTime' => 'required|date|after_or_equal:editStartTime',
        ]);

        $activity = auth()->user()->activities()->find($this->editingActivityId);
        if ($activity) {
            $activity->update([
                'detail' => $this->editDetail,
                'task_id' => $this->editTaskId ?: null,
                'start_time' => Carbon::parse($this->editStartTime),
                'end_time' => Carbon::parse($this->editEndTime),
            ]);
            
            $this->showEditModal = false;
            $this->editingActivityId = null;
        }
    }

    public function cancelEdit()
    {
        $this->showEditModal = false;
        $this->editingActivityId = null;
    }

    public function deleteActivity($id)
    {
        $activity = auth()->user()->activities()->find($id);
        if ($activity) {
            $activity->delete();
        }
    }
};
?>

<div class="flex h-full w-full flex-col gap-5 md:gap-6 px-3 sm:px-4 py-2 text-neutral-900 dark:text-neutral-100 max-w-6xl mx-auto mt-0 md:mt-4 pb-32 md:pb-8" x-data="{ scrolled: false }" @scroll.window="scrolled = (window.pageYOffset > 300)">
    
    <!-- Header -->
    <div class="border-b border-zinc-200/80 dark:border-zinc-800/80 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 animate-page-entrance">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                <div class="size-8.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                    <flux:icon name="clock" class="size-4.5 text-zinc-700 dark:text-zinc-300" />
                </div>
                <span>Time Tracker Studio</span>
            </h2>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">Track your day-to-day work, projects, and activities in real-time.</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-[11px] font-mono font-semibold px-3 py-1 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700/60 flex items-center gap-1.5">
                <flux:icon name="bolt" class="size-3 text-zinc-500 dark:text-zinc-400" />
                <span>Live Activity Tracker</span>
            </span>
        </div>
    </div>

    <!-- Start Activity Form -->
    <div class="fixed bottom-3 left-3 right-3 z-20 md:sticky md:top-4 md:left-auto md:right-auto overflow-visible md:overflow-hidden rounded-2xl border border-zinc-200/80 dark:border-zinc-800/80 bg-zinc-50/95 dark:bg-zinc-900/95 backdrop-blur-xl p-3 md:p-5 shadow-[0_8px_32px_rgba(0,0,0,0.18)] dark:shadow-[0_8px_32px_rgba(0,0,0,0.5)] mb-0 md:mb-6"
         x-data="{}"
         @keydown.window.prevent.ctrl.slash="$refs.detailInput.focus()"
         @keydown.window.ctrl.enter="$wire.startActivity()">
        <!-- Mobile Form (Linear/Raycast Inspired Floating Bar) -->
        <form wire:submit.prevent="startActivity" class="md:hidden space-y-2 max-w-5xl mx-auto">
            <!-- Main Input + Start Button Row -->
            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <input type="text" wire:model="detail" x-ref="detailInputMobile" placeholder="What are you working on?" required autocomplete="off"
                           class="w-full h-10 pl-9 pr-3 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-zinc-600 focus:ring-2 focus:ring-zinc-600/20 shadow-2xs transition-all">
                    <flux:icon name="play-circle" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-400" />
                </div>
                <button type="submit" 
                        class="h-10 px-4 flex items-center justify-center gap-1.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs rounded-xl border border-indigo-500/80 active:scale-95 transition-all shrink-0 shadow-xs shadow-indigo-500/20">
                    <flux:icon name="play" class="size-3.5 text-white" />
                    <span>Start</span>
                </button>
            </div>
            <!-- Context Controls Row (Project, Task, Category, Parallel Chip) -->
            <div class="flex items-center gap-2 overflow-x-auto py-0.5 shrink-0 hide-scrollbar" style="scrollbar-width: none;">
                <!-- Project Select Chip -->
                <div class="relative shrink-0 max-w-[180px]">
                    <select wire:model.live="project_id" required 
                            class="w-full max-w-full appearance-none [-webkit-appearance:none] [-moz-appearance:none] bg-none h-7 pl-6.5 pr-6 rounded-lg bg-white dark:bg-zinc-950 border border-zinc-200/80 dark:border-zinc-800 text-[11px] font-medium text-zinc-700 dark:text-zinc-300 focus:outline-none focus:border-indigo-500 cursor-pointer shadow-2xs truncate">
                        @foreach($this->projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                    <flux:icon name="folder" class="absolute left-2 top-1/2 -translate-y-1/2 size-3 text-zinc-400 pointer-events-none" />
                    <flux:icon name="chevron-down" class="absolute right-1.5 top-1/2 -translate-y-1/2 size-2.5 text-zinc-400 pointer-events-none" />
                </div>

                <!-- Task Select Chip (Optional) -->
                <div class="relative shrink-0 max-w-[180px]">
                    <select wire:model.live="task_id" 
                            class="w-full max-w-full appearance-none [-webkit-appearance:none] [-moz-appearance:none] bg-none h-7 pl-6.5 pr-6 rounded-lg bg-white dark:bg-zinc-950 border border-zinc-200/80 dark:border-zinc-800 text-[11px] font-medium text-zinc-700 dark:text-zinc-300 focus:outline-none focus:border-indigo-500 cursor-pointer shadow-2xs truncate">
                        <option value="">-- Task (Optional) --</option>
                        @foreach($this->tasks as $task)
                            <option value="{{ $task->id }}">{{ $task->title }}</option>
                        @endforeach
                    </select>
                    <flux:icon name="clipboard-document-list" class="absolute left-2 top-1/2 -translate-y-1/2 size-3 text-zinc-400 pointer-events-none" />
                    <flux:icon name="chevron-down" class="absolute right-1.5 top-1/2 -translate-y-1/2 size-2.5 text-zinc-400 pointer-events-none" />
                </div>

                <!-- Category Select Chip -->
                <div class="relative shrink-0 max-w-[160px]">
                    <select wire:model="category_id" required 
                            class="w-full max-w-full appearance-none [-webkit-appearance:none] [-moz-appearance:none] bg-none h-7 pl-6.5 pr-6 rounded-lg bg-white dark:bg-zinc-950 border border-zinc-200/80 dark:border-zinc-800 text-[11px] font-medium text-zinc-700 dark:text-zinc-300 focus:outline-none focus:border-indigo-500 cursor-pointer shadow-2xs truncate">
                        @foreach($this->categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <flux:icon name="tag" class="absolute left-2 top-1/2 -translate-y-1/2 size-3 text-zinc-400 pointer-events-none" />
                    <flux:icon name="chevron-down" class="absolute right-1.5 top-1/2 -translate-y-1/2 size-2.5 text-zinc-400 pointer-events-none" />
                </div>

                <!-- Parallel Toggle Chip -->
                <button type="button" 
                        wire:click="$toggle('is_parallel')" 
                        class="h-7 px-2.5 rounded-lg border text-[11px] font-medium flex items-center gap-1 transition-all duration-200 shrink-0 cursor-pointer active:scale-95 shadow-2xs"
                        :class="$wire.is_parallel 
                            ? 'bg-indigo-600 text-white border-indigo-600 font-semibold shadow-xs shadow-indigo-500/20' 
                            : 'bg-white dark:bg-zinc-950 text-zinc-600 dark:text-zinc-400 border-zinc-200/80 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-900'">
                    <flux:icon name="arrows-right-left" class="size-3" />
                    <span>Parallel</span>
                </button>
            </div>
        </form>

        <!-- Desktop Form (Raycast / Linear Inspired 2-Row Command Studio) -->
        <form wire:submit.prevent="startActivity" class="hidden md:block space-y-3 max-w-5xl mx-auto">
            <!-- Row 1: Hero Activity Input + Primary Start Button -->
            <div class="flex items-center gap-3">
                <div class="relative flex-1">
                    <input type="text" wire:model="detail" x-ref="detailInput" placeholder="What are you working on?" required autocomplete="off"
                           class="w-full h-11 pl-10 pr-4 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-sm font-medium border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 shadow-2xs transition-all">
                    <flux:icon name="play-circle" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4.5 text-zinc-400 pointer-events-none" />
                </div>
                <button type="submit" class="h-11 px-6 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl border border-indigo-500/80 active:scale-95 transition-all shrink-0 shadow-xs shadow-indigo-500/20 flex items-center justify-center gap-2 cursor-pointer text-xs">
                    <flux:icon name="play" class="size-4 text-white" />
                    <span>Start Tracking</span>
                </button>
            </div>

            <!-- Row 2: Context Selector Chips & Shortcuts -->
            <div class="flex items-center justify-between gap-3 pt-0.5">
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Project Selector Chip -->
                    <div class="relative shrink-0 max-w-[200px] sm:max-w-[220px]">
                        <select wire:model.live="project_id" required title="Project Reference"
                                class="w-full max-w-full appearance-none [-webkit-appearance:none] [-moz-appearance:none] bg-none h-8 pl-7.5 pr-6 rounded-lg bg-white dark:bg-zinc-950 border border-zinc-200/80 dark:border-zinc-800 text-xs font-semibold text-zinc-700 dark:text-zinc-300 focus:outline-none focus:border-indigo-500 cursor-pointer shadow-2xs truncate transition-all">
                            @foreach($this->projects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                        <flux:icon name="folder" class="absolute left-2.5 top-1/2 -translate-y-1/2 size-3.5 text-zinc-400 pointer-events-none" />
                        <flux:icon name="chevron-down" class="absolute right-2 top-1/2 -translate-y-1/2 size-2.5 text-zinc-400 pointer-events-none" />
                    </div>

                    <!-- Task Selector Chip (Optional) -->
                    <div class="relative shrink-0 max-w-[220px] sm:max-w-[240px]">
                        <select wire:model.live="task_id" title="Task Reference"
                                class="w-full max-w-full appearance-none [-webkit-appearance:none] [-moz-appearance:none] bg-none h-8 pl-7.5 pr-7 rounded-lg text-xs font-semibold cursor-pointer shadow-2xs truncate transition-all focus:outline-none focus:border-indigo-500 {{ $task_id ? 'bg-indigo-50/90 text-indigo-900 dark:bg-indigo-500/15 dark:text-indigo-200 border-indigo-300 dark:border-indigo-500/40 ring-1 ring-indigo-500/30' : 'bg-white dark:bg-zinc-950 text-zinc-700 dark:text-zinc-300 border-zinc-200/80 dark:border-zinc-800' }}">
                            <option value="">-- Task (Optional) --</option>
                            @foreach($this->tasks as $task)
                                <option value="{{ $task->id }}">{{ $task->title }}</option>
                            @endforeach
                        </select>
                        <flux:icon name="clipboard-document-list" class="absolute left-2.5 top-1/2 -translate-y-1/2 size-3.5 {{ $task_id ? 'text-indigo-500' : 'text-zinc-400' }} pointer-events-none" />
                        @if($task_id)
                            <button type="button" wire:click="$set('task_id', null)" class="absolute right-2 top-1/2 -translate-y-1/2 size-3.5 rounded-full bg-zinc-200 dark:bg-zinc-800 hover:bg-zinc-300 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300 flex items-center justify-center cursor-pointer" title="Clear task">
                                <flux:icon name="x-mark" class="size-2" />
                            </button>
                        @else
                            <flux:icon name="chevron-down" class="absolute right-2 top-1/2 -translate-y-1/2 size-2.5 text-zinc-400 pointer-events-none" />
                        @endif
                    </div>

                    <!-- Category Selector Chip -->
                    <div class="relative shrink-0 max-w-[180px] sm:max-w-[200px]">
                        <select wire:model="category_id" required title="Category Reference"
                                class="w-full max-w-full appearance-none [-webkit-appearance:none] [-moz-appearance:none] bg-none h-8 pl-7.5 pr-6 rounded-lg bg-white dark:bg-zinc-950 border border-zinc-200/80 dark:border-zinc-800 text-xs font-semibold text-zinc-700 dark:text-zinc-300 focus:outline-none focus:border-indigo-500 cursor-pointer shadow-2xs truncate transition-all">
                            @foreach($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <flux:icon name="tag" class="absolute left-2.5 top-1/2 -translate-y-1/2 size-3.5 text-zinc-400 pointer-events-none" />
                        <flux:icon name="chevron-down" class="absolute right-2 top-1/2 -translate-y-1/2 size-2.5 text-zinc-400 pointer-events-none" />
                    </div>

                    <!-- Parallel Toggle Chip -->
                    <button type="button" 
                            wire:click="$toggle('is_parallel')" 
                            class="h-8 px-3 rounded-lg border text-xs font-semibold flex items-center gap-1.5 transition-all duration-200 shrink-0 cursor-pointer active:scale-95 shadow-2xs"
                            :class="$wire.is_parallel 
                                ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs shadow-indigo-500/20' 
                                : 'bg-white dark:bg-zinc-950 text-zinc-600 dark:text-zinc-400 border-zinc-200/80 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-900'">
                        <flux:icon name="arrows-right-left" class="size-3.5" />
                        <span>Parallel</span>
                    </button>
                </div>

                <!-- Keyboard Shortcuts Hint -->
                <div class="text-[11px] text-zinc-400 dark:text-zinc-500 flex items-center gap-1.5 shrink-0">
                    <span class="font-mono bg-zinc-100 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700/60 px-1.5 py-0.5 rounded text-[10px]">Ctrl /</span> focus
                    <span class="text-zinc-300 dark:text-zinc-700">•</span>
                    <span class="font-mono bg-zinc-100 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700/60 px-1.5 py-0.5 rounded text-[10px]">Ctrl Enter</span> start
                </div>
            </div>
        </form>
    </div>

    <!-- Running Activities (Initial Layout with Modern Live Timer Motion Cues) -->
    @if($this->runningActivities->count() > 0)
    <div class="space-y-3 animate-page-entrance"
         wire:transition.slide.up>
        <h2 class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 flex items-center gap-2 font-mono">
            <span class="relative flex h-2.5 w-2.5">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
            </span>
            <span>CURRENTLY ACTIVE TRACKING</span>
        </h2>

        <div class="grid gap-3">
            @foreach($this->runningActivities as $running)
                <div wire:key="running-{{ $running->id }}" 
                     class="group relative overflow-hidden rounded-2xl border border-emerald-500/30 bg-emerald-500/5 dark:bg-emerald-950/20 backdrop-blur-xl p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 transition-all duration-300" 
                     x-data="{ elapsed: '00:00:00', start: new Date('{{ $running->start_time->toISOString() }}').getTime() }"
                     x-init="setInterval(() => { 
                          let diff = Math.floor((new Date().getTime() - start) / 1000);
                          let h = Math.floor(diff / 3600).toString().padStart(2, '0');
                          let m = Math.floor((diff % 3600) / 60).toString().padStart(2, '0');
                          let s = (diff % 60).toString().padStart(2, '0');
                          elapsed = `${h}:${m}:${s}`;
                      }, 1000)">
                    
                    <div class="min-w-0 flex-1">
                        <div class="font-bold text-base sm:text-lg text-zinc-900 dark:text-zinc-100 truncate">{{ $running->detail }}</div>
                        <div class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 font-medium text-neutral-700 dark:text-neutral-300">
                                <flux:icon name="folder" class="size-3.5 text-emerald-500 shrink-0" />
                                <span>{{ $running->project->name }}</span>
                            </span>
                            <span>&bull;</span>
                            <span>{{ $running->category->name }}</span>
                            @if($running->is_parallel) 
                                <span class="bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-[9px] uppercase tracking-wider px-2 py-0.5 rounded-md font-mono font-bold ml-1 border border-indigo-500/20">Parallel</span> 
                            @endif
                        </div>
                    </div>

                    <!-- Live Stopwatch Readout with Spinning Gear & Stop Button -->
                    <div class="flex items-center justify-between sm:justify-end gap-4 w-full sm:w-auto">
                        <div class="flex items-center gap-2">
                            <flux:icon name="clock" class="size-4 text-emerald-500 animate-spin" style="animation-duration: 3s;" />
                            <span class="font-mono text-2xl sm:text-3xl text-emerald-600 dark:text-emerald-400 font-extrabold tracking-wider" x-text="elapsed"></span>
                        </div>
                        <button type="button" 
                                wire:click="stopActivity({{ $running->id }})" 
                                class="bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs sm:text-sm px-4 py-2 rounded-xl border border-rose-500/80 shadow-xs shadow-rose-600/20 active:scale-95 transition-all flex items-center gap-1.5 cursor-pointer shrink-0" 
                                title="Stop Activity">
                            <flux:icon name="stop" class="size-4 fill-current" />
                            <span>Stop</span>
                        </button>
                    </div>

                    <!-- Bottom Live Pulse Line -->
                    <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-transparent via-emerald-500/60 to-transparent animate-pulse"></div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Activity Feed / History Feed -->
    <div class="flex flex-col gap-4 mt-2 animate-page-entrance">
        <div class="flex flex-col md:flex-row justify-between md:items-center gap-3">
            <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-200 flex items-center gap-2 shrink-0">
                <flux:icon name="clock" class="size-4.5 text-zinc-400" />
                <span>History Log</span>
            </h3>
            
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2.5 w-full md:w-auto">
                <!-- Search Input -->
                <div class="relative w-full md:w-60 shrink-0">
                    <input type="text" 
                           wire:model.live.debounce.300ms="searchQuery" 
                           placeholder="Search activities..." 
                           autocomplete="off"
                           class="w-full h-9 pl-9 pr-3 rounded-xl bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-xs border border-zinc-200/80 dark:border-zinc-800 focus:outline-none focus:border-zinc-600 focus:ring-2 focus:ring-zinc-600/20 shadow-2xs transition-all">
                    <flux:icon name="magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-400 pointer-events-none" />
                </div>
                
                <!-- Action Buttons / Filter Bar -->
                <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap shrink-0">
                    <!-- Date Range Filter Popover -->
                    <div x-data="{ open: false }" class="relative shrink-0" @click.outside="open = false">
                        <button type="button" @click="open = !open" 
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800/80 transition-colors shadow-2xs cursor-pointer">
                            <flux:icon name="calendar" class="size-3.5 text-zinc-400 dark:text-zinc-500" />
                            <span>
                                @if($startDate && $endDate)
                                    {{ Carbon::parse($startDate)->format('M d') }} - {{ Carbon::parse($endDate)->format('M d') }}
                                @elseif($startDate)
                                    From {{ Carbon::parse($startDate)->format('M d') }}
                                @elseif($endDate)
                                    Until {{ Carbon::parse($endDate)->format('M d') }}
                                @else
                                    Date Filter
                                @endif
                            </span>
                            <flux:icon name="chevron-down" class="size-3 text-zinc-400" />
                        </button>

                        @if($startDate || $endDate)
                            <button type="button" wire:click="$set('startDate', null); $set('endDate', null)" 
                                    class="absolute -top-1 -right-1 size-4 rounded-full bg-red-500 text-white flex items-center justify-center transition-transform hover:scale-110 shadow-xs cursor-pointer"
                                    title="Clear filter">
                                <flux:icon name="x-mark" class="size-2.5" />
                            </button>
                        @endif

                        <!-- Dropdown Panel -->
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute left-0 sm:left-auto sm:right-0 mt-2 z-50 w-64 origin-top-left sm:origin-top-right rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 shadow-xl"
                             style="display: none;">
                             
                             <div class="space-y-4">
                                 <div>
                                     <label class="block text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-2">Presets</label>
                                     <div class="grid grid-cols-2 gap-1.5">
                                         <button type="button" @click="open = false" wire:click="$set('startDate', '{{ Carbon::today()->toDateString() }}'); $set('endDate', '{{ Carbon::today()->toDateString() }}')"
                                                 class="text-left text-xs px-2.5 py-1.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 font-medium text-zinc-700 dark:text-zinc-300 transition-colors cursor-pointer">
                                             Today
                                         </button>
                                         <button type="button" @click="open = false" wire:click="$set('startDate', '{{ Carbon::yesterday()->toDateString() }}'); $set('endDate', '{{ Carbon::yesterday()->toDateString() }}')"
                                                 class="text-left text-xs px-2.5 py-1.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 font-medium text-zinc-700 dark:text-zinc-300 transition-colors cursor-pointer">
                                             Yesterday
                                         </button>
                                         <button type="button" @click="open = false" wire:click="$set('startDate', '{{ Carbon::now()->startOfWeek()->toDateString() }}'); $set('endDate', '{{ Carbon::now()->endOfWeek()->toDateString() }}')"
                                                 class="text-left text-xs px-2.5 py-1.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 font-medium text-zinc-700 dark:text-zinc-300 transition-colors cursor-pointer">
                                             This Week
                                         </button>
                                         <button type="button" @click="open = false" wire:click="$set('startDate', '{{ Carbon::now()->startOfMonth()->toDateString() }}'); $set('endDate', '{{ Carbon::now()->endOfMonth()->toDateString() }}')"
                                                 class="text-left text-xs px-2.5 py-1.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 font-medium text-zinc-700 dark:text-zinc-300 transition-colors cursor-pointer">
                                             This Month
                                         </button>
                                     </div>
                                 </div>

                                 <div class="h-px bg-zinc-200 dark:bg-zinc-800"></div>

                                 <div class="space-y-3">
                                     <label class="block text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Custom Range</label>
                                     <div class="space-y-2">
                                         <div class="flex items-center gap-2 bg-zinc-50 dark:bg-zinc-950 border border-zinc-200/50 dark:border-zinc-800 rounded-lg px-2 py-0.5">
                                             <span class="text-[9px] text-zinc-400 font-bold uppercase w-8 shrink-0">Start</span>
                                             <input type="date" wire:model.live="startDate" onclick="if ('showPicker' in HTMLInputElement.prototype) { try { this.showPicker(); } catch(e) {} }" class="text-xs border-none bg-transparent focus:ring-0 p-1 text-zinc-700 dark:text-zinc-300 w-full cursor-pointer" title="Start Date">
                                         </div>
                                         <div class="flex items-center gap-2 bg-zinc-50 dark:bg-zinc-950 border border-zinc-200/50 dark:border-zinc-800 rounded-lg px-2 py-0.5">
                                             <span class="text-[9px] text-zinc-400 font-bold uppercase w-8 shrink-0">End</span>
                                             <input type="date" wire:model.live="endDate" onclick="if ('showPicker' in HTMLInputElement.prototype) { try { this.showPicker(); } catch(e) {} }" class="text-xs border-none bg-transparent focus:ring-0 p-1 text-zinc-700 dark:text-zinc-300 w-full cursor-pointer" title="End Date">
                                         </div>
                                     </div>
                                 </div>
                             </div>
                        </div>
                    </div>

                    <!-- Export Trigger Button -->
                    <flux:modal.trigger name="export-modal">
                        <button type="button" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800/80 transition-colors shadow-2xs cursor-pointer">
                            <flux:icon name="arrow-up-tray" class="size-3.5 text-zinc-400 dark:text-zinc-500" />
                            <span>Export</span>
                        </button>
                    </flux:modal.trigger>
                </div>
            </div>
        </div>
        
        @if(session()->has('import_status'))
            @php $status = session('import_status'); @endphp
            <div x-data="{ show: true }" x-show="show" x-transition.out.duration.500ms class="rounded-2xl p-4 border shadow-2xs transition-all duration-300 {{ $status['type'] === 'success' ? 'bg-emerald-50/90 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800/60 text-emerald-900 dark:text-emerald-100' : 'bg-rose-50/90 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800/60 text-rose-900 dark:text-rose-100' }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3 min-w-0">
                        <div class="size-8 rounded-xl flex items-center justify-center shrink-0 border {{ $status['type'] === 'success' ? 'bg-emerald-100 dark:bg-emerald-900/60 border-emerald-300 dark:border-emerald-700/60 text-emerald-600 dark:text-emerald-400' : 'bg-rose-100 dark:bg-rose-900/60 border-rose-300 dark:border-rose-700/60 text-rose-600 dark:text-rose-400' }}">
                            @if($status['type'] === 'success')
                                <flux:icon name="check-circle" class="size-4.5" />
                            @else
                                <flux:icon name="exclamation-triangle" class="size-4.5" />
                            @endif
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold">{{ $status['title'] }}</h4>
                            <p class="text-xs mt-0.5 opacity-90 leading-relaxed">{{ $status['message'] }}</p>
                        </div>
                    </div>
                    <button type="button" @click="show = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1 rounded-lg transition-colors cursor-pointer shrink-0">
                        <flux:icon name="x-mark" class="size-4" />
                    </button>
                </div>
            </div>
        @elseif(session()->has('message'))
            <div x-data="{ show: true }" x-show="show" x-transition class="text-xs font-medium text-emerald-600 dark:text-emerald-400 flex items-center justify-between gap-2 bg-emerald-50 dark:bg-emerald-950/30 p-3 rounded-xl border border-emerald-200 dark:border-emerald-800/40">
                <div class="flex items-center gap-2">
                    <flux:icon name="check-circle" class="size-4 shrink-0" />
                    <span>{{ session('message') }}</span>
                </div>
                <button type="button" @click="show = false" class="text-emerald-500 hover:text-emerald-700 p-0.5 cursor-pointer">
                    <flux:icon name="x-mark" class="size-3.5" />
                </button>
            </div>
        @endif
        
        <div class="space-y-4">
            @forelse($this->activitiesData['activities'] as $date => $dayActivities)
                @php
                    $dayTotalSeconds = $dayActivities->sum(function($act) {
                        return $act->end_time ? $act->start_time->diffInSeconds($act->end_time) : 0;
                    });
                    $dayH = floor($dayTotalSeconds / 3600);
                    $dayM = floor(($dayTotalSeconds % 3600) / 60);
                    $dayS = $dayTotalSeconds % 60;
                    $dayFormatted = sprintf('%02d:%02d:%02d', $dayH, $dayM, $dayS);
                @endphp
                <div wire:key="day-{{ $date }}" class="rounded-2xl border border-zinc-200/80 dark:border-zinc-800/80 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-xl shadow-xs overflow-hidden">
                    <!-- Day Header -->
                    <div class="px-4 py-3 bg-zinc-100/70 dark:bg-zinc-950/70 border-b border-zinc-200/60 dark:border-zinc-800/60 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="size-6 rounded-lg bg-zinc-200 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                                <flux:icon name="calendar-days" class="size-3.5 text-zinc-700 dark:text-zinc-300" />
                            </div>
                            <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200 uppercase tracking-wider">
                                {{ Carbon::parse($date)->isToday() ? 'Today' : (Carbon::parse($date)->isYesterday() ? 'Yesterday' : Carbon::parse($date)->format('l, j F Y')) }}
                            </span>
                            <span class="text-[10px] font-mono text-zinc-500 dark:text-zinc-500 hidden sm:inline">({{ Carbon::parse($date)->format('M d, Y') }})</span>
                        </div>
                        <!-- Daily Total Duration -->
                        <div class="font-mono text-xs font-extrabold text-zinc-900 dark:text-zinc-100 bg-zinc-200 dark:bg-zinc-800 px-3 py-1 rounded-xl border border-zinc-300 dark:border-zinc-700/60 flex items-center gap-1.5 shadow-2xs">
                            <span class="text-[10px] text-zinc-500 dark:text-zinc-400 font-medium uppercase tracking-wider">Total:</span>
                            <span>{{ $dayFormatted }}</span>
                        </div>
                    </div>

                    <!-- Day Activities List -->
                    <div class="divide-y divide-zinc-200/40 dark:divide-zinc-800/40">
                        @foreach($dayActivities as $activity)
                            <div wire:key="activity-{{ $activity->id }}" class="group px-4 py-3.5 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors flex items-center justify-between gap-3 relative overflow-hidden">
                                
                                <!-- Left Hover Indicator Bar -->
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-zinc-400 opacity-0 group-hover:opacity-100 transition-opacity"></div>

                                <!-- Left: Icon & Meta -->
                                <div class="flex items-center gap-3.5 min-w-0 flex-1 pl-1">
                                    <div class="size-9 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0 group-hover:scale-105 transition-transform">
                                        <flux:icon name="folder" class="size-4.5 text-zinc-700 dark:text-zinc-300" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-zinc-100 truncate group-hover:text-zinc-700 dark:group-hover:text-zinc-300 transition-colors">
                                            {{ $activity->detail }}
                                        </div>
                                        <div class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1 flex flex-wrap items-center gap-2">
                                            @if($activity->task)
                                                <span class="bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-[10px] font-medium px-2 py-0.5 rounded-md border border-indigo-200 dark:border-indigo-500/20 flex items-center gap-1">
                                                    <flux:icon name="clipboard-document-list" class="size-3 shrink-0" />
                                                    <span class="truncate max-w-[150px]">{{ $activity->task->title }}</span>
                                                </span>
                                            @endif
                                            <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-[10px] font-medium px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60">
                                                {{ $activity->project->name }}
                                            </span>
                                            <span class="bg-zinc-100/80 dark:bg-zinc-800/60 text-zinc-600 dark:text-zinc-400 text-[10px] font-medium px-2 py-0.5 rounded-md border border-zinc-200/80 dark:border-zinc-800">
                                                {{ $activity->category->name }}
                                            </span>
                                            <span class="font-mono text-[10px] text-zinc-600 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-950 px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-800">
                                                {{ $activity->start_time->format('H:i') }} &ndash; {{ $activity->end_time->format('H:i') }}
                                            </span>
                                            @if($activity->is_parallel) 
                                                <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-[9px] font-mono font-medium uppercase tracking-wider px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60">Parallel</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Right: Duration & Dropdown -->
                                <div class="flex items-center gap-3 shrink-0">
                                    <span class="font-mono text-xs sm:text-sm font-extrabold text-zinc-900 dark:text-zinc-100 tracking-tight">{{ $activity->duration }}</span>
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="xs" icon="ellipsis-vertical" square class="cursor-pointer size-8 text-zinc-400 hover:text-zinc-200 rounded-xl hover:bg-zinc-800/50" title="Actions" />
                                        <flux:menu class="min-w-[8rem]">
                                            <flux:menu.item wire:click="editActivity({{ $activity->id }})" icon="pencil" class="cursor-pointer">Edit</flux:menu.item>
                                            <flux:modal.trigger name="delete-activity-{{ $activity->id }}">
                                                <flux:menu.item icon="trash" variant="danger" class="cursor-pointer">Delete</flux:menu.item>
                                            </flux:modal.trigger>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </div>

                            <flux:modal name="delete-activity-{{ $activity->id }}" class="w-[calc(100vw-2rem)] max-w-md backdrop:backdrop-blur-md z-[200]">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">Delete Activity?</flux:heading>
                                        <flux:text class="mt-2 text-xs">
                                            Are you sure you want to delete this activity? <br>
                                            <strong>{{ $activity->detail }}</strong>
                                        </flux:text>
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <flux:modal.close>
                                            <flux:button variant="ghost">Cancel</flux:button>
                                        </flux:modal.close>
                                        <flux:modal.close>
                                            <flux:button variant="danger" wire:click="deleteActivity({{ $activity->id }})">Delete</flux:button>
                                        </flux:modal.close>
                                    </div>
                                </div>
                            </flux:modal>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="text-center py-16 text-xs text-zinc-400 border border-dashed border-zinc-200 dark:border-zinc-800 rounded-2xl bg-zinc-50/50 dark:bg-zinc-900/30 flex flex-col items-center gap-2">
                    <flux:icon name="clock" class="size-8 text-zinc-300 dark:text-zinc-700" />
                    <span>No activity history found. Start tracking your tasks above!</span>
                </div>
            @endforelse

            @if($this->activitiesData['hasMore'])
                <div class="flex justify-center mt-6 mb-2">
                    <flux:button wire:click="loadMore" variant="subtle" size="sm" class="cursor-pointer !rounded-xl">
                        View More
                    </flux:button>
                </div>
            @endif
        </div>
    </div>

    <!-- Edit Time Modal -->
    <div x-data="{ show: @entangle('showEditModal') }" x-show="show" style="display: none;" class="relative z-[100]">
        <!-- Backdrop -->
        <div x-show="show" x-transition.opacity class="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm"></div>
        
        <!-- Modal -->
        <div class="fixed inset-0 flex items-center justify-center p-4 sm:p-6 z-[101]">
            <div x-show="show"
                 @click.outside="$wire.cancelEdit()"
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-zinc-200 dark:border-zinc-800 w-full max-w-sm overflow-hidden text-left relative">
                <div class="p-6">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Edit Activity</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Activity Name</label>
                            <input type="text" wire:model="editDetail" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 focus:ring-indigo-500 focus:border-indigo-500 shadow-xs text-xs py-2 px-3 transition-colors">
                            @error('editDetail') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Associated Task</label>
                            <select wire:model="editTaskId" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 focus:ring-indigo-500 focus:border-indigo-500 shadow-xs text-xs py-2 px-3 transition-colors">
                                <option value="">-- Non-Task Activity --</option>
                                @foreach($this->tasks as $t)
                                    <option value="{{ $t->id }}">{{ $t->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Start Time</label>
                            <input type="datetime-local" wire:model="editStartTime" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 focus:ring-indigo-500 focus:border-indigo-500 shadow-xs text-xs cursor-pointer py-2 px-3 transition-colors">
                            @error('editStartTime') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">End Time</label>
                            <input type="datetime-local" wire:model="editEndTime" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 focus:ring-indigo-500 focus:border-indigo-500 shadow-xs text-xs cursor-pointer py-2 px-3 transition-colors">
                            @error('editEndTime') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                
                <div class="bg-zinc-50 dark:bg-zinc-950 px-6 py-4 flex justify-end gap-3 border-t border-zinc-200 dark:border-zinc-800">
                    <flux:button variant="subtle" wire:click="cancelEdit">Cancel</flux:button>
                    <flux:button variant="primary" wire:click="updateActivity">Save Changes</flux:button>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Activities Modal (Full Screen Teleported via Flux) -->
    <flux:modal name="export-modal" class="w-[calc(100vw-2rem)] max-w-md backdrop:backdrop-blur-md z-[200]">
        <div x-data="{ exportStart: '', exportEnd: '' }" class="space-y-5">
            <div class="flex items-start gap-3.5">
                <div class="size-10 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                    <flux:icon name="arrow-down-tray" class="size-5 text-zinc-700 dark:text-zinc-300" />
                </div>
                <div class="flex-1 min-w-0">
                    <flux:heading size="lg" class="font-bold tracking-tight">Export Activities</flux:heading>
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Select a date range to export your activities to Excel (.xlsx). Leave blank to export all history.</flux:text>
                </div>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-1">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5 flex items-center gap-1">
                        <flux:icon name="calendar" class="size-3.5 text-zinc-400" />
                        <span>Start Date</span>
                    </label>
                    <input type="date" x-model="exportStart" onclick="if ('showPicker' in HTMLInputElement.prototype) { try { this.showPicker(); } catch(e) {} }" class="w-full rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-zinc-900 dark:focus:ring-white focus:border-transparent shadow-2xs text-xs cursor-pointer py-2.5 px-3 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5 flex items-center gap-1">
                        <flux:icon name="calendar" class="size-3.5 text-zinc-400" />
                        <span>End Date</span>
                    </label>
                    <input type="date" x-model="exportEnd" onclick="if ('showPicker' in HTMLInputElement.prototype) { try { this.showPicker(); } catch(e) {} }" class="w-full rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-zinc-900 dark:focus:ring-white focus:border-transparent shadow-2xs text-xs cursor-pointer py-2.5 px-3 transition-all">
                </div>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-zinc-200/80 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="ghost" size="sm" class="cursor-pointer">Cancel</flux:button>
                </flux:modal.close>
                
                <flux:modal.close>
                    <flux:button variant="primary" size="sm" class="cursor-pointer font-semibold" @click="$wire.export(exportStart, exportEnd)">
                        <flux:icon name="arrow-down-tray" class="size-3.5 mr-1.5" />
                        <span>Download Excel</span>
                    </flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>

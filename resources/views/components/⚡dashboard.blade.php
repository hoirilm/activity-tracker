<?php

use Livewire\Component;
use App\Models\Activity;
use App\Models\Task;
use Carbon\Carbon;

new class extends Component
{
    public $chartPeriod = 'weekly';
    public $taskFilter = 'on_progress';
    public $taskSearch = '';
    public $newTaskTitle = '';
    public $newTaskProjectId = null;
    public $showQuickTaskForm = false;

    public function getTodayDurationProperty()
    {
        $activities = auth()->user()->activities()
            ->whereNotNull('end_time')
            ->whereDate('start_time', Carbon::today())
            ->get();
        return $this->formatDuration($this->sumSeconds($activities));
    }

    public function getWeekDurationProperty()
    {
        $activities = auth()->user()->activities()
            ->whereNotNull('end_time')
            ->whereBetween('start_time', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->get();
        return $this->formatDuration($this->sumSeconds($activities));
    }

    public function getActiveProjectsCountProperty()
    {
        return auth()->user()->activities()
            ->whereBetween('start_time', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->distinct('project_id')
            ->count('project_id');
    }

    public function getRunningActivitiesProperty()
    {
        return auth()->user()->activities()
            ->with(['project', 'category'])
            ->whereNull('end_time')
            ->orderBy('start_time', 'desc')
            ->get();
    }

    public function getRecentActivitiesProperty()
    {
        return auth()->user()->activities()
            ->with(['project', 'category', 'task'])
            ->whereNotNull('end_time')
            ->orderBy('end_time', 'desc')
            ->take(5)
            ->get();
    }

    public function updatedChartPeriod()
    {
        $this->dispatch('chart-updated', stats: $this->chartStats);
    }

    public function getChartStatsProperty()
    {
        if ($this->chartPeriod === 'weekly') {
            // Last 7 days
            $days = collect();
            for ($i = 6; $i >= 0; $i--) {
                $days->push(Carbon::today()->subDays($i)->format('Y-m-d'));
            }

            $activities = auth()->user()->activities()
                ->whereNotNull('end_time')
                ->whereDate('start_time', '>=', Carbon::today()->subDays(6))
                ->get();

            $chartData = $days->map(function ($day) use ($activities) {
                $dailyActivities = $activities->filter(function ($activity) use ($day) {
                    return $activity->start_time->format('Y-m-d') === $day;
                });
                return round($this->sumSeconds($dailyActivities) / 3600, 2);
            });

            return [
                'labels' => $days->map(fn($day) => Carbon::parse($day)->format('D, M d'))->toArray(),
                'data' => $chartData->toArray(),
            ];
        } elseif ($this->chartPeriod === 'monthly') {
            // Last 30 days
            $days = collect();
            for ($i = 29; $i >= 0; $i--) {
                $days->push(Carbon::today()->subDays($i)->format('Y-m-d'));
            }

            $activities = auth()->user()->activities()
                ->whereNotNull('end_time')
                ->whereDate('start_time', '>=', Carbon::today()->subDays(29))
                ->get();

            $chartData = $days->map(function ($day) use ($activities) {
                $dailyActivities = $activities->filter(function ($activity) use ($day) {
                    return $activity->start_time->format('Y-m-d') === $day;
                });
                return round($this->sumSeconds($dailyActivities) / 3600, 2);
            });

            return [
                'labels' => $days->map(fn($day) => Carbon::parse($day)->format('M d'))->toArray(),
                'data' => $chartData->toArray(),
            ];
        } else {
            // Daily (Today's hours)
            $hours = collect();
            for ($i = 0; $i < 24; $i++) {
                $hours->push($i);
            }
            
            $activities = auth()->user()->activities()
                ->whereNotNull('end_time')
                ->whereDate('start_time', Carbon::today())
                ->get();

            $hourlyData = $hours->map(function ($hour) use ($activities) {
                $hourlyActivities = $activities->filter(function ($activity) use ($hour) {
                    return $activity->start_time->hour == $hour;
                });
                return round($this->sumSeconds($hourlyActivities) / 3600, 2);
            });

            $activeHours = $hours->filter(function ($hour) use ($hourlyData) {
                return $hourlyData[$hour] > 0;
            });
            
            $startHour = $activeHours->min() ?? 9;
            $endHour = $activeHours->max() ?? 17;
            if ($endHour <= $startHour) $endHour = $startHour + 8;
            if ($endHour > 23) $endHour = 23;
            
            $labels = [];
            $data = [];
            for ($h = $startHour; $h <= $endHour; $h++) {
                $labels[] = sprintf('%02d:00', $h);
                $data[] = $hourlyData[$h];
            }

            return [
                'labels' => $labels,
                'data' => $data,
            ];
        }
    }

    public function getProjectStatsProperty()
    {
        $activities = auth()->user()->activities()
            ->with(['project', 'category'])
            ->whereNotNull('end_time')
            ->whereBetween('start_time', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->get();

        if ($activities->isEmpty()) return collect();

        $projectGroups = $activities->groupBy('project_id')->map(function ($group) {
            $projectTotalSeconds = $this->sumSeconds($group);
            
            // Calculate category breakdown inside this project
            $categoryGroups = $group->groupBy('category_id')->map(function ($catGroup) use ($projectTotalSeconds) {
                $catSeconds = $this->sumSeconds($catGroup);
                $catPercentage = $projectTotalSeconds > 0 ? round(($catSeconds / $projectTotalSeconds) * 100) : 0;
                return [
                    'name' => $catGroup->first()->category->name ?? 'Uncategorized',
                    'seconds' => $catSeconds,
                    'duration' => $this->formatDuration($catSeconds),
                    'percentage' => $catPercentage,
                ];
            })->sortByDesc('seconds')->values()->toArray();

            return [
                'name' => $group->first()->project->name ?? 'Unnamed Project',
                'seconds' => $projectTotalSeconds,
                'categories' => $categoryGroups,
            ];
        });

        $totalSecondsForPercentage = $projectGroups->sum('seconds');
        if ($totalSecondsForPercentage == 0) return collect();

        return $projectGroups->map(function ($group) use ($totalSecondsForPercentage) {
            $percentage = round(($group['seconds'] / $totalSecondsForPercentage) * 100);
            return [
                'name' => $group['name'],
                'seconds' => $group['seconds'],
                'duration' => $this->formatDuration($group['seconds']),
                'percentage' => $percentage,
                'categories' => $group['categories'],
            ];
        })->sortByDesc('seconds')->values();
    }

    private function sumSeconds($activities)
    {
        if ($activities->isEmpty()) return 0;
        
        $intervals = $activities->map(function ($activity) {
            return [
                'start' => $activity->start_time->getTimestamp(),
                'end' => $activity->end_time->getTimestamp(),
            ];
        })->sortBy('start')->values();
        
        $merged = [];
        $currentStart = $intervals[0]['start'];
        $currentEnd = $intervals[0]['end'];
        
        for ($i = 1; $i < $intervals->count(); $i++) {
            $start = $intervals[$i]['start'];
            $end = $intervals[$i]['end'];
            
            if ($start <= $currentEnd) {
                $currentEnd = max($currentEnd, $end);
            } else {
                $merged[] = ['start' => $currentStart, 'end' => $currentEnd];
                $currentStart = $start;
                $currentEnd = $end;
            }
        }
        $merged[] = ['start' => $currentStart, 'end' => $currentEnd];
        
        $totalSeconds = 0;
        foreach ($merged as $interval) {
            $totalSeconds += ($interval['end'] - $interval['start']);
        }
        
        return $totalSeconds;
    }

    private function formatDuration($seconds)
    {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        
        if ($h > 0) {
            return "{$h}h {$m}m";
        }
        return "{$m}m";
    }

    public function getTodayTasksProperty()
    {
        return auth()->user()->tasks()
            ->with(['project', 'labels'])
            ->where('status', Task::STATUS_ON_PROGRESS)
            ->latest('updated_at')
            ->get();
    }

    public function getTaskSummaryCountsProperty()
    {
        $userTasks = auth()->user()->tasks()->where('status', '!=', Task::STATUS_ARCHIVED)->get();

        return [
            'on_progress' => $userTasks->where('status', Task::STATUS_ON_PROGRESS)->count(),
            'new' => $userTasks->where('status', Task::STATUS_NEW)->count(),
            'on_hold' => $userTasks->where('status', Task::STATUS_ON_HOLD)->count(),
            'done_today' => $userTasks->where('status', Task::STATUS_DONE)->filter(fn($t) => Carbon::parse($t->updated_at)->isToday())->count(),
        ];
    }

    public function markTaskDone(int $taskId)
    {
        $this->updateTaskStatus($taskId, Task::STATUS_DONE);
    }

    public function toggleTaskStatus(int $taskId)
    {
        $task = auth()->user()->tasks()->find($taskId);
        if (!$task) {
            return;
        }

        if ($task->status === Task::STATUS_DONE) {
            $this->updateTaskStatus($taskId, Task::STATUS_ON_PROGRESS);
        } else {
            $this->markTaskDone($taskId);
        }
    }

    public function getProjectsProperty()
    {
        return auth()->user()->projects()->orderBy('name')->get();
    }

    public function createQuickTask()
    {
        $this->validate([
            'newTaskTitle' => 'required|string|max:255',
            'newTaskProjectId' => 'nullable|exists:projects,id',
        ]);

        $task = auth()->user()->tasks()->create([
            'title' => trim($this->newTaskTitle),
            'project_id' => $this->newTaskProjectId ?: null,
            'status' => Task::STATUS_ON_PROGRESS,
        ]);

        $this->reset(['newTaskTitle', 'newTaskProjectId', 'showQuickTaskForm']);
        $this->dispatch('task-created', title: $task->title);
    }

    public function updateTaskStatus(int $taskId, string $status)
    {
        $allowed = [Task::STATUS_NEW, Task::STATUS_ON_PROGRESS, Task::STATUS_ON_HOLD, Task::STATUS_DONE, Task::STATUS_ARCHIVED];
        if (!in_array($status, $allowed)) {
            return;
        }

        $task = auth()->user()->tasks()->find($taskId);
        if ($task) {
            $task->update([
                'status' => $status,
                'updated_at' => now(),
            ]);

            if ($status === Task::STATUS_DONE) {
                $this->dispatch('task-completed', title: $task->title);
                $this->js("window.dispatchEvent(new CustomEvent('task-completed', { detail: { title: " . json_encode($task->title) . " } }))");
            }
        }
    }

    public function getFilteredTasksProperty()
    {
        $query = auth()->user()->tasks()
            ->with(['project', 'labels']);

        match ($this->taskFilter) {
            'new' => $query->where('status', Task::STATUS_NEW),
            'on_progress' => $query->where('status', Task::STATUS_ON_PROGRESS),
            'done_today' => $query->where('status', Task::STATUS_DONE)->whereDate('updated_at', Carbon::today()),
            'all' => $query->where('status', '!=', Task::STATUS_ARCHIVED),
            default => $query->where('status', Task::STATUS_ON_PROGRESS),
        };

        if (!empty(trim($this->taskSearch))) {
            $search = '%' . mb_strtolower(trim($this->taskSearch)) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(title) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(description) LIKE ?', [$search])
                  ->orWhereHas('project', fn($pq) => $pq->whereRaw('LOWER(name) LIKE ?', [$search]))
                  ->orWhereHas('labels', fn($lq) => $lq->whereRaw('LOWER(name) LIKE ?', [$search]));
            });
        }

        return $query->latest('updated_at')->get();
    }

    public function getTaskProgressStatsProperty()
    {
        $todayAll = auth()->user()->tasks()
            ->where('status', '!=', Task::STATUS_ARCHIVED)
            ->get();

        $doneToday = $todayAll->where('status', Task::STATUS_DONE)
            ->filter(fn($t) => Carbon::parse($t->updated_at)->isToday())
            ->count();

        $onProgress = $todayAll->where('status', Task::STATUS_ON_PROGRESS)->count();
        $new = $todayAll->where('status', Task::STATUS_NEW)->count();
        $onHold = $todayAll->where('status', Task::STATUS_ON_HOLD)->count();
        
        // Progress meter total strictly considers on-progress stream tasks (on_progress + done_today)
        $totalProgressTarget = $onProgress + $doneToday;
        $percentage = $totalProgressTarget > 0 ? (int) round(($doneToday / $totalProgressTarget) * 100) : 0;

        return [
            'done_today' => $doneToday,
            'on_progress' => $onProgress,
            'new' => $new,
            'on_hold' => $onHold,
            'total_active' => $totalProgressTarget,
            'percentage' => $percentage,
        ];
    }

    public function getLabelBgClass(string $color): string
    {
        return match ($color) {
            'amber' => 'bg-amber-500/10 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300/90 border-amber-200/80 dark:border-amber-900/40',
            'emerald' => 'bg-emerald-500/10 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300/90 border-emerald-200/80 dark:border-emerald-900/40',
            'rose' => 'bg-rose-500/10 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300/90 border-rose-200/80 dark:border-rose-900/40',
            'sky' => 'bg-sky-500/10 text-sky-800 dark:bg-sky-950/40 dark:text-sky-300/90 border-sky-200/80 dark:border-sky-900/40',
            'purple' => 'bg-purple-500/10 text-purple-800 dark:bg-purple-950/40 dark:text-purple-300/90 border-purple-200/80 dark:border-purple-900/40',
            'zinc' => 'bg-zinc-500/10 text-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-300/90 border-zinc-200/80 dark:border-zinc-700/60',
            default => 'bg-indigo-500/10 text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300/90 border-indigo-200/80 dark:border-indigo-900/40',
        };
    }

    public function pauseActivity($id)
    {
        $activity = auth()->user()->activities()->find($id);
        if ($activity && ! $activity->end_time && ! $activity->paused_at) {
            $activity->update([
                'paused_at' => now(),
            ]);
        }
    }

    public function resumeActivity($id)
    {
        $activity = auth()->user()->activities()->find($id);
        if ($activity && ! $activity->end_time && $activity->paused_at) {
            $pauseDuration = (int) $activity->paused_at->diffInSeconds(now());
            $activity->update([
                'paused_seconds' => ($activity->paused_seconds ?? 0) + $pauseDuration,
                'paused_at' => null,
            ]);
        }
    }

    public function stopActivity($id)
    {
        $activity = auth()->user()->activities()->find($id);
        if ($activity && ! $activity->end_time) {
            if ($activity->paused_at) {
                $pauseDuration = (int) $activity->paused_at->diffInSeconds(now());
                $activity->update([
                    'paused_seconds' => ($activity->paused_seconds ?? 0) + $pauseDuration,
                    'paused_at' => null,
                    'end_time' => now(),
                ]);
            } else {
                $activity->update(['end_time' => now()]);
            }
        }
    }
};
?>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 9999px; }
</style>

<div class="flex min-h-[calc(100vh-10rem)] w-full flex-col gap-5 sm:gap-6 px-3 sm:px-4 py-2 text-neutral-900 dark:text-neutral-100 max-w-6xl mx-auto mt-1 sm:mt-4 pb-12" x-data="{ scrolled: false }" @scroll.window="scrolled = (window.pageYOffset > 300)">

    <!-- Main Animated Content -->
    <div class="flex flex-col gap-6 animate-page-entrance">
        <!-- Header -->
    <div class="border-b border-zinc-200/80 dark:border-zinc-800/80 pb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                <div class="size-8.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                    <flux:icon name="chart-bar" class="size-4.5 text-zinc-700 dark:text-zinc-300" />
                </div>
                <span>Dashboard Overview</span>
            </h2>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">Welcome back, {{ auth()->user()->name }}! Here is your real-time activity metrics breakdown.</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-[11px] font-mono font-semibold px-3 py-1 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700/60 flex items-center gap-1.5">
                <flux:icon name="sparkles" class="size-3 text-zinc-500 dark:text-zinc-400" />
                <span>Live Analytics</span>
            </span>
        </div>
    </div>

    <!-- Summary Cards (3 Sejajar Grid) -->
    <div class="grid grid-cols-3 gap-2 sm:gap-5">
        <!-- Card 1: Today's Total -->
        <div class="relative overflow-hidden bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-2.5 sm:p-5 shadow-xs flex flex-col justify-between group hover:border-zinc-400 dark:hover:border-zinc-700 transition-all duration-300">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-zinc-300/60 dark:via-zinc-600/40 to-transparent"></div>
            <div class="flex items-center justify-between mb-1.5 sm:mb-3">
                <div class="size-7 sm:size-10 rounded-lg sm:rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0 group-hover:scale-105 transition-transform">
                    <flux:icon name="clock" class="size-3.5 sm:size-5 text-zinc-700 dark:text-zinc-300" />
                </div>
                <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 text-[8px] sm:text-[10px] font-mono font-medium px-1 sm:px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60">
                    Today
                </span>
            </div>

            <div>
                <div class="text-[8px] sm:text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 truncate">Today's Total</div>
                <div class="text-base sm:text-3xl font-mono font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100 mt-0.5 sm:mt-1 truncate">{{ $this->todayDuration }}</div>
                <p class="text-[10px] sm:text-[11px] text-zinc-500 dark:text-zinc-400 mt-1 hidden sm:block truncate">
                    Logged today
                </p>
            </div>
        </div>
        
        <!-- Card 2: This Week -->
        <div class="relative overflow-hidden bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-2.5 sm:p-5 shadow-xs flex flex-col justify-between group hover:border-zinc-400 dark:hover:border-zinc-700 transition-all duration-300">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-zinc-300/60 dark:via-zinc-600/40 to-transparent"></div>
            <div class="flex items-center justify-between mb-1.5 sm:mb-3">
                <div class="size-7 sm:size-10 rounded-lg sm:rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0 group-hover:scale-105 transition-transform">
                    <flux:icon name="calendar-days" class="size-3.5 sm:size-5 text-zinc-700 dark:text-zinc-300" />
                </div>
                <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 text-[8px] sm:text-[10px] font-mono font-medium px-1 sm:px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60">
                    Week
                </span>
            </div>

            <div>
                <div class="text-[8px] sm:text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 truncate">This Week</div>
                <div class="text-base sm:text-3xl font-mono font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100 mt-0.5 sm:mt-1 truncate">{{ $this->weekDuration }}</div>
                <p class="text-[10px] sm:text-[11px] text-zinc-500 dark:text-zinc-400 mt-1 hidden sm:block truncate">
                    Cumulative week
                </p>
            </div>
        </div>

        <!-- Card 3: Active Projects -->
        <div class="relative overflow-hidden bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-2.5 sm:p-5 shadow-xs flex flex-col justify-between group hover:border-zinc-400 dark:hover:border-zinc-700 transition-all duration-300">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-zinc-300/60 dark:via-zinc-600/40 to-transparent"></div>
            <div class="flex items-center justify-between mb-1.5 sm:mb-3">
                <div class="size-7 sm:size-10 rounded-lg sm:rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0 group-hover:scale-105 transition-transform">
                    <flux:icon name="briefcase" class="size-3.5 sm:size-5 text-zinc-700 dark:text-zinc-300" />
                </div>
                <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 text-[8px] sm:text-[10px] font-mono font-medium px-1 sm:px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60">
                    Active
                </span>
            </div>

            <div>
                <div class="text-[8px] sm:text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 truncate">Projects</div>
                <div class="text-base sm:text-3xl font-mono font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100 mt-0.5 sm:mt-1 truncate">{{ $this->activeProjectsCount }}</div>
                <p class="text-[10px] sm:text-[11px] text-zinc-500 dark:text-zinc-400 mt-1 hidden sm:block truncate">
                    Logged projects
                </p>
            </div>
        </div>
    </div>

    <!-- Currently Active / Paused Tracking -->
    @if($this->runningActivities->count() > 0)
    <div class="mt-1">
        <h2 class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 mb-3 flex items-center gap-2 font-mono">
            <span class="relative flex h-2.5 w-2.5">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
            </span>
            <span>ACTIVE TRACKING SESSIONS</span>
        </h2>

        <div class="grid gap-3">
            @foreach($this->runningActivities as $running)
                @php $isPaused = $running->isPaused(); @endphp
                <div wire:key="running-{{ $running->id }}-{{ $isPaused ? 'paused-' . ($running->paused_at ? $running->paused_at->timestamp : '1') : 'active' }}" 
                     class="group relative overflow-hidden rounded-2xl border transition-all duration-300 p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3
                            {{ $isPaused 
                                ? 'border-amber-500/40 bg-amber-500/10 dark:bg-amber-950/30' 
                                : 'border-emerald-500/30 bg-emerald-500/5 dark:bg-emerald-950/20' }}" 
                     x-data="{ 
                         initialSeconds: {{ $running->elapsed_seconds }},
                         seconds: {{ $running->elapsed_seconds }}, 
                         paused: {{ $isPaused ? 'true' : 'false' }},
                         startTime: Date.now(),
                         timer: null,
                         visibilityHandler: null,
                         focusHandler: null,
                         update() {
                             if (this.paused) return;
                             const elapsedSinceInit = Math.floor((Date.now() - this.startTime) / 1000);
                             this.seconds = this.initialSeconds + Math.max(0, elapsedSinceInit);
                         },
                         formatTime(sec) {
                             let h = Math.floor(sec / 3600).toString().padStart(2, '0');
                             let m = Math.floor((sec % 3600) / 60).toString().padStart(2, '0');
                             let s = (sec % 60).toString().padStart(2, '0');
                             return `${h}:${m}:${s}`;
                         }
                     }"
                     x-init="
                         if (!paused) {
                             update();
                             timer = setInterval(() => { update(); }, 1000);

                             visibilityHandler = () => {
                                 if (document.visibilityState === 'visible') update();
                             };
                             focusHandler = () => { update(); };

                             document.addEventListener('visibilitychange', visibilityHandler);
                             window.addEventListener('focus', focusHandler);
                         }
                         $cleanup(() => {
                             if (timer) clearInterval(timer);
                             if (visibilityHandler) document.removeEventListener('visibilitychange', visibilityHandler);
                             if (focusHandler) window.removeEventListener('focus', focusHandler);
                         });
                     ">
                    
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <div class="font-bold text-base sm:text-lg text-zinc-900 dark:text-zinc-100 truncate">{{ $running->detail }}</div>
                            @if($isPaused)
                                <span class="bg-amber-500/20 text-amber-700 dark:text-amber-300 text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-md font-mono font-bold border border-amber-500/40 flex items-center gap-1 animate-pulse">
                                    <span class="size-1.5 rounded-full bg-amber-500"></span>
                                    <span>PAUSED</span>
                                </span>
                            @else
                                <span class="bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-md font-mono font-bold border border-emerald-500/40 flex items-center gap-1">
                                    <span class="size-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                    <span>LIVE</span>
                                </span>
                            @endif
                        </div>

                        <div class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 font-medium text-neutral-700 dark:text-neutral-300">
                                <flux:icon name="folder" class="size-3.5 {{ $isPaused ? 'text-amber-500' : 'text-emerald-500' }} shrink-0" />
                                <span>{{ $running->project->name }}</span>
                            </span>
                            <span>&bull;</span>
                            <span>{{ $running->category->name }}</span>
                            @if($running->is_parallel) 
                                <span class="bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-[9px] uppercase tracking-wider px-2 py-0.5 rounded-md font-mono font-bold ml-1 border border-indigo-500/20">Parallel</span> 
                            @endif
                        </div>
                    </div>

                    <!-- Live Stopwatch Readout & Actions (Pause / Resume / Stop) -->
                    <div class="flex items-center justify-between sm:justify-end gap-3 w-full sm:w-auto">
                        <div class="flex items-center gap-2">
                            <flux:icon name="clock" class="size-4 {{ $isPaused ? 'text-amber-500' : 'text-emerald-500 animate-spin' }}" style="animation-duration: 3s;" />
                            <span class="font-mono text-2xl sm:text-3xl font-extrabold tracking-wider {{ $isPaused ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}" 
                                  x-text="formatTime(seconds)"></span>
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            @if($isPaused)
                                <!-- Resume Button -->
                                <button type="button" 
                                        wire:click="resumeActivity({{ $running->id }})" 
                                        class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs sm:text-sm px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl border border-emerald-500/80 shadow-xs shadow-emerald-600/20 active:scale-95 transition-all flex items-center gap-1.5 cursor-pointer shrink-0" 
                                        title="Resume Activity">
                                    <flux:icon name="play" class="size-3.5 sm:size-4 fill-current" />
                                    <span>Resume</span>
                                </button>
                            @else
                                <!-- Pause Button -->
                                <button type="button" 
                                        wire:click="pauseActivity({{ $running->id }})" 
                                        class="bg-amber-500 hover:bg-amber-400 text-white font-semibold text-xs sm:text-sm px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl border border-amber-400/80 shadow-xs shadow-amber-500/20 active:scale-95 transition-all flex items-center gap-1.5 cursor-pointer shrink-0" 
                                        title="Pause Activity">
                                    <flux:icon name="pause" class="size-3.5 sm:size-4 fill-current" />
                                    <span>Pause</span>
                                </button>
                            @endif

                            <!-- Stop Button -->
                            <button type="button" 
                                    wire:click="stopActivity({{ $running->id }})" 
                                    class="bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs sm:text-sm px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl border border-rose-500/80 shadow-xs shadow-rose-600/20 active:scale-95 transition-all flex items-center gap-1.5 cursor-pointer shrink-0" 
                                    title="Stop Activity">
                                <flux:icon name="stop" class="size-3.5 sm:size-4 fill-current" />
                                <span>Stop</span>
                            </button>
                        </div>
                    </div>

                    <!-- Bottom Pulse Bar -->
                    <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-transparent {{ $isPaused ? 'via-amber-500/60' : 'via-emerald-500/60' }} to-transparent animate-pulse"></div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- TASK STREAM WIDGET (Interactive & Professional Command Center) -->
    <div x-data="{
             isStreamHidden: localStorage.getItem('dashboard_task_stream_hidden') !== 'false',
             toggleStream() {
                 this.isStreamHidden = !this.isStreamHidden;
                 localStorage.setItem('dashboard_task_stream_hidden', this.isStreamHidden ? 'true' : 'false');
             }
         }"
         class="flex flex-col mt-2 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800 rounded-2xl p-4 shadow-sm transition-colors duration-200">
        <!-- Top Bar: Header, Momentum Progress & Main Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3 cursor-pointer select-none group/stream-title"
                 @click="toggleStream()"
                 title="Click to toggle On Progress Stream">
                <div class="size-8.5 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-500 shrink-0 shadow-xs group-hover/stream-title:scale-105 group-hover/stream-title:bg-amber-500/20 transition-all">
                    <flux:icon name="bolt" class="size-4 text-amber-500" />
                </div>
                <div>
                    <h3 class="text-xs font-mono font-bold uppercase tracking-wider text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <span class="group-hover/stream-title:text-amber-600 dark:group-hover/stream-title:text-amber-400 transition-colors">ON PROGRESS STREAM</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/25 flex items-center gap-1">
                            <span class="size-1.5 rounded-full bg-amber-500 animate-ping"></span>
                            <span>{{ $this->todayTasks->count() }} active</span>
                        </span>
                    </h3>
                    <div class="flex items-center gap-2 mt-1">
                        <div class="w-28 sm:w-36 bg-zinc-200 dark:bg-zinc-800 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-gradient-to-r from-amber-500 to-emerald-500 h-full transition-all duration-500 rounded-full" style="width: {{ $this->taskProgressStats['percentage'] }}%"></div>
                        </div>
                        <span class="text-[10px] font-mono text-zinc-500 dark:text-zinc-400 font-medium">
                            {{ $this->taskProgressStats['done_today'] }}/{{ $this->taskProgressStats['total_active'] }} done ({{ $this->taskProgressStats['percentage'] }}%)
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
                <button type="button" 
                        wire:click="$toggle('showQuickTaskForm')" 
                        @click="if (isStreamHidden) { isStreamHidden = false; localStorage.setItem('dashboard_task_stream_hidden', 'false'); }"
                        class="px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/25 hover:bg-amber-500 hover:text-white transition-all flex items-center gap-1.5 cursor-pointer active:scale-95 shadow-2xs">
                    <flux:icon name="plus" class="size-3.5" />
                    <span>Quick Add</span>
                </button>

                <flux:button variant="subtle" size="xs" href="{{ route('manage') }}" wire:navigate class="cursor-pointer font-semibold text-xs text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100 active:scale-95 transition-all flex items-center gap-1">
                    <span>Manage Tasks</span>
                    <flux:icon name="arrow-right" class="size-3" />
                </flux:button>

                <!-- Hide / Show Toggle Button -->
                <button type="button" 
                        @click="toggleStream()"
                        :aria-expanded="(!isStreamHidden).toString()"
                        aria-controls="dashboard-task-stream-content"
                        :title="isStreamHidden ? 'Show On Progress Stream' : 'Hide On Progress Stream'"
                        class="px-2.5 py-1.5 rounded-xl text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 bg-zinc-100/80 hover:bg-zinc-200/80 dark:bg-zinc-800/80 dark:hover:bg-zinc-700/80 border border-zinc-200/80 dark:border-zinc-700/60 transition-all flex items-center gap-1.5 cursor-pointer active:scale-95 shadow-2xs">
                    <flux:icon name="chevron-down" class="size-3.5 transition-transform duration-300" ::class="!isStreamHidden && 'rotate-180'" />
                    <span x-text="isStreamHidden ? 'Show' : 'Hide'">Show</span>
                </button>
            </div>
        </div>

        <!-- Collapsible Content Wrapper -->
        <div x-show="!isStreamHidden" 
             x-collapse 
             id="dashboard-task-stream-content"
             style="display: none;">
            <div class="pt-3.5 mt-3.5 border-t border-zinc-200/60 dark:border-zinc-800/60 space-y-3.5">
                <!-- Toolbar: Filter Tabs & Quick Search Input -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            <!-- Tabs (On Progress & Done Today) -->
            <div class="flex items-center gap-1 bg-zinc-100 dark:bg-zinc-950/70 p-1 rounded-xl border border-zinc-200/80 dark:border-zinc-800/80 overflow-x-auto custom-scrollbar">
                <button type="button" wire:click="$set('taskFilter', 'on_progress')"
                        class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold transition-all whitespace-nowrap cursor-pointer flex items-center gap-1.5 {{ $taskFilter === 'on_progress' ? 'bg-white dark:bg-zinc-800 text-amber-600 dark:text-amber-400 shadow-2xs border border-zinc-200/60 dark:border-zinc-700/60' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                    <span class="size-1.5 rounded-full bg-amber-500"></span>
                    <span>On Progress</span>
                    <span class="px-1.5 py-0.2 rounded-md text-[10px] bg-amber-500/10 text-amber-600 dark:text-amber-400 font-mono">{{ $this->taskProgressStats['on_progress'] }}</span>
                </button>

                <button type="button" wire:click="$set('taskFilter', 'done_today')"
                        class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold transition-all whitespace-nowrap cursor-pointer flex items-center gap-1.5 {{ $taskFilter === 'done_today' ? 'bg-white dark:bg-zinc-800 text-emerald-600 dark:text-emerald-400 shadow-2xs border border-zinc-200/60 dark:border-zinc-700/60' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                    <span class="size-1.5 rounded-full bg-emerald-500"></span>
                    <span>Done Today</span>
                    <span class="px-1.5 py-0.2 rounded-md text-[10px] bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-mono">{{ $this->taskProgressStats['done_today'] }}</span>
                </button>
            </div>

            <!-- Instant Search Input -->
            <div class="relative w-full sm:w-52">
                <flux:icon name="magnifying-glass" class="absolute left-2.5 top-1/2 -translate-y-1/2 size-3.5 text-zinc-400 pointer-events-none" />
                <input type="text" 
                       wire:model.live.debounce.150ms="taskSearch"
                       placeholder="Search tasks..." 
                       class="w-full h-8 pl-8 pr-7 rounded-xl text-xs bg-zinc-100 dark:bg-zinc-950/70 border border-zinc-200/80 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:outline-none focus:border-amber-500/60 transition-all" />
                @if($taskSearch)
                    <button type="button" wire:click="$set('taskSearch', '')" class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 text-xs cursor-pointer">✕</button>
                @endif
            </div>
        </div>

        <!-- Inline Quick Add Task Form Collapsible -->
        @if($showQuickTaskForm)
            <form wire:submit.prevent="createQuickTask" class="p-3 bg-amber-500/5 dark:bg-amber-500/10 border border-amber-500/20 rounded-xl flex flex-col sm:flex-row gap-2 items-stretch sm:items-center">
                <input type="text" 
                       wire:model="newTaskTitle" 
                       placeholder="What task are you working on today?..." 
                       class="flex-1 h-9 px-3 text-xs bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-amber-500" 
                       required 
                       autofocus />

                <div class="flex items-center gap-2">
                    <select wire:model="newTaskProjectId" 
                            class="h-9 px-2 text-xs bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-700 rounded-lg text-zinc-700 dark:text-zinc-300 focus:outline-none cursor-pointer">
                        <option value="">No Project</option>
                        @foreach($this->projects as $proj)
                            <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="h-9 px-3.5 bg-amber-500 hover:bg-amber-600 text-white font-semibold text-xs rounded-lg transition-all active:scale-95 shadow-xs flex items-center gap-1 cursor-pointer shrink-0">
                        <flux:icon name="plus" class="size-3.5" />
                        <span>Add Task</span>
                    </button>

                    <button type="button" wire:click="$set('showQuickTaskForm', false)" class="h-9 px-2.5 text-xs text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 font-medium cursor-pointer">
                        Cancel
                    </button>
                </div>
            </form>
        @endif

        <!-- Stream Row Items -->
        @if($this->filteredTasks->count() > 0)
            <div class="divide-y divide-zinc-200/50 dark:divide-zinc-800/50 border border-zinc-200/80 dark:border-zinc-800 rounded-xl overflow-hidden bg-white/40 dark:bg-zinc-900/40">
                @foreach($this->filteredTasks as $task)
                    <div wire:key="dashboard-stream-task-{{ $task->id }}" 
                         x-data="{ isCompleting: false }"
                         :class="{ 'opacity-40 scale-98 bg-emerald-500/5': isCompleting }"
                         class="group px-3.5 py-3 hover:bg-zinc-100/60 dark:hover:bg-zinc-800/40 transition-all duration-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        
                        <!-- Left Task Title & Project Tag & Labels -->
                        <div class="min-w-0 flex-1 flex flex-col sm:flex-row sm:items-center gap-2">
                            <!-- Task Status Toggle Checkbox Button -->
                            @if($task->status === 'done')
                                <button type="button" 
                                        wire:click="updateTaskStatus({{ $task->id }}, 'on_progress')"
                                        class="group/revert size-5 rounded-md bg-emerald-500 border border-emerald-500 text-white flex items-center justify-center shrink-0 transition-all cursor-pointer hover:bg-amber-500 hover:border-amber-500 active:scale-95 shadow-2xs"
                                        title="Move back to On Progress">
                                    <flux:icon name="check" class="size-3.5 stroke-[3] group-hover/revert:hidden" />
                                    <flux:icon name="arrow-path" class="size-3 hidden group-hover/revert:block stroke-[2.5]" />
                                </button>
                            @else
                                <button type="button" 
                                        @click="isCompleting = true; $wire.markTaskDone({{ $task->id }})"
                                        class="size-5 rounded-md border border-zinc-300 dark:border-zinc-600 hover:border-emerald-500 group-hover:border-amber-500 flex items-center justify-center shrink-0 transition-all cursor-pointer active:scale-95"
                                        title="Mark as Done">
                                    <span class="size-2 rounded-full bg-transparent group-hover:bg-amber-500/50 transition-all"></span>
                                </button>
                            @endif

                            <div class="min-w-0 flex-1 flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2.5">
                                <h4 class="font-semibold text-sm text-zinc-900 dark:text-zinc-100 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors truncate {{ $task->status === 'done' ? 'line-through text-zinc-400 dark:text-zinc-500' : '' }}"
                                    :class="{ 'line-through text-zinc-400': isCompleting }">
                                    {{ $task->title }}
                                </h4>

                                <div class="flex items-center gap-1.5 shrink-0 flex-wrap">
                                    @if($task->project)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-medium text-zinc-600 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-800/80 px-2 py-0.5 rounded-md border border-zinc-200/60 dark:border-zinc-700/50 max-w-[150px] truncate">
                                            <span class="size-1.5 rounded-full bg-amber-500 shrink-0"></span>
                                            <span class="truncate">{{ $task->project->name }}</span>
                                        </span>
                                    @endif

                                    @if($task->due_badge)
                                        @php $badge = $task->due_badge; @endphp
                                        <span class="inline-flex items-center gap-1 text-[9px] font-semibold px-2 py-0.5 rounded-md border
                                            {{ $badge['color'] === 'rose' ? 'bg-rose-500/10 text-rose-700 dark:text-rose-300 border-rose-200/80 dark:border-rose-900/40 font-bold' : '' }}
                                            {{ $badge['color'] === 'amber' ? 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-200/80 dark:border-amber-900/40 font-bold' : '' }}
                                            {{ $badge['color'] === 'indigo' ? 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border-indigo-200/80 dark:border-indigo-900/40' : '' }}
                                            {{ $badge['color'] === 'sky' ? 'bg-sky-500/10 text-sky-700 dark:text-sky-300 border-sky-200/80 dark:border-sky-900/40' : '' }}
                                            {{ $badge['color'] === 'zinc' ? 'bg-zinc-100/60 dark:bg-zinc-800/40 text-zinc-500 dark:text-zinc-400 border-zinc-200/50 dark:border-zinc-800/80' : '' }}">
                                            <flux:icon name="{{ $badge['icon'] }}" class="size-2.5 shrink-0" />
                                            <span>{{ $badge['label'] }}</span>
                                        </span>
                                    @endif

                                    @foreach($task->labels as $label)
                                        <span class="inline-flex items-center text-[9px] font-semibold px-2 py-0.5 rounded-md border {{ $this->getLabelBgClass($label->color) }}">
                                            {{ $label->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Right Meta -->
                        <div class="flex items-center justify-between sm:justify-end gap-2.5 shrink-0 pt-1.5 sm:pt-0 border-t sm:border-t-0 border-zinc-200/40 dark:border-zinc-800/40">
                            <!-- Time ago badge -->
                            <span class="text-[11px] font-mono text-zinc-400 dark:text-zinc-500">
                                {{ $task->created_at->locale('en')->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif(!empty(trim($taskSearch)))
            <!-- Search No Results Empty State -->
            <div class="py-8 px-4 rounded-2xl text-center text-xs text-zinc-400 border border-dashed border-zinc-200 dark:border-zinc-800 flex flex-col items-center justify-center gap-2">
                <div class="size-10 rounded-xl bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-400 shrink-0">
                    <flux:icon name="magnifying-glass" class="size-5" />
                </div>
                <div class="max-w-xs">
                    <span class="font-medium text-zinc-700 dark:text-zinc-300 block">No tasks match "<span class="font-semibold text-amber-500">{{ $taskSearch }}</span>"</span>
                    <span class="text-[11px] text-zinc-400 mt-0.5 block">Try searching with a different keyword or clear the search query.</span>
                </div>
                <button type="button" wire:click="$set('taskSearch', '')" class="mt-1 text-xs text-amber-600 dark:text-amber-400 hover:underline font-semibold flex items-center gap-1 cursor-pointer">
                    <span>Clear search filter</span>
                </button>
            </div>
        @elseif($taskFilter === 'on_progress')
            <!-- Celebratory Empty State when 0 On-Progress tasks exist -->
            <div class="py-10 px-6 rounded-2xl text-center border border-dashed border-amber-500/25 bg-gradient-to-b from-amber-500/5 via-transparent to-emerald-500/5 flex flex-col items-center justify-center gap-3">
                <div class="relative flex items-center justify-center mb-1">
                    <span class="absolute size-14 rounded-full bg-amber-500/15 animate-ping"></span>
                    <div class="relative size-12 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-500 shadow-sm">
                        <flux:icon name="sparkles" class="size-6 text-amber-500" />
                    </div>
                </div>

                <div class="max-w-md">
                    <h4 class="text-sm font-extrabold text-zinc-900 dark:text-zinc-100 flex items-center justify-center gap-1.5">
                        <span>All On-Progress Tasks Completed! 🎉</span>
                    </h4>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Great job! You have no pending tasks in progress right now. Enjoy your accomplishment or create a new task for today.
                    </p>
                </div>

                <button type="button" 
                        wire:click="$set('showQuickTaskForm', true)" 
                        class="mt-2 px-3.5 py-1.5 text-xs font-semibold bg-amber-500 hover:bg-amber-600 text-white rounded-xl shadow-xs transition-all active:scale-95 flex items-center gap-1.5 cursor-pointer">
                    <flux:icon name="plus" class="size-3.5" />
                    <span>Add New Task</span>
                </button>
            </div>
        @else
            <!-- Done Today Empty State -->
            <div class="py-8 px-4 rounded-2xl text-center text-xs text-zinc-400 border border-dashed border-zinc-200 dark:border-zinc-800 flex flex-col items-center justify-center gap-2">
                <flux:icon name="sparkles" class="size-6 text-zinc-400/80" />
                <span class="font-medium text-zinc-700 dark:text-zinc-300">No completed tasks recorded today yet.</span>
                <button type="button" wire:click="$set('showQuickTaskForm', true)" class="mt-1 text-xs text-amber-600 dark:text-amber-400 hover:underline font-semibold flex items-center gap-1 cursor-pointer">
                    <flux:icon name="plus" class="size-3" />
                    <span>Add New Task</span>
                </button>
            </div>
        @endif
            </div>
        </div>
    </div>

    <!-- Insights Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Activity Chart -->
        <div wire:ignore class="lg:col-span-2 bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800 rounded-2xl p-5 shadow-xs relative overflow-hidden flex flex-col justify-between group hover:border-zinc-400 dark:hover:border-zinc-700 transition-all duration-300"
             x-data="{
                 period: 'weekly',
                 isDark: document.documentElement.classList.contains('dark'),
                 initChart(labels, data) {
                     let chart = this.$refs.canvas.chartInstance;
                     if (chart) {
                         chart.data.labels = labels;
                         chart.data.datasets[0].data = data;
                         chart.update();
                         return;
                     }
                     const canvas = this.$refs.canvas;
                     const ctx = canvas.getContext('2d');
                     
                     const gradient = ctx.createLinearGradient(0, 0, 0, canvas.parentElement.offsetHeight || 300);
                     if (this.isDark) {
                         gradient.addColorStop(0, 'rgba(96, 165, 250, 0.25)');
                         gradient.addColorStop(1, 'rgba(96, 165, 250, 0.0)');
                     } else {
                         gradient.addColorStop(0, 'rgba(59, 130, 246, 0.15)');
                         gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');
                     }

                     const gridColor = this.isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.04)';
                     const textColor = this.isDark ? '#a3a3a3' : '#737373';
                     
                     this.$refs.canvas.chartInstance = new Chart(ctx, {
                         type: 'line',
                         data: {
                             labels: labels,
                             datasets: [{
                                 label: 'Hours Worked',
                                 data: data,
                                 borderColor: this.isDark ? '#60a5fa' : '#3b82f6',
                                 backgroundColor: gradient,
                                 borderWidth: 2.5,
                                 fill: true,
                                 tension: 0.4,
                                 pointBackgroundColor: this.isDark ? '#18181b' : '#ffffff',
                                 pointBorderColor: this.isDark ? '#60a5fa' : '#3b82f6',
                                 pointBorderWidth: 2,
                                 pointRadius: 0,
                                 pointHoverRadius: 5,
                                 pointHoverBackgroundColor: this.isDark ? '#60a5fa' : '#3b82f6',
                                 pointHoverBorderColor: '#ffffff',
                                 pointHoverBorderWidth: 2,
                                 pointHitRadius: 10,
                             }]
                         },
                         options: {
                             responsive: true,
                             maintainAspectRatio: false,
                             animation: { duration: 1200, easing: 'easeOutQuart' },
                             interaction: { mode: 'index', intersect: false },
                             plugins: {
                                 legend: { display: false },
                                 tooltip: {
                                     backgroundColor: this.isDark ? 'rgba(24, 24, 27, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                                     titleColor: this.isDark ? '#f4f4f5' : '#18181b',
                                     bodyColor: this.isDark ? '#d4d4d8' : '#52525b',
                                     borderColor: this.isDark ? '#3f3f46' : '#e4e4e7',
                                     borderWidth: 1,
                                     padding: 12,
                                     displayColors: false,
                                     titleFont: { size: 13, weight: '600', family: 'ui-sans-serif, system-ui, sans-serif' },
                                     bodyFont: { size: 12, family: 'ui-sans-serif, system-ui, sans-serif' },
                                     callbacks: {
                                         label: function(context) {
                                             let val = context.raw;
                                             let h = Math.floor(val);
                                             let m = Math.round((val - h) * 60);
                                             if (h === 0 && m === 0) return ' No activity';
                                             return ` ⏱ ${h}h ${m}m`;
                                         }
                                     }
                                 }
                             },
                             scales: {
                                 y: {
                                     beginAtZero: true,
                                     border: { display: false },
                                     grid: { color: gridColor, drawBorder: false },
                                     ticks: { color: textColor, padding: 10, font: { size: 11 } }
                                 },
                                 x: {
                                     border: { display: false },
                                     grid: { display: false, drawBorder: false },
                                     ticks: { color: textColor, padding: 10, font: { size: 11 } }
                                 }
                             }
                         }
                     });
                 }
             }"
              x-init="initChart(@js($this->chartStats['labels']), @js($this->chartStats['data']))"
              x-on:chart-updated.window="initChart($event.detail.stats.labels, $event.detail.stats.data)">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-zinc-300/60 dark:via-zinc-600/40 to-transparent"></div>
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-4 relative z-10">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <div class="size-7 rounded-lg bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                        <flux:icon name="chart-bar" class="size-4 text-zinc-700 dark:text-zinc-300" />
                    </div>
                    <span>Activity Overview (Hours)</span>
                </h3>
                
                <div class="flex bg-zinc-100 dark:bg-zinc-800/80 rounded-xl p-1 self-start sm:self-auto shrink-0 border border-zinc-200/80 dark:border-zinc-700/60">
                    <button type="button" @click="period = 'weekly'; $wire.set('chartPeriod', 'weekly')" 
                            class="text-[10px] px-3 py-1.5 rounded-lg font-semibold uppercase tracking-wider transition-all duration-200 cursor-pointer"
                            :class="period === 'weekly' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-xs border border-zinc-200 dark:border-zinc-600' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200'">
                        Weekly
                    </button>
                    <button type="button" @click="period = 'monthly'; $wire.set('chartPeriod', 'monthly')" 
                            class="text-[10px] px-3 py-1.5 rounded-lg font-semibold uppercase tracking-wider transition-all duration-200 cursor-pointer"
                            :class="period === 'monthly' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-xs border border-zinc-200 dark:border-zinc-600' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200'">
                        Monthly
                    </button>
                </div>
            </div>
            
            <div class="relative h-64 w-full">
                <canvas x-ref="canvas"></canvas>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        </div>

        <!-- Project Allocation Breakdown -->
        <div class="bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800 rounded-2xl p-5 shadow-xs flex flex-col justify-between relative overflow-hidden group hover:border-zinc-400 dark:hover:border-zinc-700 transition-all duration-300">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-zinc-300/60 dark:via-zinc-600/40 to-transparent"></div>
            <div>
                <div class="flex items-center justify-between mb-4 shrink-0 relative z-10">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <div class="size-7 rounded-lg bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                            <flux:icon name="chart-pie" class="size-4 text-zinc-700 dark:text-zinc-300" />
                        </div>
                        <span>Time Allocation</span>
                    </h3>
                    <span class="text-[10px] font-mono font-medium text-zinc-600 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60">Weekly</span>
                </div>
                
                @if($this->projectStats->count() > 0)
                    <div class="space-y-4 max-h-[220px] overflow-y-auto pr-1.5 custom-scrollbar relative z-10">
                        @foreach($this->projectStats as $index => $stat)
                            <div x-data="{ open: false }" class="space-y-2">
                                <button type="button" @click="open = !open" class="w-full text-left focus:outline-none group cursor-pointer block">
                                    <div class="flex items-center justify-between text-xs mb-1.5">
                                        <div class="flex items-center gap-1.5 font-semibold text-zinc-800 dark:text-zinc-200 group-hover:text-zinc-900 dark:group-hover:text-zinc-100 transition-colors">
                                            <flux:icon name="chevron-right" class="size-3.5 text-zinc-400 transition-transform duration-200" ::class="open && 'rotate-90'" />
                                            <span class="size-2 rounded-full bg-blue-600 dark:bg-blue-500 shrink-0"></span>
                                            <span>{{ $stat['name'] }}</span>
                                        </div>
                                        <span class="text-zinc-500 dark:text-zinc-400 font-mono text-[11px] group-hover:text-zinc-800 dark:group-hover:text-zinc-300 transition-colors">{{ $stat['duration'] }} <span class="font-bold text-blue-600 dark:text-blue-400">({{ $stat['percentage'] }}%)</span></span>
                                    </div>
                                    <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2 overflow-hidden p-0.5 border border-zinc-200/50 dark:border-zinc-700/50">
                                        <div class="bg-blue-600 dark:bg-blue-500 h-1.5 rounded-full transition-all duration-500 shadow-2xs" style="width: {{ $stat['percentage'] }}%"></div>
                                    </div>
                                </button>

                                <div x-show="open" 
                                     x-collapse
                                     class="pl-4 pr-1 space-y-2 border-l-2 border-emerald-500/30 dark:border-emerald-500/20 ml-2 mt-2" 
                                     style="display: none;">
                                     @foreach($stat['categories'] as $cat)
                                         <div class="space-y-1">
                                             <div class="flex justify-between text-[10px]">
                                                 <span class="text-zinc-500 dark:text-zinc-400 font-medium flex items-center gap-1">
                                                     <span class="size-1.5 rounded-full bg-emerald-500/80 inline-block"></span>
                                                     <span>{{ $cat['name'] }}</span>
                                                 </span>
                                                 <span class="text-zinc-500 dark:text-zinc-400 font-mono">{{ $cat['duration'] }} <span class="font-semibold text-emerald-600 dark:text-emerald-400">({{ $cat['percentage'] }}%)</span></span>
                                             </div>
                                             <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-1 overflow-hidden">
                                                 <div class="bg-emerald-500/70 dark:bg-emerald-500/60 h-1 rounded-full" style="width: {{ $cat['percentage'] }}%"></div>
                                             </div>
                                         </div>
                                     @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex-1 flex flex-col items-center justify-center text-center text-neutral-500 dark:text-neutral-400 space-y-2.5 py-8 border border-dashed border-zinc-200/80 dark:border-zinc-800 rounded-xl bg-zinc-50/50 dark:bg-zinc-950/30 relative z-10">
                        <div class="size-10 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-500 dark:text-zinc-400">
                            <flux:icon name="chart-pie" class="size-5" />
                        </div>
                        <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">No Time Allocated Yet</p>
                        <p class="text-[11px] text-zinc-400">Log activities this week to view project breakdowns.</p>
                    </div>
                @endif
            </div>

            <!-- Footer micro-hint -->
            <div class="mt-4 pt-3 border-t border-zinc-200/50 dark:border-zinc-800/50 text-[10px] text-zinc-500 dark:text-zinc-400 flex items-center justify-between relative z-10">
                <span>Updated in real-time</span>
                <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ $this->projectStats->count() }} Projects</span>
            </div>
        </div>
    </div>

    <!-- Recent History (Single-Row Rich ListTiles) -->
    <div class="flex flex-col gap-3 mt-2">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <div class="size-7 rounded-lg bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                    <flux:icon name="clock" class="size-4 text-zinc-700 dark:text-zinc-300" />
                </div>
                <span>Recent History Log</span>
            </h3>
            <flux:button variant="subtle" size="xs" href="{{ route('tracker') }}" wire:navigate class="cursor-pointer font-medium text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200 active:scale-95 transition-transform">
                View All Tracker &rarr;
            </flux:button>
        </div>
        
        <div class="bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800 rounded-2xl overflow-hidden shadow-xs divide-y divide-zinc-200/50 dark:divide-zinc-800/50 relative group hover:border-zinc-400 dark:hover:border-zinc-700 transition-all duration-300">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-zinc-300/60 dark:via-zinc-600/40 to-transparent"></div>
            @forelse($this->recentActivities as $activity)
                <div class="px-4 py-3.5 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors flex items-center justify-between gap-3 group/row relative z-10">
                    <!-- Left active hover indicator line -->
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-zinc-400 opacity-0 group-hover/row:opacity-100 transition-opacity"></div>
                    
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="size-9 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0 group-hover/row:scale-105 transition-transform">
                            <flux:icon name="folder" class="size-4 text-zinc-700 dark:text-zinc-300" />
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-sm text-zinc-900 dark:text-zinc-100 truncate group-hover/row:text-zinc-700 dark:group-hover/row:text-zinc-300 transition-colors">{{ $activity->detail }}</div>
                            <div class="text-[11px] truncate flex items-center gap-1.5 mt-1 flex-wrap">
                                @if($activity->task)
                                    <span class="bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-[10px] font-medium px-2 py-0.5 rounded-md border border-indigo-200 dark:border-indigo-500/20 flex items-center gap-1 shrink-0">
                                        <flux:icon name="clipboard-document-list" class="size-3 shrink-0" />
                                        <span class="truncate max-w-[140px]">{{ $activity->task->title }}</span>
                                    </span>
                                @endif
                                <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-[10px] font-medium px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60 shrink-0">
                                    {{ $activity->project->name }}
                                </span>
                                <span class="bg-zinc-100/80 dark:bg-zinc-800/60 text-zinc-600 dark:text-zinc-400 text-[10px] font-medium px-2 py-0.5 rounded-md border border-zinc-200/80 dark:border-zinc-800 shrink-0">
                                    {{ $activity->category->name }}
                                </span>
                                @if($activity->is_parallel) 
                                    <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-[9px] font-mono font-medium uppercase tracking-wider px-1.5 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60 shrink-0">Parallel</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="shrink-0 text-right">
                        <div class="font-mono text-sm font-extrabold text-zinc-900 dark:text-zinc-100 tracking-tight">{{ $activity->duration }}</div>
                        <div class="text-[10px] font-medium text-zinc-500 dark:text-zinc-400 mt-0.5 flex items-center justify-end gap-1 font-mono">
                            <span>{{ Carbon::parse($activity->start_time)->isToday() ? 'Today' : Carbon::parse($activity->start_time)->format('M d') }},</span>
                            <span title="Start Time">{{ $activity->formatted_start_time }}</span>
                            @if($activity->formatted_pause_duration)
                                <span class="text-zinc-400 dark:text-zinc-500 font-mono text-[9px] flex items-center gap-0.5"
                                      title="Paused for {{ $activity->formatted_pause_duration }}">
                                    <flux:icon name="pause" class="size-2 text-zinc-400 dark:text-zinc-500 fill-current shrink-0" />
                                    <span>{{ $activity->formatted_pause_duration }}</span>
                                </span>
                            @else
                                <span class="text-zinc-400 dark:text-zinc-600">&ndash;</span>
                            @endif
                            <span title="End Time">{{ $activity->formatted_end_time }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-xs text-neutral-400 flex flex-col items-center gap-2">
                    <flux:icon name="clock" class="size-8 text-neutral-300 dark:text-neutral-700" />
                    <span class="font-semibold text-zinc-600 dark:text-zinc-400">No recent activities logged yet.</span>
                </div>
            @endforelse
        </div>
    </div>
</div>

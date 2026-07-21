<?php

use Livewire\Component;
use App\Models\Activity;
use Carbon\Carbon;

new class extends Component
{
    public function getTodayDurationProperty()
    {
        $activities = Activity::whereNotNull('end_time')
            ->whereDate('start_time', Carbon::today())
            ->get();
        return $this->formatDuration($this->sumSeconds($activities));
    }

    public function getWeekDurationProperty()
    {
        $activities = Activity::whereNotNull('end_time')
            ->whereBetween('start_time', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->get();
        return $this->formatDuration($this->sumSeconds($activities));
    }

    public function getActiveProjectsCountProperty()
    {
        return Activity::whereBetween('start_time', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->distinct('project_id')
            ->count('project_id');
    }

    public function getRunningActivitiesProperty()
    {
        return Activity::with(['project', 'category'])
            ->whereNull('end_time')
            ->orderBy('start_time', 'desc')
            ->get();
    }

    public function getRecentActivitiesProperty()
    {
        return Activity::with(['project', 'category'])
            ->whereNotNull('end_time')
            ->orderBy('end_time', 'desc')
            ->take(5)
            ->get();
    }

    public function getDailyStatsProperty()
    {
        // Get last 7 days including today
        $days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $days->push(Carbon::today()->subDays($i)->format('Y-m-d'));
        }

        $activities = Activity::whereNotNull('end_time')
            ->whereDate('start_time', '>=', Carbon::today()->subDays(6))
            ->get();

        $chartData = $days->map(function ($day) use ($activities) {
            $dailyActivities = $activities->filter(function ($activity) use ($day) {
                return $activity->start_time->format('Y-m-d') === $day;
            });
            $seconds = $this->sumSeconds($dailyActivities);
            // Convert to hours (float)
            return round($seconds / 3600, 2);
        });

        return [
            'labels' => $days->map(fn($day) => Carbon::parse($day)->format('D, M d'))->toArray(),
            'data' => $chartData->toArray(),
        ];
    }

    public function getProjectStatsProperty()
    {
        $activities = Activity::with('project')
            ->whereNotNull('end_time')
            ->whereBetween('start_time', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->get();

        $totalSeconds = $this->sumSeconds($activities);
        if ($totalSeconds == 0) return collect();

        return $activities->groupBy('project_id')->map(function ($group) use ($totalSeconds) {
            $seconds = $this->sumSeconds($group);
            $percentage = round(($seconds / $totalSeconds) * 100);
            return [
                'name' => $group->first()->project->name,
                'seconds' => $seconds,
                'duration' => $this->formatDuration($seconds),
                'percentage' => $percentage,
            ];
        })->sortByDesc('seconds')->values();
    }

    private function sumSeconds($activities)
    {
        return $activities->sum(function ($activity) {
            return $activity->start_time->diffInSeconds($activity->end_time);
        });
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

    public function stopActivity($id)
    {
        $activity = Activity::find($id);
        if ($activity && !$activity->end_time) {
            $activity->update(['end_time' => now()]);
        }
    }
};
?>

<div class="flex h-full w-full flex-col gap-6 p-4 text-neutral-900 dark:text-neutral-100 max-w-5xl mx-auto mt-2">

    <!-- Header -->
    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-bold tracking-tight">Dashboard</h1>
        <p class="text-neutral-500 dark:text-neutral-400 text-sm">Welcome back! Here's a summary of your activity.</p>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-xl p-5 shadow-sm">
            <div class="text-sm font-medium text-neutral-500 dark:text-neutral-400 mb-1">Today's Total</div>
            <div class="text-3xl font-bold tracking-tight text-indigo-600 dark:text-indigo-400">{{ $this->todayDuration }}</div>
        </div>
        
        <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-xl p-5 shadow-sm">
            <div class="text-sm font-medium text-neutral-500 dark:text-neutral-400 mb-1">This Week</div>
            <div class="text-3xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">{{ $this->weekDuration }}</div>
        </div>

        <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-xl p-5 shadow-sm">
            <div class="text-sm font-medium text-neutral-500 dark:text-neutral-400 mb-1">Active Projects (Week)</div>
            <div class="text-3xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">{{ $this->activeProjectsCount }}</div>
        </div>
    </div>

    <!-- Running Activities -->
    @if($this->runningActivities->count() > 0)
    <div class="mt-4">
        <h2 class="text-lg font-semibold tracking-tight mb-3 flex items-center gap-2">
            <span class="relative flex h-2.5 w-2.5">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
            </span>
            Currently Working On
        </h2>
        <div class="grid gap-3">
            @foreach($this->runningActivities as $running)
                <div class="group relative overflow-hidden rounded-xl border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/50 dark:bg-emerald-900/10 p-5 shadow-sm flex justify-between items-center" 
                     x-data="{ elapsed: '00:00:00', start: new Date('{{ $running->start_time->toISOString() }}').getTime() }"
                     x-init="setInterval(() => { 
                         let diff = Math.floor((new Date().getTime() - start) / 1000);
                         let h = Math.floor(diff / 3600).toString().padStart(2, '0');
                         let m = Math.floor((diff % 3600) / 60).toString().padStart(2, '0');
                         let s = (diff % 60).toString().padStart(2, '0');
                         elapsed = `${h}:${m}:${s}`;
                     }, 1000)">
                    <div>
                        <div class="font-medium text-lg">{{ $running->detail }}</div>
                        <div class="text-sm text-neutral-600 dark:text-neutral-400 mt-1 flex items-center gap-2">
                            <span><span class="font-medium text-neutral-900 dark:text-neutral-200">{{ $running->project->name }}</span> &bull; {{ $running->category->name }}</span>
                            @if($running->is_parallel) 
                                <span class="bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-300 text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-full font-semibold">Parallel</span> 
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-5">
                        <div class="font-mono text-2xl text-emerald-700 dark:text-emerald-400 font-bold tracking-tight" x-text="elapsed"></div>
                        <flux:button variant="danger" wire:click="stopActivity({{ $running->id }})" title="Stop Activity">Stop</flux:button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Insights Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-2">
        
        <!-- Weekly Chart -->
        <div class="lg:col-span-2 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-xl p-5 shadow-sm"
             x-data="{ stats: @js($this->dailyStats) }"
             x-init="
                const ctx = $refs.canvas;
                const isDarkMode = document.documentElement.classList.contains('dark');
                const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';
                const textColor = isDarkMode ? '#a3a3a3' : '#737373';
                
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: stats.labels,
                        datasets: [{
                            label: 'Hours Worked',
                            data: stats.data,
                            backgroundColor: '#6366f1', // indigo-500
                            borderRadius: 6,
                            barThickness: 'flex',
                            maxBarThickness: 45,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: isDarkMode ? '#262626' : '#ffffff',
                                titleColor: isDarkMode ? '#f5f5f5' : '#171717',
                                bodyColor: isDarkMode ? '#d4d4d4' : '#525252',
                                borderColor: isDarkMode ? '#404040' : '#e5e5e5',
                                borderWidth: 1,
                                padding: 10,
                                displayColors: false,
                                callbacks: {
                                    label: function(context) {
                                        let val = context.raw;
                                        let h = Math.floor(val);
                                        let m = Math.round((val - h) * 60);
                                        return ` ${h}h ${m}m`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: gridColor },
                                ticks: { color: textColor }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { color: textColor }
                            }
                        }
                    }
                });
             ">
            <h2 class="text-lg font-semibold tracking-tight mb-4">Activity (Last 7 Days)</h2>
            <div class="relative h-64 w-full">
                <canvas x-ref="canvas"></canvas>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        </div>

        <!-- Project Distribution -->
        <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-xl p-5 shadow-sm">
            <h2 class="text-lg font-semibold tracking-tight mb-4">Time Allocation (This Week)</h2>
            
            @if($this->projectStats->count() > 0)
                <div class="space-y-5">
                    @foreach($this->projectStats as $stat)
                        <div>
                            <div class="flex justify-between text-sm mb-1.5">
                                <span class="font-medium text-neutral-900 dark:text-neutral-100">{{ $stat['name'] }}</span>
                                <span class="text-neutral-500 dark:text-neutral-400">{{ $stat['duration'] }} ({{ $stat['percentage'] }}%)</span>
                            </div>
                            <div class="w-full bg-neutral-100 dark:bg-neutral-800 rounded-full h-2">
                                <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $stat['percentage'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="h-full flex flex-col items-center justify-center text-center text-neutral-500 dark:text-neutral-400 space-y-3 py-10">
                    <flux:icon.chart-pie class="w-10 h-10 text-neutral-300 dark:text-neutral-700" />
                    <p class="text-sm">No project data for this week yet.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Recent History -->
    <div class="mt-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold tracking-tight">Recent History</h2>
            <flux:button variant="subtle" size="sm" href="{{ route('tracker') }}" wire:navigate>View All</flux:button>
        </div>
        
        <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-xl overflow-hidden shadow-sm">
            @if($this->recentActivities->count() > 0)
                <div class="divide-y divide-neutral-200 dark:divide-neutral-800">
                    @foreach($this->recentActivities as $activity)
                        <div class="p-4 hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors flex justify-between items-center group">
                            <div>
                                <div class="font-medium text-neutral-900 dark:text-neutral-100">{{ $activity->detail }}</div>
                                <div class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">
                                    {{ $activity->project->name }} &bull; {{ $activity->category->name }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-mono font-semibold text-neutral-700 dark:text-neutral-300">{{ $activity->duration }}</div>
                                <div class="text-xs text-neutral-400 dark:text-neutral-500 mt-1">
                                    {{ Carbon::parse($activity->start_time)->isToday() ? 'Today' : Carbon::parse($activity->start_time)->format('M d') }}, {{ $activity->start_time->format('H:i') }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center text-sm text-neutral-500 dark:text-neutral-400">
                    No recent activities found. Start tracking to see your history!
                </div>
            @endif
        </div>
    </div>

</div>

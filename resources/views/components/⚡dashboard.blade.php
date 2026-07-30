<?php

use Livewire\Component;
use App\Models\Activity;
use Carbon\Carbon;

new class extends Component
{
    public $chartPeriod = 'weekly';

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
            ->with(['project', 'category'])
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

    public function stopActivity($id)
    {
        $activity = auth()->user()->activities()->find($id);
        if ($activity && !$activity->end_time) {
            $activity->update(['end_time' => now()]);
        }
    }
};
?>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 9999px; }
</style>

<div class="flex h-full w-full flex-col gap-6 p-3 sm:p-4 text-neutral-900 dark:text-neutral-100 max-w-6xl mx-auto mt-2 sm:mt-4 pb-12" x-data="{ scrolled: false }" @scroll.window="scrolled = (window.pageYOffset > 300)">

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
    <div class="grid grid-cols-3 gap-2.5 sm:gap-5">
        <!-- Card 1: Today's Total -->
        <div class="relative overflow-hidden bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800 rounded-2xl p-3.5 sm:p-5 shadow-xs flex flex-col justify-between group hover:border-zinc-400 dark:hover:border-zinc-700 transition-all duration-300">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-zinc-300/60 dark:via-zinc-600/40 to-transparent"></div>
            <div class="flex items-center justify-between mb-2 sm:mb-3">
                <div class="size-8 sm:size-10 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0 group-hover:scale-105 transition-transform">
                    <flux:icon name="clock" class="size-4 sm:size-5 text-zinc-700 dark:text-zinc-300" />
                </div>
                <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 text-[8px] sm:text-[10px] font-mono font-medium px-1.5 sm:px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60">
                    Today
                </span>
            </div>

            <div>
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 truncate">Today's Total</div>
                <div class="text-xl sm:text-3xl font-mono font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100 mt-0.5 sm:mt-1 truncate">{{ $this->todayDuration }}</div>
                <p class="text-[10px] sm:text-[11px] text-zinc-500 dark:text-zinc-400 mt-1 hidden sm:block truncate">
                    Logged today
                </p>
            </div>
        </div>
        
        <!-- Card 2: This Week -->
        <div class="relative overflow-hidden bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800 rounded-2xl p-3.5 sm:p-5 shadow-xs flex flex-col justify-between group hover:border-zinc-400 dark:hover:border-zinc-700 transition-all duration-300">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-zinc-300/60 dark:via-zinc-600/40 to-transparent"></div>
            <div class="flex items-center justify-between mb-2 sm:mb-3">
                <div class="size-8 sm:size-10 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0 group-hover:scale-105 transition-transform">
                    <flux:icon name="calendar-days" class="size-4 sm:size-5 text-zinc-700 dark:text-zinc-300" />
                </div>
                <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 text-[8px] sm:text-[10px] font-mono font-medium px-1.5 sm:px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60">
                    Week
                </span>
            </div>

            <div>
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 truncate">This Week</div>
                <div class="text-xl sm:text-3xl font-mono font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100 mt-0.5 sm:mt-1 truncate">{{ $this->weekDuration }}</div>
                <p class="text-[10px] sm:text-[11px] text-zinc-500 dark:text-zinc-400 mt-1 hidden sm:block truncate">
                    Cumulative week
                </p>
            </div>
        </div>

        <!-- Card 3: Active Projects -->
        <div class="relative overflow-hidden bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800 rounded-2xl p-3.5 sm:p-5 shadow-xs flex flex-col justify-between group hover:border-zinc-400 dark:hover:border-zinc-700 transition-all duration-300">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-zinc-300/60 dark:via-zinc-600/40 to-transparent"></div>
            <div class="flex items-center justify-between mb-2 sm:mb-3">
                <div class="size-8 sm:size-10 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0 group-hover:scale-105 transition-transform">
                    <flux:icon name="briefcase" class="size-4 sm:size-5 text-zinc-700 dark:text-zinc-300" />
                </div>
                <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 text-[8px] sm:text-[10px] font-mono font-medium px-1.5 sm:px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60">
                    Active
                </span>
            </div>

            <div>
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 truncate">Projects</div>
                <div class="text-xl sm:text-3xl font-mono font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100 mt-0.5 sm:mt-1 truncate">{{ $this->activeProjectsCount }}</div>
                <p class="text-[10px] sm:text-[11px] text-zinc-500 dark:text-zinc-400 mt-1 hidden sm:block truncate">
                    Logged projects
                </p>
            </div>
        </div>
    </div>

    <!-- Currently Active Tracking (Initial Layout with Modern Live Timer Motion Cues) -->
    @if($this->runningActivities->count() > 0)
    <div class="mt-1">
        <h2 class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 mb-3 flex items-center gap-2 font-mono">
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
                            <div class="text-[11px] truncate flex items-center gap-1.5 mt-1">
                                <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-[10px] font-medium px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60">
                                    {{ $activity->project->name }}
                                </span>
                                <span class="bg-zinc-100/80 dark:bg-zinc-800/60 text-zinc-600 dark:text-zinc-400 text-[10px] font-medium px-2 py-0.5 rounded-md border border-zinc-200/80 dark:border-zinc-800">
                                    {{ $activity->category->name }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="shrink-0 text-right">
                        <div class="font-mono text-sm font-extrabold text-zinc-900 dark:text-zinc-100 tracking-tight">{{ $activity->duration }}</div>
                        <div class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 mt-0.5">
                            {{ Carbon::parse($activity->start_time)->isToday() ? 'Today' : Carbon::parse($activity->start_time)->format('M d') }}, {{ $activity->start_time->format('H:i') }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-xs text-neutral-400 flex flex-col items-center gap-2">
                    <flux:icon name="clock" class="size-8 text-neutral-300 dark:text-neutral-700" />
                    <span class="font-semibold text-zinc-600 dark:text-zinc-400">No recent activities logged yet.</span>
                    <span class="text-[11px]">Start a new tracker from the activity bar below.</span>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Back to top button (Centered on mobile, Right-aligned on PC) -->
    <div class="fixed bottom-8 left-1/2 -translate-x-1/2 md:left-auto md:right-6 lg:right-8 md:translate-x-0 pointer-events-none z-50"
         x-cloak
         x-show="scrolled" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-8"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-8">
        
        <button 
            @click="window.scrollTo({top: 0, behavior: 'smooth'})"
            class="pointer-events-auto bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 px-5 py-2.5 rounded-full shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-900 dark:focus:ring-white flex items-center gap-2 font-semibold text-xs border border-zinc-700/20 dark:border-zinc-200/20"
            title="Back to top"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
            Back to top
        </button>
    </div>

</div>

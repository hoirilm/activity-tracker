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

<div class="flex h-full w-full flex-col gap-6 p-4 text-neutral-900 dark:text-neutral-100 max-w-5xl mx-auto mt-4">

    <!-- Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-800 pb-4">
        <h2 class="text-xl font-semibold tracking-tight">Dashboard</h2>
        <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Welcome back, {{ auth()->user()->name }}! Here is a summary of your tracked work.</p>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Card 1 -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-xs flex items-center gap-4">
            <div class="size-11 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100/50 dark:border-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
                <flux:icon name="clock" class="size-5" />
            </div>
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Today's Total</div>
                <div class="text-2xl font-bold tracking-tight text-indigo-600 dark:text-indigo-400 mt-0.5">{{ $this->todayDuration }}</div>
            </div>
        </div>
        
        <!-- Card 2 -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-xs flex items-center gap-4">
            <div class="size-11 rounded-xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-200/50 dark:border-zinc-800/40 flex items-center justify-center text-zinc-550 dark:text-zinc-400 shrink-0">
                <flux:icon name="calendar-days" class="size-5" />
            </div>
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">This Week</div>
                <div class="text-2xl font-bold tracking-tight text-zinc-850 dark:text-zinc-150 mt-0.5">{{ $this->weekDuration }}</div>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-xs flex items-center gap-4">
            <div class="size-11 rounded-xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-200/50 dark:border-zinc-800/40 flex items-center justify-center text-zinc-550 dark:text-zinc-400 shrink-0">
                <flux:icon name="briefcase" class="size-5" />
            </div>
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Active Projects (Week)</div>
                <div class="text-2xl font-bold tracking-tight text-zinc-850 dark:text-zinc-150 mt-0.5">{{ $this->activeProjectsCount }}</div>
            </div>
        </div>
    </div>

    <!-- Running Activities -->
    @if($this->runningActivities->count() > 0)
    <div class="mt-2">
        <h2 class="text-sm font-semibold text-zinc-850 dark:text-zinc-150 mb-3 flex items-center gap-2">
            <span class="relative flex h-2.5 w-2.5">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
            </span>
            <span>Currently Working On</span>
        </h2>
        <div class="grid gap-3">
            @foreach($this->runningActivities as $running)
                <div wire:key="running-{{ $running->id }}" class="group relative overflow-hidden rounded-2xl border border-emerald-100 dark:border-emerald-900/30 bg-emerald-50/50 dark:bg-emerald-950/10 p-5 shadow-xs flex justify-between items-center" 
                     x-data="{ elapsed: '00:00:00', start: new Date('{{ $running->start_time->toISOString() }}').getTime() }"
                     x-init="setInterval(() => { 
                          let diff = Math.floor((new Date().getTime() - start) / 1000);
                          let h = Math.floor(diff / 3600).toString().padStart(2, '0');
                          let m = Math.floor((diff % 3600) / 60).toString().padStart(2, '0');
                          let s = (diff % 60).toString().padStart(2, '0');
                          elapsed = `${h}:${m}:${s}`;
                      }, 1000)">
                    <div>
                        <div class="font-medium text-base text-zinc-850 dark:text-zinc-150">{{ $running->detail }}</div>
                        <div class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 flex items-center gap-1.5">
                            <flux:icon name="folder" class="size-3.5 shrink-0" />
                            <span><span class="font-medium text-neutral-700 dark:text-neutral-300">{{ $running->project->name }}</span> &bull; {{ $running->category->name }}</span>
                            @if($running->is_parallel) 
                                <span class="bg-indigo-100 dark:bg-indigo-950/40 text-indigo-800 dark:text-indigo-400 text-[9px] uppercase tracking-wider px-2 py-0.5 rounded font-semibold ml-2">Parallel</span> 
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-5">
                        <div class="font-mono text-2xl text-emerald-600 dark:text-emerald-455 font-bold tracking-tight" x-text="elapsed"></div>
                        <flux:button variant="danger" wire:click="stopActivity({{ $running->id }})" size="sm" class="cursor-pointer" title="Stop Activity">Stop</flux:button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Insights Cards -->
        
        <!-- Activity Chart (Daily, Weekly, Monthly, Yearly) -->
        <div wire:ignore class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-xs relative overflow-hidden"
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
                     
                     // Create a sleek gradient for the area chart
                     const gradient = ctx.createLinearGradient(0, 0, 0, canvas.parentElement.offsetHeight || 300);
                     if (this.isDark) {
                         gradient.addColorStop(0, 'rgba(99, 102, 241, 0.3)'); // Indigo 500
                         gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');
                     } else {
                         gradient.addColorStop(0, 'rgba(99, 102, 241, 0.15)');
                         gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');
                     }

                     const gridColor = this.isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.03)';
                     const textColor = this.isDark ? '#a3a3a3' : '#737373';
                     
                     this.$refs.canvas.chartInstance = new Chart(ctx, {
                         type: 'line',
                         data: {
                             labels: labels,
                             datasets: [{
                                 label: 'Hours Worked',
                                 data: data,
                                 borderColor: '#6366f1',
                                 backgroundColor: gradient,
                                 borderWidth: 2.5,
                                 fill: true,
                                 tension: 0.4, // Smooth bezier curves
                                 pointBackgroundColor: this.isDark ? '#18181b' : '#ffffff',
                                 pointBorderColor: '#6366f1',
                                 pointBorderWidth: 2,
                                 pointRadius: 0, // Hide points by default
                                 pointHoverRadius: 5, // Show on hover
                                 pointHoverBackgroundColor: '#6366f1',
                                 pointHoverBorderColor: '#ffffff',
                                 pointHoverBorderWidth: 2,
                                 pointHitRadius: 10,
                             }]
                         },
                         options: {
                             responsive: true,
                             maintainAspectRatio: false,
                             animation: { 
                                 duration: 1200,
                                 easing: 'easeOutQuart'
                             },
                             interaction: {
                                 mode: 'index',
                                 intersect: false,
                             },
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
                                     boxPadding: 4,
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
                                     grid: { 
                                         color: gridColor,
                                         drawBorder: false,
                                     },
                                     ticks: { 
                                         color: textColor,
                                         padding: 10,
                                         font: { size: 11 }
                                     }
                                 },
                                 x: {
                                     border: { display: false },
                                     grid: { display: false, drawBorder: false },
                                     ticks: { 
                                         color: textColor,
                                         padding: 10,
                                         font: { size: 11 }
                                     }
                                 }
                             }
                         }
                     });
                 }
             }"
             x-init="initChart(@js($this->chartStats['labels']), @js($this->chartStats['data']))"
             x-on:chart-updated.window="initChart($event.detail.stats.labels, $event.detail.stats.data)">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-4">
                <h3 class="text-sm font-semibold text-zinc-850 dark:text-zinc-150 flex items-center gap-2">
                    <flux:icon name="chart-bar" class="size-4.5 text-zinc-500" />
                    <span>Activity Overview (Hours)</span>
                </h3>
                
                <!-- Period Toggle Tabs -->
                <div class="flex bg-zinc-100 dark:bg-zinc-800/80 rounded-lg p-0.5 self-start sm:self-auto shrink-0">
                    <button type="button" @click="period = 'weekly'; $wire.set('chartPeriod', 'weekly')" 
                            class="text-[10px] px-3 py-1.5 rounded-md font-bold uppercase tracking-wider transition-colors cursor-pointer"
                            :class="period === 'weekly' ? 'bg-white dark:bg-zinc-900 shadow-xs text-zinc-900 dark:text-zinc-100' : 'text-zinc-450 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200'">
                        Weekly
                    </button>
                    <button type="button" @click="period = 'monthly'; $wire.set('chartPeriod', 'monthly')" 
                            class="text-[10px] px-3 py-1.5 rounded-md font-bold uppercase tracking-wider transition-colors cursor-pointer"
                            :class="period === 'monthly' ? 'bg-white dark:bg-zinc-900 shadow-xs text-zinc-900 dark:text-zinc-100' : 'text-zinc-450 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200'">
                        Monthly
                    </button>
                </div>
            </div>
            
            <div class="relative h-64 w-full">
                <canvas x-ref="canvas"></canvas>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        </div>

        <!-- Project Distribution -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-xs">
            <h3 class="text-sm font-semibold text-zinc-850 dark:text-zinc-150 mb-4 flex items-center gap-2">
                <flux:icon name="chart-pie" class="size-4.5 text-zinc-500" />
                <span>Time Allocation</span>
            </h3>
            
            @if($this->projectStats->count() > 0)
                <div class="space-y-4">
                    @foreach($this->projectStats as $stat)
                        <div x-data="{ open: false }" class="space-y-2">
                            <!-- Project Toggle Row -->
                            <button type="button" @click="open = !open" class="w-full text-left focus:outline-none group cursor-pointer block">
                                <div class="flex items-center justify-between text-xs mb-1.5">
                                    <div class="flex items-center gap-1 font-semibold text-zinc-800 dark:text-zinc-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                        <flux:icon name="chevron-right" class="size-3 text-zinc-400 transition-transform duration-200" ::class="open && 'rotate-90'" />
                                        <span>{{ $stat['name'] }}</span>
                                    </div>
                                    <span class="text-zinc-450 dark:text-zinc-500 font-mono text-[11px] group-hover:text-zinc-700 dark:group-hover:text-zinc-305 transition-colors">{{ $stat['duration'] }} ({{ $stat['percentage'] }}%)</span>
                                </div>
                                <div class="w-full bg-zinc-100 dark:bg-zinc-800/80 rounded-full h-1.5">
                                    <div class="bg-indigo-500 h-1.5 rounded-full transition-all duration-300" style="width: {{ $stat['percentage'] }}%"></div>
                                </div>
                            </button>

                            <!-- Nested Categories Allocation -->
                            <div x-show="open" 
                                 x-collapse
                                 class="pl-4 pr-1 space-y-2 border-l border-zinc-100 dark:border-zinc-800/50 ml-1.5 mt-2" 
                                 style="display: none;">
                                 @foreach($stat['categories'] as $cat)
                                     <div class="space-y-1">
                                         <div class="flex justify-between text-[10px]">
                                             <span class="text-zinc-600 dark:text-zinc-400 font-medium">{{ $cat['name'] }}</span>
                                             <span class="text-zinc-450 dark:text-zinc-500 font-mono">{{ $cat['duration'] }} ({{ $cat['percentage'] }}%)</span>
                                         </div>
                                         <div class="w-full bg-zinc-100 dark:bg-zinc-800/80 rounded-full h-1.5">
                                             <div class="bg-emerald-400 h-1.5 rounded-full" style="width: {{ $cat['percentage'] }}%"></div>
                                         </div>
                                     </div>
                                 @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="h-full flex flex-col items-center justify-center text-center text-neutral-500 dark:text-neutral-400 space-y-3 py-10">
                    <flux:icon name="chart-pie" class="w-10 h-10 text-neutral-300 dark:text-neutral-700" />
                    <p class="text-xs">No project data for this week yet.</p>
                </div>
            @endif
        </div>

    <!-- Recent History -->
    <div class="flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-zinc-850 dark:text-zinc-150 flex items-center gap-2">
                <flux:icon name="clock" class="size-4.5 text-zinc-500" />
                <span>Recent History</span>
            </h3>
            <flux:button variant="subtle" size="xs" href="{{ route('tracker') }}" wire:navigate class="cursor-pointer">View All</flux:button>
        </div>
        
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl overflow-hidden shadow-xs">
            @if($this->recentActivities->count() > 0)
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800/40">
                    @foreach($this->recentActivities as $activity)
                        <div class="p-4 hover:bg-zinc-50/50 dark:hover:bg-zinc-950/15 transition-colors flex justify-between items-center group">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="size-9 rounded-lg bg-zinc-50 dark:bg-zinc-950 border border-zinc-200/50 dark:border-zinc-800/40 flex items-center justify-center text-zinc-500 dark:text-zinc-400 shrink-0">
                                    <flux:icon name="folder" class="size-4.5" />
                                </div>
                                <div class="truncate">
                                    <div class="font-medium text-sm text-zinc-850 dark:text-zinc-150 truncate">{{ $activity->detail }}</div>
                                    <div class="text-[11px] text-zinc-400 dark:text-zinc-500 mt-0.5 truncate flex items-center gap-1.5">
                                        <span>{{ $activity->project->name }} &bull; {{ $activity->category->name }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="font-mono text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ $activity->duration }}</div>
                                <div class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-0.5">
                                    {{ Carbon::parse($activity->start_time)->isToday() ? 'Today' : Carbon::parse($activity->start_time)->format('M d') }}, {{ $activity->start_time->format('H:i') }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-10 text-center text-xs text-neutral-400 flex flex-col items-center gap-2">
                    <flux:icon name="clock" class="size-8 text-neutral-300 dark:text-neutral-700" />
                    <span>No recent activities found. Start tracking to see your history!</span>
                </div>
            @endif
        </div>
    </div>

</div>

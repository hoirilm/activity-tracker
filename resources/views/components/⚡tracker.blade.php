<?php

use Livewire\Component;
use App\Models\Activity;
use App\Models\Project;
use App\Models\Category;
use Carbon\Carbon;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ActivitiesExport;
use App\Imports\ActivitiesImport;

new class extends Component
{
    use WithFileUploads;

    public $detail = '';
    public $project_id;
    public $category_id;
    public $is_parallel = false;
    public $importFile;
    public $startDate;
    public $endDate;
    
    public $editingActivityId = null;
    public $editStartTime;
    public $editEndTime;
    public $showEditModal = false;

    public function mount()
    {
        $this->project_id = Project::where('user_id', auth()->id())->first()->id ?? null;
        $this->category_id = Category::where('user_id', auth()->id())->first()->id ?? null;
    }

    public function getProjectsProperty()
    {
        return Project::where('user_id', auth()->id())->get();
    }

    public function getCategoriesProperty()
    {
        return Category::where('user_id', auth()->id())->get();
    }

    public function getActivitiesProperty()
    {
        $query = auth()->user()->activities()->with(['project', 'category'])
            ->whereNotNull('end_time');

        if ($this->startDate) {
            $query->whereDate('start_time', '>=', $this->startDate);
        }
        
        if ($this->endDate) {
            $query->whereDate('start_time', '<=', $this->endDate);
        }

        return $query->orderBy('start_time', 'desc')
            ->get()
            ->groupBy(function($activity) {
                return $activity->start_time->format('Y-m-d');
            });
    }

    public function getRunningActivitiesProperty()
    {
        return auth()->user()->activities()->with(['project', 'category'])
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
            'detail' => $this->detail,
            'is_parallel' => $this->is_parallel,
            'start_time' => now(),
        ]);

        $this->detail = '';
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
        $this->validate([
            'importFile' => 'required|mimes:xlsx,csv',
        ]);

        Excel::import(new ActivitiesImport, $this->importFile);
        $this->importFile = null;
        session()->flash('message', 'Activities imported successfully.');
    }

    public function editActivity($id)
    {
        $activity = auth()->user()->activities()->find($id);
        if ($activity) {
            $this->editingActivityId = $activity->id;
            $this->editStartTime = $activity->start_time->format('Y-m-d\TH:i');
            $this->editEndTime = $activity->end_time ? $activity->end_time->format('Y-m-d\TH:i') : null;
            $this->showEditModal = true;
        }
    }

    public function updateActivityTime()
    {
        $this->validate([
            'editStartTime' => 'required|date',
            'editEndTime' => 'required|date|after_or_equal:editStartTime',
        ]);

        $activity = auth()->user()->activities()->find($this->editingActivityId);
        if ($activity) {
            $activity->update([
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

<div class="flex h-full w-full flex-col gap-6 p-4 text-neutral-900 dark:text-neutral-100 max-w-5xl mx-auto mt-4" x-data="{ scrolled: false }" @scroll.window="scrolled = (window.pageYOffset > 300)">
    
    <!-- Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-800 pb-4">
        <h2 class="text-xl font-semibold tracking-tight">Time Tracker</h2>
        <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Track your day-to-day work, projects, and activities in real-time.</p>
    </div>

    <!-- Sticky form -->
    <div class="sticky top-0 z-10 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 p-5 shadow-xs mb-4"
         x-data="{}"
         @keydown.window.prevent.ctrl.slash="$refs.detailInput.focus()"
         @keydown.window.ctrl.enter="$wire.startActivity()">
        <form wire:submit.prevent="startActivity" class="space-y-4">
            <div class="flex flex-col md:flex-row gap-3 items-center">
                <div class="flex-1 w-full">
                    <flux:input wire:model="detail" x-ref="detailInput" placeholder="What are you working on?" required icon="play-circle" size="sm" autocomplete="off" />
                </div>
                <div class="w-full md:w-48">
                    <flux:select wire:model="project_id" placeholder="Select Project" required size="sm">
                        @foreach($this->projects as $project)
                            <flux:select.option value="{{ $project->id }}">{{ $project->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="w-full md:w-48">
                    <flux:select wire:model="category_id" placeholder="Select Category" required size="sm">
                        @foreach($this->categories as $category)
                            <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:button variant="primary" type="submit" size="sm" class="w-full md:w-auto cursor-pointer bg-indigo-600 hover:bg-indigo-700 text-white dark:bg-indigo-500 dark:hover:bg-indigo-600 border-none px-6">
                    Start
                </flux:button>
            </div>
            <div class="flex items-center justify-between mt-1">
                <flux:checkbox wire:model="is_parallel" label="Parallel (allow running with other tasks)" />
                <span class="hidden md:inline-block text-[10px] text-zinc-400 dark:text-zinc-500 bg-zinc-100 dark:bg-zinc-800 px-2.5 py-1 rounded-lg">Shortcuts: <strong>Ctrl + /</strong> to focus, <strong>Ctrl + Enter</strong> to Start</span>
            </div>
        </form>
    </div>

    <!-- Running Activities -->
    @if($this->runningActivities->count() > 0)
    <div class="space-y-3">
        <h2 class="text-sm font-semibold text-zinc-850 dark:text-zinc-150 mb-3 flex items-center gap-2">
            <span class="relative flex h-2.5 w-2.5">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
            </span>
            <span>Running Activities</span>
        </h2>
        <div class="grid gap-3">
            @foreach($this->runningActivities as $running)
                <div wire:key="running-{{ $running->id }}" class="group relative overflow-hidden rounded-2xl border border-emerald-100 dark:border-emerald-900/30 bg-emerald-50/50 dark:bg-emerald-950/10 p-5 shadow-xs flex flex-col md:flex-row justify-between items-start md:items-center gap-4" 
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
                        <div class="text-xs text-neutral-500 dark:text-neutral-400 mt-1.5 flex items-center gap-1.5">
                            <flux:icon name="folder" class="size-3.5 shrink-0" />
                            <span><span class="font-medium text-neutral-700 dark:text-neutral-300">{{ $running->project->name }}</span> &bull; {{ $running->category->name }}</span>
                            @if($running->is_parallel) 
                                <span class="bg-indigo-100 dark:bg-indigo-950/40 text-indigo-800 dark:text-indigo-400 text-[9px] uppercase tracking-wider px-2 py-0.5 rounded font-semibold ml-2">Parallel</span> 
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-5 w-full md:w-auto justify-between md:justify-end">
                        <div class="font-mono text-2xl text-emerald-600 dark:text-emerald-455 font-bold tracking-tight" x-text="elapsed"></div>
                        <flux:button variant="danger" wire:click="stopActivity({{ $running->id }})" size="sm" class="cursor-pointer" title="Stop Activity">Stop</flux:button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Activity Feed / History -->
    <div class="flex flex-col gap-4 mt-2">
        <div class="flex flex-col xl:flex-row justify-between xl:items-center gap-4 mb-2">
            <h3 class="text-sm font-semibold text-zinc-850 dark:text-zinc-150 flex items-center gap-2">
                <flux:icon name="clock" class="size-4.5 text-zinc-500" />
                <span>History</span>
            </h3>
            <div class="flex flex-col md:flex-row gap-3 items-start md:items-center">
                <!-- Date Range Filter Popover -->
                <div x-data="{ open: false }" class="relative" @click.outside="open = false">
                    <!-- Trigger Button -->
                    <button type="button" @click="open = !open" 
                            class="flex items-center gap-2 px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors shadow-xs cursor-pointer">
                        <flux:icon name="calendar" class="size-4 text-zinc-400 dark:text-zinc-500" />
                        <span>
                            @if($startDate && $endDate)
                                {{ Carbon::parse($startDate)->format('M d, Y') }} - {{ Carbon::parse($endDate)->format('M d, Y') }}
                            @elseif($startDate)
                                From {{ Carbon::parse($startDate)->format('M d, Y') }}
                            @elseif($endDate)
                                Until {{ Carbon::parse($endDate)->format('M d, Y') }}
                            @else
                                Filter Date
                            @endif
                        </span>
                        <flux:icon name="chevron-down" class="size-3 text-zinc-400" />
                    </button>

                    <!-- Clear Filter Button -->
                    @if($startDate || $endDate)
                        <button type="button" wire:click="$set('startDate', null); $set('endDate', null)" 
                                class="absolute -top-1.5 -right-1.5 size-4 rounded-full bg-red-55 dark:bg-red-950/80 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900 border border-red-200/30 dark:border-red-900/30 flex items-center justify-center transition-colors shadow-xs cursor-pointer"
                                title="Clear filter">
                            <flux:icon name="x-mark" class="size-2.5" />
                        </button>
                    @endif

                    <!-- Dropdown Panel -->
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 sm:left-0 mt-2 z-50 w-64 origin-top-right rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 p-4 shadow-lg"
                         style="display: none;">
                         
                         <div class="space-y-4">
                             <!-- Presets -->
                             <div>
                                 <label class="block text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-2">Presets</label>
                                 <div class="grid grid-cols-2 gap-1.5">
                                     <button type="button" @click="open = false" wire:click="$set('startDate', '{{ Carbon::today()->toDateString() }}'); $set('endDate', '{{ Carbon::today()->toDateString() }}')"
                                             class="text-left text-xs px-2.5 py-1.5 rounded-lg hover:bg-zinc-100/70 dark:hover:bg-zinc-800/50 font-medium text-zinc-650 dark:text-zinc-350 transition-colors cursor-pointer">
                                         Today
                                     </button>
                                     <button type="button" @click="open = false" wire:click="$set('startDate', '{{ Carbon::yesterday()->toDateString() }}'); $set('endDate', '{{ Carbon::yesterday()->toDateString() }}')"
                                             class="text-left text-xs px-2.5 py-1.5 rounded-lg hover:bg-zinc-100/70 dark:hover:bg-zinc-800/50 font-medium text-zinc-650 dark:text-zinc-350 transition-colors cursor-pointer">
                                         Yesterday
                                     </button>
                                     <button type="button" @click="open = false" wire:click="$set('startDate', '{{ Carbon::now()->startOfWeek()->toDateString() }}'); $set('endDate', '{{ Carbon::now()->endOfWeek()->toDateString() }}')"
                                             class="text-left text-xs px-2.5 py-1.5 rounded-lg hover:bg-zinc-100/70 dark:hover:bg-zinc-800/50 font-medium text-zinc-650 dark:text-zinc-350 transition-colors cursor-pointer">
                                         This Week
                                     </button>
                                     <button type="button" @click="open = false" wire:click="$set('startDate', '{{ Carbon::now()->startOfMonth()->toDateString() }}'); $set('endDate', '{{ Carbon::now()->endOfMonth()->toDateString() }}')"
                                             class="text-left text-xs px-2.5 py-1.5 rounded-lg hover:bg-zinc-100/70 dark:hover:bg-zinc-800/50 font-medium text-zinc-650 dark:text-zinc-350 transition-colors cursor-pointer">
                                         This Month
                                     </button>
                                 </div>
                             </div>

                             <div class="h-px bg-zinc-150 dark:bg-zinc-800/60"></div>

                             <!-- Custom Inputs -->
                             <div class="space-y-3">
                                 <label class="block text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Custom Range</label>
                                 <div class="space-y-2">
                                     <div class="flex items-center gap-2 bg-zinc-50 dark:bg-zinc-950 border border-zinc-200/50 dark:border-zinc-850/60 rounded-lg px-2 py-0.5">
                                         <span class="text-[9px] text-zinc-450 dark:text-zinc-500 font-bold uppercase w-8 shrink-0">Start</span>
                                         <input type="date" wire:model.live="startDate" onclick="this.showPicker()" class="text-xs border-none bg-transparent focus:ring-0 p-1 text-zinc-650 dark:text-zinc-300 w-full cursor-pointer" title="Start Date">
                                     </div>
                                     <div class="flex items-center gap-2 bg-zinc-50 dark:bg-zinc-950 border border-zinc-200/50 dark:border-zinc-850/60 rounded-lg px-2 py-0.5">
                                         <span class="text-[9px] text-zinc-450 dark:text-zinc-500 font-bold uppercase w-8 shrink-0">End</span>
                                         <input type="date" wire:model.live="endDate" onclick="this.showPicker()" class="text-xs border-none bg-transparent focus:ring-0 p-1 text-zinc-650 dark:text-zinc-300 w-full cursor-pointer" title="End Date">
                                     </div>
                                 </div>
                             </div>
                         </div>
                    </div>
                </div>

                <div class="h-6 w-px bg-zinc-200 dark:bg-zinc-800 hidden md:block"></div>

                <!-- Export Modal & Button -->
                <div x-data="{ showExportModal: false, exportStart: '', exportEnd: '' }" class="m-0 flex items-center">
                    <flux:button variant="subtle" size="xs" @click="showExportModal = true" class="cursor-pointer">Export</flux:button>
                    
                    <!-- Modal Backdrop -->
                    <div x-show="showExportModal" class="fixed inset-0 z-[100] bg-neutral-900/50 backdrop-blur-sm transition-opacity" x-transition.opacity style="display: none;"></div>
                    
                    <!-- Modal Content -->
                    <div x-show="showExportModal" 
                         class="fixed inset-0 z-[101] flex items-center justify-center p-4 sm:p-6" 
                         x-transition:enter="ease-out duration-300" 
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                         x-transition:leave="ease-in duration-200" 
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         style="display: none;">
                         
                        <div @click.outside="showExportModal = false" class="bg-zinc-50 dark:bg-neutral-900 rounded-xl shadow-xl border border-neutral-200 dark:border-neutral-800 w-full max-w-sm overflow-hidden text-left">
                            <div class="p-6">
                                <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100 mb-1">Export Activities</h3>
                                <p class="text-xs text-neutral-550 dark:text-neutral-450 mb-6">Select a date range to export your activities. Leave blank to export all history.</p>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">Start Date</label>
                                        <input type="date" x-model="exportStart" onclick="this.showPicker()" class="w-full rounded-lg border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-950 text-neutral-900 dark:text-neutral-100 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm text-xs cursor-pointer py-2 px-3 transition-colors">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1">End Date</label>
                                        <input type="date" x-model="exportEnd" onclick="this.showPicker()" class="w-full rounded-lg border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-950 text-neutral-900 dark:text-neutral-100 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm text-xs cursor-pointer py-2 px-3 transition-colors">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-neutral-50 dark:bg-neutral-900/50 px-6 py-4 flex justify-end gap-3 border-t border-neutral-200 dark:border-neutral-800">
                                <flux:button variant="subtle" size="sm" @click="showExportModal = false">Cancel</flux:button>
                                <flux:button variant="primary" size="sm" class="bg-indigo-600 hover:bg-indigo-700 text-white dark:bg-indigo-500 dark:hover:bg-indigo-600 border-none cursor-pointer" @click="$wire.export(exportStart, exportEnd); showExportModal = false">Download Excel</flux:button>
                            </div>
                        </div>
                    </div>
                </div>
                <form wire:submit.prevent="import" class="flex gap-2 items-center m-0" x-data="{ fileName: '' }">
                    <label class="relative flex items-center cursor-pointer bg-neutral-100 hover:bg-neutral-200 dark:bg-neutral-800 dark:hover:bg-neutral-700 text-neutral-700 dark:text-neutral-300 text-xs font-semibold py-1.5 px-3 rounded-lg transition-colors border border-transparent shadow-xs">
                        <input type="file" wire:model="importFile" x-on:change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''" class="hidden" required>
                        <flux:icon name="paper-clip" class="size-3.5 mr-1.5" />
                        <span x-text="fileName ? (fileName.length > 12 ? fileName.substring(0, 12) + '...' : fileName) : 'Select File'"></span>
                    </label>
                    <flux:button type="submit" variant="primary" size="sm" x-show="fileName" x-transition class="bg-indigo-600 hover:bg-indigo-700 text-white dark:bg-indigo-500 dark:hover:bg-indigo-600 border-none cursor-pointer">
                        Import
                    </flux:button>
                </form>
            </div>
        </div>
        
        @if(session()->has('message'))
            <div class="text-xs font-medium text-emerald-600 dark:text-emerald-450 flex items-center gap-1.5">
                <flux:icon name="check-circle" class="size-4" />
                <span>{{ session('message') }}</span>
            </div>
        @endif
        
        <div class="space-y-6">
            @forelse($this->activities as $date => $dayActivities)
                <div wire:key="day-{{ $date }}">
                    <h4 class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-3 flex items-center gap-3">
                        <span>{{ Carbon::parse($date)->format('l, j F Y') }}</span>
                        <div class="h-px flex-1 bg-zinc-200 dark:bg-zinc-800/60"></div>
                    </h4>
                    <div class="flex flex-col gap-3">
                        @foreach($dayActivities as $activity)
                            <div wire:key="activity-{{ $activity->id }}" class="group relative overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 p-4 shadow-xs hover:shadow-sm hover:border-zinc-300 dark:hover:border-zinc-700 transition-all duration-200 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <div class="size-9 rounded-lg bg-white dark:bg-zinc-950 border border-zinc-200/50 dark:border-zinc-800/40 flex items-center justify-center text-zinc-500 dark:text-zinc-400 shrink-0">
                                        <flux:icon name="folder" class="size-4.5" />
                                    </div>
                                    <div class="truncate">
                                        <div class="font-medium text-sm text-zinc-850 dark:text-zinc-150 truncate">{{ $activity->detail }}</div>
                                        <div class="text-[11px] text-zinc-400 dark:text-zinc-500 mt-1 truncate flex items-center gap-1.5">
                                            <span>{{ $activity->project->name }} &bull; {{ $activity->category->name }}</span>
                                            @if($activity->is_parallel) 
                                                <span class="text-[9px] bg-indigo-50 dark:bg-indigo-950/30 text-indigo-650 dark:text-indigo-400 px-1.5 py-0.5 rounded font-semibold uppercase tracking-wider ml-1">Parallel</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 shrink-0 w-full md:w-auto justify-between md:justify-end">
                                    <!-- Time and Duration Info -->
                                    <div class="font-mono flex items-center gap-2">
                                        <span class="text-[10px] text-zinc-450 dark:text-zinc-500 font-semibold">{{ $activity->start_time->format('H:i') }} - {{ $activity->end_time->format('H:i') }}</span>
                                        <span class="text-zinc-300 dark:text-zinc-700">|</span>
                                        <span class="text-lg font-bold text-zinc-850 dark:text-zinc-150 leading-none">{{ $activity->duration }}</span>
                                    </div>
                                    
                                    <!-- Actions Dropdown (3-dots) -->
                                    <div class="shrink-0">
                                        <flux:dropdown>
                                            <flux:button variant="ghost" size="xs" icon="ellipsis-vertical" square class="cursor-pointer" title="Actions" />
                                            <flux:menu class="min-w-[8rem]">
                                                <flux:menu.item wire:click="editActivity({{ $activity->id }})" icon="pencil" class="cursor-pointer">Edit Time</flux:menu.item>
                                                <flux:modal.trigger name="delete-activity-{{ $activity->id }}">
                                                    <flux:menu.item icon="trash" variant="danger" class="cursor-pointer">Delete</flux:menu.item>
                                                </flux:modal.trigger>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </div>
                                </div>
                            </div>

                            <flux:modal name="delete-activity-{{ $activity->id }}" class="min-w-[22rem] backdrop:backdrop-blur-sm z-[200]">
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
                <div class="text-center py-16 text-xs text-neutral-400 border border-dashed border-neutral-200 dark:border-neutral-800 rounded-xl bg-zinc-50 dark:bg-zinc-900/50 flex flex-col items-center gap-2">
                    <flux:icon name="clock" class="size-8 text-neutral-300 dark:text-neutral-700" />
                    <span>No activity history found. Start tracking your tasks above!</span>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Back to top button -->
    <div class="fixed bottom-8 left-[90%] -translate-x-1/2 pointer-events-none z-50 md:bottom-12"
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
            class="pointer-events-auto bg-neutral-900 dark:bg-white text-white dark:text-neutral-900 px-6 py-2.5 rounded-full shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-900 dark:focus:ring-white flex items-center gap-2 font-medium text-sm"
            title="Back to top"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
            Back to top
        </button>
    </div>

    <!-- Edit Time Modal -->
    <div x-data="{ show: @entangle('showEditModal') }" x-show="show" style="display: none;" class="relative z-[100]">
        <!-- Backdrop -->
        <div x-show="show" x-transition.opacity class="fixed inset-0 bg-neutral-900/50 backdrop-blur-sm"></div>
        
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
                 class="bg-zinc-50 dark:bg-neutral-900 rounded-xl shadow-xl border border-neutral-200 dark:border-neutral-800 w-full max-w-sm overflow-hidden text-left relative">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 mb-4">Edit Activity Time</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Start Time</label>
                            <input type="datetime-local" wire:model="editStartTime" class="w-full rounded-lg border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-950 text-neutral-900 dark:text-neutral-100 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm sm:text-sm cursor-pointer py-2 px-3 transition-colors">
                            @error('editStartTime') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">End Time</label>
                            <input type="datetime-local" wire:model="editEndTime" class="w-full rounded-lg border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-950 text-neutral-900 dark:text-neutral-100 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm sm:text-sm cursor-pointer py-2 px-3 transition-colors">
                            @error('editEndTime') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                
                <div class="bg-neutral-50 dark:bg-neutral-900/50 px-6 py-4 flex justify-end gap-3 border-t border-neutral-200 dark:border-neutral-800">
                    <flux:button variant="subtle" wire:click="cancelEdit">Cancel</flux:button>
                    <flux:button variant="primary" wire:click="updateActivityTime">Save Changes</flux:button>
                </div>
            </div>
        </div>
    </div>
</div>
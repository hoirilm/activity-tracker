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
        $this->project_id = Project::first()->id ?? null;
        $this->category_id = Category::first()->id ?? null;
    }

    public function getProjectsProperty()
    {
        return Project::all();
    }

    public function getCategoriesProperty()
    {
        return Category::all();
    }

    public function getActivitiesProperty()
    {
        $query = Activity::with(['project', 'category']);

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
        return Activity::with(['project', 'category'])
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
            Activity::whereNull('end_time')
                ->where('is_parallel', false)
                ->update(['end_time' => now()]);
        }

        Activity::create([
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
        $activity = Activity::find($id);
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
        $activity = Activity::find($id);
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

        $activity = Activity::find($this->editingActivityId);
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
        $activity = Activity::find($id);
        if ($activity) {
            $activity->delete();
        }
    }
};
?>

<div class="flex h-full w-full flex-col gap-8 p-4 text-neutral-900 dark:text-neutral-100 max-w-5xl mx-auto mt-4" x-data="{ scrolled: false }" @scroll.window="scrolled = (window.pageYOffset > 300)">
    <!-- Sticky form -->
    <div class="sticky top-0 z-10 overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900 p-5 shadow-sm mb-4"
         x-data="{}"
         @keydown.window.prevent.ctrl.slash="$refs.detailInput.focus()"
         @keydown.window.ctrl.enter="$wire.startActivity()">
        <form wire:submit.prevent="startActivity" class="flex flex-col gap-4">
            <div class="flex flex-col md:flex-row gap-3 items-start">
                <div class="flex-1 w-full">
                    <flux:input wire:model="detail" x-ref="detailInput" placeholder="What are you working on?" required />
                </div>
                <div class="w-full md:w-48">
                    <flux:select wire:model="project_id" required>
                        <option value="" disabled selected>Select Project</option>
                        @foreach($this->projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="w-full md:w-48">
                    <flux:select wire:model="category_id" required>
                        <option value="" disabled selected>Select Category</option>
                        @foreach($this->categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:button variant="primary" type="submit" class="w-full md:w-auto">Start</flux:button>
            </div>
            <div class="flex items-center justify-between mt-1">
                <flux:checkbox wire:model="is_parallel" label="Parallel (allow running with other tasks)" />
                <span class="hidden md:inline-block text-xs text-neutral-500 dark:text-neutral-400 bg-neutral-100 dark:bg-neutral-800 px-2.5 py-1 rounded-md">Shortcuts: <strong>Ctrl + /</strong> to focus, <strong>Ctrl + Enter</strong> to Start</span>
            </div>
        </form>
    </div>

    <!-- Running Activities -->
    @if($this->runningActivities->count() > 0)
    <div>
        <h2 class="text-lg font-semibold tracking-tight mb-4 flex items-center gap-2">
            <span class="relative flex h-2.5 w-2.5">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
            </span>
            Running Activities
        </h2>
        <div class="grid gap-3">
            @foreach($this->runningActivities as $running)
                <div class="group relative overflow-hidden rounded-xl border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/50 dark:bg-emerald-900/10 p-5 shadow-sm transition-all flex flex-col md:flex-row justify-between items-start md:items-center gap-4" 
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
                    <div class="flex items-center gap-5 w-full md:w-auto justify-between md:justify-end">
                        <div class="font-mono text-2xl text-emerald-700 dark:text-emerald-400 font-bold tracking-tight" x-text="elapsed"></div>
                        <flux:button variant="danger" wire:click="stopActivity({{ $running->id }})" title="Stop Activity">Stop</flux:button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Activity Feed -->
    <div>
        <div class="flex flex-col xl:flex-row justify-between xl:items-end gap-4 mb-6">
            <h2 class="text-lg font-semibold tracking-tight">History</h2>
            <div class="flex flex-col md:flex-row gap-3 items-start md:items-center">
                <!-- Date Range Filter -->
                <div class="flex items-center bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-lg px-2 py-1 shadow-sm transition-all focus-within:ring-2 focus-within:ring-indigo-500/20 focus-within:border-indigo-500">
                    <span class="text-xs text-neutral-500 font-medium pl-1 hidden sm:inline-block">Filter:</span>
                    <input type="date" wire:model.live="startDate" onclick="this.showPicker()" class="text-sm border-none bg-transparent focus:ring-0 p-1 text-neutral-700 dark:text-neutral-300 w-[120px] cursor-pointer" title="Start Date">
                    <span class="text-neutral-300 dark:text-neutral-600 px-1">-</span>
                    <input type="date" wire:model.live="endDate" onclick="this.showPicker()" class="text-sm border-none bg-transparent focus:ring-0 p-1 text-neutral-700 dark:text-neutral-300 w-[120px] cursor-pointer" title="End Date">
                    
                    @if($this->startDate || $this->endDate)
                        <button type="button" wire:click="$set('startDate', null); $set('endDate', null)" class="text-neutral-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 p-1 ml-1 rounded-md transition-colors" title="Clear filter">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    @else
                        <div class="w-6 ml-1"></div> <!-- Spacer to prevent jumping -->
                    @endif
                </div>

                <div class="h-6 w-px bg-neutral-200 dark:bg-neutral-700 hidden md:block"></div>

                <!-- Export Modal & Button -->
                <div x-data="{ showExportModal: false, exportStart: '', exportEnd: '' }" class="m-0 flex items-center">
                    <flux:button variant="subtle" @click="showExportModal = true">Export</flux:button>
                    
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
                         
                        <div @click.outside="showExportModal = false" class="bg-white dark:bg-neutral-900 rounded-xl shadow-xl border border-neutral-200 dark:border-neutral-800 w-full max-w-md overflow-hidden text-left">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 mb-1">Export Activities</h3>
                                <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-6">Select a date range to export your activities. Leave blank to export all history.</p>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Start Date</label>
                                        <input type="date" x-model="exportStart" onclick="this.showPicker()" class="w-full rounded-lg border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-950 text-neutral-900 dark:text-neutral-100 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm sm:text-sm cursor-pointer py-2 px-3 transition-colors">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">End Date</label>
                                        <input type="date" x-model="exportEnd" onclick="this.showPicker()" class="w-full rounded-lg border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-950 text-neutral-900 dark:text-neutral-100 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm sm:text-sm cursor-pointer py-2 px-3 transition-colors">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-neutral-50 dark:bg-neutral-900/50 px-6 py-4 flex justify-end gap-3 border-t border-neutral-200 dark:border-neutral-800">
                                <flux:button variant="subtle" @click="showExportModal = false">Cancel</flux:button>
                                <flux:button variant="primary" @click="$wire.export(exportStart, exportEnd); showExportModal = false">Download Excel</flux:button>
                            </div>
                        </div>
                    </div>
                </div>
                <form wire:submit.prevent="import" class="flex gap-2 items-center m-0" x-data="{ fileName: '' }">
                    <label class="relative flex items-center cursor-pointer bg-neutral-100 hover:bg-neutral-200 dark:bg-neutral-800 dark:hover:bg-neutral-700 text-neutral-700 dark:text-neutral-300 text-sm font-medium py-1.5 px-3 rounded-lg transition-colors border border-transparent shadow-sm">
                        <input type="file" wire:model="importFile" x-on:change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''" class="hidden" required>
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                        <span x-text="fileName ? (fileName.length > 15 ? fileName.substring(0, 15) + '...' : fileName) : 'Select File'"></span>
                    </label>
                    <button type="submit" x-show="fileName" x-transition class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-1.5 px-3 rounded-lg shadow-sm transition-colors cursor-pointer">
                        Import
                    </button>
                </form>
            </div>
        </div>
        
        @if(session()->has('message'))
            <div class="mb-5 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                {{ session('message') }}
            </div>
        @endif
        
        @forelse($this->activities as $date => $dayActivities)
            <div class="mb-8">
                <h3 class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider mb-3 flex items-center gap-3">
                    {{ Carbon::parse($date)->format('l, j F Y') }}
                    <div class="h-px flex-1 bg-neutral-200 dark:bg-neutral-800"></div>
                </h3>
                <div class="flex flex-col gap-3">
                    @foreach($dayActivities as $activity)
                        @if($activity->end_time)
                            <div class="group relative overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900 p-4 shadow-sm hover:border-neutral-300 dark:hover:border-neutral-600 transition-all flex flex-col md:flex-row justify-between items-start md:items-center gap-2">
                                <div>
                                    <div class="font-medium text-neutral-900 dark:text-neutral-100">{{ $activity->detail }}</div>
                                    <div class="text-sm text-neutral-500 dark:text-neutral-400 mt-1 flex items-center gap-2">
                                        <span>{{ $activity->project->name }} &bull; {{ $activity->category->name }}</span>
                                        @if($activity->is_parallel) 
                                            <span class="text-[10px] bg-neutral-100 dark:bg-neutral-800 px-1.5 py-0.5 rounded uppercase tracking-wider font-semibold">Parallel</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-left md:text-right w-full md:w-auto flex flex-row md:flex-col justify-between items-center md:items-end">
                                    <div class="font-mono text-lg font-semibold text-neutral-700 dark:text-neutral-300">{{ $activity->duration }}</div>
                                    <div class="text-xs text-neutral-400 dark:text-neutral-500 md:mt-1 flex items-center gap-1">
                                        {{ $activity->start_time->format('H:i') }} - {{ $activity->end_time->format('H:i') }}
                                        <button wire:click="editActivity({{ $activity->id }})" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 p-0.5" title="Edit Time">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </button>
                                        <flux:modal.trigger name="delete-activity-{{ $activity->id }}">
                                            <button class="hover:text-red-600 dark:hover:text-red-400 transition-colors rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 p-0.5" title="Delete Activity">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </flux:modal.trigger>
                                    </div>
                                </div>
                            </div>

                            <flux:modal name="delete-activity-{{ $activity->id }}" class="min-w-[22rem] backdrop:backdrop-blur-sm z-[200]">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">Delete Activity?</flux:heading>
                                        <flux:text class="mt-2">
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
                        @endif
                    @endforeach
                </div>
            </div>
        @empty
            <div class="text-center py-16 text-sm text-neutral-400 border border-dashed border-neutral-200 dark:border-neutral-700 rounded-xl">
                No activity history found. Start tracking your tasks above!
            </div>
        @endforelse
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
                 class="bg-white dark:bg-neutral-900 rounded-xl shadow-xl border border-neutral-200 dark:border-neutral-800 w-full max-w-sm overflow-hidden text-left relative">
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
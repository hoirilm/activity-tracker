<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Backup & Restore Settings')] class extends Component {
    use WithFileUploads;

    public $backupFile;

    public string $restoreMode = 'merge'; // 'merge' or 'replace'

    public bool $confirmDifferentAccount = false;

    public ?array $previewData = null;

    public function updatedBackupFile(): void
    {
        $this->validate([
            'backupFile' => 'required|file|max:20480',
        ]);

        try {
            $content = file_get_contents($this->backupFile->getRealPath());
            $data = json_decode($content, true);

            if (! is_array($data) || ! isset($data['activities'])) {
                $this->dispatch('toast', title: 'Invalid file format. Please use a JSON backup file.', category: 'BACKUP', type: 'danger');
                $this->previewData = null;

                return;
            }

            $fileEmail = strtolower(trim($data['user']['email'] ?? ''));
            $currentEmail = strtolower(trim(auth()->user()->email));
            $emailMatched = ($fileEmail !== '' && $fileEmail === $currentEmail);

            $this->previewData = [
                'fileName' => $this->backupFile->getClientOriginalName(),
                'version' => $data['version'] ?? '1.0',
                'exported_at' => isset($data['exported_at']) ? Carbon::parse($data['exported_at'])->format('d M Y, H:i') : 'Unknown',
                'source_user' => $data['user']['name'] ?? ($data['user']['email'] ?? 'Unknown'),
                'source_email' => $data['user']['email'] ?? 'Unknown',
                'email_matched' => $emailMatched,
                'total_projects' => count($data['projects'] ?? []),
                'total_categories' => count($data['categories'] ?? []),
                'total_activities' => count($data['activities'] ?? []),
                'total_tasks' => count($data['tasks'] ?? []),
                'total_labels' => count($data['labels'] ?? []),
            ];

            $this->confirmDifferentAccount = false;
        } catch (\Throwable $e) {
            $this->dispatch('toast', title: 'Failed to read backup file.', category: 'BACKUP', type: 'danger');
            $this->previewData = null;
        }
    }

    public function downloadBackup()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $projects = $user->projects()->get(['id', 'name', 'created_at']);
        $categories = $user->categories()->get(['id', 'name', 'created_at']);
        $labels = $user->labels()->get(['id', 'name', 'color', 'created_at']);
        $tasks = $user->tasks()->with(['project', 'labels', 'checklists'])->latest()->get();
        $activities = $user->activities()
            ->with(['project', 'category'])
            ->orderBy('start_time', 'asc')
            ->get();

        $latestActivity = $activities->last();

        $backupData = [
            'version' => '1.1',
            'generator' => 'Klakoan Activity Tracker',
            'exported_at' => now()->toIso8601String(),
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'stats' => [
                'total_projects' => $projects->count(),
                'total_categories' => $categories->count(),
                'total_activities' => $activities->count(),
                'total_tasks' => $tasks->count(),
                'total_labels' => $labels->count(),
                'latest_activity_at' => $latestActivity ? $latestActivity->start_time->toIso8601String() : null,
            ],
            'projects' => $projects->map(fn ($p) => ['name' => $p->name])->values()->all(),
            'categories' => $categories->map(fn ($c) => ['name' => $c->name])->values()->all(),
            'labels' => $labels->map(fn ($l) => ['name' => $l->name, 'color' => $l->color])->values()->all(),
            'tasks' => $tasks->map(function ($t) {
                return [
                    'title' => $t->title,
                    'description' => $t->description,
                    'project' => $t->project ? $t->project->name : null,
                    'status' => $t->status,
                    'due_at' => $t->due_at ? $t->due_at->toIso8601String() : null,
                    'labels' => $t->labels->pluck('name')->all(),
                    'checklists' => $t->checklists->map(fn ($c) => [
                        'title' => $c->title,
                        'is_completed' => (bool) $c->is_completed,
                        'position' => (int) $c->position,
                    ])->values()->all(),
                ];
            })->values()->all(),
            'activities' => $activities->map(function ($act) {
                return [
                    'project' => $act->project ? $act->project->name : 'General',
                    'category' => $act->category ? $act->category->name : 'Uncategorized',
                    'detail' => $act->detail,
                    'start_time' => $act->start_time->toDateTimeString(),
                    'end_time' => $act->end_time ? $act->end_time->toDateTimeString() : null,
                    'paused_seconds' => (int) ($act->paused_seconds ?? 0),
                    'is_parallel' => (bool) $act->is_parallel,
                ];
            })->values()->all(),
        ];

        $json = json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $slugName = Str::slug($user->name, '_');
        $filename = "backup_activity_tracker_{$slugName}_".date('Ymd_His').'.json';

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function processRestore(): void
    {
        if (! $this->backupFile) {
            $this->dispatch('toast', title: 'Please select a backup file first.', category: 'RESTORE', type: 'danger');

            return;
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();

        try {
            $content = file_get_contents($this->backupFile->getRealPath());
            $data = json_decode($content, true);

            if (! is_array($data) || ! isset($data['activities'])) {
                $this->dispatch('toast', title: 'Invalid backup file structure.', category: 'RESTORE', type: 'danger');

                return;
            }

            $fileEmail = strtolower(trim($data['user']['email'] ?? ''));
            $currentEmail = strtolower(trim($user->email));

            if ($fileEmail !== '' && $fileEmail !== $currentEmail && ! $this->confirmDifferentAccount) {
                $this->dispatch('toast', title: "Account email ({$fileEmail}) does not match your account.", category: 'RESTORE', type: 'danger');

                return;
            }

            DB::transaction(function () use ($user, $data) {
                if ($this->restoreMode === 'replace') {
                    $user->activities()->delete();
                    $user->tasks()->delete();
                }

                $importedCount = 0;
                $importedTaskCount = 0;
                $projectMap = [];
                $categoryMap = [];
                $labelMap = [];

                foreach ($data['projects'] ?? [] as $proj) {
                    $name = trim($proj['name'] ?? '');
                    if ($name !== '') {
                        $p = $user->projects()->firstOrCreate(['name' => $name]);
                        $projectMap[$name] = $p->id;
                    }
                }

                foreach ($data['categories'] ?? [] as $cat) {
                    $name = trim($cat['name'] ?? '');
                    if ($name !== '') {
                        $c = $user->categories()->firstOrCreate(['name' => $name]);
                        $categoryMap[$name] = $c->id;
                    }
                }

                foreach ($data['labels'] ?? [] as $lbl) {
                    $name = trim($lbl['name'] ?? '');
                    $color = trim($lbl['color'] ?? 'amber');
                    if ($name !== '') {
                        $l = $user->labels()->firstOrCreate(['name' => $name], ['color' => $color]);
                        $labelMap[$name] = $l->id;
                    }
                }

                foreach ($data['tasks'] ?? [] as $tData) {
                    $title = trim($tData['title'] ?? '');
                    if ($title !== '') {
                        $projectName = trim($tData['project'] ?? '');
                        $projectId = !empty($projectName) ? ($projectMap[$projectName] ?? null) : null;

                        if ($this->restoreMode === 'merge') {
                            $exists = $user->tasks()
                                ->where('title', $title)
                                ->where('project_id', $projectId)
                                ->exists();

                            if ($exists) {
                                continue;
                            }
                        }

                        $task = $user->tasks()->create([
                            'title' => $title,
                            'description' => $tData['description'] ?? null,
                            'project_id' => $projectId,
                            'status' => $tData['status'] ?? 'new',
                            'due_at' => isset($tData['due_at']) && !empty($tData['due_at']) ? Carbon::parse($tData['due_at']) : null,
                        ]);

                        if (!empty($tData['labels'])) {
                            $tLabelIds = [];
                            foreach ($tData['labels'] as $lName) {
                                if (isset($labelMap[$lName])) {
                                    $tLabelIds[] = $labelMap[$lName];
                                }
                            }
                            if ($tLabelIds) {
                                $task->labels()->sync($tLabelIds);
                            }
                        }

                        if (!empty($tData['checklists'])) {
                            foreach ($tData['checklists'] as $cItem) {
                                if (!empty($cItem['title'])) {
                                    $task->checklists()->create([
                                        'title' => $cItem['title'],
                                        'is_completed' => (bool) ($cItem['is_completed'] ?? false),
                                        'position' => (int) ($cItem['position'] ?? 0),
                                    ]);
                                }
                            }
                        }

                        $importedTaskCount++;
                    }
                }

                foreach ($data['activities'] ?? [] as $act) {
                    $projectName = trim($act['project'] ?? 'General');
                    $categoryName = trim($act['category'] ?? 'Uncategorized');

                    if (! isset($projectMap[$projectName])) {
                        $p = $user->projects()->firstOrCreate(['name' => $projectName]);
                        $projectMap[$projectName] = $p->id;
                    }

                    if (! isset($categoryMap[$categoryName])) {
                        $c = $user->categories()->firstOrCreate(['name' => $categoryName]);
                        $categoryMap[$categoryName] = $c->id;
                    }

                    $startTime = Carbon::parse($act['start_time']);
                    $endTime = ! empty($act['end_time']) ? Carbon::parse($act['end_time']) : null;

                    // Duplicate prevention if merge mode
                    if ($this->restoreMode === 'merge') {
                        $exists = $user->activities()
                            ->where('project_id', $projectMap[$projectName])
                            ->where('category_id', $categoryMap[$categoryName])
                            ->where('detail', $act['detail'])
                            ->where('start_time', $startTime)
                            ->exists();

                        if ($exists) {
                            continue;
                        }
                    }

                    $user->activities()->create([
                        'project_id' => $projectMap[$projectName],
                        'category_id' => $categoryMap[$categoryName],
                        'detail' => $act['detail'] ?? 'Restored Activity',
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'paused_seconds' => (int) ($act['paused_seconds'] ?? 0),
                        'is_parallel' => (bool) ($act['is_parallel'] ?? false),
                    ]);

                    $importedCount++;
                }

                $this->dispatch('toast', title: "Restored {$importedCount} activities and {$importedTaskCount} tasks!", category: 'RESTORE', type: 'success');

                $user->notifications()->create([
                    'title' => '🔄 Data Restore Successful',
                    'body' => "A total of {$importedCount} activities and {$importedTaskCount} tasks have been restored from the backup file.",
                    'type' => 'success',
                ]);
            });

            $this->reset(['backupFile', 'previewData']);
        } catch (\Throwable $e) {
            $this->dispatch('toast', title: 'Failed to process data restore.', category: 'RESTORE', type: 'danger');
        }
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Backup & Restore settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Backup & Restore')" :subheading="__('Export your complete activity history or restore from a JSON backup file.')">
        
        <div class="space-y-6">
            <!-- BACKUP SECTION -->
            <div class="p-5 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 space-y-4 shadow-2xs">
                <div class="flex items-start gap-3.5">
                    <div class="size-10 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                        <flux:icon name="arrow-down-tray" class="size-5" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Backup Data</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 leading-relaxed">
                            Download your complete activity history, projects, and categories in a JSON backup file.
                        </p>
                    </div>
                </div>

                <!-- Stats summary badge -->
                @php
                    $user = auth()->user();
                    $totalProjects = $user->projects()->count();
                    $totalCategories = $user->categories()->count();
                    $totalActivities = $user->activities()->count();
                    $totalTasks = $user->tasks()->count();
                    $totalLabels = $user->labels()->count();
                    $latestActivity = $user->activities()->latest('start_time')->first();
                @endphp

                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 p-3 rounded-xl bg-zinc-50 dark:bg-zinc-950/60 border border-zinc-200/60 dark:border-zinc-800/60">
                    <div class="text-center">
                        <div class="text-[10px] uppercase font-bold text-zinc-400 dark:text-zinc-500">Projects</div>
                        <div class="text-base font-extrabold text-zinc-900 dark:text-zinc-100 mt-0.5 font-mono">{{ $totalProjects }}</div>
                    </div>
                    <div class="text-center sm:border-l border-zinc-200/60 dark:border-zinc-800/60">
                        <div class="text-[10px] uppercase font-bold text-zinc-400 dark:text-zinc-500">Categories</div>
                        <div class="text-base font-extrabold text-zinc-900 dark:text-zinc-100 mt-0.5 font-mono">{{ $totalCategories }}</div>
                    </div>
                    <div class="text-center border-l border-zinc-200/60 dark:border-zinc-800/60">
                        <div class="text-[10px] uppercase font-bold text-zinc-400 dark:text-zinc-500">Activities</div>
                        <div class="text-base font-extrabold text-zinc-900 dark:text-zinc-100 mt-0.5 font-mono">{{ $totalActivities }}</div>
                    </div>
                    <div class="text-center border-l border-zinc-200/60 dark:border-zinc-800/60">
                        <div class="text-[10px] uppercase font-bold text-zinc-400 dark:text-zinc-500">Tasks</div>
                        <div class="text-base font-extrabold text-zinc-900 dark:text-zinc-100 mt-0.5 font-mono">{{ $totalTasks }}</div>
                    </div>
                    <div class="text-center border-l border-zinc-200/60 dark:border-zinc-800/60">
                        <div class="text-[10px] uppercase font-bold text-zinc-400 dark:text-zinc-500">Labels</div>
                        <div class="text-base font-extrabold text-zinc-900 dark:text-zinc-100 mt-0.5 font-mono">{{ $totalLabels }}</div>
                    </div>
                </div>

                @if($latestActivity)
                    <div class="text-[11px] text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 pt-1">
                        <flux:icon name="clock" class="size-3.5 text-zinc-400" />
                        <span>Latest Activity: <strong>{{ $latestActivity->start_time->format('d M Y, H:i') }}</strong> ({{ $latestActivity->detail }})</span>
                    </div>
                @endif

                <div class="pt-2">
                    <button type="button" wire:click="downloadBackup" wire:loading.attr="disabled" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-zinc-900 hover:bg-zinc-800 dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-900 text-xs font-semibold shadow-2xs active:scale-95 transition-all cursor-pointer">
                        <flux:icon name="arrow-down-tray" class="size-4 shrink-0 text-white dark:text-zinc-900" wire:loading.remove wire:target="downloadBackup" />
                        <svg wire:loading wire:target="downloadBackup" class="animate-spin h-4 w-4 text-white dark:text-zinc-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="downloadBackup">Download Backup File (.json)</span>
                        <span wire:loading wire:target="downloadBackup">Downloading...</span>
                    </button>
                </div>
            </div>

            <!-- RESTORE SECTION -->
            <div class="p-5 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 space-y-4 shadow-2xs">
                <div class="flex items-start gap-3.5">
                    <div class="size-10 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                        <flux:icon name="arrow-up-tray" class="size-5" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Restore Data</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 leading-relaxed">
                            Upload a JSON backup file to restore all activities, tasks, projects, categories, and labels to your account.
                        </p>
                    </div>
                </div>

                <!-- Upload Dropzone / Button -->
                <div class="space-y-3">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Select Backup File (.json)</label>
                    
                    <div class="relative flex items-center justify-center p-6 rounded-2xl border-2 border-dashed border-zinc-300 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-950/40 hover:bg-zinc-100/60 dark:hover:bg-zinc-800/40 transition-colors cursor-pointer text-center">
                        <input type="file" wire:model="backupFile" accept=".json" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <div class="space-y-2 pointer-events-none">
                            <div class="size-10 rounded-xl bg-zinc-200/80 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700/60 mx-auto flex items-center justify-center text-zinc-600 dark:text-zinc-400">
                                <flux:icon name="document-arrow-up" class="size-5" />
                            </div>
                            <div class="text-xs text-zinc-600 dark:text-zinc-400 font-medium">
                                <span class="text-zinc-900 dark:text-zinc-100 font-bold">Click to select file</span> or drag &amp; drop file here
                            </div>
                            <div class="text-[10px] text-zinc-400">Supports official Activity Tracker JSON backup files (Max 20MB)</div>
                        </div>
                    </div>
                </div>

                <!-- Preview File Info & Mode Options -->
                @if($previewData)
                    <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 space-y-3 animate-fadeIn">
                        <div class="flex items-center justify-between gap-3 border-b border-zinc-200/60 dark:border-zinc-800/60 pb-2.5">
                            <div class="flex items-center gap-2 min-w-0">
                                <flux:icon name="document-text" class="size-4 text-emerald-500 shrink-0" />
                                <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 truncate" title="{{ $previewData['fileName'] }}">{{ $previewData['fileName'] }}</span>
                            </div>
                            <span class="text-[10px] font-mono px-2.5 py-1 rounded-lg bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 font-bold shrink-0 whitespace-nowrap">Valid Backup v{{ $previewData['version'] }}</span>
                        </div>

                        <!-- Email Validation Check -->
                        @if($previewData['email_matched'])
                            <div class="p-2.5 rounded-xl bg-emerald-50/90 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-900 dark:text-emerald-100 text-xs flex items-center gap-2">
                                <flux:icon name="check-circle" class="size-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                <span>Account email matches: <strong>{{ $previewData['source_email'] }}</strong></span>
                            </div>
                        @else
                            <div class="p-3 rounded-xl bg-amber-50/90 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-amber-900 dark:text-amber-100 text-xs space-y-2">
                                <div class="flex items-start gap-2">
                                    <flux:icon name="exclamation-triangle" class="size-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                                    <div class="leading-relaxed">
                                        <strong>Warning:</strong> Backup file originates from email <strong>{{ $previewData['source_email'] }}</strong>, while your current account is <strong>{{ auth()->user()->email }}</strong>.
                                    </div>
                                </div>
                                
                                <label class="flex items-center gap-2 pt-1 font-semibold text-[11px] cursor-pointer text-amber-950 dark:text-amber-100 border-t border-amber-200/60 dark:border-amber-800/60">
                                    <input type="checkbox" wire:model.live="confirmDifferentAccount" class="rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                                    <span>I understand &amp; wish to import this data into my account anyway</span>
                                </label>
                            </div>
                        @endif

                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-center text-xs">
                            <div class="p-2 rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800">
                                <div class="text-[10px] text-zinc-400 font-bold">Projects</div>
                                <div class="font-bold text-zinc-900 dark:text-zinc-100 font-mono mt-0.5">{{ $previewData['total_projects'] }}</div>
                            </div>
                            <div class="p-2 rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800">
                                <div class="text-[10px] text-zinc-400 font-bold">Categories</div>
                                <div class="font-bold text-zinc-900 dark:text-zinc-100 font-mono mt-0.5">{{ $previewData['total_categories'] }}</div>
                            </div>
                            <div class="p-2 rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800">
                                <div class="text-[10px] text-zinc-400 font-bold">Activities</div>
                                <div class="font-bold text-zinc-900 dark:text-zinc-100 font-mono mt-0.5">{{ $previewData['total_activities'] }}</div>
                            </div>
                            <div class="p-2 rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800">
                                <div class="text-[10px] text-zinc-400 font-bold">Tasks</div>
                                <div class="font-bold text-zinc-900 dark:text-zinc-100 font-mono mt-0.5">{{ $previewData['total_tasks'] ?? 0 }}</div>
                            </div>
                            <div class="p-2 rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800">
                                <div class="text-[10px] text-zinc-400 font-bold">Labels</div>
                                <div class="font-bold text-zinc-900 dark:text-zinc-100 font-mono mt-0.5">{{ $previewData['total_labels'] ?? 0 }}</div>
                            </div>
                        </div>

                        <!-- Mode Options with Instant Alpine Switching -->
                        <div class="pt-2 space-y-2" x-data="{ selectedMode: @entangle('restoreMode') }">
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Restore Method:</label>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label :class="selectedMode === 'merge' ? 'border-zinc-900 dark:border-white bg-white dark:bg-zinc-900 shadow-2xs' : 'border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/50'" class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition-all duration-150">
                                    <input type="radio" x-model="selectedMode" value="merge" class="mt-0.5">
                                    <div class="text-xs">
                                        <div class="font-bold text-zinc-900 dark:text-zinc-100">Merge Data</div>
                                        <div class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5 leading-relaxed">Append new data without removing existing data (Recommended).</div>
                                    </div>
                                </label>

                                <label :class="selectedMode === 'replace' ? 'border-rose-500 bg-rose-50/30 dark:bg-rose-950/20' : 'border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/50'" class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition-all duration-150">
                                    <input type="radio" x-model="selectedMode" value="replace" class="mt-0.5 text-rose-600">
                                    <div class="text-xs">
                                        <div class="font-bold text-rose-600 dark:text-rose-400">Replace Data</div>
                                        <div class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5 leading-relaxed">Wipe existing history and replace with data from backup file.</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Confirm Restore Button -->
                        <div class="pt-2">
                            <button type="button" wire:click="processRestore" wire:loading.attr="disabled" @if(! $previewData['email_matched'] && ! $confirmDifferentAccount) disabled @endif class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-zinc-900 hover:bg-zinc-800 dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-900 text-xs font-semibold shadow-2xs active:scale-95 transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100">
                                <flux:icon name="arrow-path" class="size-4 shrink-0 text-white dark:text-zinc-900" wire:loading.remove wire:target="processRestore" />
                                <svg wire:loading wire:target="processRestore" class="animate-spin h-4 w-4 text-white dark:text-zinc-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span wire:loading.remove wire:target="processRestore">Process &amp; Restore Data Now</span>
                                <span wire:loading wire:target="processRestore">Restoring Data...</span>
                            </button>
                        </div>
                    </div>
                @endif

            </div>

        </div>

    </x-pages::settings.layout>
</section>

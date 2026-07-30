<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
                session()->flash('status_error', 'Format berkas tidak valid. Harap gunakan berkas JSON hasil ekspor Activity Tracker.');
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
            ];

            $this->confirmDifferentAccount = false;
        } catch (\Throwable $e) {
            session()->flash('status_error', 'Gagal membaca berkas. Pastikan berkas tidak terenkripsi atau rusak.');
            $this->previewData = null;
        }
    }

    public function downloadBackup()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $projects = $user->projects()->get(['id', 'name', 'created_at']);
        $categories = $user->categories()->get(['id', 'name', 'created_at']);
        $activities = $user->activities()
            ->with(['project', 'category'])
            ->orderBy('start_time', 'asc')
            ->get();

        $latestActivity = $activities->last();

        $backupData = [
            'version' => '1.0',
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
                'latest_activity_at' => $latestActivity ? $latestActivity->start_time->toIso8601String() : null,
            ],
            'projects' => $projects->map(fn ($p) => ['name' => $p->name])->values()->all(),
            'categories' => $categories->map(fn ($c) => ['name' => $c->name])->values()->all(),
            'activities' => $activities->map(function ($act) {
                return [
                    'project' => $act->project ? $act->project->name : 'General',
                    'category' => $act->category ? $act->category->name : 'Uncategorized',
                    'detail' => $act->detail,
                    'start_time' => $act->start_time->toDateTimeString(),
                    'end_time' => $act->end_time ? $act->end_time->toDateTimeString() : null,
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
            session()->flash('status_error', 'Harap pilih berkas backup terlebih dahulu.');

            return;
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();

        try {
            $content = file_get_contents($this->backupFile->getRealPath());
            $data = json_decode($content, true);

            if (! is_array($data) || ! isset($data['activities'])) {
                session()->flash('status_error', 'Struktur berkas backup tidak valid.');

                return;
            }

            $fileEmail = strtolower(trim($data['user']['email'] ?? ''));
            $currentEmail = strtolower(trim($user->email));

            if ($fileEmail !== '' && $fileEmail !== $currentEmail && ! $this->confirmDifferentAccount) {
                session()->flash('status_error', "Email akun pada berkas backup ({$fileEmail}) tidak cocok dengan akun Anda ({$currentEmail}). Harap centang konfirmasi jika Anda ingin mengimpor data ini.");

                return;
            }

            DB::transaction(function () use ($user, $data) {
                if ($this->restoreMode === 'replace') {
                    $user->activities()->delete();
                }

                $importedCount = 0;
                $projectMap = [];
                $categoryMap = [];

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
                        'is_parallel' => (bool) ($act['is_parallel'] ?? false),
                    ]);

                    $importedCount++;
                }

                session()->flash('status_success', "Berhasil memulihkan {$importedCount} aktivitas ke dalam akun Anda! 🎉");

                $user->notifications()->create([
                    'title' => '🔄 Restore Data Berhasil',
                    'body' => "Sebanyak {$importedCount} aktivitas telah dipulihkan dari berkas cadangan.",
                    'type' => 'success',
                ]);
            });

            $this->reset(['backupFile', 'previewData']);
        } catch (\Throwable $e) {
            session()->flash('status_error', 'Gagal memproses restore data: '.$e->getMessage());
        }
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Backup & Restore settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Backup & Restore')" :subheading="__('Export your complete activity history or restore from a JSON backup file.')">
        
        <div class="space-y-6">
            
            <!-- Alert Notifications -->
            @if (session()->has('status_success'))
                <div class="rounded-2xl p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-900 dark:text-emerald-100 flex items-start gap-3 shadow-2xs">
                    <div class="size-8 rounded-xl bg-emerald-100 dark:bg-emerald-900/60 border border-emerald-300 dark:border-emerald-700/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                        <flux:icon name="check-circle" class="size-4.5" />
                    </div>
                    <div class="text-xs">
                        <h4 class="font-bold">Restore Berhasil</h4>
                        <p class="mt-0.5 opacity-90 leading-relaxed">{{ session('status_success') }}</p>
                    </div>
                </div>
            @endif

            @if (session()->has('status_error'))
                <div class="rounded-2xl p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-900 dark:text-rose-100 flex items-start gap-3 shadow-2xs">
                    <div class="size-8 rounded-xl bg-rose-100 dark:bg-rose-900/60 border border-rose-300 dark:border-rose-700/60 flex items-center justify-center text-rose-600 dark:text-rose-400 shrink-0">
                        <flux:icon name="exclamation-triangle" class="size-4.5" />
                    </div>
                    <div class="text-xs">
                        <h4 class="font-bold">Gagal Memproses</h4>
                        <p class="mt-0.5 opacity-90 leading-relaxed">{{ session('status_error') }}</p>
                    </div>
                </div>
            @endif

            <!-- BACKUP SECTION -->
            <div class="p-5 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 space-y-4 shadow-2xs">
                <div class="flex items-start gap-3.5">
                    <div class="size-10 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0">
                        <flux:icon name="arrow-down-tray" class="size-5" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Cadangkan Data (Backup)</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 leading-relaxed">
                            Unduh seluruh riwayat aktivitas, proyek, dan kategori Anda hingga aktivitas terbaru dalam berkas cadangan JSON yang sangat restore-friendly.
                        </p>
                    </div>
                </div>

                <!-- Stats summary badge -->
                @php
                    $user = auth()->user();
                    $totalProjects = $user->projects()->count();
                    $totalCategories = $user->categories()->count();
                    $totalActivities = $user->activities()->count();
                    $latestActivity = $user->activities()->latest('start_time')->first();
                @endphp

                <div class="grid grid-cols-3 gap-2.5 p-3 rounded-xl bg-zinc-50 dark:bg-zinc-950/60 border border-zinc-200/60 dark:border-zinc-800/60">
                    <div class="text-center">
                        <div class="text-[10px] uppercase font-bold text-zinc-400 dark:text-zinc-500">Proyek</div>
                        <div class="text-base font-extrabold text-zinc-900 dark:text-zinc-100 mt-0.5 font-mono">{{ $totalProjects }}</div>
                    </div>
                    <div class="text-center border-x border-zinc-200/60 dark:border-zinc-800/60">
                        <div class="text-[10px] uppercase font-bold text-zinc-400 dark:text-zinc-500">Kategori</div>
                        <div class="text-base font-extrabold text-zinc-900 dark:text-zinc-100 mt-0.5 font-mono">{{ $totalCategories }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-[10px] uppercase font-bold text-zinc-400 dark:text-zinc-500">Aktivitas</div>
                        <div class="text-base font-extrabold text-zinc-900 dark:text-zinc-100 mt-0.5 font-mono">{{ $totalActivities }}</div>
                    </div>
                </div>

                @if($latestActivity)
                    <div class="text-[11px] text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 pt-1">
                        <flux:icon name="clock" class="size-3.5 text-zinc-400" />
                        <span>Aktivitas Terakhir: <strong>{{ $latestActivity->start_time->format('d M Y, H:i') }}</strong> ({{ $latestActivity->detail }})</span>
                    </div>
                @endif

                <div class="pt-2">
                    <button type="button" wire:click="downloadBackup" wire:loading.attr="disabled" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-zinc-900 hover:bg-zinc-800 dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-900 text-xs font-semibold shadow-2xs active:scale-95 transition-all cursor-pointer">
                        <flux:icon name="arrow-down-tray" class="size-4 shrink-0 text-white dark:text-zinc-900" wire:loading.remove wire:target="downloadBackup" />
                        <svg wire:loading wire:target="downloadBackup" class="animate-spin h-4 w-4 text-white dark:text-zinc-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="downloadBackup">Unduh Berkas Backup (.json)</span>
                        <span wire:loading wire:target="downloadBackup">Mengunduh...</span>
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
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Pulihkan Data (Restore)</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 leading-relaxed">
                            Unggah berkas cadangan JSON untuk memulihkan seluruh aktivitas, proyek, dan kategori ke akun Anda.
                        </p>
                    </div>
                </div>

                <!-- Upload Dropzone / Button -->
                <div class="space-y-3">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Pilih Berkas Backup (.json)</label>
                    
                    <div class="relative flex items-center justify-center p-6 rounded-2xl border-2 border-dashed border-zinc-300 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-950/40 hover:bg-zinc-100/60 dark:hover:bg-zinc-800/40 transition-colors cursor-pointer text-center">
                        <input type="file" wire:model="backupFile" accept=".json" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <div class="space-y-2 pointer-events-none">
                            <div class="size-10 rounded-xl bg-zinc-200/80 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700/60 mx-auto flex items-center justify-center text-zinc-600 dark:text-zinc-400">
                                <flux:icon name="document-arrow-up" class="size-5" />
                            </div>
                            <div class="text-xs text-zinc-600 dark:text-zinc-400 font-medium">
                                <span class="text-zinc-900 dark:text-zinc-100 font-bold">Klik untuk memilih berkas</span> atau seret berkas ke sini
                            </div>
                            <div class="text-[10px] text-zinc-400">Mendukung berkas JSON backup resmi Activity Tracker (Maks 20MB)</div>
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
                                <span>Email akun cocok: <strong>{{ $previewData['source_email'] }}</strong></span>
                            </div>
                        @else
                            <div class="p-3 rounded-xl bg-amber-50/90 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-amber-900 dark:text-amber-100 text-xs space-y-2">
                                <div class="flex items-start gap-2">
                                    <flux:icon name="exclamation-triangle" class="size-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                                    <div class="leading-relaxed">
                                        <strong>Perhatian:</strong> Berkas backup berasal dari email <strong>{{ $previewData['source_email'] }}</strong>, sedangkan akun Anda saat ini adalah <strong>{{ auth()->user()->email }}</strong>.
                                    </div>
                                </div>
                                
                                <label class="flex items-center gap-2 pt-1 font-semibold text-[11px] cursor-pointer text-amber-950 dark:text-amber-100 border-t border-amber-200/60 dark:border-amber-800/60">
                                    <input type="checkbox" wire:model.live="confirmDifferentAccount" class="rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                                    <span>Saya mengerti &amp; tetap ingin mengimpor data ini ke akun saya</span>
                                </label>
                            </div>
                        @endif

                        <div class="grid grid-cols-3 gap-2 text-center text-xs">
                            <div class="p-2 rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800">
                                <div class="text-[10px] text-zinc-400 font-bold">Proyek</div>
                                <div class="font-bold text-zinc-900 dark:text-zinc-100 font-mono mt-0.5">{{ $previewData['total_projects'] }}</div>
                            </div>
                            <div class="p-2 rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800">
                                <div class="text-[10px] text-zinc-400 font-bold">Kategori</div>
                                <div class="font-bold text-zinc-900 dark:text-zinc-100 font-mono mt-0.5">{{ $previewData['total_categories'] }}</div>
                            </div>
                            <div class="p-2 rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800">
                                <div class="text-[10px] text-zinc-400 font-bold">Aktivitas</div>
                                <div class="font-bold text-zinc-900 dark:text-zinc-100 font-mono mt-0.5">{{ $previewData['total_activities'] }}</div>
                            </div>
                        </div>

                        <!-- Mode Options with Instant Alpine Switching -->
                        <div class="pt-2 space-y-2" x-data="{ selectedMode: @entangle('restoreMode') }">
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Metode Pemulihan Data:</label>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label :class="selectedMode === 'merge' ? 'border-zinc-900 dark:border-white bg-white dark:bg-zinc-900 shadow-2xs' : 'border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/50'" class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition-all duration-150">
                                    <input type="radio" x-model="selectedMode" value="merge" class="mt-0.5">
                                    <div class="text-xs">
                                        <div class="font-bold text-zinc-900 dark:text-zinc-100">Gabungkan (Merge)</div>
                                        <div class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5 leading-relaxed">Tambahkan data baru tanpa menghapus data lama (Recommended).</div>
                                    </div>
                                </label>

                                <label :class="selectedMode === 'replace' ? 'border-rose-500 bg-rose-50/30 dark:bg-rose-950/20' : 'border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/50'" class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition-all duration-150">
                                    <input type="radio" x-model="selectedMode" value="replace" class="mt-0.5 text-rose-600">
                                    <div class="text-xs">
                                        <div class="font-bold text-rose-600 dark:text-rose-400">Timpa (Replace)</div>
                                        <div class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5 leading-relaxed">Hapus riwayat lama &amp; gantikan dengan data dari berkas backup.</div>
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
                                <span wire:loading.remove wire:target="processRestore">Proses &amp; Pulihkan Data Sekarang</span>
                                <span wire:loading wire:target="processRestore">Memulihkan Data...</span>
                            </button>
                        </div>
                    </div>
                @endif

            </div>

        </div>

    </x-pages::settings.layout>
</section>

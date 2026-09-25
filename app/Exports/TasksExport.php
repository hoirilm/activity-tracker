<?php

namespace App\Exports;

use App\Models\Task;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TasksExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected int $userId;

    protected array $statuses;

    protected array $projectIds;

    protected string $dateType;

    protected ?string $startDate;

    protected ?string $endDate;

    protected string $sortBy;

    protected bool $includeChecklists;

    protected bool $includeLabels;

    protected bool $includeDescription;

    protected bool $includeTrackedTime;

    public function __construct(
        ?int $userId = null,
        array $statuses = [Task::STATUS_ON_HOLD, Task::STATUS_NEW, Task::STATUS_ON_PROGRESS, Task::STATUS_DONE],
        array $projectIds = ['all'],
        string $dateType = 'all',
        ?string $startDate = null,
        ?string $endDate = null,
        string $sortBy = 'latest',
        bool $includeChecklists = true,
        bool $includeLabels = true,
        bool $includeDescription = true,
        bool $includeTrackedTime = true
    ) {
        $this->userId = $userId ?? (int) auth()->id();
        $this->statuses = ! empty($statuses) ? $statuses : [Task::STATUS_ON_HOLD, Task::STATUS_NEW, Task::STATUS_ON_PROGRESS, Task::STATUS_DONE];
        $this->projectIds = ! empty($projectIds) ? $projectIds : ['all'];
        $this->dateType = $dateType;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->sortBy = $sortBy;
        $this->includeChecklists = $includeChecklists;
        $this->includeLabels = $includeLabels;
        $this->includeDescription = $includeDescription;
        $this->includeTrackedTime = $includeTrackedTime;
    }

    public function collection()
    {
        $query = Task::with(['project', 'labels', 'checklists', 'activities'])
            ->where('user_id', $this->userId)
            ->whereIn('status', $this->statuses);

        // Project filtering
        if (! in_array('all', $this->projectIds, true)) {
            $includeNonProject = in_array('non_project', $this->projectIds, true);
            $numericProjectIds = array_values(array_filter($this->projectIds, fn ($id) => is_numeric($id)));

            $query->where(function ($q) use ($includeNonProject, $numericProjectIds) {
                if ($includeNonProject && ! empty($numericProjectIds)) {
                    $q->whereIn('project_id', $numericProjectIds)
                        ->orWhereNull('project_id');
                } elseif ($includeNonProject) {
                    $q->whereNull('project_id');
                } elseif (! empty($numericProjectIds)) {
                    $q->whereIn('project_id', $numericProjectIds);
                } else {
                    $q->whereRaw('1 = 0'); // No project selected
                }
            });
        }

        // Date range filtering
        if ($this->dateType === 'created_at') {
            if ($this->startDate) {
                $query->whereDate('created_at', '>=', $this->startDate);
            }
            if ($this->endDate) {
                $query->whereDate('created_at', '<=', $this->endDate);
            }
        } elseif ($this->dateType === 'due_at') {
            if ($this->startDate) {
                $query->whereDate('due_at', '>=', $this->startDate);
            }
            if ($this->endDate) {
                $query->whereDate('due_at', '<=', $this->endDate);
            }
        }

        // Sorting
        switch ($this->sortBy) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'due_at':
                $query->orderByRaw('due_at IS NULL, due_at ASC');
                break;
            case 'title':
                $query->orderBy('title', 'asc');
                break;
            case 'status':
                $query->orderBy('status', 'asc');
                break;
            case 'project':
                $query->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
                    ->orderBy('projects.name', 'asc')
                    ->select('tasks.*');
                break;
            case 'latest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        return $query->get();
    }

    public function headings(): array
    {
        $headings = [
            'ID',
            'Title',
        ];

        if ($this->includeDescription) {
            $headings[] = 'Description';
        }

        $headings[] = 'Status';
        $headings[] = 'Project';
        $headings[] = 'Due Date';

        if ($this->includeLabels) {
            $headings[] = 'Labels';
        }

        if ($this->includeChecklists) {
            $headings[] = 'Checklist Progress';
            $headings[] = 'Checklist Items';
        }

        if ($this->includeTrackedTime) {
            $headings[] = 'Total Tracked Time';
        }

        $headings[] = 'Created At';
        $headings[] = 'Updated At';

        return $headings;
    }

    public function map($task): array
    {
        $statusLabels = [
            Task::STATUS_NEW => 'New',
            Task::STATUS_ON_PROGRESS => 'On Progress',
            Task::STATUS_ON_HOLD => 'On Hold',
            Task::STATUS_DONE => 'Done',
            Task::STATUS_ARCHIVED => 'Archived',
        ];

        $statusText = $statusLabels[$task->status] ?? ucfirst(str_replace('_', ' ', $task->status));
        $projectName = $task->project?->name ?? 'No Project';
        $dueAtText = $task->due_at ? $task->due_at->format('Y-m-d H:i') : '-';

        $row = [
            $task->id,
            $task->title,
        ];

        if ($this->includeDescription) {
            $row[] = $task->description ?? '';
        }

        $row[] = $statusText;
        $row[] = $projectName;
        $row[] = $dueAtText;

        if ($this->includeLabels) {
            $labels = $task->labels->pluck('name')->implode(', ');
            $row[] = $labels ?: '-';
        }

        if ($this->includeChecklists) {
            $totalChecklists = $task->checklists->count();
            if ($totalChecklists === 0) {
                $progressText = 'None';
                $itemsText = '-';
            } else {
                $completed = $task->checklists->where('is_completed', true)->count();
                $pct = (int) round(($completed / $totalChecklists) * 100);
                $progressText = "{$completed}/{$totalChecklists} ({$pct}%)";
                $itemsText = $task->checklists->map(function ($item) {
                    return ($item->is_completed ? '[x] ' : '[ ] ').$item->title;
                })->implode("\n");
            }

            $row[] = $progressText;
            $row[] = $itemsText;
        }

        if ($this->includeTrackedTime) {
            $totalSeconds = 0;
            foreach ($task->activities as $activity) {
                $totalSeconds += $activity->elapsed_seconds;
            }

            if ($totalSeconds <= 0) {
                $timeText = '00:00:00';
            } else {
                $hours = floor($totalSeconds / 3600);
                $minutes = floor(($totalSeconds % 3600) / 60);
                $seconds = $totalSeconds % 60;
                $timeText = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            }

            $row[] = $timeText;
        }

        $row[] = $task->created_at ? $task->created_at->format('Y-m-d H:i:s') : '';
        $row[] = $task->updated_at ? $task->updated_at->format('Y-m-d H:i:s') : '';

        return $row;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '18181B'], // Zinc 900
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => false,
                ],
            ],
        ];
    }
}

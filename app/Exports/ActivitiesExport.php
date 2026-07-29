<?php

namespace App\Exports;

use App\Models\Activity;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ActivitiesExport implements FromCollection, WithHeadings, WithMapping
{
    protected $startDate;

    protected $endDate;

    protected $userId;

    public function __construct($startDate = null, $endDate = null, $userId = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->userId = $userId ?? auth()->id();
    }

    public function collection()
    {
        $query = Activity::with(['project', 'category'])->where('user_id', $this->userId);

        if ($this->startDate) {
            $query->whereDate('start_time', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('start_time', '<=', $this->endDate);
        }

        return $query->orderBy('start_time', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Project',
            'Category',
            'Detail',
            'Start Time',
            'End Time',
            'Duration',
            'Is Parallel',
        ];
    }

    public function map($activity): array
    {
        return [
            $activity->id,
            $activity->project->name ?? 'Unknown',
            $activity->category->name ?? 'Unknown',
            $activity->detail,
            $activity->start_time,
            $activity->end_time,
            $activity->duration,
            $activity->is_parallel ? 'Yes' : 'No',
        ];
    }
}

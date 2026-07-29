<?php

namespace App\Imports;

use App\Models\Activity;
use App\Models\Category;
use App\Models\Project;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ActivitiesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (! isset($row['project']) || ! isset($row['category']) || ! isset($row['detail']) || ! isset($row['start_time'])) {
            return null;
        }

        $project = Project::firstOrCreate(['name' => $row['project']]);
        $category = Category::firstOrCreate(['name' => $row['category']]);

        return new Activity([
            'project_id' => $project->id,
            'category_id' => $category->id,
            'detail' => $row['detail'],
            'start_time' => is_numeric($row['start_time']) ? Carbon::instance(Date::excelToDateTimeObject($row['start_time'])) : Carbon::parse($row['start_time']),
            'end_time' => $row['end_time'] ? (is_numeric($row['end_time']) ? Carbon::instance(Date::excelToDateTimeObject($row['end_time'])) : Carbon::parse($row['end_time'])) : null,
            'is_parallel' => strtolower($row['is_parallel'] ?? '') === 'yes',
        ]);
    }
}

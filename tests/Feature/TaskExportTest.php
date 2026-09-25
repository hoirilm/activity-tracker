<?php

use App\Exports\TasksExport;
use App\Models\Activity;
use App\Models\Category;
use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;

test('authenticated user can export tasks with default settings', function () {
    Excel::fake();
    Excel::matchByRegex();

    $user = User::factory()->create();
    $project = Project::create(['user_id' => $user->id, 'name' => 'Project Alpha']);

    Task::create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'New Task',
        'status' => Task::STATUS_NEW,
    ]);

    Task::create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Progress Task',
        'status' => Task::STATUS_ON_PROGRESS,
    ]);

    Task::create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Hold Task',
        'status' => Task::STATUS_ON_HOLD,
    ]);

    Task::create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Done Task',
        'status' => Task::STATUS_DONE,
    ]);

    Task::create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Archived Task',
        'status' => Task::STATUS_ARCHIVED,
    ]);

    $component = Livewire::actingAs($user)
        ->test('manage');

    // Default matching count should be 4 (on_hold, new, on_progress, done)
    expect($component->get('exportFilteredCount'))->toBe(4);

    $component->call('exportTasks')
        ->assertHasNoErrors();

    Excel::assertDownloaded('/tasks_export_.*\.xlsx/', function (TasksExport $export) {
        $collection = $export->collection();
        expect($collection->count())->toBe(4);

        $statuses = $collection->pluck('status')->all();
        expect($statuses)->toContain(Task::STATUS_NEW, Task::STATUS_ON_PROGRESS, Task::STATUS_ON_HOLD, Task::STATUS_DONE)
            ->not->toContain(Task::STATUS_ARCHIVED);

        return true;
    });
});

test('user can filter export by specific project and standalone tasks', function () {
    Excel::fake();
    Excel::matchByRegex();

    $user = User::factory()->create();
    $projectA = Project::create(['user_id' => $user->id, 'name' => 'Project A']);
    $projectB = Project::create(['user_id' => $user->id, 'name' => 'Project B']);

    Task::create([
        'user_id' => $user->id,
        'project_id' => $projectA->id,
        'title' => 'Task in Project A',
        'status' => Task::STATUS_NEW,
    ]);

    Task::create([
        'user_id' => $user->id,
        'project_id' => $projectB->id,
        'title' => 'Task in Project B',
        'status' => Task::STATUS_NEW,
    ]);

    Task::create([
        'user_id' => $user->id,
        'project_id' => null,
        'title' => 'Standalone Task',
        'status' => Task::STATUS_NEW,
    ]);

    // Export only Project A
    $component = Livewire::actingAs($user)
        ->test('manage')
        ->set('exportProjectMode', 'custom')
        ->set('exportSelectedProjectIds', [(string) $projectA->id]);

    expect($component->get('exportFilteredCount'))->toBe(1);

    $component->call('exportTasks')
        ->assertHasNoErrors();

    Excel::assertDownloaded('/tasks_export_.*\.xlsx/', function (TasksExport $export) use ($projectA) {
        $tasks = $export->collection();
        expect($tasks->count())->toBe(1);
        expect($tasks->first()->project_id)->toBe($projectA->id);
        return true;
    });

    // Export only Standalone (non-project) tasks
    $componentStandalone = Livewire::actingAs($user)
        ->test('manage')
        ->set('exportProjectMode', 'custom')
        ->set('exportSelectedProjectIds', ['non_project']);

    expect($componentStandalone->get('exportFilteredCount'))->toBe(1);

    $componentStandalone->call('exportTasks')
        ->assertHasNoErrors();

    Excel::assertDownloaded('/tasks_export_.*\.xlsx/', function (TasksExport $export) {
        $tasks = $export->collection();
        expect($tasks->count())->toBe(1);
        expect($tasks->first()->project_id)->toBeNull();
        expect($tasks->first()->title)->toBe('Standalone Task');
        return true;
    });
});

test('user can filter export by specific status including archived', function () {
    Excel::fake();
    Excel::matchByRegex();

    $user = User::factory()->create();

    Task::create(['user_id' => $user->id, 'title' => 'Task New', 'status' => Task::STATUS_NEW]);
    Task::create(['user_id' => $user->id, 'title' => 'Task Done 1', 'status' => Task::STATUS_DONE]);
    Task::create(['user_id' => $user->id, 'title' => 'Task Done 2', 'status' => Task::STATUS_DONE]);
    Task::create(['user_id' => $user->id, 'title' => 'Task Archived', 'status' => Task::STATUS_ARCHIVED]);

    // Select done only
    $component = Livewire::actingAs($user)
        ->test('manage')
        ->call('selectDoneExportStatuses');

    expect($component->get('exportFilteredCount'))->toBe(2);

    $component->call('exportTasks')
        ->assertHasNoErrors();

    Excel::assertDownloaded('/tasks_export_.*\.xlsx/', function (TasksExport $export) {
        $tasks = $export->collection();
        expect($tasks->count())->toBe(2);
        expect($tasks->pluck('status')->unique()->all())->toBe([Task::STATUS_DONE]);
        return true;
    });

    // Select all statuses including archived
    $componentAll = Livewire::actingAs($user)
        ->test('manage')
        ->call('selectAllExportStatuses');

    expect($componentAll->get('exportFilteredCount'))->toBe(4);
});

test('user can export in CSV format', function () {
    Excel::fake();
    Excel::matchByRegex();

    $user = User::factory()->create();
    Task::create(['user_id' => $user->id, 'title' => 'Test CSV Task', 'status' => Task::STATUS_NEW]);

    Livewire::actingAs($user)
        ->test('manage')
        ->set('exportFormat', 'csv')
        ->call('exportTasks')
        ->assertHasNoErrors();

    Excel::assertDownloaded('/tasks_export_.*\.csv/');
});

test('tasks export mapping contains labels, checklists, and tracked duration', function () {
    $user = User::factory()->create();
    $project = Project::create(['user_id' => $user->id, 'name' => 'Design System']);
    $category = Category::create(['user_id' => $user->id, 'name' => 'Engineering']);
    $label = Label::create(['user_id' => $user->id, 'name' => 'Urgent', 'color' => 'rose']);

    $task = Task::create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Complex Export Task',
        'description' => 'Important details',
        'status' => Task::STATUS_ON_PROGRESS,
        'due_at' => Carbon::parse('2026-10-01 15:00:00'),
    ]);

    $task->labels()->attach($label);
    $task->checklists()->create(['title' => 'Step 1', 'is_completed' => true, 'position' => 0]);
    $task->checklists()->create(['title' => 'Step 2', 'is_completed' => false, 'position' => 1]);

    // Create an activity linked to this task with elapsed time
    Activity::create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'category_id' => $category->id,
        'task_id' => $task->id,
        'detail' => 'Worked on task design and logic',
        'start_time' => Carbon::now()->subMinutes(90),
        'end_time' => Carbon::now(),
        'paused_seconds' => 0,
        'is_parallel' => false,
    ]);

    $export = new TasksExport(
        userId: $user->id,
        statuses: [Task::STATUS_ON_PROGRESS],
        projectIds: ['all'],
        includeChecklists: true,
        includeLabels: true,
        includeDescription: true,
        includeTrackedTime: true
    );

    $collection = $export->collection();
    expect($collection->count())->toBe(1);

    $mapped = $export->map($collection->first());
    $headings = $export->headings();

    expect($headings)->toContain(
        'ID',
        'Title',
        'Description',
        'Status',
        'Project',
        'Due Date',
        'Labels',
        'Checklist Progress',
        'Checklist Items',
        'Total Tracked Time',
        'Created At',
        'Updated At'
    );

    expect($mapped)->toContain(
        'Complex Export Task',
        'Important details',
        'On Progress',
        'Design System',
        '2026-10-01 15:00',
        'Urgent',
        '1/2 (50%)',
        "[x] Step 1\n[ ] Step 2"
    );

    // Tracked time index
    $timeIndex = array_search('Total Tracked Time', $headings);
    expect($mapped[$timeIndex])->toBe('01:30:00');
});

test('validation prevents export when no status is selected', function () {
    Excel::fake();

    $user = User::factory()->create();
    Task::create(['user_id' => $user->id, 'title' => 'Test Task', 'status' => Task::STATUS_NEW]);

    Livewire::actingAs($user)
        ->test('manage')
        ->set('exportStatuses', [])
        ->call('exportTasks')
        ->assertHasErrors(['exportStatuses']);
});

test('validation prevents export when custom project mode is chosen without projects', function () {
    Excel::fake();

    $user = User::factory()->create();
    Task::create(['user_id' => $user->id, 'title' => 'Test Task', 'status' => Task::STATUS_NEW]);

    Livewire::actingAs($user)
        ->test('manage')
        ->set('exportProjectMode', 'custom')
        ->set('exportSelectedProjectIds', [])
        ->call('exportTasks')
        ->assertHasErrors(['exportSelectedProjectIds']);
});

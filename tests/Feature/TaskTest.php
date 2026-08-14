<?php

use App\Models\Activity;
use App\Models\Category;
use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Livewire\Livewire;

test('authenticated user can create project and non-project tasks via livewire', function () {
    $user = User::factory()->create();
    $project = Project::create(['user_id' => $user->id, 'name' => 'Project Antigravity']);

    Livewire::actingAs($user)
        ->test('manage')
        ->set('taskTitle', 'Feature Task Project')
        ->set('taskProjectId', $project->id)
        ->set('taskStatus', 'new')
        ->call('addTask')
        ->assertHasNoErrors();

    expect(Task::where('title', 'Feature Task Project')->exists())->toBeTrue();
    $task = Task::where('title', 'Feature Task Project')->first();
    expect($task->project_id)->toBe($project->id);
    expect($task->status)->toBe('new');

    // Create non-project task
    Livewire::actingAs($user)
        ->test('manage')
        ->set('taskTitle', 'Standalone Task Non Project')
        ->set('taskProjectId', null)
        ->call('addTask')
        ->assertHasNoErrors();

    $nonProjectTask = Task::where('title', 'Standalone Task Non Project')->first();
    expect($nonProjectTask->project_id)->toBeNull();
    expect($nonProjectTask->status)->toBe('new');
});

test('user can update task status and attach labels', function () {
    $user = User::factory()->create();
    $task = Task::create([
        'user_id' => $user->id,
        'title' => 'Sample Task',
        'status' => 'new',
    ]);
    $label = Label::create([
        'user_id' => $user->id,
        'name' => 'belum ada open project',
        'color' => 'amber',
    ]);

    Livewire::actingAs($user)
        ->test('manage')
        ->call('updateTaskStatus', $task->id, 'on_progress');

    expect($task->fresh()->status)->toBe('on_progress');

    Livewire::actingAs($user)
        ->test('manage')
        ->set('editingTaskId', $task->id)
        ->set('editingTaskTitle', 'Sample Task Updated')
        ->set('editingTaskStatus', 'done')
        ->set('editingTaskLabelIds', [$label->id])
        ->call('updateTask');

    expect($task->fresh()->status)->toBe('done');
    expect($task->fresh()->labels->first()->name)->toBe('belum ada open project');
});

test('user can manage dynamic task labels', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('manage')
        ->set('labelName', 'urgent bug')
        ->set('labelColor', 'rose')
        ->call('addLabel');

    expect(Label::where('name', 'urgent bug')->exists())->toBeTrue();

    Livewire::actingAs($user)
        ->test('manage')
        ->call('addPresetLabel', 'belum ada open project', 'amber');

    expect(Label::where('name', 'belum ada open project')->exists())->toBeTrue();
});

test('starting activity linked to task automatically updates task status to on_progress', function () {
    $user = User::factory()->create();
    $project = Project::create(['user_id' => $user->id, 'name' => 'Project Demo']);
    $category = Category::create(['user_id' => $user->id, 'name' => 'Dev']);
    $task = Task::create([
        'user_id' => $user->id,
        'title' => 'Fix Login Bug',
        'status' => 'new',
        'project_id' => $project->id,
    ]);

    Livewire::actingAs($user)
        ->test('tracker')
        ->set('task_id', $task->id)
        ->set('project_id', $project->id)
        ->set('category_id', $category->id)
        ->set('detail', 'Working on Login Bug')
        ->call('startActivity')
        ->assertHasNoErrors();

    $activity = Activity::where('detail', 'Working on Login Bug')->first();
    expect($activity->task_id)->toBe($task->id);
    expect($task->fresh()->status)->toBe('on_progress');
});

test('user can set task deadline and filter by deadline', function () {
    $user = User::factory()->create();

    $dueAt = now()->addDays(2)->format('Y-m-d\TH:i');

    Livewire::actingAs($user)
        ->test('manage')
        ->set('taskTitle', 'Task With Deadline')
        ->set('taskDueAt', $dueAt)
        ->call('addTask')
        ->assertHasNoErrors();

    $task = Task::where('title', 'Task With Deadline')->first();
    expect($task->due_at)->not()->toBeNull();
    expect($task->due_badge)->not()->toBeNull();

    // Overdue task test
    $overdueTask = Task::create([
        'user_id' => $user->id,
        'title' => 'Overdue Task',
        'status' => 'new',
        'due_at' => now()->subDay(),
    ]);

    expect($overdueTask->isOverdue())->toBeTrue();
    expect($overdueTask->due_badge['type'])->toBe('overdue');

    // Test deadline filtering
    Livewire::actingAs($user)
        ->test('manage')
        ->set('filterDeadline', 'overdue')
        ->assertSee('Overdue Task')
        ->assertDontSee('Task With Deadline');
});

test('task search in manage menu is case-insensitive', function () {
    $user = User::factory()->create();

    Task::create([
        'user_id' => $user->id,
        'title' => 'Develop QRIS Integration',
        'description' => 'Implement QRIS API endpoints',
        'status' => 'new',
    ]);

    Livewire::actingAs($user)
        ->test('manage')
        ->set('searchTask', 'qris')
        ->assertSee('Develop QRIS Integration')
        ->set('searchTask', 'QRIS')
        ->assertSee('Develop QRIS Integration')
        ->set('searchTask', 'Qris')
        ->assertSee('Develop QRIS Integration')
        ->set('searchTask', 'NONEXISTENT_KEYWORD')
        ->assertDontSee('Develop QRIS Integration');
});

test('user can open task detail modal and switch to edit task', function () {
    $user = User::factory()->create();

    $task = Task::create([
        'user_id' => $user->id,
        'title' => 'Detailed Task Test',
        'description' => 'Important details about this task.',
        'status' => 'new',
    ]);

    Livewire::actingAs($user)
        ->test('manage')
        ->call('showTaskDetail', $task->id)
        ->assertSet('viewingTaskId', $task->id)
        ->assertSee('Detailed Task Test')
        ->assertSee('Important details about this task.')
        ->call('editTaskFromDetail')
        ->assertDispatched('close-modal', name: 'detail-task-modal');
});

test('user can create task with checklist items and toggle completion', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('manage')
        ->set('taskTitle', 'Task with Checklist')
        ->set('newTaskChecklistInput', 'update db')
        ->call('addChecklistItemToNewTask')
        ->set('newTaskChecklistInput', 'update processor core')
        ->call('addChecklistItemToNewTask')
        ->call('addTask')
        ->assertHasNoErrors();

    $task = $user->tasks()->first();
    expect($task->checklists)->toHaveCount(2);
    expect($task->checklist_stats['total'])->toBe(2);
    expect($task->checklist_stats['completed'])->toBe(0);
    expect($task->checklist_stats['percent'])->toBe(0);

    $checklist1 = $task->checklists->first();

    Livewire::actingAs($user)
        ->test('manage')
        ->call('toggleChecklistItem', $checklist1->id);

    $task->refresh();
    expect($task->checklist_stats['completed'])->toBe(1);
    expect($task->checklist_stats['percent'])->toBe(50);
});

test('user can add and delete checklist items directly in detail modal', function () {
    $user = User::factory()->create();
    $task = Task::create([
        'user_id' => $user->id,
        'title' => 'Task for Detail Checklist',
        'status' => 'new',
    ]);

    Livewire::actingAs($user)
        ->test('manage')
        ->call('showTaskDetail', $task->id)
        ->set('newDetailChecklistInput', 'sub-item 1')
        ->call('addChecklistItemToDetail')
        ->assertSee('sub-item 1');

    $item = $task->checklists()->first();
    expect($item->title)->toBe('sub-item 1');

    Livewire::actingAs($user)
        ->test('manage')
        ->call('showTaskDetail', $task->id)
        ->call('deleteChecklistItem', $item->id);

    expect($task->checklists()->count())->toBe(0);
});

test('user can reorder checklist items in create task form and detail modal', function () {
    $user = User::factory()->create();

    // 1. Reorder in create task array
    Livewire::actingAs($user)
        ->test('manage')
        ->set('newTaskChecklistInput', 'point A')
        ->call('addChecklistItemToNewTask')
        ->set('newTaskChecklistInput', 'point B')
        ->call('addChecklistItemToNewTask')
        ->call('reorderNewTaskChecklists', 0, 1)
        ->assertSet('newTaskChecklists', ['point B', 'point A']);

    // 2. Reorder in detail modal database positions
    $task = Task::create([
        'user_id' => $user->id,
        'title' => 'Task for Reordering',
        'status' => 'new',
    ]);

    $item1 = $task->checklists()->create(['title' => 'Item 1', 'position' => 0]);
    $item2 = $task->checklists()->create(['title' => 'Item 2', 'position' => 1]);

    Livewire::actingAs($user)
        ->test('manage')
        ->call('showTaskDetail', $task->id)
        ->call('reorderDetailChecklistItems', $item1->id, $item2->id);

    expect($item1->fresh()->position)->toBe(1);
    expect($item2->fresh()->position)->toBe(0);
});


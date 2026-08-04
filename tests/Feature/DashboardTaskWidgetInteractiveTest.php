<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('user can create quick task directly from dashboard widget', function () {
    $user = User::factory()->create();
    $project = Project::create(['user_id' => $user->id, 'name' => 'Project Dashboard Beta']);

    Livewire::actingAs($user)
        ->test('dashboard')
        ->set('newTaskTitle', 'Task Baru Dari Dashboard Input')
        ->set('newTaskProjectId', $project->id)
        ->call('createQuickTask')
        ->assertDispatched('task-created', title: 'Task Baru Dari Dashboard Input');

    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'title' => 'Task Baru Dari Dashboard Input',
        'project_id' => $project->id,
        'status' => Task::STATUS_ON_PROGRESS,
    ]);
});

test('user can filter task stream by status and search query', function () {
    $user = User::factory()->create();

    $taskOnProgress = Task::create([
        'user_id' => $user->id,
        'title' => 'Fixing Auth Bug',
        'status' => Task::STATUS_ON_PROGRESS,
    ]);

    $taskNew = Task::create([
        'user_id' => $user->id,
        'title' => 'Design New Header',
        'status' => Task::STATUS_NEW,
    ]);

    Livewire::actingAs($user)
        ->test('dashboard')
        ->set('taskFilter', 'new')
        ->assertSee('Design New Header')
        ->assertDontSee('Fixing Auth Bug')
        ->set('taskSearch', 'Header')
        ->assertSee('Design New Header')
        ->set('taskSearch', 'NonExistentTitle')
        ->assertSee('No tasks match');
});

test('user can update task status directly from widget status selector', function () {
    $user = User::factory()->create();
    $task = Task::create([
        'user_id' => $user->id,
        'title' => 'Refactoring API Route',
        'status' => Task::STATUS_NEW,
    ]);

    Livewire::actingAs($user)
        ->test('dashboard')
        ->call('updateTaskStatus', $task->id, Task::STATUS_ON_PROGRESS);

    expect($task->fresh()->status)->toBe(Task::STATUS_ON_PROGRESS);
});

test('user can revert done task back to on progress using toggle', function () {
    $user = User::factory()->create();
    $task = Task::create([
        'user_id' => $user->id,
        'title' => 'Done Task Revert Test',
        'status' => Task::STATUS_DONE,
    ]);

    Livewire::actingAs($user)
        ->test('dashboard')
        ->call('toggleTaskStatus', $task->id);

    expect($task->fresh()->status)->toBe(Task::STATUS_ON_PROGRESS);
});

test('task search is case-insensitive', function () {
    $user = User::factory()->create();
    Task::create([
        'user_id' => $user->id,
        'title' => 'Develop QRIS Tuntas Transfer',
        'status' => Task::STATUS_ON_PROGRESS,
    ]);

    Livewire::actingAs($user)
        ->test('dashboard')
        ->set('taskSearch', 'qris')
        ->assertSee('Develop QRIS Tuntas Transfer')
        ->set('taskSearch', 'QRIS')
        ->assertSee('Develop QRIS Tuntas Transfer')
        ->set('taskSearch', 'Qris')
        ->assertSee('Develop QRIS Tuntas Transfer');
});

test('progress stats calculation strictly uses on_progress stream tasks', function () {
    $user = User::factory()->create();

    // 1 on_progress task
    Task::create([
        'user_id' => $user->id,
        'title' => 'Task On Progress',
        'status' => Task::STATUS_ON_PROGRESS,
    ]);

    // 1 done task updated today
    Task::create([
        'user_id' => $user->id,
        'title' => 'Task Done Today',
        'status' => Task::STATUS_DONE,
        'updated_at' => now(),
    ]);

    // 5 new tasks (should be ignored in progress percentage denominator)
    for ($i = 0; $i < 5; $i++) {
        Task::create([
            'user_id' => $user->id,
            'title' => "New Task {$i}",
            'status' => Task::STATUS_NEW,
        ]);
    }

    Livewire::actingAs($user)
        ->test('dashboard')
        ->assertSee('1/2 done (50%)');
});

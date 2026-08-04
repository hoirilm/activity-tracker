<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('dashboard displays today tasks preview for active user', function () {
    $user = User::factory()->create();
    $project = Project::create(['user_id' => $user->id, 'name' => 'Project Dashboard']);

    Task::create([
        'user_id' => $user->id,
        'title' => 'Task Progress Hari Ini',
        'status' => Task::STATUS_ON_PROGRESS,
        'project_id' => $project->id,
    ]);

    Task::create([
        'user_id' => $user->id,
        'title' => 'Task Baru Hari Ini',
        'status' => Task::STATUS_ON_PROGRESS,
    ]);

    Livewire::actingAs($user)
        ->test('dashboard')
        ->assertSee('ON PROGRESS STREAM')
        ->assertSee('Task Progress Hari Ini')
        ->assertSee('Task Baru Hari Ini')
        ->assertSee('Project Dashboard');
});

test('user can mark task as done directly from dashboard and trigger celebration event', function () {
    $user = User::factory()->create();
    $task = Task::create([
        'user_id' => $user->id,
        'title' => 'Task Dari Dashboard',
        'status' => Task::STATUS_ON_PROGRESS,
    ]);

    Livewire::actingAs($user)
        ->test('dashboard')
        ->call('markTaskDone', $task->id)
        ->assertDispatched('task-completed', title: 'Task Dari Dashboard');

    expect($task->fresh()->status)->toBe(Task::STATUS_DONE);
});

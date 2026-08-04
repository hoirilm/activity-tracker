<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('updating task status to done dispatches task-completed browser event', function () {
    $user = User::factory()->create();
    $task = Task::create([
        'user_id' => $user->id,
        'title' => 'Selesaikan Fitur Selebrasi',
        'status' => Task::STATUS_ON_PROGRESS,
    ]);

    Livewire::actingAs($user)
        ->test('manage')
        ->call('updateTaskStatus', $task->id, Task::STATUS_DONE)
        ->assertDispatched('task-completed', title: 'Selesaikan Fitur Selebrasi');

    expect($task->fresh()->status)->toBe(Task::STATUS_DONE);
});

test('adding task with done status dispatches task-completed browser event', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('manage')
        ->set('taskTitle', 'Task Langsung Selesai')
        ->set('taskStatus', Task::STATUS_DONE)
        ->call('addTask')
        ->assertDispatched('task-completed', title: 'Task Langsung Selesai');

    $task = Task::where('title', 'Task Langsung Selesai')->first();
    expect($task->status)->toBe(Task::STATUS_DONE);
});

test('updating existing task to done dispatches task-completed browser event', function () {
    $user = User::factory()->create();
    $task = Task::create([
        'user_id' => $user->id,
        'title' => 'Task Edit Ke Done',
        'status' => Task::STATUS_NEW,
    ]);

    Livewire::actingAs($user)
        ->test('manage')
        ->set('editingTaskId', $task->id)
        ->set('editingTaskTitle', 'Task Edit Ke Done')
        ->set('editingTaskStatus', Task::STATUS_DONE)
        ->call('updateTask')
        ->assertDispatched('task-completed', title: 'Task Edit Ke Done');

    expect($task->fresh()->status)->toBe(Task::STATUS_DONE);
});

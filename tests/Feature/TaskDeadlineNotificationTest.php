<?php

use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

test('artisan command check task deadlines creates notifications for due today and overdue tasks', function () {
    $user = User::factory()->create();

    // Create Due Today task
    $dueTodayTask = Task::create([
        'user_id' => $user->id,
        'title' => 'Important Meeting Prep',
        'status' => Task::STATUS_NEW,
        'due_at' => now()->endOfDay(),
    ]);

    // Create Overdue task
    $overdueTask = Task::create([
        'user_id' => $user->id,
        'title' => 'Submit Weekly Report',
        'status' => Task::STATUS_ON_PROGRESS,
        'due_at' => now()->subDay(),
    ]);

    // Create Future task (should not trigger notification)
    $futureTask = Task::create([
        'user_id' => $user->id,
        'title' => 'Future Sprint Planning',
        'status' => Task::STATUS_NEW,
        'due_at' => now()->addDays(5),
    ]);

    // Clear any notifications created during task creation
    Notification::where('user_id', $user->id)->delete();

    // Run Artisan Command
    Artisan::call('app:check-task-deadlines');

    // Check Due Today Notification
    $dueTodayNotif = Notification::where('user_id', $user->id)
        ->where('title', 'like', '%Important Meeting Prep%')
        ->first();
    expect($dueTodayNotif)->not()->toBeNull();
    expect($dueTodayNotif->type)->toBe('warning');

    // Check Overdue Notification
    $overdueNotif = Notification::where('user_id', $user->id)
        ->where('title', 'like', '%Submit Weekly Report%')
        ->first();
    expect($overdueNotif)->not()->toBeNull();
    expect($overdueNotif->type)->toBe('danger');

    // Ensure future task has no notification
    $futureNotif = Notification::where('user_id', $user->id)
        ->where('title', 'like', '%Future Sprint Planning%')
        ->first();
    expect($futureNotif)->toBeNull();
});

test('command does not duplicate notifications on the same day', function () {
    $user = User::factory()->create();

    $task = Task::create([
        'user_id' => $user->id,
        'title' => 'Daily Task Notification',
        'status' => Task::STATUS_NEW,
        'due_at' => now()->endOfDay(),
    ]);

    Notification::where('user_id', $user->id)->delete();

    // Run command twice
    Artisan::call('app:check-task-deadlines');
    Artisan::call('app:check-task-deadlines');

    $notifCount = Notification::where('user_id', $user->id)
        ->where('title', 'like', '%Daily Task Notification%')
        ->count();

    expect($notifCount)->toBe(1);
});

test('creating task via Livewire triggers instant in-app notification when due today or overdue', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('manage')
        ->set('taskTitle', 'Instant Due Today Task')
        ->set('taskDueAt', now()->endOfDay()->format('Y-m-d\TH:i'))
        ->call('addTask')
        ->assertHasNoErrors();

    $notif = Notification::where('user_id', $user->id)
        ->where('title', 'like', '%Instant Due Today Task%')
        ->first();

    expect($notif)->not()->toBeNull();
    expect($notif->type)->toBe('warning');
});

test('editing task deadline via Livewire triggers instant in-app notification when updated to overdue', function () {
    $user = User::factory()->create();
    $task = Task::create([
        'user_id' => $user->id,
        'title' => 'Task To Edit Deadline',
        'status' => Task::STATUS_NEW,
    ]);

    Notification::where('user_id', $user->id)->delete();

    Livewire::actingAs($user)
        ->test('manage')
        ->set('editingTaskId', $task->id)
        ->set('editingTaskTitle', 'Task To Edit Deadline')
        ->set('editingTaskStatus', Task::STATUS_NEW)
        ->set('editingTaskDueAt', now()->subDay()->format('Y-m-d\TH:i'))
        ->call('updateTask')
        ->assertHasNoErrors();

    $notif = Notification::where('user_id', $user->id)
        ->where('title', 'like', '%Task To Edit Deadline%')
        ->first();

    expect($notif)->not()->toBeNull();
    expect($notif->type)->toBe('danger');
});

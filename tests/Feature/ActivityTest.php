<?php

use App\Models\User;
use App\Models\Project;
use App\Models\Category;
use App\Models\Activity;
use App\Models\Task;
use Livewire\Livewire;

test('user can pause and resume active activity tracking session', function () {
    $user = User::factory()->create();
    $project = Project::create(['user_id' => $user->id, 'name' => 'Project Alpha']);
    $category = Category::create(['user_id' => $user->id, 'name' => 'Coding']);

    $activity = Activity::create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'category_id' => $category->id,
        'detail' => 'Developing Pause Feature',
        'start_time' => now()->subMinutes(10),
    ]);

    expect($activity->isPaused())->toBeFalse();

    // Pause activity
    Livewire::actingAs($user)
        ->test('tracker')
        ->call('pauseActivity', $activity->id);

    $activity->refresh();
    expect($activity->isPaused())->toBeTrue();
    expect($activity->paused_at)->not->toBeNull();

    // Travel time forward
    $this->travel(5)->minutes();

    // Resume activity
    Livewire::actingAs($user)
        ->test('tracker')
        ->call('resumeActivity', $activity->id);

    $activity->refresh();
    expect($activity->isPaused())->toBeFalse();
    expect($activity->paused_at)->toBeNull();
    expect($activity->paused_seconds)->toBeGreaterThanOrEqual(299);

    // Stop activity
    Livewire::actingAs($user)
        ->test('tracker')
        ->call('stopActivity', $activity->id);

    $activity->refresh();
    expect($activity->end_time)->not->toBeNull();
});

test('user can edit activity with separate project and task dropdowns and update all fields', function () {
    $user = User::factory()->create();
    $projectA = Project::create(['user_id' => $user->id, 'name' => 'Project Alpha']);
    $projectB = Project::create(['user_id' => $user->id, 'name' => 'Project Beta']);
    $category = Category::create(['user_id' => $user->id, 'name' => 'Development']);

    $taskA = Task::create([
        'user_id' => $user->id,
        'project_id' => $projectA->id,
        'title' => 'Task for Project Alpha',
        'status' => Task::STATUS_ON_PROGRESS,
    ]);

    $taskB = Task::create([
        'user_id' => $user->id,
        'project_id' => $projectB->id,
        'title' => 'Task for Project Beta',
        'status' => Task::STATUS_NEW,
    ]);

    $activity = Activity::create([
        'user_id' => $user->id,
        'project_id' => $projectA->id,
        'category_id' => $category->id,
        'task_id' => $taskA->id,
        'detail' => 'Initial Activity Name',
        'start_time' => now()->subHours(3),
        'end_time' => now()->subHours(1),
    ]);

    // 1. Open edit modal and verify initial population
    $component = Livewire::actingAs($user)
        ->test('tracker')
        ->call('editActivity', $activity->id)
        ->assertSet('showEditModal', true)
        ->assertSet('editingActivityId', $activity->id)
        ->assertSet('editDetail', 'Initial Activity Name')
        ->assertSet('editProjectId', $projectA->id)
        ->assertSet('editTaskId', $taskA->id);

    // Verify task options filtered for Project A
    $modalTasks = $component->get('editModalTasks');
    expect($modalTasks->pluck('id')->all())->toContain($taskA->id)
        ->and($modalTasks->pluck('id')->all())->not->toContain($taskB->id);

    // 2. Change project to Project B -> previous task should reset if belongs to old project
    $component->set('editProjectId', $projectB->id)
        ->assertSet('editTaskId', null);

    // Verify task options updated to Project B
    $modalTasksAfterChange = $component->get('editModalTasks');
    expect($modalTasksAfterChange->pluck('id')->all())->toContain($taskB->id)
        ->and($modalTasksAfterChange->pluck('id')->all())->not->toContain($taskA->id);

    // 3. Select Task B, change detail, change start time and end time
    $newStartTime = '2026-10-08T09:00';
    $newEndTime = '2026-10-08T11:45';

    $component->set('editTaskId', $taskB->id)
        ->set('editDetail', 'Updated Activity Name')
        ->set('editStartTime', $newStartTime)
        ->set('editEndTime', $newEndTime)
        ->call('updateActivity')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false);

    // 4. Verify activity database record has been updated with all changes
    $activity->refresh();
    expect($activity->detail)->toBe('Updated Activity Name')
        ->and($activity->project_id)->toBe($projectB->id)
        ->and($activity->task_id)->toBe($taskB->id)
        ->and($activity->start_time->format('Y-m-d H:i'))->toBe('2026-10-08 09:00')
        ->and($activity->end_time->format('Y-m-d H:i'))->toBe('2026-10-08 11:45');

    // Also verify task status updated to on_progress
    $taskB->refresh();
    expect($taskB->status)->toBe(Task::STATUS_ON_PROGRESS);
});

test('user can clear associated task when editing activity', function () {
    $user = User::factory()->create();
    $project = Project::create(['user_id' => $user->id, 'name' => 'Project Alpha']);
    $category = Category::create(['user_id' => $user->id, 'name' => 'Development']);

    $task = Task::create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'title' => 'Sample Task',
        'status' => Task::STATUS_ON_PROGRESS,
    ]);

    $activity = Activity::create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'category_id' => $category->id,
        'task_id' => $task->id,
        'detail' => 'Task Linked Activity',
        'start_time' => now()->subHours(2),
        'end_time' => now()->subHour(),
    ]);

    Livewire::actingAs($user)
        ->test('tracker')
        ->call('editActivity', $activity->id)
        ->set('editTaskId', '')
        ->call('updateActivity')
        ->assertHasNoErrors();

    $activity->refresh();
    expect($activity->task_id)->toBeNull();
});

test('validation fails when editing activity with invalid times or missing required fields', function () {
    $user = User::factory()->create();
    $project = Project::create(['user_id' => $user->id, 'name' => 'Project Alpha']);
    $category = Category::create(['user_id' => $user->id, 'name' => 'Development']);

    $activity = Activity::create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'category_id' => $category->id,
        'detail' => 'Valid Activity',
        'start_time' => now()->subHours(2),
        'end_time' => now()->subHour(),
    ]);

    // Empty detail
    Livewire::actingAs($user)
        ->test('tracker')
        ->call('editActivity', $activity->id)
        ->set('editDetail', '')
        ->call('updateActivity')
        ->assertHasErrors(['editDetail' => 'required']);

    // End time before start time
    Livewire::actingAs($user)
        ->test('tracker')
        ->call('editActivity', $activity->id)
        ->set('editStartTime', '2026-10-08T12:00')
        ->set('editEndTime', '2026-10-08T10:00')
        ->call('updateActivity')
        ->assertHasErrors(['editEndTime' => 'after_or_equal']);
});


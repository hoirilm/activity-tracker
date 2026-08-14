<?php

use App\Models\User;
use App\Models\Project;
use App\Models\Category;
use App\Models\Activity;
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

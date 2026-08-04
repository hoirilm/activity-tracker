<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

test('backup settings page can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('backup.edit'))
        ->assertOk();
});

test('user can download backup json file', function () {
    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Test Project']);
    $category = $user->categories()->create(['name' => 'Test Category']);
    $user->activities()->create([
        'project_id' => $project->id,
        'category_id' => $category->id,
        'detail' => 'Test Activity Task',
        'start_time' => now()->subHour(),
        'end_time' => now(),
    ]);

    $this->actingAs($user);

    $component = Livewire::test('pages::settings.backup')
        ->call('downloadBackup');

    $component->assertStatus(200);
});

test('restore validates user email match and requires confirmation if emails mismatch', function () {
    $user = User::factory()->create(['email' => 'myaccount@example.com']);
    $this->actingAs($user);

    $backupPayload = [
        'version' => '1.0',
        'user' => ['name' => 'Other Account', 'email' => 'other@example.com'],
        'projects' => [['name' => 'Demo Project']],
        'categories' => [['name' => 'Demo Category']],
        'activities' => [
            [
                'project' => 'Demo Project',
                'category' => 'Demo Category',
                'detail' => 'Sample imported activity',
                'start_time' => now()->subHours(2)->toDateTimeString(),
                'end_time' => now()->subHour()->toDateTimeString(),
            ],
        ],
        'tasks' => [
            [
                'title' => 'Sample Task With Deadline',
                'description' => 'Test task description',
                'project' => 'Demo Project',
                'status' => 'new',
                'due_at' => now()->addDays(3)->toIso8601String(),
            ],
        ],
    ];

    $file = UploadedFile::fake()->createWithContent('backup.json', json_encode($backupPayload));

    // Test preview detects mismatch
    $test = Livewire::test('pages::settings.backup')
        ->set('backupFile', $file);

    expect($test->get('previewData')['email_matched'])->toBeFalse();

    // Process restore without confirmation should fail
    $test->call('processRestore');
    expect($user->activities()->count())->toBe(0);

    // With confirmation checkbox checked, restore succeeds
    $test->set('confirmDifferentAccount', true)
        ->call('processRestore');

    expect($user->activities()->count())->toBe(1);
    expect($user->activities()->first()->detail)->toBe('Sample imported activity');
    expect($user->tasks()->count())->toBe(1);
    expect($user->tasks()->first()->due_at)->not()->toBeNull();
});

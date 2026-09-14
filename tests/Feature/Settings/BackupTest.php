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
                'checklists' => [
                    ['title' => 'Subtask Step 1', 'is_completed' => true, 'position' => 1],
                    ['title' => 'Subtask Step 2', 'is_completed' => false, 'position' => 2],
                ],
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
    $restoredTask = $user->tasks()->first();
    expect($restoredTask->due_at)->not()->toBeNull();
    expect($restoredTask->checklists()->count())->toBe(2);
    expect($restoredTask->checklists()->first()->title)->toBe('Subtask Step 1');
});

test('user can download backup json file with notes included', function () {
    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Secret Project']);
    $task = $user->tasks()->create([
        'title' => 'Important Task',
        'project_id' => $project->id,
    ]);
    $label = $user->labels()->create(['name' => 'Research', 'color' => 'blue']);

    $note = $user->notes()->create([
        'title' => 'Meeting Minutes',
        'content' => 'Discussed project scope and deadlines.',
        'project_id' => $project->id,
        'task_id' => $task->id,
        'is_pinned' => true,
        'is_archived' => false,
    ]);
    $note->labels()->attach($label);

    $this->actingAs($user);

    $component = Livewire::test('pages::settings.backup')
        ->call('downloadBackup');

    $component->assertStatus(200);

    $response = $component->instance()->downloadBackup();
    ob_start();
    $response->sendContent();
    $json = ob_get_clean();

    $data = json_decode($json, true);
    expect($data['version'])->toBe('1.2')
        ->and($data['stats']['total_notes'])->toBe(1)
        ->and($data['notes'])->toHaveCount(1)
        ->and($data['notes'][0]['title'])->toBe('Meeting Minutes')
        ->and($data['notes'][0]['project'])->toBe('Secret Project')
        ->and($data['notes'][0]['task'])->toBe('Important Task')
        ->and($data['notes'][0]['is_pinned'])->toBeTrue()
        ->and($data['notes'][0]['labels'])->toContain('Research');
});

test('restore in merge mode restores notes and links to project and task without duplicating', function () {
    $user = User::factory()->create(['email' => 'author@example.com']);
    $this->actingAs($user);

    $backupPayload = [
        'version' => '1.2',
        'user' => ['name' => 'Author', 'email' => 'author@example.com'],
        'projects' => [['name' => 'App Launch']],
        'tasks' => [
            ['title' => 'Prepare Press Release', 'project' => 'App Launch', 'status' => 'new'],
        ],
        'labels' => [
            ['name' => 'Confidential', 'color' => 'rose'],
        ],
        'activities' => [],
        'notes' => [
            [
                'title' => 'Launch Strategy Note',
                'content' => "# Plan\nRelease on Tuesday at 9 AM.",
                'project' => 'App Launch',
                'task' => 'Prepare Press Release',
                'is_pinned' => true,
                'is_archived' => false,
                'labels' => ['Confidential'],
            ],
        ],
    ];

    $file = UploadedFile::fake()->createWithContent('backup.json', json_encode($backupPayload));

    $test = Livewire::test('pages::settings.backup')
        ->set('backupFile', $file);

    expect($test->get('previewData')['total_notes'])->toBe(1);

    $test->call('processRestore');

    expect($user->notes()->count())->toBe(1);
    $note = $user->notes()->first();
    expect($note->title)->toBe('Launch Strategy Note')
        ->and($note->project->name)->toBe('App Launch')
        ->and($note->task->title)->toBe('Prepare Press Release')
        ->and($note->is_pinned)->toBeTrue()
        ->and($note->labels->pluck('name')->all())->toContain('Confidential');

    // Restore again in merge mode: duplicate note should not be created
    $file2 = UploadedFile::fake()->createWithContent('backup2.json', json_encode($backupPayload));
    Livewire::test('pages::settings.backup')
        ->set('backupFile', $file2)
        ->call('processRestore');

    expect($user->notes()->count())->toBe(1);
});

test('restore in replace mode wipes existing notes and replaces them', function () {
    $user = User::factory()->create(['email' => 'wipe@example.com']);
    $user->notes()->create(['title' => 'Old Note to be replaced', 'content' => 'Old content']);

    $this->actingAs($user);

    $backupPayload = [
        'version' => '1.2',
        'user' => ['name' => 'Wipe User', 'email' => 'wipe@example.com'],
        'projects' => [],
        'activities' => [],
        'tasks' => [],
        'notes' => [
            [
                'title' => 'Fresh Replaced Note',
                'content' => 'Fresh new content',
                'is_pinned' => false,
                'is_archived' => false,
            ],
        ],
    ];

    $file = UploadedFile::fake()->createWithContent('backup.json', json_encode($backupPayload));

    Livewire::test('pages::settings.backup')
        ->set('backupFile', $file)
        ->set('restoreMode', 'replace')
        ->call('processRestore');

    expect($user->notes()->count())->toBe(1)
        ->and($user->notes()->first()->title)->toBe('Fresh Replaced Note');
});

test('restore remains backward compatible with older backups without notes key', function () {
    $user = User::factory()->create(['email' => 'legacy@example.com']);
    $this->actingAs($user);

    $legacyPayload = [
        'version' => '1.0',
        'user' => ['name' => 'Legacy', 'email' => 'legacy@example.com'],
        'projects' => [['name' => 'Legacy Project']],
        'categories' => [['name' => 'Legacy Category']],
        'activities' => [
            [
                'project' => 'Legacy Project',
                'category' => 'Legacy Category',
                'detail' => 'Legacy Activity',
                'start_time' => now()->subHour()->toDateTimeString(),
            ],
        ],
    ];

    $file = UploadedFile::fake()->createWithContent('legacy_backup.json', json_encode($legacyPayload));

    $test = Livewire::test('pages::settings.backup')
        ->set('backupFile', $file);

    expect($test->get('previewData')['total_notes'])->toBe(0);

    $test->call('processRestore');

    expect($user->activities()->count())->toBe(1)
        ->and($user->notes()->count())->toBe(0);
});


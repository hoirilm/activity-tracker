<?php

use App\Models\User;
use Livewire\Livewire;

test('admin can compose and broadcast markdown announcement', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false]);

    $markdownBody = "# Release v5.5.0\n- **Pause & Resume**: Added live timer pause feature.\n- **Second Precision**: Timestamps formatted with H:i:s.";

    Livewire::actingAs($admin)
        ->test('broadcast-manager')
        ->set('title', 'Release Notes v5.5.0')
        ->set('body', $markdownBody)
        ->set('type', 'info')
        ->call('broadcast')
        ->assertSet('successMessage', 'Broadcast successfully sent to 2 users!');

    expect($user->notifications()->count())->toBe(1);
    $notification = $user->notifications()->first();
    expect($notification->title)->toBe('Release Notes v5.5.0');
    expect($notification->body)->toContain('Pause & Resume');
});

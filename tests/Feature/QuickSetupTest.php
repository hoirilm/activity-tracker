<?php

use App\Models\User;
use Livewire\Livewire;

test('quick setup modal auto opens for new user without projects or categories', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('quick-setup')
        ->assertSet('showModal', true);
});

test('quick setup modal does not auto open for user with projects and categories', function () {
    $user = User::factory()->create();
    $user->projects()->create(['name' => 'Existing Project']);
    $user->categories()->create(['name' => 'Existing Category']);

    Livewire::actingAs($user)
        ->test('quick-setup')
        ->assertSet('showModal', false);
});

test('applying starter pack creates default project category task and label', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('quick-setup')
        ->call('applyStarterPack')
        ->assertDispatched('quick-setup-updated');

    expect($user->projects()->count())->toBeGreaterThan(0)
        ->and($user->categories()->count())->toBeGreaterThan(0)
        ->and($user->tasks()->count())->toBeGreaterThan(0)
        ->and($user->labels()->count())->toBeGreaterThan(0);
});

test('user can manually create project and category in quick setup', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('quick-setup')
        ->set('projectName', 'Client Alpha Project')
        ->set('projectClient', 'Alpha Corp')
        ->call('createProject')
        ->set('categoryName', 'Backend API')
        ->call('createCategory');

    expect($user->projects()->where('name', 'Client Alpha Project')->exists())->toBeTrue()
        ->and($user->categories()->where('name', 'Backend API')->exists())->toBeTrue();
});

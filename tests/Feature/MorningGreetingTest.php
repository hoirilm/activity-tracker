<?php

use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use App\Services\MorningGreetingGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('morning greeting generator creates dynamic greeting with pending task summary', function () {
    $user = User::factory()->create(['name' => 'Budi Santoso']);
    Task::create([
        'user_id' => $user->id,
        'title' => 'Kerjakan Laporan',
        'status' => Task::STATUS_ON_PROGRESS,
    ]);

    $generator = new MorningGreetingGenerator;
    $greeting = $generator->generateForUser($user);

    expect($greeting['title'])->toContain('Budi');
    expect($greeting['body'])->toContain('Info Hari Ini');
    expect($greeting['body'])->toContain('Motivasi');
    expect($greeting['body'])->toContain('1 task aktif');
});

test('send morning greetings command creates notifications for all users', function () {
    $user1 = User::factory()->create(['name' => 'User One']);
    $user2 = User::factory()->create(['name' => 'User Two']);

    $this->artisan('app:send-morning-greetings')
        ->assertSuccessful();

    expect(Notification::where('user_id', $user1->id)->count())->toBe(1);
    expect(Notification::where('user_id', $user2->id)->count())->toBe(1);

    $notif = Notification::where('user_id', $user1->id)->first();
    expect($notif->title)->toContain('User');
    expect($notif->body)->toContain('Motivasi');
});

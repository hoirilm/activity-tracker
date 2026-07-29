<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    $users = User::all();
    foreach ($users as $user) {
        $user->notifications()->create([
            'title' => '🌞 Semangat Pagi!',
            'body' => 'Selamat bekerja dan jangan lupa berdoa sebelum memulai aktivitas hari ini.',
            'type' => 'info',
        ]);
    }
})->dailyAt('07:00');

Schedule::call(function () {
    Notification::where('created_at', '<', now()->subDays(3))->delete();
})->daily();

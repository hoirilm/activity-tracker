<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use App\Models\User;
use App\Models\Activity;
use App\Mail\DailyActivityReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ActivitiesExport;



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
    \App\Models\Notification::where('created_at', '<', now()->subDays(3))->delete();
})->daily();

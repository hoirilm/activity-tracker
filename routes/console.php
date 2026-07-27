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
    $today = Carbon::today();
    
    // Get all users who have activities today
    $users = User::whereHas('activities', function ($query) use ($today) {
        $query->whereDate('start_time', $today);
    })->get();

    foreach ($users as $user) {
        $fileName = 'backup_activity_' . \Illuminate\Support\Str::slug($user->name, '_') . '_' . $today->format('Ymd') . '.xlsx';
        $filePath = storage_path('app/temp/' . $fileName);
        
        // Ensure directory exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0777, true);
        }

        // Generate Excel and store locally
        Excel::store(new ActivitiesExport($today, $today, $user->id), 'temp/' . $fileName);

        // Send Email
        Mail::to($user->email)->send(new DailyActivityReport($user->name, $filePath));
        
        // Optional: Delete the file after sending if you don't want to keep it
        @unlink($filePath);
    }
})->dailyAt('23:59');

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

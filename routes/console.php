<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use App\Console\Commands\SendMorningGreetings;
use App\Models\Notification;
use Illuminate\Support\Facades\Schedule;

Schedule::command(SendMorningGreetings::class)->dailyAt('07:00');


Schedule::call(function () {
    Notification::where('created_at', '<', now()->subDays(3))->delete();
})->daily();

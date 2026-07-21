<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('tracker', 'tracker')->name('tracker');
    Route::view('manage', 'manage')->name('manage');
});

require __DIR__.'/settings.php';

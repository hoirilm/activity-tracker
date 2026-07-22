<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('tracker', 'tracker')->name('tracker');
    Route::view('manage', 'manage')->name('manage');

    Route::view('faq', 'faq')->name('faq');
    Route::view('help', 'help')->name('help');

    Route::get('issues', function () {
        if (!auth()->user()->is_admin) {
            abort(403);
        }
        return view('issues');
    })->name('issues');
});

require __DIR__.'/settings.php';

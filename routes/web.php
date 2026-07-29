<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;

Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('language.switch');

Route::view('/', 'welcome')->name('home');

Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'callback']);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('tracker', 'tracker')->name('tracker');
    Route::view('manage', 'manage')->name('manage');

    Route::get('issues', function () {
        if (! auth()->user()->is_admin) {
            abort(403);
        }

        return view('issues');
    })->name('issues');

    Route::get('members', function () {
        if (! auth()->user()->is_admin) {
            abort(403);
        }

        return view('members');
    })->name('members');

    Route::get('broadcast', function () {
        if (! auth()->user()->is_admin) {
            abort(403);
        }

        return view('broadcast');
    })->name('broadcast');
});

require __DIR__.'/settings.php';

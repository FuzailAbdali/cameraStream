<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\RecordingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
});

Route::middleware(['auth'])->group(function (): void {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::resource('cameras', CameraController::class);
    Route::get('cameras/{id}/stream', [CameraController::class, 'stream'])->name('cameras.stream');

    Route::resource('recordings', RecordingController::class)->only(['index', 'show', 'destroy']);
    Route::get('recordings/{recording}/file', [RecordingController::class, 'file'])->name('recordings.file');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

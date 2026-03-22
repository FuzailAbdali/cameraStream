<?php

use App\Http\Controllers\CameraPlayerController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/cameras');
Route::get('/cameras', [CameraPlayerController::class, 'index'])->name('cameras.index');
Route::get('/cameras/{camera}/stream', [CameraPlayerController::class, 'stream'])->name('cameras.stream');
Route::get('/streams/{camera}/index.m3u8', [CameraPlayerController::class, 'playlist'])->name('cameras.playlist');
Route::get('/streams/{camera}/{file}', [CameraPlayerController::class, 'segment'])->where('file', '.*')->name('cameras.segment');

<?php

use App\Http\Controllers\Api\CameraController;
use Illuminate\Support\Facades\Route;

Route::get('/cameras', [CameraController::class, 'index']);
Route::post('/cameras', [CameraController::class, 'store']);
Route::put('/cameras/{camera}', [CameraController::class, 'update']);
Route::delete('/cameras/{camera}', [CameraController::class, 'destroy']);

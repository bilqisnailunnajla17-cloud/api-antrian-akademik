<?php

use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\MahasiswaController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::apiResource('services', ServiceController::class);

    Route::get('/mahasiswa/{nim}', [MahasiswaController::class, 'show']);
});


<?php

main
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\MahasiswaController;
main
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\TicketController;

Route::prefix('v1')->group(function () {

    Route::apiResource('services', ServiceController::class);

main
    Route::get('/mahasiswa/{nim}', [MahasiswaController::class, 'show']);
    Route::get('tickets', [TicketController::class, 'index']);
    Route::post('tickets', [TicketController::class, 'store']);
    Route::post('tickets/call-next', [TicketController::class, 'callNext']);
main
});

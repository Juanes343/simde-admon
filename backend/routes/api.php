<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TerceroController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas
Route::middleware('custom.auth')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Terceros
    Route::get('/terceros', [TerceroController::class, 'index']);
    Route::post('/terceros', [TerceroController::class, 'store']);
    Route::get('/terceros/{tipo_id_tercero}/{tercero_id}', [TerceroController::class, 'show']);
    Route::put('/terceros/{tipo_id_tercero}/{tercero_id}', [TerceroController::class, 'update']);
    Route::delete('/terceros/{tipo_id_tercero}/{tercero_id}', [TerceroController::class, 'destroy']);
    
    // Crear tercero desde PDF
    Route::post('/terceros/upload-pdf', [TerceroController::class, 'createFromPdf']);
});

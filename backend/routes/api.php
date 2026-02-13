<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TerceroController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\TipoIdTerceroController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas con Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Tipos de Identificación (catálogo)
    Route::get('/tipos-id-tercero', [TipoIdTerceroController::class, 'index']);

    // Terceros
    Route::get('/terceros', [TerceroController::class, 'index']);
    Route::post('/terceros', [TerceroController::class, 'store']);
    Route::get('/terceros/{tipo_id_tercero}/{tercero_id}', [TerceroController::class, 'show']);
    Route::put('/terceros/{tipo_id_tercero}/{tercero_id}', [TerceroController::class, 'update']);
    Route::delete('/terceros/{tipo_id_tercero}/{tercero_id}', [TerceroController::class, 'destroy']);
    
    // Crear tercero desde PDF
    Route::post('/terceros/upload-pdf', [TerceroController::class, 'createFromPdf']);

    // Servicios
    Route::get('/servicios', [ServicioController::class, 'index']);
    Route::post('/servicios', [ServicioController::class, 'store']);
    Route::get('/servicios/activos', [ServicioController::class, 'activos']);
    Route::get('/servicios/{id}', [ServicioController::class, 'show']);
    Route::put('/servicios/{id}', [ServicioController::class, 'update']);
    Route::delete('/servicios/{id}', [ServicioController::class, 'destroy']);
});

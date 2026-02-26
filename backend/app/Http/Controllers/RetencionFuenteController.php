<?php

namespace App\Http\Controllers;

use App\Models\RetencionFuente;
use Illuminate\Http\Request;

class RetencionFuenteController extends Controller
{
    /**
     * Get all active retenciones
     */
    public function index()
    {
        try {
            // Verificar si la tabla existe
            if (!\Schema::hasTable('retencion_fuente')) {
                return response()->json([
                    'success' => false,
                    'message' => 'La tabla retencion_fuente no existe. Ejecuta: php artisan migrate',
                    'data' => []
                ], 200);
            }

            $retenciones = RetencionFuente::activas()->get();

            return response()->json([
                'success' => true,
                'data' => $retenciones
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar retenciones: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
}
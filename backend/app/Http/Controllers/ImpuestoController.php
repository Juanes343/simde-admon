<?php

namespace App\Http\Controllers;

use App\Models\Impuesto;
use Illuminate\Http\Request;

class ImpuestoController extends Controller
{
    /**
     * Get all active impuestos
     */
    public function index()
    {
        try {
            // Verificar si la tabla existe
            if (!\Schema::hasTable('impuestos')) {
                return response()->json([
                    'success' => false,
                    'message' => 'La tabla impuestos no existe. Ejecuta: php artisan migrate',
                    'data' => []
                ], 200);
            }

            $impuestos = Impuesto::activos()
                                ->orderBy('porcentaje')
                                ->get();

            return response()->json([
                'success' => true,
                'data' => $impuestos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar impuestos: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
}

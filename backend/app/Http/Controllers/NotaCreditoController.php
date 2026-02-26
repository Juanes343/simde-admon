<?php

namespace App\Http\Controllers;

use App\Models\NotaCredito;
use App\Models\NotaCreditoConcepto;
use App\Services\NotaCreditoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class NotaCreditoController extends Controller
{
    protected $notaCreditoService;

    public function __construct(NotaCreditoService $notaCreditoService)
    {
        $this->notaCreditoService = $notaCreditoService;
    }

    /**
     * GET /api/notas-credito
     * Listar notas crédito con filtros
     */
    public function index(Request $request)
    {
        try {
            $filtros = [
                'empresa_id' => $request->query('empresa_id'),
                'estado' => $request->query('estado'),
                'prefijo' => $request->query('prefijo'),
                'fecha_desde' => $request->query('fecha_desde'),
                'fecha_hasta' => $request->query('fecha_hasta'),
            ];

            $notas = $this->notaCreditoService->obtenerNotas($filtros);

            return response()->json([
                'success' => true,
                'data' => $notas->items(),
                'pagination' => [
                    'total' => $notas->total(),
                    'per_page' => $notas->perPage(),
                    'current_page' => $notas->currentPage(),
                    'last_page' => $notas->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo notas crédito: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener notas crédito',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/notas-credito
     * Crear nueva nota crédito
     */
    public function store(Request $request)
    {
        try {
            // Validar datos
            $validator = Validator::make($request->all(), [
                'empresa_id' => 'required|string|max:10',
                'prefijo' => 'required|string|max:10',
                'prefijo_factura' => 'required|string|max:10',
                'factura_fiscal' => 'required|integer',
                'concepto_id' => 'required|integer|exists:notas_credito_conceptos,id',
                'valor_nota' => 'required|numeric|min:0.01',
                'observacion' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validación fallida',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Crear nota crédito
            $result = $this->notaCreditoService->crearNotaCredito($validator->validated());

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json($result, 201);

        } catch (\Exception $e) {
            Log::error('Error creando nota crédito: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear nota crédito',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/notas-credito/{id}
     * Obtener detalle de una nota crédito
     */
    public function show(int $id)
    {
        try {
            $notaCredito = $this->notaCreditoService->obtenerDetalle($id);

            return response()->json([
                'success' => true,
                'data' => $notaCredito,
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo nota crédito: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Nota crédito no encontrada',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * POST /api/notas-credito/{id}/enviar
     * Enviar nota crédito a DATAICO
     */
    public function enviar(int $id)
    {
        try {
            $result = $this->notaCreditoService->enviarNotaCredito($id);

            $statusCode = $result['success'] ? 200 : 400;

            return response()->json($result, $statusCode);

        } catch (\Exception $e) {
            Log::error('Error enviando nota crédito: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar nota crédito',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/notas-credito/{id}
     * Eliminar nota crédito (solo si está PENDIENTE)
     */
    public function destroy(int $id)
    {
        try {
            $notaCredito = NotaCredito::findOrFail($id);

            if ($notaCredito->estado !== 'PENDIENTE') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden eliminar notas crédito PENDIENTES',
                ], 400);
            }

            $notaCredito->delete();

            return response()->json([
                'success' => true,
                'message' => 'Nota crédito eliminada exitosamente',
            ]);

        } catch (\Exception $e) {
            Log::error('Error eliminando nota crédito: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar nota crédito',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/notas-credito-conceptos
     * Obtener lista de conceptos de notas crédito
     */
    public function conceptos(Request $request)
    {
        try {
            $empresaId = $request->query('empresa_id');
            
            $query = NotaCreditoConcepto::query();

            if ($empresaId) {
                $query->where('empresa_id', $empresaId);
            }

            // Solo mostrar conceptos de crédito (sw_naturaleza = 'C')
            $conceptos = $query->creditos()
                               ->activos()
                               ->orderBy('descripcion')
                               ->get();

            return response()->json([
                'success' => true,
                'data' => $conceptos,
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo conceptos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener conceptos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/notas-credito/estadisticas
     * Obtener estadísticas de notas crédito
     */
    public function estadisticas(Request $request)
    {
        try {
            $empresaId = $request->query('empresa_id');

            $query = NotaCredito::query();
            if ($empresaId) {
                $query->where('empresa_id', $empresaId);
            }

            $stats = [
                'total' => (clone $query)->count(),
                'pendientes' => (clone $query)->where('estado', 'PENDIENTE')->count(),
                'enviadas' => (clone $query)->where('estado', 'ENVIADO')->count(),
                'aceptadas' => (clone $query)->where('estado', 'ACEPTADO')->count(),
                'rechazadas' => (clone $query)->where('estado', 'RECHAZADO')->count(),
                'valor_total' => (clone $query)->sum('valor_nota'),
                'valor_aceptado' => (clone $query)->where('estado', 'ACEPTADO')->sum('valor_nota'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdenServicio;
use App\Models\OrdenServicioItem;
use App\Models\Servicio;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrdenServicioController extends Controller
{
    /**
     * Display a listing of ordenes de servicio
     */
    public function index(Request $request)
    {
        try {
            $query = OrdenServicio::with(['items', 'usuario']);

            // Filtro por tercero
            if ($request->has('tipo_id_tercero') && $request->has('tercero_id')) {
                $query->porTercero($request->tipo_id_tercero, $request->tercero_id);
            }

            // Filtro por estado
            if ($request->has('sw_estado') && $request->sw_estado !== '') {
                $query->where('sw_estado', $request->sw_estado);
            }

            // Filtro por búsqueda (número de orden o tercero)
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('numero_orden', 'like', "%{$search}%");
                    // Búsqueda por tercero usando join
                    $q->orWhere(function($q2) use ($search) {
                        $q2->whereExists(function($q3) use ($search) {
                            $q3->select(DB::raw(1))
                               ->from('terceros')
                               ->whereColumn('terceros.tipo_id_tercero', 'ordenes_servicio.tipo_id_tercero')
                               ->whereColumn('terceros.tercero_id', 'ordenes_servicio.tercero_id')
                               ->where('terceros.nombre_tercero', 'like', "%{$search}%");
                        });
                    });
                });
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $ordenes = $query->paginate($perPage);

            // Agregar total calculado a cada orden
            $ordenes->getCollection()->transform(function ($orden) {
                $orden->total = $orden->calcularTotal();
                $orden->permite_facturar_hoy = $orden->permiteFacturar();
                return $orden;
            });

            return response()->json($ordenes);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener órdenes de servicio',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created orden de servicio
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo_id_tercero' => 'required|string|max:20',
            'tercero_id' => 'required|string|max:32',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'sw_prorroga_automatica' => 'required|in:0,1',
            'periodo_facturacion_dias' => 'required|integer|min:1',
            'porcentaje_soltec' => 'nullable|numeric|min:0|max:100',
            'porcentaje_ret_fuente' => 'nullable|numeric|in:0,0.1,0.5,1,1.5,2,2.5,3,3.5,4,6,7,10,11,20',
            'observaciones' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.servicio_id' => 'required|exists:servicios,servicio_id',
            'items.*.cantidad' => 'required|numeric|min:0.01',
            'items.*.observaciones' => 'nullable|string',
        ], [
            'tipo_id_tercero.required' => 'El tipo de identificación es obligatorio',
            'tercero_id.required' => 'El tercero es obligatorio',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria',
            'fecha_fin.required' => 'La fecha de fin es obligatoria',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio',
            'porcentaje_ret_fuente.in' => 'El porcentaje de retención en la fuente no es válido',
            'items.required' => 'Debe agregar al menos un servicio',
            'items.min' => 'Debe agregar al menos un servicio',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Generar número de orden
            $numeroOrden = OrdenServicio::generarNumeroOrden();

            // Crear orden de servicio
            $orden = OrdenServicio::create([
                'tipo_id_tercero' => $request->tipo_id_tercero,
                'tercero_id' => $request->tercero_id,
                'numero_orden' => $numeroOrden,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'sw_prorroga_automatica' => $request->sw_prorroga_automatica,
                'periodo_facturacion_dias' => $request->periodo_facturacion_dias,
                'porcentaje_soltec' => $request->porcentaje_soltec ?? 0,
                'porcentaje_ret_fuente' => $request->porcentaje_ret_fuente ?? 0,
                'sw_estado' => $request->sw_estado ?? '1',
                'observaciones' => $request->observaciones,
                'usuario_id' => $request->user()->usuario_id,
            ]);

            // Crear items de la orden
            $ordenItem = 1;
            foreach ($request->items as $itemData) {
                $servicio = Servicio::find($itemData['servicio_id']);
                
                OrdenServicioItem::create([
                    'orden_servicio_id' => $orden->orden_servicio_id,
                    'servicio_id' => $servicio->servicio_id,
                    'nombre_servicio' => $servicio->nombre_servicio,
                    'descripcion' => $servicio->descripcion,
                    'cantidad' => $itemData['cantidad'],
                    'tipo_unidad' => $servicio->tipo_unidad,
                    'precio_unitario' => $servicio->precio_unitario,
                    'orden' => $ordenItem++,
                    'observaciones' => $itemData['observaciones'] ?? null,
                ]);
            }

            DB::commit();

            $orden->load(['items', 'usuario']);
            $orden->total = $orden->calcularTotal();

            return response()->json([
                'message' => 'Orden de servicio creada exitosamente',
                'orden' => $orden
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear orden de servicio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified orden de servicio
     */
    public function show($id)
    {
        try {
            $orden = OrdenServicio::with(['items.servicio', 'usuario'])->find($id);

            if (!$orden) {
                return response()->json(['message' => 'Orden de servicio no encontrada'], 404);
            }

            $orden->total = $orden->calcularTotal();
            $orden->permite_facturar_hoy = $orden->permiteFacturar();

            return response()->json($orden);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener orden de servicio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified orden de servicio
     */
    public function update(Request $request, $id)
    {
        $orden = OrdenServicio::find($id);

        if (!$orden) {
            return response()->json(['message' => 'Orden de servicio no encontrada'], 404);
        }

        $validator = Validator::make($request->all(), [
            'fecha_inicio' => 'sometimes|required|date',
            'fecha_fin' => 'sometimes|required|date|after_or_equal:fecha_inicio',
            'sw_prorroga_automatica' => 'sometimes|required|in:0,1',
            'periodo_facturacion_dias' => 'sometimes|required|integer|min:1',
            'porcentaje_soltec' => 'nullable|numeric|min:0|max:100',
            'porcentaje_ret_fuente' => 'nullable|numeric|in:0,0.1,0.5,1,1.5,2,2.5,3,3.5,4,6,7,10,11,20',
            'observaciones' => 'nullable|string',
            'sw_estado' => 'sometimes|required|in:0,1',
            'items' => 'sometimes|array|min:1',
            'items.*.servicio_id' => 'required|exists:servicios,servicio_id',
            'items.*.cantidad' => 'required|numeric|min:0.01',
            'items.*.observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Actualizar datos de la orden
            $orden->update($request->only([
                'fecha_inicio',
                'fecha_fin',
                'sw_prorroga_automatica',
                'periodo_facturacion_dias',
                'porcentaje_soltec',
                'porcentaje_ret_fuente',
                'observaciones',
                'sw_estado'
            ]));

            // Si se enviaron items, actualizar
            if ($request->has('items')) {
                // Eliminar items antiguos
                $orden->items()->delete();

                // Crear nuevos items
                $ordenItem = 1;
                foreach ($request->items as $itemData) {
                    $servicio = Servicio::find($itemData['servicio_id']);
                    
                    OrdenServicioItem::create([
                        'orden_servicio_id' => $orden->orden_servicio_id,
                        'servicio_id' => $servicio->servicio_id,
                        'nombre_servicio' => $servicio->nombre_servicio,
                        'descripcion' => $servicio->descripcion,
                        'cantidad' => $itemData['cantidad'],
                        'tipo_unidad' => $servicio->tipo_unidad,
                        'precio_unitario' => $servicio->precio_unitario,
                        'orden' => $ordenItem++,
                        'observaciones' => $itemData['observaciones'] ?? null,
                    ]);
                }
            }

            DB::commit();

            $orden->load(['items', 'usuario']);
            $orden->total = $orden->calcularTotal();

            return response()->json([
                'message' => 'Orden de servicio actualizada exitosamente',
                'orden' => $orden
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar orden de servicio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle estado de la orden (activar/desactivar)
     */
    public function destroy($id)
    {
        $orden = OrdenServicio::find($id);

        if (!$orden) {
            return response()->json(['message' => 'Orden de servicio no encontrada'], 404);
        }

        // Toggle estado
        $nuevoEstado = $orden->sw_estado === '1' ? '0' : '1';
        $orden->update(['sw_estado' => $nuevoEstado]);

        $mensaje = $nuevoEstado === '1' ? 'Orden de servicio activada exitosamente' : 'Orden de servicio desactivada exitosamente';

        return response()->json([
            'message' => $mensaje,
            'estado' => $nuevoEstado
        ]);
    }
}

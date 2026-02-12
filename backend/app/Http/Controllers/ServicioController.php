<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServicioController extends Controller
{
    /**
     * Display a listing of servicios
     */
    public function index(Request $request)
    {
        try {
            $query = Servicio::query();

            // Filtro por búsqueda
            if ($request->has('buscar') && $request->buscar) {
                $query->where(function($q) use ($request) {
                    $q->where('nombre_servicio', 'like', "%{$request->buscar}%")
                      ->orWhere('descripcion', 'like', "%{$request->buscar}%");
                });
            }

            // Filtro por estado
            if ($request->has('estado') && $request->estado !== '') {
                $query->where('sw_estado', $request->estado);
            }

            // Filtro por tipo de unidad
            if ($request->has('tipo_unidad') && $request->tipo_unidad) {
                $query->where('tipo_unidad', $request->tipo_unidad);
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'fecha_registro');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $servicios = $query->paginate($perPage);

            return response()->json($servicios);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Store a newly created servicio
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre_servicio' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'cantidad' => 'nullable|numeric|min:0',
            'tipo_unidad' => 'required|in:UNIDAD,HORAS',
            'precio_unitario' => 'required|numeric|min:0',
        ], [
            'nombre_servicio.required' => 'El nombre del servicio es obligatorio',
            'tipo_unidad.required' => 'El tipo de unidad es obligatorio',
            'tipo_unidad.in' => 'El tipo de unidad debe ser UNIDAD o HORAS',
            'precio_unitario.required' => 'El precio unitario es obligatorio',
            'precio_unitario.numeric' => 'El precio debe ser un valor numérico',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtener el usuario autenticado
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        $servicio = Servicio::create(array_merge(
            $request->all(),
            ['usuario_id' => $user->usuario_id]
        ));

        return response()->json([
            'message' => 'Servicio creado exitosamente',
            'servicio' => $servicio->load('usuario')
        ], 201);
    }

    /**
     * Display the specified servicio
     */
    public function show($id)
    {
        $servicio = Servicio::with('usuario')->find($id);

        if (!$servicio) {
            return response()->json(['message' => 'Servicio no encontrado'], 404);
        }

        return response()->json($servicio);
    }

    /**
     * Update the specified servicio
     */
    public function update(Request $request, $id)
    {
        $servicio = Servicio::find($id);

        if (!$servicio) {
            return response()->json(['message' => 'Servicio no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre_servicio' => 'sometimes|required|string|max:100',
            'descripcion' => 'nullable|string',
            'cantidad' => 'nullable|numeric|min:0',
            'tipo_unidad' => 'sometimes|required|in:UNIDAD,HORAS',
            'precio_unitario' => 'sometimes|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $servicio->update($request->all());

        return response()->json([
            'message' => 'Servicio actualizado exitosamente',
            'servicio' => $servicio->load('usuario')
        ]);
    }

    /**
     * Remove the specified servicio (soft delete)
     */
    public function destroy($id)
    {
        $servicio = Servicio::find($id);

        if (!$servicio) {
            return response()->json(['message' => 'Servicio no encontrado'], 404);
        }

        $servicio->update(['sw_estado' => '0']);

        return response()->json([
            'message' => 'Servicio desactivado exitosamente'
        ]);
    }

    /**
     * Get only active servicios
     */
    public function activos()
    {
        $servicios = Servicio::activos()
                            ->with('usuario')
                            ->orderBy('nombre_servicio')
                            ->get();

        return response()->json($servicios);
    }
}

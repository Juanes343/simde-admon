<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Services\CotizacionOrdenService;
use App\Support\FirmaImagen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Endpoints PÚBLICOS (sin sesión) que atiende el enlace del correo de la cotización:
 * el cliente revisa la cotización, la aprueba con su firma o la rechaza.
 * La autorización es el token del enlace (se compara contra el hash guardado).
 */
class CotizacionAprobacionController extends Controller
{
    /**
     * Datos de la cotización para mostrar en la página de aprobación.
     */
    public function ver($id, $token)
    {
        $cotizacion = Cotizacion::with(['items.impuesto'])->find($id);

        if ($error = $this->validarEnlace($cotizacion, $token)) {
            return $error;
        }

        $cotizacion->append('tercero');

        $items = $cotizacion->items->map(function ($item) {
            $base   = (float) $item->subtotal * (1 - (float) ($item->porcentaje_descuento ?? 0) / 100);
            $impPct = (float) ($item->impuesto?->porcentaje ?? 0);
            $retPct = (float) ($item->porcentaje_ret_fuente ?? 0);

            return [
                'descripcion'          => $item->descripcion,
                'observaciones'        => $item->observaciones,
                'cantidad'             => (float) $item->cantidad,
                'tipo_unidad'          => $item->tipo_unidad,
                'precio_unitario'      => (float) $item->precio_unitario,
                'porcentaje_descuento' => (float) ($item->porcentaje_descuento ?? 0),
                'impuesto_porcentaje'  => $item->impuesto ? $impPct : null,
                'porcentaje_ret_fuente'=> $retPct,
                'subtotal'             => (float) $item->subtotal,
                'total'                => round($base * (1 + $impPct / 100) - $base * $retPct / 100, 2),
            ];
        })->values();

        $retencionTotal = $cotizacion->items->sum(function ($item) {
            $base = (float) $item->subtotal * (1 - (float) ($item->porcentaje_descuento ?? 0) / 100);
            return $base * (float) ($item->porcentaje_ret_fuente ?? 0) / 100;
        });

        return response()->json([
            'numero_cotizacion' => $cotizacion->numero_cotizacion,
            'cliente'           => $cotizacion->tercero?->nombre_tercero ?? $cotizacion->tercero_id,
            'fecha_emision'     => $cotizacion->fecha_emision?->format('Y-m-d'),
            'fecha_vencimiento' => $cotizacion->fecha_vencimiento?->format('Y-m-d'),
            'metodo_pago'       => $cotizacion->metodo_pago,
            'tipo_pago'         => $cotizacion->tipo_pago,
            'orden_compra'      => $cotizacion->orden_compra,
            'notas'             => $cotizacion->notas,
            'subtotal'          => (float) $cotizacion->subtotal,
            'descuento_total'   => (float) $cotizacion->descuento_total,
            'impuestos_total'   => (float) $cotizacion->impuestos_total,
            'retencion_total'   => round($retencionTotal, 2),
            'total'             => (float) $cotizacion->total,
            'items'             => $items,
            'enlace_expira_en'  => $cotizacion->token_aprobacion_expira_en?->toIso8601String(),
        ]);
    }

    /**
     * El cliente aprueba y firma: se guarda la firma, se crea la orden de servicio
     * y se envía la orden (PDF) al cliente y a los correos internos.
     */
    public function aprobar(Request $request, $id, $token, CotizacionOrdenService $servicioOrden)
    {
        $validator = Validator::make($request->all(), [
            'nombre'    => 'required|string|max:150',
            'documento' => 'required|string|max:50',
            'firma'     => 'required|string|max:700000',
        ], [
            'nombre.required'    => 'Ingrese su nombre completo.',
            'documento.required' => 'Ingrese su número de documento.',
            'firma.required'     => 'Por favor firme en el recuadro antes de aprobar.',
            'firma.max'          => 'La imagen de la firma es demasiado grande.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        // La firma debe ser una imagen PNG en base64 (data URI) válida
        $firma = $request->input('firma');
        if (!FirmaImagen::esValida($firma)) {
            return response()->json(['message' => 'La firma no tiene un formato válido.'], 422);
        }

        try {
            $resultado = DB::transaction(function () use ($id, $token, $request, $firma, $servicioOrden) {
                // Bloqueo de fila: si el cliente pulsa dos veces, la segunda petición ve el estado ya cambiado
                $cotizacion = Cotizacion::with('items')->lockForUpdate()->find($id);

                if ($error = $this->validarEnlace($cotizacion, $token)) {
                    return ['error' => $error];
                }

                $datosOrden = $cotizacion->datos_orden;
                if (empty($datosOrden['fecha_inicio']) || empty($datosOrden['fecha_fin'])) {
                    return ['error' => response()->json([
                        'message' => 'Esta cotización no tiene los datos para generar la orden de servicio. Comuníquese con SIMDE.',
                    ], 422)];
                }

                $cotizacion->update([
                    'sw_estado'          => 'aprobada',
                    'firma_cliente'      => $firma,
                    'firmante_nombre'    => trim($request->input('nombre')),
                    'firmante_documento' => trim($request->input('documento')),
                    'firma_ip'           => $request->ip(),
                    'fecha_firma'        => now(),
                ]);

                $orden = $servicioOrden->crearOrden($cotizacion, $datosOrden, (int) $cotizacion->usuario_id);

                // La orden hereda la firma: así figura como firmada (no se vuelve a solicitar firma) y su PDF la lleva
                $orden->update([
                    'firma_tercero'      => $firma,
                    'fecha_firma'        => $cotizacion->fecha_firma,
                    'firmante_nombre'    => $cotizacion->firmante_nombre,
                    'firmante_documento' => $cotizacion->firmante_documento,
                    'firma_ip'           => $cotizacion->firma_ip,
                ]);

                return ['cotizacion' => $cotizacion, 'orden' => $orden];
            });
        } catch (\Throwable $e) {
            Log::error("Error aprobando cotización {$id}: " . $e->getMessage());
            return response()->json(['message' => 'No se pudo registrar la aprobación. Intente nuevamente.'], 500);
        }

        if (isset($resultado['error'])) {
            return $resultado['error'];
        }

        // Ya confirmada la aprobación: los correos no pueden revertirla
        $servicioOrden->notificarAprobacion($resultado['cotizacion'], $resultado['orden']);

        return response()->json([
            'message'      => 'Cotización aprobada correctamente.',
            'numero_orden' => $resultado['orden']->numero_orden,
        ]);
    }

    /**
     * El cliente rechaza la cotización desde el enlace.
     */
    public function rechazar(Request $request, $id, $token)
    {
        $validator = Validator::make($request->all(), [
            'motivo' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        try {
            $error = DB::transaction(function () use ($id, $token, $request) {
                $cotizacion = Cotizacion::lockForUpdate()->find($id);

                if ($error = $this->validarEnlace($cotizacion, $token)) {
                    return $error;
                }

                $cotizacion->update([
                    'sw_estado'     => 'rechazada',
                    'motivo_rechazo' => $request->input('motivo'),
                    'fecha_rechazo' => now(),
                ]);

                return null;
            });
        } catch (\Throwable $e) {
            Log::error("Error rechazando cotización {$id}: " . $e->getMessage());
            return response()->json(['message' => 'No se pudo registrar la respuesta. Intente nuevamente.'], 500);
        }

        if ($error) {
            return $error;
        }

        return response()->json(['message' => 'Hemos registrado que la cotización no fue aprobada.']);
    }

    /**
     * Devuelve una respuesta de error si el enlace no es utilizable, o null si todo está bien.
     */
    private function validarEnlace(?Cotizacion $cotizacion, string $token): ?JsonResponse
    {
        $hash = $cotizacion?->token_aprobacion;

        if (!$cotizacion || !$hash || !hash_equals($hash, hash('sha256', $token))) {
            return response()->json(['message' => 'El enlace no es válido.'], 404);
        }

        if ($cotizacion->sw_estado === 'convertida' || $cotizacion->sw_estado === 'aprobada') {
            return response()->json(['message' => 'Esta cotización ya fue aprobada.', 'estado' => 'aprobada'], 409);
        }

        if ($cotizacion->sw_estado === 'rechazada') {
            return response()->json(['message' => 'Esta cotización ya fue rechazada.', 'estado' => 'rechazada'], 409);
        }

        if ($cotizacion->sw_estado !== 'enviada') {
            return response()->json(['message' => 'Esta cotización ya no está disponible para aprobación.'], 410);
        }

        if ($cotizacion->token_aprobacion_expira_en && $cotizacion->token_aprobacion_expira_en->isPast()) {
            return response()->json(['message' => 'El enlace de aprobación expiró. Solicite una nueva cotización a SIMDE.'], 410);
        }

        return null;
    }
}

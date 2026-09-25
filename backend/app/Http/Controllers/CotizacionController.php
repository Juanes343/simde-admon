<?php

namespace App\Http\Controllers;

use App\Mail\CotizacionMail;
use App\Models\Cotizacion;
use App\Models\CotizacionItem;
use App\Models\OrdenServicio;
use App\Models\OrdenServicioItem;
use App\Models\Impuesto;
use App\Services\CotizacionOrdenService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CotizacionController extends Controller
{
    /**
     * Listar cotizaciones con filtros y paginación
     */
    public function index(Request $request)
    {
        try {
            $query = Cotizacion::with(['items', 'usuario']);

            if ($request->filled('tipo_id_tercero') && $request->filled('tercero_id')) {
                $query->where('tipo_id_tercero', $request->tipo_id_tercero)
                      ->where('tercero_id', $request->tercero_id);
            }

            if ($request->filled('sw_estado')) {
                $query->where('sw_estado', $request->sw_estado);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('numero_cotizacion', 'like', "%{$search}%")
                      ->orWhere('tercero_id', 'like', "%{$search}%")
                      ->orWhere('orden_compra', 'like', "%{$search}%");
                });
            }

            $perPage   = $request->get('per_page', 15);
            $cotizaciones = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json($cotizaciones);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener cotizaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nueva cotización
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo_id_tercero'              => 'required|string|max:20',
            'tercero_id'                   => 'required|string|max:32',
            'fecha_emision'                => 'required|date',
            'fecha_vencimiento'            => 'nullable|date|after_or_equal:fecha_emision',
            'metodo_pago'                  => 'nullable|string|max:50',
            'tipo_pago'                    => 'nullable|string|max:50',
            'orden_compra'                 => 'nullable|string|max:100',
            'notas'                        => 'nullable|string',
            'items'                        => 'required|array|min:1',
            'items.*.descripcion'          => 'required|string',
            'items.*.cantidad'             => 'required|numeric|min:0.01',
            'items.*.precio_unitario'      => 'required|numeric|min:0',
            'items.*.porcentaje_descuento' => 'nullable|numeric|min:0|max:100',
            'items.*.porcentaje_ret_fuente'=> 'nullable|numeric|min:0|max:100',
            'items.*.impuesto_id'          => 'nullable|exists:impuestos,impuesto_id',
        ], [
            'tipo_id_tercero.required' => 'El tipo de identificación es requerido',
            'tercero_id.required'      => 'El cliente es requerido',
            'fecha_emision.required'   => 'La fecha de emisión es requerida',
            'items.required'           => 'Debe agregar al menos un ítem',
            'items.min'                => 'Debe agregar al menos un ítem',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $cotizacion = Cotizacion::create([
                'numero_cotizacion' => Cotizacion::generarNumeroCotizacion(),
                'tipo_id_tercero'   => $request->tipo_id_tercero,
                'tercero_id'        => $request->tercero_id,
                'fecha_emision'     => $request->fecha_emision,
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'metodo_pago'       => $request->metodo_pago,
                'tipo_pago'         => $request->tipo_pago,
                'orden_compra'      => $request->orden_compra,
                'notas'             => $request->notas,
                'sw_estado'         => 'borrador',
                'usuario_id'        => $request->user()->usuario_id,
            ]);

            [$subtotal, $descuentoTotal, $impuestosTotal, $retencionTotal] = $this->crearItems(
                $cotizacion->cotizacion_id,
                $request->items
            );

            $cotizacion->update([
                'subtotal'        => $subtotal,
                'descuento_total' => $descuentoTotal,
                'impuestos_total' => $impuestosTotal,
                'total'           => $subtotal - $descuentoTotal + $impuestosTotal - $retencionTotal,
            ]);

            DB::commit();

            return response()->json([
                'message'     => 'Cotización creada exitosamente',
                'cotizacion'  => $cotizacion->load(['items.impuesto', 'items.servicio', 'usuario']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar cotización con sus ítems
     */
    public function show($id)
    {
        try {
            $cotizacion = Cotizacion::with(['items.impuesto', 'items.servicio', 'usuario'])->find($id);

            if (!$cotizacion) {
                return response()->json(['message' => 'Cotización no encontrada'], 404);
            }

            // Forzar append del accessor tercero
            $cotizacion->append('tercero');

            return response()->json($cotizacion);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Actualizar cotización (solo en estado borrador)
     */
    public function update(Request $request, $id)
    {
        $cotizacion = Cotizacion::find($id);

        if (!$cotizacion) {
            return response()->json(['message' => 'Cotización no encontrada'], 404);
        }

        if ($cotizacion->sw_estado !== 'borrador') {
            return response()->json([
                'message' => 'Solo se pueden editar cotizaciones en estado borrador'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'tipo_id_tercero'              => 'required|string|max:20',
            'tercero_id'                   => 'required|string|max:32',
            'fecha_emision'                => 'required|date',
            'fecha_vencimiento'            => 'nullable|date',
            'metodo_pago'                  => 'nullable|string|max:50',
            'tipo_pago'                    => 'nullable|string|max:50',
            'orden_compra'                 => 'nullable|string|max:100',
            'notas'                        => 'nullable|string',
            'items'                        => 'required|array|min:1',
            'items.*.descripcion'          => 'required|string',
            'items.*.cantidad'             => 'required|numeric|min:0.01',
            'items.*.precio_unitario'      => 'required|numeric|min:0',
            'items.*.porcentaje_descuento' => 'nullable|numeric|min:0|max:100',
            'items.*.porcentaje_ret_fuente'=> 'nullable|numeric|min:0|max:100',
            'items.*.impuesto_id'          => 'nullable|exists:impuestos,impuesto_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $cotizacion->update([
                'tipo_id_tercero'   => $request->tipo_id_tercero,
                'tercero_id'        => $request->tercero_id,
                'fecha_emision'     => $request->fecha_emision,
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'metodo_pago'       => $request->metodo_pago,
                'tipo_pago'         => $request->tipo_pago,
                'orden_compra'      => $request->orden_compra,
                'notas'             => $request->notas,
                // Si se edita, el enlace enviado antes ya no corresponde a lo que ve el cliente
                'token_aprobacion'           => null,
                'token_aprobacion_expira_en' => null,
            ]);

            // Reemplazar ítems
            CotizacionItem::where('cotizacion_id', $cotizacion->cotizacion_id)->delete();

            [$subtotal, $descuentoTotal, $impuestosTotal, $retencionTotal] = $this->crearItems(
                $cotizacion->cotizacion_id,
                $request->items
            );

            $cotizacion->update([
                'subtotal'        => $subtotal,
                'descuento_total' => $descuentoTotal,
                'impuestos_total' => $impuestosTotal,
                'total'           => $subtotal - $descuentoTotal + $impuestosTotal - $retencionTotal,
            ]);

            DB::commit();

            return response()->json([
                'message'    => 'Cotización actualizada exitosamente',
                'cotizacion' => $cotizacion->load(['items.impuesto', 'items.servicio', 'usuario']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar estado de la cotización
     */
    public function cambiarEstado(Request $request, $id)
    {
        $cotizacion = Cotizacion::find($id);

        if (!$cotizacion) {
            return response()->json(['message' => 'Cotización no encontrada'], 404);
        }

        $request->validate([
            'estado' => 'required|in:borrador,enviada,aprobada,rechazada,vencida',
        ]);

        $cotizacion->update(['sw_estado' => $request->estado]);

        // Solo una cotización "enviada" puede ser aprobada por el cliente desde su enlace
        if ($request->estado !== 'enviada') {
            $cotizacion->update([
                'token_aprobacion'           => null,
                'token_aprobacion_expira_en' => null,
            ]);
        }

        return response()->json([
            'message'    => 'Estado actualizado exitosamente',
            'sw_estado'  => $cotizacion->sw_estado,
            'cotizacion' => $cotizacion,
        ]);
    }

    /**
     * Convertir cotización aprobada a Orden de Servicio
     */
    public function convertirAOrden(Request $request, $id, CotizacionOrdenService $servicioOrden)
    {
        $cotizacion = Cotizacion::with('items')->find($id);

        if (!$cotizacion) {
            return response()->json(['message' => 'Cotización no encontrada'], 404);
        }

        if ($cotizacion->sw_estado !== 'aprobada') {
            return response()->json([
                'message' => 'Solo se pueden convertir cotizaciones aprobadas'
            ], 422);
        }

        if ($cotizacion->orden_servicio_id) {
            return response()->json([
                'message' => 'Esta cotización ya fue convertida a orden de servicio'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'fecha_inicio'              => 'required|date',
            'fecha_fin'                 => 'required|date|after_or_equal:fecha_inicio',
            'periodo_facturacion_dias'  => 'nullable|integer|min:1',
            'sw_prorroga_automatica'    => 'nullable|in:0,1',
            'porcentaje_soltec'         => 'nullable|numeric|min:0|max:100',
            'porcentaje_ret_fuente'     => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $orden = $servicioOrden->crearOrden($cotizacion, [
                'fecha_inicio'             => $request->fecha_inicio,
                'fecha_fin'                => $request->fecha_fin,
                'sw_prorroga_automatica'   => $request->sw_prorroga_automatica ?? '0',
                'periodo_facturacion_dias' => $request->periodo_facturacion_dias ?? 30,
                'porcentaje_soltec'        => $request->porcentaje_soltec ?? 0,
                'porcentaje_ret_fuente'    => $request->porcentaje_ret_fuente ?? 0,
            ], $request->user()->usuario_id);

            DB::commit();

            return response()->json([
                'message'           => 'Cotización convertida a orden de servicio exitosamente',
                'orden_servicio_id' => $orden->orden_servicio_id,
                'numero_orden'      => $orden->numero_orden,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al convertir cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar cotización (no permite eliminar convertidas)
     */
    public function destroy($id)
    {
        $cotizacion = Cotizacion::find($id);

        if (!$cotizacion) {
            return response()->json(['message' => 'Cotización no encontrada'], 404);
        }

        if ($cotizacion->sw_estado === 'convertida') {
            return response()->json([
                'message' => 'No se puede eliminar una cotización ya convertida a orden de servicio'
            ], 422);
        }

        $cotizacion->delete();

        return response()->json(['message' => 'Cotización eliminada exitosamente']);
    }

    /**
     * Descargar PDF de la cotización
     */
    public function descargarPdf($id)
    {
        $cotizacion = Cotizacion::with(['items.impuesto', 'items.servicio'])->find($id);

        if (!$cotizacion) {
            return response()->json(['message' => 'Cotización no encontrada'], 404);
        }

        $cotizacion->append('tercero');

        $pdf = Pdf::loadView('pdf.cotizacion', compact('cotizacion'))
                  ->setPaper('a4', 'portrait');

        return $pdf->download("{$cotizacion->numero_cotizacion}.pdf");
    }

    /**
     * Enviar cotización por correo electrónico con PDF adjunto
     * y, opcionalmente, documentos adicionales (multipart: adjuntos[]).
     *
     * Si la cotización está en borrador/enviada, el correo lleva un botón para que el cliente
     * la apruebe y firme en línea (enlace con token y vigencia). Al aprobar se crea la orden de
     * servicio sin fechas ni condiciones: quien la edita después las completa.
     */
    public function enviarEmail(Request $request, $id)
    {
        $cotizacion = Cotizacion::with(['items.impuesto', 'items.servicio'])->find($id);

        if (!$cotizacion) {
            return response()->json(['message' => 'Cotización no encontrada'], 404);
        }

        $conAprobacion = in_array($cotizacion->sw_estado, ['borrador', 'enviada'], true);

        $rules = [
            'email'       => 'required|email',
            'adjuntos'    => 'nullable|array|max:5',
            'adjuntos.*'  => 'file|max:10240|mimes:pdf',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'adjuntos.max'   => 'Puede adjuntar máximo 5 documentos.',
            'adjuntos.*.max' => 'Cada documento adjunto debe pesar máximo 10 MB.',
            'adjuntos.*.mimes' => 'Solo se admiten documentos en formato PDF.',
            'adjuntos.*.uploaded' => 'No se pudo subir uno de los adjuntos (revise que no supere el límite del servidor).',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Vigencia del enlace: hasta el vencimiento de la cotización, o N días si no lo tiene
        $enlaceExpiraEn = null;
        if ($conAprobacion) {
            $enlaceExpiraEn = $cotizacion->fecha_vencimiento
                ? $cotizacion->fecha_vencimiento->copy()->endOfDay()
                : now()->addDays(config('cotizaciones.dias_vigencia_enlace', 30));

            if ($enlaceExpiraEn->isPast()) {
                return response()->json([
                    'message' => 'La cotización ya venció; no se puede enviar para aprobación. Ajuste la fecha de vencimiento.',
                ], 422);
            }
        }

        try {
            $cotizacion->append('tercero');

            // Enlace de aprobación (en BD se guarda solo el hash del token)
            $enlaceAprobacion = null;
            if ($conAprobacion) {
                $token = Str::random(64);

                $cotizacion->update([
                    'token_aprobacion'           => hash('sha256', $token),
                    'token_aprobacion_expira_en' => $enlaceExpiraEn,
                ]);

                $enlaceAprobacion = $this->urlFrontend($request) . "/#/aprobar-cotizacion/{$cotizacion->cotizacion_id}/{$token}";
            }

            // Generar PDF en archivo temporal
            $pdf = Pdf::loadView('pdf.cotizacion', compact('cotizacion'))
                      ->setPaper('a4', 'portrait');

            $tmpPath = tempnam(sys_get_temp_dir(), 'cot_') . '.pdf';
            $pdf->save($tmpPath);

            // Documentos adicionales subidos por el usuario (se leen del temporal de PHP, no se guardan)
            $adjuntos = collect($request->file('adjuntos', []))
                ->map(fn ($archivo) => [
                    'path' => $archivo->getRealPath(),
                    'name' => $archivo->getClientOriginalName(),
                    'mime' => $archivo->getMimeType(),
                ])
                ->all();

            // Enviar correo con PDF adjunto
            try {
                Mail::to($request->email)->send(
                    new CotizacionMail($cotizacion, $tmpPath, $adjuntos, $enlaceAprobacion, $enlaceExpiraEn)
                );

                // Copia a los correos internos (sin enlace de aprobación); un fallo aquí no afecta el envío al cliente
                $internos = config('cotizaciones.correos_aprobacion', []);
                if (config('cotizaciones.enviar_copia_internos') && !empty($internos)) {
                    try {
                        Mail::to($internos)->send(
                            new CotizacionMail($cotizacion, $tmpPath, $adjuntos, null, null, $request->email)
                        );
                    } catch (\Throwable $e) {
                        Log::error("Error enviando copia interna de {$cotizacion->numero_cotizacion}: " . $e->getMessage());
                    }
                }
            } finally {
                @unlink($tmpPath);
            }

            // Cambiar estado a "enviada" si estaba en borrador
            if ($cotizacion->sw_estado === 'borrador') {
                $cotizacion->update(['sw_estado' => 'enviada']);
            }

            return response()->json([
                'message' => "Cotización enviada correctamente a {$request->email}",
                'estado'  => $cotizacion->fresh()->sw_estado,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al enviar el correo: ' . $e->getMessage()
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Crear ítems y retornar [subtotal, descuentoTotal, impuestosTotal]
     */
    private function crearItems(int $cotizacionId, array $items): array
    {
        $subtotal       = 0;
        $descuentoTotal = 0;
        $impuestosTotal = 0;
        $retencionTotal = 0;

        foreach ($items as $i => $data) {
            $cantidad  = floatval($data['cantidad']);
            $precio    = floatval($data['precio_unitario']);
            $descPct   = floatval($data['porcentaje_descuento'] ?? 0);
            $retPct    = floatval($data['porcentaje_ret_fuente'] ?? 0);

            $itemSubtotal  = $cantidad * $precio;
            $itemDescuento = $itemSubtotal * $descPct / 100;
            $baseNeta      = $itemSubtotal - $itemDescuento;

            $impuestoPct = 0;
            if (!empty($data['impuesto_id'])) {
                $imp = Impuesto::find($data['impuesto_id']);
                if ($imp) {
                    $impuestoPct = floatval($imp->porcentaje);
                }
            }

            $itemImpuesto  = $baseNeta * $impuestoPct / 100;
            $itemRetencion = $baseNeta * $retPct / 100;
            $itemTotal     = $baseNeta + $itemImpuesto - $itemRetencion;

            CotizacionItem::create([
                'cotizacion_id'        => $cotizacionId,
                'servicio_id'          => $data['servicio_id'] ?? null,
                'referencia'           => $data['referencia'] ?? null,
                'descripcion'          => $data['descripcion'],
                'cantidad'             => $cantidad,
                'tipo_unidad'          => $data['tipo_unidad'] ?? 'UNIDAD',
                'precio_unitario'      => $precio,
                'porcentaje_descuento' => $descPct,
                'porcentaje_ret_fuente'=> $retPct ?: null,
                'impuesto_id'          => $data['impuesto_id'] ?? null,
                'subtotal'             => $itemSubtotal,
                'total'                => $itemTotal,
                'orden'                => $i,
                'estado'               => $data['estado'] ?? '1',
                'porcentaje_soltec'    => $data['porcentaje_soltec'] ?? 0,
                'observaciones'        => $data['observaciones'] ?? null,
            ]);

            $subtotal       += $itemSubtotal;
            $descuentoTotal += $itemDescuento;
            $impuestosTotal += $itemImpuesto;
            $retencionTotal += $itemRetencion;
        }

        return [$subtotal, $descuentoTotal, $impuestosTotal, $retencionTotal];
    }
}

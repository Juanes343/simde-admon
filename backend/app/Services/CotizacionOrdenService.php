<?php

namespace App\Services;

use App\Mail\CotizacionAprobadaMail;
use App\Models\Cotizacion;
use App\Models\OrdenServicio;
use App\Models\OrdenServicioItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CotizacionOrdenService
{
    public function __construct(private OrdenServicioPdfService $pdfService)
    {
    }

    /**
     * Crea la orden de servicio (con sus ítems) a partir de una cotización y la marca como convertida.
     * No abre transacción: quien la llama debe hacerlo.
     *
     * @param array $datos fecha_inicio, fecha_fin, periodo_facturacion_dias, sw_prorroga_automatica,
     *                     porcentaje_soltec y porcentaje_ret_fuente. Todos opcionales: sin fechas la orden queda
     *                     "por completar" (se editan después) y el resto toma los valores por defecto.
     */
    public function crearOrden(Cotizacion $cotizacion, array $datos, int $usuarioId): OrdenServicio
    {
        $orden = OrdenServicio::create([
            'numero_orden'             => OrdenServicio::generarNumeroOrden(),
            'tipo_id_tercero'          => $cotizacion->tipo_id_tercero,
            'tercero_id'               => $cotizacion->tercero_id,
            'fecha_inicio'             => $datos['fecha_inicio'] ?? null,
            'fecha_fin'                => $datos['fecha_fin'] ?? null,
            'sw_prorroga_automatica'   => $datos['sw_prorroga_automatica'] ?? '0',
            'periodo_facturacion_dias' => $datos['periodo_facturacion_dias'] ?? 30,
            'porcentaje_soltec'        => $datos['porcentaje_soltec'] ?? 0,
            'porcentaje_ret_fuente'    => $datos['porcentaje_ret_fuente'] ?? 0,
            'observaciones'            => $cotizacion->notas,
            'sw_estado'                => '1',
            'usuario_id'               => $usuarioId,
        ]);

        foreach ($cotizacion->items as $i => $item) {
            OrdenServicioItem::create([
                'orden_servicio_id'    => $orden->orden_servicio_id,
                'servicio_id'          => $item->servicio_id,
                'nombre_servicio'      => $item->descripcion,
                'descripcion'          => $item->observaciones,
                'cantidad'             => $item->cantidad,
                'tipo_unidad'          => $item->tipo_unidad ?? 'UNIDAD',
                'precio_unitario'      => $item->precio_unitario,
                'subtotal'             => $item->subtotal,
                'orden'                => $i,
                'observaciones'        => $item->observaciones,
                'estado'               => '1',
                'porcentaje_soltec'    => $item->porcentaje_soltec ?? 0,
                'porcentaje_descuento' => $item->porcentaje_descuento ?? 0,
            ]);
        }

        $cotizacion->update([
            'sw_estado'         => 'convertida',
            'orden_servicio_id' => $orden->orden_servicio_id,
        ]);

        return $orden;
    }

    /**
     * Envía la orden de servicio (PDF) al cliente y a los correos internos configurados.
     * Cada envío es independiente: un fallo se registra en el log y no afecta a los demás.
     */
    public function notificarAprobacion(Cotizacion $cotizacion, OrdenServicio $orden): void
    {
        $pdfPath = null;

        try {
            $cotizacion->append('tercero');

            $pdfPath = tempnam(sys_get_temp_dir(), 'os_');
            file_put_contents($pdfPath, $this->pdfService->generar($orden)->output());

            // Cliente
            $emailCliente = $cotizacion->tercero?->email;
            if ($emailCliente) {
                $this->enviar($emailCliente, new CotizacionAprobadaMail($cotizacion, $orden, $pdfPath, false), 'cliente');
            } else {
                Log::warning("Cotización {$cotizacion->numero_cotizacion} aprobada: el tercero no tiene correo, no se envió copia al cliente.");
            }

            // Internos (simdeinfo, admon, ...)
            $internos = config('cotizaciones.correos_aprobacion', []);
            if (!empty($internos)) {
                $this->enviar($internos, new CotizacionAprobadaMail($cotizacion, $orden, $pdfPath, true), 'internos');
            } else {
                Log::warning("Cotización {$cotizacion->numero_cotizacion} aprobada: COTIZACION_CORREOS_APROBACION no está configurado, no se notificó a los correos internos.");
            }
        } catch (\Throwable $e) {
            Log::error("Error preparando notificación de aprobación de {$cotizacion->numero_cotizacion}: " . $e->getMessage());
        } finally {
            if ($pdfPath) {
                @unlink($pdfPath);
            }
        }
    }

    private function enviar($destino, CotizacionAprobadaMail $mail, string $etiqueta): void
    {
        try {
            Mail::to($destino)->send($mail);
        } catch (\Throwable $e) {
            Log::error("Error enviando correo de aprobación ({$etiqueta}): " . $e->getMessage());
        }
    }
}

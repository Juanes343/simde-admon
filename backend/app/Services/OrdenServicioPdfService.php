<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\OrdenServicio;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Arma el PDF de una orden de servicio. Es el único lugar que lo genera, de modo que el PDF
 * enviado al aprobar una cotización, el que se descarga y el que se envía manualmente son idénticos.
 *
 * - Si la orden nació de una cotización, ítems, totales y condiciones salen de esa cotización
 *   (es lo que el cliente vio y aprobó).
 * - Si no, salen de los ítems de la propia orden.
 */
class OrdenServicioPdfService
{
    public function generar(OrdenServicio $orden, ?array $doc = null): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('pdf.orden_servicio', ['doc' => $doc ?? $this->datos($orden)])
                  ->setPaper('a4', 'portrait');
    }

    /**
     * Datos normalizados que consume la plantilla pdf.orden_servicio.
     */
    public function datos(OrdenServicio $orden): array
    {
        $cotizacion = Cotizacion::with('items.impuesto')
            ->where('orden_servicio_id', $orden->orden_servicio_id)
            ->first();

        $tercero = $orden->tercero;

        [$items, $totales, $notas] = $cotizacion
            ? $this->desdeCotizacion($cotizacion)
            : $this->desdeOrden($orden);

        return [
            'numero_orden'      => $orden->numero_orden,
            'estado_orden'      => $orden->sw_estado === '1' ? 'Activa' : 'Inactiva',
            'cotizacion_numero' => $cotizacion?->numero_cotizacion,
            'cliente' => [
                'nombre'    => $tercero?->nombre_tercero ?? $orden->tercero_id,
                'tipo_id'   => $orden->tipo_id_tercero,
                'id'        => $orden->tercero_id,
                'email'     => $tercero?->email,
                'telefono'  => $tercero?->telefono,
                'direccion' => $tercero?->direccion,
            ],
            'fecha_inicio'  => $orden->fecha_inicio,
            'fecha_fin'     => $orden->fecha_fin,
            'periodo_dias'  => $orden->periodo_facturacion_dias,
            'prorroga'      => $orden->sw_prorroga_automatica == '1',
            'metodo_pago'   => $cotizacion?->metodo_pago,
            'tipo_pago'     => $cotizacion?->tipo_pago,
            'orden_compra'  => $cotizacion?->orden_compra,
            'items'         => $items,
            'totales'       => $totales,
            'notas'         => $notas,
            'firma'         => $this->firma($orden, $cotizacion),
        ];
    }

    private function desdeCotizacion(Cotizacion $cotizacion): array
    {
        $items = [];
        $retencion = 0.0;

        foreach ($cotizacion->items as $item) {
            $subtotal = (float) $item->subtotal;
            $base     = $subtotal * (1 - (float) ($item->porcentaje_descuento ?? 0) / 100);
            $impPct   = (float) ($item->impuesto?->porcentaje ?? 0);
            $retPct   = (float) ($item->porcentaje_ret_fuente ?? 0);
            $retencion += $base * $retPct / 100;

            $items[] = [
                'ref'           => $item->referencia,
                'descripcion'   => $item->descripcion,
                'observaciones' => $item->observaciones,
                'cantidad'      => (float) $item->cantidad,
                'unidad'        => $item->tipo_unidad,
                'precio'        => (float) $item->precio_unitario,
                'descuento'     => (float) ($item->porcentaje_descuento ?? 0),
                'impuesto'      => $item->impuesto ? 'IVA ' . number_format($impPct, 0) . '%' : null,
                'impuesto_pct'  => $item->impuesto ? $impPct : null,
                'retencion'     => $retPct,
                'subtotal'      => $subtotal,
                'total'         => $base * (1 + $impPct / 100) - $base * $retPct / 100,
            ];
        }

        $totales = [
            'subtotal'  => (float) $cotizacion->subtotal,
            'descuento' => (float) $cotizacion->descuento_total,
            'impuestos' => (float) $cotizacion->impuestos_total,
            'retencion' => $retencion,
            'total'     => (float) $cotizacion->total,
        ];

        return [$items, $totales, $cotizacion->notas];
    }

    private function desdeOrden(OrdenServicio $orden): array
    {
        $orden->loadMissing('items.servicio.impuesto');

        $items = [];
        $subtotal = $descuento = $impuestos = $baseTotal = 0.0;

        foreach ($orden->items as $item) {
            if (($item->estado ?? '1') === '0') {
                continue; // ítem inactivo
            }

            $sub     = (float) $item->subtotal;
            $descPct = (float) ($item->porcentaje_descuento ?? 0);
            $base    = $sub * (1 - $descPct / 100);
            $impPct  = (float) ($item->servicio?->impuesto?->porcentaje ?? 0);

            $subtotal  += $sub;
            $descuento += $sub - $base;
            $impuestos += $base * $impPct / 100;
            $baseTotal += $base;

            $items[] = [
                'ref'           => $item->servicio_id,
                'descripcion'   => $item->nombre_servicio,
                'observaciones' => $item->observaciones ?: $item->descripcion,
                'cantidad'      => (float) $item->cantidad,
                'unidad'        => $item->tipo_unidad,
                'precio'        => (float) $item->precio_unitario,
                'descuento'     => $descPct,
                'impuesto'      => $item->servicio?->impuesto ? 'IVA ' . number_format($impPct, 0) . '%' : null,
                'impuesto_pct'  => $item->servicio?->impuesto ? $impPct : null,
                'retencion'     => 0.0, // en la orden la retención es un % único, se muestra en los totales
                'subtotal'      => $sub,
                'total'         => $base * (1 + $impPct / 100),
            ];
        }

        // Retención en la fuente: porcentaje único de la orden sobre la base (igual que al facturar)
        $retencion = round($baseTotal * (float) ($orden->porcentaje_ret_fuente ?? 0) / 100, 2);

        $totales = [
            'subtotal'  => $subtotal,
            'descuento' => $descuento,
            'impuestos' => $impuestos,
            'retencion' => $retencion,
            'total'     => $subtotal - $descuento + $impuestos - $retencion,
        ];

        return [$items, $totales, $orden->observaciones];
    }

    /**
     * Firma del cliente. Imagen, fecha y datos del firmante viven en la orden (se llenan tanto al aprobar una
     * cotización como al firmar el enlace de solicitud de firma). Para órdenes firmadas antes de que existieran
     * los datos del firmante, se usan los de su cotización de origen.
     */
    private function firma(OrdenServicio $orden, ?Cotizacion $cotizacion): ?array
    {
        $imagen = $orden->firma_tercero ?: $cotizacion?->firma_cliente;
        $fecha  = $orden->fecha_firma ?: $cotizacion?->fecha_firma;

        if (!$imagen && !$fecha) {
            return null;
        }

        return [
            'imagen'    => $imagen,
            'fecha'     => $fecha,
            'nombre'    => $orden->firmante_nombre ?: $cotizacion?->firmante_nombre,
            'documento' => $orden->firmante_documento ?: $cotizacion?->firmante_documento,
            'telefono'  => $orden->firmante_telefono ?: $cotizacion?->firmante_telefono,
            'ip'        => $orden->firma_ip ?: $cotizacion?->firma_ip,
        ];
    }
}

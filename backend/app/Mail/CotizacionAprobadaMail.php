<?php

namespace App\Mail;

use App\Models\Cotizacion;
use App\Models\OrdenServicio;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Correo que se envía cuando un cliente aprueba y firma una cotización.
 * Lleva adjunta la orden de servicio en PDF. Una misma clase sirve para el cliente y para los correos internos.
 */
class CotizacionAprobadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public Cotizacion $cotizacion;
    public OrdenServicio $orden;
    public string $pdfPath;
    public bool $interno;
    public string $nombreTercero;

    public function __construct(Cotizacion $cotizacion, OrdenServicio $orden, string $pdfPath, bool $interno)
    {
        $this->cotizacion    = $cotizacion;
        $this->orden         = $orden;
        $this->pdfPath       = $pdfPath;
        $this->interno       = $interno;
        $this->nombreTercero = $cotizacion->tercero?->nombre_tercero ?? $cotizacion->tercero_id;
    }

    public function build(): static
    {
        $asunto = $this->interno
            ? "Cotización {$this->cotizacion->numero_cotizacion} aprobada — Orden {$this->orden->numero_orden}"
            : "Orden de servicio {$this->orden->numero_orden} — SIMDE SAS";

        return $this
            ->subject($asunto)
            ->view($this->interno ? 'emails.cotizacion_aprobada_interno' : 'emails.cotizacion_aprobada_cliente')
            ->with([
                'cotizacion'    => $this->cotizacion,
                'orden'         => $this->orden,
                'nombreTercero' => $this->nombreTercero,
            ])
            ->attach($this->pdfPath, [
                'as'   => "{$this->orden->numero_orden}.pdf",
                'mime' => 'application/pdf',
            ]);
    }
}

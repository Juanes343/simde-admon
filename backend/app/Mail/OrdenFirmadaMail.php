<?php

namespace App\Mail;

use App\Models\OrdenServicio;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Correo que se envía cuando el cliente firma una orden de servicio desde el enlace de "Solicitar firma".
 * Lleva adjunta la orden firmada en PDF. Una misma clase sirve para el cliente y para los correos internos.
 */
class OrdenFirmadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public OrdenServicio $orden;
    public string $pdfPath;
    /** @var array Datos normalizados de OrdenServicioPdfService::datos() */
    public array $doc;
    public bool $interno;

    public function __construct(OrdenServicio $orden, string $pdfPath, array $doc, bool $interno)
    {
        $this->orden   = $orden;
        $this->pdfPath = $pdfPath;
        $this->doc     = $doc;
        $this->interno = $interno;
    }

    public function build(): static
    {
        $asunto = $this->interno
            ? "Orden {$this->orden->numero_orden} firmada por el cliente"
            : "Orden de servicio {$this->orden->numero_orden} firmada — SIMDE SAS";

        return $this
            ->subject($asunto)
            ->view($this->interno ? 'emails.orden_firmada_interno' : 'emails.orden_firmada_cliente')
            ->with(['orden' => $this->orden, 'doc' => $this->doc])
            ->attach($this->pdfPath, [
                'as'   => "{$this->orden->numero_orden}.pdf",
                'mime' => 'application/pdf',
            ]);
    }
}

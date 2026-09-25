<?php

namespace App\Mail;

use App\Models\OrdenServicio;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Envío manual de una orden de servicio (PDF adjunto) desde el módulo de órdenes.
 */
class OrdenServicioMail extends Mailable
{
    use Queueable, SerializesModels;

    public OrdenServicio $orden;
    public string $pdfPath;
    /** @var array Datos normalizados de OrdenServicioPdfService::datos() */
    public array $doc;

    public function __construct(OrdenServicio $orden, string $pdfPath, array $doc)
    {
        $this->orden   = $orden;
        $this->pdfPath = $pdfPath;
        $this->doc     = $doc;
    }

    public function build(): static
    {
        return $this
            ->subject("Orden de servicio {$this->orden->numero_orden} — SIMDE SAS")
            ->view('emails.orden_servicio')
            ->with(['orden' => $this->orden, 'doc' => $this->doc])
            ->attach($this->pdfPath, [
                'as'   => "{$this->orden->numero_orden}.pdf",
                'mime' => 'application/pdf',
            ]);
    }
}

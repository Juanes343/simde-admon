<?php

namespace App\Mail;

use App\Models\OrdenServicio;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Solicitud de firma de una orden de servicio: enlace para revisarla y firmarla, con la orden en PDF adjunta.
 */
class SolicitudFirmaMailable extends Mailable
{
    use Queueable, SerializesModels;

    public OrdenServicio $orden;
    public string $link;
    /** @var array Datos normalizados de OrdenServicioPdfService::datos() */
    public array $doc;
    public string $pdfPath;
    public Carbon $expiraEn;

    public function __construct(OrdenServicio $orden, string $link, array $doc, string $pdfPath, Carbon $expiraEn)
    {
        $this->orden    = $orden;
        $this->link     = $link;
        $this->doc      = $doc;
        $this->pdfPath  = $pdfPath;
        $this->expiraEn = $expiraEn;
    }

    public function build(): static
    {
        return $this
            ->subject("Solicitud de firma — Orden de servicio {$this->orden->numero_orden} — SIMDE SAS")
            ->view('emails.solicitud_firma')
            ->with([
                'orden'    => $this->orden,
                'link'     => $this->link,
                'doc'      => $this->doc,
                'expiraEn' => $this->expiraEn,
            ])
            ->attach($this->pdfPath, [
                'as'   => "{$this->orden->numero_orden}.pdf",
                'mime' => 'application/pdf',
            ]);
    }
}

<?php

namespace App\Mail;

use App\Models\Cotizacion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CotizacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public Cotizacion $cotizacion;
    public string $nombreTercero;
    public string $pdfPath;

    public function __construct(Cotizacion $cotizacion, string $pdfPath)
    {
        $this->cotizacion    = $cotizacion;
        $this->pdfPath       = $pdfPath;
        $this->nombreTercero = $cotizacion->tercero?->nombre_tercero
                               ?? $cotizacion->tercero_id;
    }

    public function build(): static
    {
        return $this
            ->subject("Cotización {$this->cotizacion->numero_cotizacion} — SIMDE ADMON")
            ->view('emails.cotizacion')
            ->with([
                'cotizacion'     => $this->cotizacion,
                'nombreTercero'  => $this->nombreTercero,
            ])
            ->attach($this->pdfPath, [
                'as'   => "{$this->cotizacion->numero_cotizacion}.pdf",
                'mime' => 'application/pdf',
            ]);
    }
}

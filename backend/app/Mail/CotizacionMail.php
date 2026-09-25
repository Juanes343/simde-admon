<?php

namespace App\Mail;

use App\Models\Cotizacion;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CotizacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public Cotizacion $cotizacion;
    public string $nombreTercero;
    public string $pdfPath;
    /** @var array<int, array{path: string, name: string, mime: ?string}> */
    public array $adjuntos;
    /** Enlace público para que el cliente apruebe y firme (null = el correo no lleva botón) */
    public ?string $enlaceAprobacion;
    public ?Carbon $enlaceExpiraEn;
    /** Si viene, el correo es una copia interna y este es el destinatario original (cliente) */
    public ?string $copiaPara;

    /**
     * @param array<int, array{path: string, name: string, mime: ?string}> $adjuntos Documentos extra que adjunta el usuario
     */
    public function __construct(
        Cotizacion $cotizacion,
        string $pdfPath,
        array $adjuntos = [],
        ?string $enlaceAprobacion = null,
        ?Carbon $enlaceExpiraEn = null,
        ?string $copiaPara = null
    ) {
        $this->cotizacion       = $cotizacion;
        $this->pdfPath          = $pdfPath;
        $this->adjuntos         = $adjuntos;
        $this->enlaceAprobacion = $enlaceAprobacion;
        $this->enlaceExpiraEn   = $enlaceExpiraEn;
        $this->copiaPara        = $copiaPara;
        $this->nombreTercero = $cotizacion->tercero?->nombre_tercero
                               ?? $cotizacion->tercero_id;
    }

    public function build(): static
    {
        $mail = $this
            ->subject(($this->copiaPara ? '[Copia] ' : '') . "Cotización {$this->cotizacion->numero_cotizacion} — SIMDE ADMON")
            ->view('emails.cotizacion')
            ->with([
                'cotizacion'     => $this->cotizacion,
                'nombreTercero'  => $this->nombreTercero,
                'enlaceAprobacion' => $this->enlaceAprobacion,
                'enlaceExpiraEn'   => $this->enlaceExpiraEn,
                'copiaPara'        => $this->copiaPara,
            ])
            ->attach($this->pdfPath, [
                'as'   => "{$this->cotizacion->numero_cotizacion}.pdf",
                'mime' => 'application/pdf',
            ]);

        foreach ($this->adjuntos as $adjunto) {
            $mail->attach($adjunto['path'], array_filter([
                'as'   => $adjunto['name'],
                'mime' => $adjunto['mime'],
            ]));
        }

        return $mail;
    }
}

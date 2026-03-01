<?php

namespace App\Mail;

use App\Models\OrdenServicio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SolicitudFirmaMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $orden;
    public $link;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(OrdenServicio $orden, $link)
    {
        $this->orden = $orden;
        $this->link = $link;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Solicitud de Firma - Orden de Servicio #' . $this->orden->id)
                    ->view('emails.solicitud_firma');
    }
}

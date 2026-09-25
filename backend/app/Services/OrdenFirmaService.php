<?php

namespace App\Services;

use App\Mail\OrdenFirmadaMail;
use App\Models\OrdenServicio;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrdenFirmaService
{
    public function __construct(private OrdenServicioPdfService $pdfService)
    {
    }

    /**
     * Envía la orden firmada (PDF) al cliente y a los correos internos configurados.
     * Cada envío es independiente: un fallo se registra en el log y no afecta a los demás.
     */
    public function notificarFirma(OrdenServicio $orden): void
    {
        $pdfPath = null;

        try {
            $doc = $this->pdfService->datos($orden);

            $pdfPath = tempnam(sys_get_temp_dir(), 'os_');
            file_put_contents($pdfPath, $this->pdfService->generar($orden, $doc)->output());

            // Cliente
            $emailCliente = $doc['cliente']['email'];
            if ($emailCliente) {
                $this->enviar($emailCliente, new OrdenFirmadaMail($orden, $pdfPath, $doc, false), 'cliente');
            } else {
                Log::warning("Orden {$orden->numero_orden} firmada: el tercero no tiene correo, no se envió copia al cliente.");
            }

            // Internos (simdeinfo, admon, ...)
            $internos = config('cotizaciones.correos_aprobacion', []);
            if (!empty($internos)) {
                $this->enviar($internos, new OrdenFirmadaMail($orden, $pdfPath, $doc, true), 'internos');
            } else {
                Log::warning("Orden {$orden->numero_orden} firmada: COTIZACION_CORREOS_APROBACION no está configurado, no se notificó a los correos internos.");
            }
        } catch (\Throwable $e) {
            Log::error("Error preparando notificación de firma de {$orden->numero_orden}: " . $e->getMessage());
        } finally {
            if ($pdfPath) {
                @unlink($pdfPath);
            }
        }
    }

    private function enviar($destino, OrdenFirmadaMail $mail, string $etiqueta): void
    {
        try {
            Mail::to($destino)->send($mail);
        } catch (\Throwable $e) {
            Log::error("Error enviando correo de firma de orden ({$etiqueta}): " . $e->getMessage());
        }
    }
}

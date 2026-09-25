<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * URL base del frontend para los enlaces de los correos.
     * Usa FRONTEND_URL si está definida; si no, la deduce de la URL desde la que se atiende el API
     * (.../backend/public -> .../frontend/build), de modo que funcione igual en cada despliegue.
     */
    protected function urlFrontend(Request $request): string
    {
        $configurada = config('cotizaciones.frontend_url');
        if ($configurada) {
            return rtrim($configurada, '/');
        }

        return str_replace(
            '/backend/public',
            '/frontend/build',
            $request->getSchemeAndHttpHost() . $request->getBaseUrl()
        );
    }
}

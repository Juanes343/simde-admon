<?php

namespace App\Support;

class FirmaImagen
{
    private const PREFIJO = 'data:image/png;base64,';

    /**
     * Una firma válida es una imagen PNG en base64 (data URI), como la genera el lienzo del navegador.
     */
    public static function esValida(?string $dataUri): bool
    {
        if (!$dataUri || !str_starts_with($dataUri, self::PREFIJO)) {
            return false;
        }

        $binario = base64_decode(substr($dataUri, strlen(self::PREFIJO)), true);

        return $binario !== false && str_starts_with($binario, "\x89PNG");
    }
}

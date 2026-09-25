<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Aprobación de cotizaciones por el cliente
    |--------------------------------------------------------------------------
    */

    // Correos internos (separados por coma) que reciben la orden de servicio
    // cuando un cliente aprueba y firma una cotización. Ej: "simdeinfo@...,admon@..."
    'correos_aprobacion' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('COTIZACION_CORREOS_APROBACION', ''))
    ))),

    // Si es true, al enviar una cotización al cliente también se envía una copia (con el PDF y los
    // adjuntos, SIN el enlace de aprobación) a los correos de arriba.
    'enviar_copia_internos' => filter_var(env('COTIZACION_ENVIAR_COPIA_INTERNOS', false), FILTER_VALIDATE_BOOLEAN),

    // Vigencia del enlace de aprobación cuando la cotización no tiene fecha de vencimiento
    'dias_vigencia_enlace' => (int) env('COTIZACION_DIAS_VIGENCIA_ENLACE', 30),

    // URL base del frontend para armar el enlace del correo (sin "/" final ni "#").
    // Si no se define, se deduce de la URL del backend (.../backend/public -> .../frontend/build).
    'frontend_url' => env('FRONTEND_URL'),

];

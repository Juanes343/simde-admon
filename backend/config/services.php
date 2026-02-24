<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'secret' => env('MAILGUN_SECRET'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /**
     * =====================================================================
     * CONFIGURACIÓN DATAICO - FACTURACIÓN ELECTRÓNICA
     * =====================================================================
     * 
     * Credenciales de conexión a la API de DataIco para facturación 
     * electrónica en Colombia. Contiene parámetros para producción y pruebas.
     */
    'dataico' => [
        // Identificador único de la cuenta del cliente en DataIco (PRODUCCIÓN)
        'dataico_account_id' => env('DATAICO_ACCOUNT_ID', '936111eb-bbd2-4752-8b6e-fdc1d24f8e96'),
        
        // Token de autenticación para las peticiones a DataIco
        'token' => env('DATAICO_TOKEN', 'ab95a878f7ea1a22410f37e1de209deb'),
        
        // URL base del API de DataIco (sin trailing slash)
        'base_url' => env('DATAICO_BASE_URL', 'https://api.dataico.com/direct/dataico_api/v2'),
        
        // Tipo de envío: 'PRODUCCION' o 'PRUEBAS'
        'tipo_envio' => env('DATAICO_TIPO_ENVIO', 'PRODUCCION'),
        
        // Número de resolución para PRODUCCIÓN (debe ser actualizado con la resolución real)
        'resolucion_produccion' => env('DATAICO_RESOLUCION_PRODUCCION', '18764096453598'),
        
        // Número de resolución para PRUEBAS (comentado, solo para referencia)
        'resolucion_pruebas' => env('DATAICO_RESOLUCION_PRUEBAS', '18760000000001'),
        
        // Habilitar el envío directo a la DIAN
        'Envio_Dian' => env('DATAICO_ENVIO_DIAN', true),
        
        /**
         * CREDENCIALES ANTIGUAS DE PRUEBAS (COMENTADAS PARA REFERENCIA)
         * 
         * Para volver a pruebas, usa estos valores en el .env:
         * - DATAICO_ACCOUNT_ID=01808624-3175-8838-83d4-1db98f4da325
         * - DATAICO_TOKEN=token_anterior
         * - DATAICO_TIPO_ENVIO=PRUEBAS
         * - DATAICO_RESOLUCION_PRUEBAS=18760000000001
         */
    ],

];
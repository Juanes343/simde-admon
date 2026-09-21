<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Solicitud de firma de órdenes de servicio
    |--------------------------------------------------------------------------
    */

    // Días de vigencia del enlace de firma que se envía al cliente
    'dias_vigencia_firma' => (int) env('ORDEN_DIAS_VIGENCIA_FIRMA', 7),

];

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Las órdenes creadas por aprobación de una cotización antes de que la orden heredara la firma
     * quedaron sin firma propia. Se les copia la firma de su cotización (no toca las que ya tienen firma).
     */
    public function up(): void
    {
        DB::table('cotizaciones')
            ->whereNotNull('orden_servicio_id')
            ->whereNotNull('fecha_firma')
            ->get(['orden_servicio_id', 'firma_cliente', 'fecha_firma'])
            ->each(function ($cotizacion) {
                DB::table('ordenes_servicio')
                    ->where('orden_servicio_id', $cotizacion->orden_servicio_id)
                    ->whereNull('fecha_firma')
                    ->update([
                        'firma_tercero' => $cotizacion->firma_cliente,
                        'fecha_firma'   => $cotizacion->fecha_firma,
                    ]);
            });
    }

    public function down(): void
    {
        // Migración de datos: no se revierte
    }
};

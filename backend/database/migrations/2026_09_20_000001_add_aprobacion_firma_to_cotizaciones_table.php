<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            // Enlace de aprobación enviado al cliente (se guarda el hash SHA-256 del token)
            $table->string('token_aprobacion', 64)->nullable()->unique()->after('orden_servicio_id');
            $table->dateTime('token_aprobacion_expira_en')->nullable()->after('token_aprobacion');

            // Datos con los que se crea la orden de servicio cuando el cliente aprueba
            $table->json('datos_orden')->nullable()->after('token_aprobacion_expira_en')
                  ->comment('fecha_inicio, fecha_fin, periodo_facturacion_dias, sw_prorroga_automatica, porcentaje_soltec, porcentaje_ret_fuente');

            // Aprobación y firma del cliente
            $table->text('firma_cliente')->nullable()->after('datos_orden')->comment('Imagen de la firma en base64');
            $table->string('firmante_nombre', 150)->nullable()->after('firma_cliente');
            $table->string('firmante_documento', 50)->nullable()->after('firmante_nombre');
            $table->string('firma_ip', 45)->nullable()->after('firmante_documento');
            $table->dateTime('fecha_firma')->nullable()->after('firma_ip');

            // Rechazo desde el enlace del cliente
            $table->text('motivo_rechazo')->nullable()->after('fecha_firma');
            $table->dateTime('fecha_rechazo')->nullable()->after('motivo_rechazo');
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn([
                'token_aprobacion',
                'token_aprobacion_expira_en',
                'datos_orden',
                'firma_cliente',
                'firmante_nombre',
                'firmante_documento',
                'firma_ip',
                'fecha_firma',
                'motivo_rechazo',
                'fecha_rechazo',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar campos a fac_facturas si no existen
        Schema::table('fac_facturas', function (Blueprint $table) {
            // Verificar y agregar columnas necesarias
            if (!Schema::hasColumn('fac_facturas', 'estado_electronico')) {
                $table->string('estado_electronico', 50)->nullable()
                    ->comment('ENVIADA, ACEPTADA, RECHAZADA, ERROR, EN_PROCESO');
            }
            
            if (!Schema::hasColumn('fac_facturas', 'cufe')) {
                $table->text('cufe')->nullable()
                    ->comment('Código Único de Facturación Electrónica del DIAN');
            }
            
            if (!Schema::hasColumn('fac_facturas', 'uuid_dataico')) {
                $table->text('uuid_dataico')->nullable()
                    ->comment('UUID único de la factura en DataIco');
            }
            
            if (!Schema::hasColumn('fac_facturas', 'response_dataico')) {
                $table->longText('response_dataico')->nullable()
                    ->comment('Respuesta JSON completa de DataIco');
            }
            
            if (!Schema::hasColumn('fac_facturas', 'fecha_respuesta_dataico')) {
                $table->timestamp('fecha_respuesta_dataico')->nullable()
                    ->comment('Fecha de respuesta de DataIco');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fac_facturas', function (Blueprint $table) {
            // Solo eliminar si fueron creadas por esta migración
            if (Schema::hasColumn('fac_facturas', 'estado_electronico')) {
                $table->dropColumn('estado_electronico');
            }
            if (Schema::hasColumn('fac_facturas', 'cufe')) {
                $table->dropColumn('cufe');
            }
            if (Schema::hasColumn('fac_facturas', 'uuid_dataico')) {
                $table->dropColumn('uuid_dataico');
            }
            if (Schema::hasColumn('fac_facturas', 'response_dataico')) {
                $table->dropColumn('response_dataico');
            }
            if (Schema::hasColumn('fac_facturas', 'fecha_respuesta_dataico')) {
                $table->dropColumn('fecha_respuesta_dataico');
            }
        });
    }
};

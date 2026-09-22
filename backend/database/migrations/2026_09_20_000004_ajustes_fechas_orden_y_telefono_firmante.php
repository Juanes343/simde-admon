<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La orden que se crea al aprobar una cotización queda SIN fechas: quien la edita las completa después.
        // Mientras no tenga fechas no aparece como facturable.
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->date('fecha_inicio')->nullable()->comment('Fecha de inicio de la orden')->change();
            $table->date('fecha_fin')->nullable()->comment('Fecha de fin de la orden')->change();
        });

        // Celular o teléfono del cliente que firma
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->string('firmante_telefono', 30)->nullable()->after('firmante_documento');
        });
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->string('firmante_telefono', 30)->nullable()->after('firmante_documento');
        });

        // Los datos de la orden ya no se piden al enviar la cotización (se completan al editar la orden)
        if (Schema::hasColumn('cotizaciones', 'datos_orden')) {
            Schema::table('cotizaciones', function (Blueprint $table) {
                $table->dropColumn('datos_orden');
            });
        }
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn('firmante_telefono');
        });
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->dropColumn('firmante_telefono');
        });

        // Para volver a exigir fechas, las órdenes sin fecha toman la de su creación
        DB::statement('UPDATE ordenes_servicio SET fecha_inicio = COALESCE(fecha_inicio, created_at::date), fecha_fin = COALESCE(fecha_fin, created_at::date)');

        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->date('fecha_inicio')->nullable(false)->comment('Fecha de inicio de la orden')->change();
            $table->date('fecha_fin')->nullable(false)->comment('Fecha de fin de la orden')->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas_externas', function (Blueprint $table) {
            $table->id();
            $table->char('empresa_id', 2);
            $table->string('prefijo', 4);
            $table->integer('factura_fiscal');
            $table->char('estado', 1);
            $table->integer('usuario_id');
            $table->timestamp('fecha_registro');
            $table->decimal('total_factura', 12, 2)->default(0);
            $table->decimal('gravamen', 12, 2)->default(0);
            $table->decimal('valor_cargos', 12, 2)->default(0);
            $table->decimal('valor_cuota_paciente', 12, 2)->default(0);
            $table->decimal('valor_cuota_moderadora', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->integer('plan_id')->nullable();
            $table->string('tipo_id_tercero', 3);
            $table->string('tercero_id', 32);
            $table->char('sw_clase_factura', 1)->default('0');
            $table->text('concepto')->nullable();
            $table->decimal('total_capitacion_real', 12, 2)->nullable()->default(0);
            $table->integer('documento_id')->nullable();
            $table->char('tipo_factura', 1);
            $table->integer('documento_contable_id')->nullable();
            $table->decimal('saldo', 12, 2)->nullable()->default(0);
            $table->date('fecha_vencimiento_factura')->nullable();
            $table->decimal('retencion_fuente', 12, 2)->nullable();
            $table->integer('sw_proceso')->nullable();
            $table->string('rango', 40)->nullable();
            $table->char('sw_imp_copia', 1)->default('0');
            $table->text('observacion')->nullable();
            $table->decimal('impuesto_cree', 12, 2)->default(0);
            $table->decimal('reteica', 12, 2)->default(0);
            $table->char('sw_factory', 1)->default('0');
            $table->char('sw_dificil_cobro', 1)->default('0');
            $table->char('sw_proceso_juridico', 1)->default('0');
            $table->char('sw_deterioro', 1)->default('0');
            $table->decimal('impuesto_4x100', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['empresa_id', 'prefijo', 'factura_fiscal'], 'facturas_externas_unique');
            $table->index(['tipo_id_tercero', 'tercero_id']);
            $table->index('fecha_registro');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas_externas');
    }
};

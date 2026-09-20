<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_items', function (Blueprint $table) {
            $table->bigIncrements('item_id');
            $table->unsignedBigInteger('cotizacion_id');
            $table->unsignedBigInteger('servicio_id')->nullable();
            $table->string('referencia', 100)->nullable();
            $table->text('descripcion');
            $table->decimal('cantidad', 10, 2)->default(1);
            $table->string('tipo_unidad', 20)->default('UNIDAD');
            $table->decimal('precio_unitario', 15, 2)->default(0);
            $table->decimal('porcentaje_descuento', 5, 2)->default(0);
            $table->decimal('porcentaje_ret_fuente', 5, 2)->nullable();
            $table->unsignedBigInteger('impuesto_id')->nullable();
            // subtotal = cantidad * precio_unitario (sin descuento ni impuesto)
            $table->decimal('subtotal', 15, 2)->default(0);
            // total = subtotal - descuento + impuesto - retencion
            $table->decimal('total', 15, 2)->default(0);
            $table->integer('orden')->default(0);
            $table->char('estado', 1)->default('1');
            $table->decimal('porcentaje_soltec', 5, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('cotizacion_id')
                  ->references('cotizacion_id')
                  ->on('cotizaciones')
                  ->cascadeOnDelete();

            $table->foreign('servicio_id')
                  ->references('servicio_id')
                  ->on('servicios')
                  ->nullOnDelete();

            $table->foreign('impuesto_id')
                  ->references('impuesto_id')
                  ->on('impuestos')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_items');
    }
};

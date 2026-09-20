<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->bigIncrements('cotizacion_id');
            $table->string('numero_cotizacion', 50)->unique();
            $table->string('tipo_id_tercero', 20);
            $table->string('tercero_id', 32);
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento')->nullable();
            $table->string('metodo_pago', 50)->nullable();
            $table->string('tipo_pago', 50)->nullable();
            $table->string('orden_compra', 100)->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('descuento_total', 15, 2)->default(0);
            $table->decimal('impuestos_total', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->text('notas')->nullable();
            // Estados: borrador, enviada, aprobada, rechazada, vencida, convertida
            $table->string('sw_estado', 20)->default('borrador');
            $table->unsignedBigInteger('orden_servicio_id')->nullable();
            $table->unsignedBigInteger('usuario_id');
            $table->timestamps();

            $table->foreign('orden_servicio_id')
                  ->references('orden_servicio_id')
                  ->on('ordenes_servicio')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};

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
        Schema::create('ordenes_servicio', function (Blueprint $table) {
            $table->id('orden_servicio_id');
            
            // Relación con tercero
            $table->string('tipo_id_tercero', 20);
            $table->string('tercero_id', 32);
            
            // Información de la orden
            $table->string('numero_orden', 50)->unique()->comment('Número de orden generado');
            $table->date('fecha_inicio')->comment('Fecha de inicio de la orden');
            $table->date('fecha_fin')->comment('Fecha de fin de la orden');
            $table->string('sw_prorroga_automatica', 1)->default('0')->comment('0=No, 1=Sí');
            $table->integer('periodo_facturacion_dias')->default(30)->comment('Cada cuántos días se factura');
            $table->decimal('porcentaje_soltec', 5, 2)->default(0)->comment('Porcentaje Soltec');
            
            // Estado y control
            $table->string('sw_estado', 1)->default('1')->comment('1=Activo, 0=Inactivo');
            $table->text('observaciones')->nullable();
            
            // Auditoría
            $table->unsignedBigInteger('usuario_id');
            $table->timestamps();
            
            // Índices
            $table->index(['tipo_id_tercero', 'tercero_id']);
            $table->index('fecha_inicio');
            $table->index('fecha_fin');
            $table->index('sw_estado');
            
            // Llaves foráneas
            $table->foreign('usuario_id')->references('usuario_id')->on('system_usuarios');
            $table->foreign(['tipo_id_tercero', 'tercero_id'])
                  ->references(['tipo_id_tercero', 'tercero_id'])
                  ->on('terceros');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes_servicio');
    }
};

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
        Schema::create('orden_servicio_items', function (Blueprint $table) {
            $table->id('item_id');
            $table->unsignedBigInteger('orden_servicio_id');
            $table->unsignedBigInteger('servicio_id');
            
            // Datos del servicio al momento de la orden
            $table->string('nombre_servicio', 100);
            $table->text('descripcion')->nullable();
            $table->decimal('cantidad', 10, 2)->default(1);
            $table->string('tipo_unidad', 20);
            $table->decimal('precio_unitario', 15, 2);
            $table->decimal('subtotal', 15, 2)->comment('cantidad * precio_unitario');
            
            // Control
            $table->integer('orden')->default(0)->comment('Orden de visualización');
            $table->timestamps();
            
            // Índices
            $table->index('orden_servicio_id');
            $table->index('servicio_id');
            
            // Llaves foráneas
            $table->foreign('orden_servicio_id')
                  ->references('orden_servicio_id')
                  ->on('ordenes_servicio')
                  ->onDelete('cascade');
            $table->foreign('servicio_id')
                  ->references('servicio_id')
                  ->on('servicios');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_servicio_items');
    }
};

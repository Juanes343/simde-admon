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
        Schema::create('servicios', function (Blueprint $table) {
            $table->id('servicio_id');
            $table->string('nombre_servicio', 100);
            $table->text('descripcion')->nullable();
            $table->decimal('cantidad', 10, 2)->nullable()->comment('Cantidad por defecto');
            $table->enum('tipo_unidad', ['UNIDAD', 'HORAS'])->default('UNIDAD');
            $table->decimal('precio_unitario', 15, 2)->default(0);
            $table->char('sw_estado', 1)->default('1')->comment('1=Activo, 0=Inactivo');
            $table->unsignedBigInteger('usuario_id');
            $table->timestamp('fecha_registro')->useCurrent();
            
            // Foreign key
            $table->foreign('usuario_id')
                  ->references('usuario_id')
                  ->on('system_usuarios')
                  ->onDelete('restrict');
            
            // Indexes
            $table->index('nombre_servicio');
            $table->index('sw_estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};

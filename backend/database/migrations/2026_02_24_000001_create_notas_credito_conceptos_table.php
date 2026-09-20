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
        Schema::create('notas_credito_conceptos', function (Blueprint $table) {
            $table->id();
            $table->string('empresa_id', 3)->index(); // FK a empresas
            $table->string('descripcion', 255)->nullable();
            $table->enum('sw_naturaleza', ['C', 'D'])->default('C')->comment('C=Crédito, D=Débito');
            $table->boolean('sw_activo')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices compuestos
            $table->index(['empresa_id', 'sw_activo']);
            $table->unique(['empresa_id', 'descripcion'], 'uk_empresa_descripcion');
            
            // Foreign key
            $table->foreign('empresa_id')
                  ->references('empresa_id')
                  ->on('empresas')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notas_credito_conceptos');
    }
};

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
        Schema::create('documentos', function (Blueprint $table) {
            $table->integer('documento_id')->primary();
            $table->char('empresa_id', 2);
            $table->string('tipo_doc_general_id', 10);
            $table->string('prefijo', 4);
            $table->char('sw_estado', 1)->default('1');
            $table->bigInteger('numeracion')->default(0);
            $table->integer('numero_digitos')->default(9);
            $table->string('texto1', 255)->nullable();
            $table->string('texto2', 255)->nullable();
            $table->string('texto3', 255)->nullable();
            $table->text('mensaje')->nullable();
            $table->string('descripcion', 255)->nullable();
            $table->integer('sw_contabiliza')->default(1);
            $table->text('pie_pagina_factura')->nullable();
            $table->string('abr_contabilidad', 10)->nullable();
            $table->integer('sw_costos')->default(0);
            $table->integer('usuario_id');
            $table->timestamp('fecha_registro')->useCurrent();
            $table->integer('sw_doc_bodega')->default(0);
            $table->integer('sw_manejo_costo')->default(0);
            $table->integer('sw_automatico')->default(0);
            $table->integer('sw_estado_interfazado')->default(0);
            $table->integer('sw_total_costo_sistema')->default(1);
            $table->string('tipo_comprobante', 10)->nullable();
            $table->integer('clase_documento')->nullable();
            $table->integer('sw_cuentas_servicios')->default(0);
            $table->string('resolucion_dian', 50)->nullable();
            $table->date('fecha_inicio_resolucion')->nullable();
            $table->date('fecha_fin_resolucion')->nullable();
            $table->bigInteger('rango_resolucion_inicio')->nullable();
            $table->bigInteger('rango_resolucion_fin')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};

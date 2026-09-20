<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateControlPagosTable extends Migration
{
    public function up()
    {
        Schema::create('control_pagos', function (Blueprint $table) {
            $table->id();
            $table->string('empresa_id', 10);
            $table->unsignedBigInteger('factura_fiscal_id');
            $table->string('tipo_id_tercero', 5);
            $table->string('tercero_id', 20);
            $table->decimal('valor_pago', 15, 2)->default(0);
            $table->decimal('valor_retencion', 15, 2)->default(0);
            $table->date('fecha_pago');
            $table->string('tipo_pago', 30)->default('EFECTIVO');
            $table->text('observaciones')->nullable();
            $table->tinyInteger('estado')->default(1)->comment('1=activo, 0=anulado');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamps();

            $table->foreign('factura_fiscal_id')
                  ->references('factura_fiscal_id')
                  ->on('fac_facturas')
                  ->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('control_pagos');
    }
}
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
        // Tabla principal de Notas Crédito / Débito
        Schema::create('notas_credito', function (Blueprint $table) {
            $table->id();
            $table->string('empresa_id', 2)->index();
            $table->string('prefijo', 10)->index();
            $table->bigInteger('nota_credito_id')->index();
            
            // Referencia a la factura
            $table->string('prefijo_factura', 10);
            $table->bigInteger('factura_fiscal');
            $table->string('tipo_factura', 2)->nullable();
            
            // Datos del tercero (cliente)
            $table->string('tipo_id_tercero', 3);
            $table->string('tercero_id', 20);
            
            // Detalles de la nota
            $table->unsignedBigInteger('concepto_id')->nullable();
            $table->decimal('valor_nota', 15, 2);
            $table->text('observacion')->nullable();
            
            // Clasificación
            $table->string('tipo_nota', 10)->default('CREDITO')->comment('CREDITO o DEBITO');
            $table->string('alcance', 10)->default('TOTAL')->comment('TOTAL o PARCIAL');
            
            // Estado y auditoría DataIco
            $table->string('estado', 20)->default('PENDIENTE')->comment('PENDIENTE, ENVIADO, ACEPTADO, RECHAZADO');
            $table->string('cufe', 100)->nullable()->unique();
            $table->string('uuid', 100)->nullable()->unique();
            $table->dateTime('fecha_envio')->nullable();
            $table->dateTime('fecha_aceptacion')->nullable();
            $table->json('respuesta_dataico')->nullable();
            
            $table->timestamps();

            // Llaves foráneas
            $table->foreign('concepto_id')->references('id')->on('notas_credito_conceptos')->nullOnDelete();
            
            // Índices compuestos
            $table->unique(['empresa_id', 'prefijo', 'nota_credito_id'], 'uk_nota_credito_numero');
            $table->index(['prefijo_factura', 'factura_fiscal'], 'idx_factura_referencia');
        });

        // Tabla para el detalle/items de las Notas Crédito (Opcional pero recomendada)
        Schema::create('notas_credito_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nota_credito_id');
            
            $table->string('codigo_item', 50)->nullable();
            $table->string('descripcion', 255);
            $table->decimal('cantidad', 10, 2)->default(1);
            $table->decimal('precio_unitario', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('porcentaje_impuesto', 5, 2)->default(0);
            $table->decimal('valor_impuesto', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            
            $table->timestamps();

            // Llave foránea hacia la tabla principal
            $table->foreign('nota_credito_id')
                  ->references('id')
                  ->on('notas_credito')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notas_credito_items');
        Schema::dropIfExists('notas_credito');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corre la migración.
     */
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            // Clave primaria: empresa_id ('01', '02', etc.)
            $table->string('empresa_id', 2)->primary();
            
            // Identificación y Razón Social
            $table->string('tipo_id_tercero', 10)->default('NIT');
            $table->string('id', 20); // Código NIT (ej: 901300510)
            $table->string('digito_verificacion', 1)->default('0');
            $table->string('razon_social', 255);
            $table->string('representante_legal', 255)->nullable();
            
            // Salud / SGSSS
            $table->string('codigo_sgsss', 20)->nullable();
            
            // Ubicación (Relacionados/FK)
            $table->string('tipo_pais_id', 10)->default('CO');
            $table->string('tipo_dpto_id', 10);
            $table->string('tipo_mpio_id', 10);
            
            // Contacto
            $table->string('direccion', 500)->nullable();
            $table->string('telefonos', 100)->nullable();
            
            // Auditoría
            $table->timestamps();

            // Comentario para llaves foráneas:
            // Dependiendo de tu base de datos, estas deberían apuntar a:
            // $table->foreign('tipo_pais_id')->references('tipo_pais_id')->on('tipo_paises');
            // $table->foreign('tipo_dpto_id')->references('tipo_dpto_id')->on('tipo_departamentos');
            // $table->foreign('tipo_mpio_id')->references('tipo_mpio_id')->on('tipo_municipios');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};

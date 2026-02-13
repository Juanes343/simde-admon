<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tipo_id_terceros', function (Blueprint $table) {
            $table->string('tipo_id_tercero', 20)->primary();
            $table->string('descripcion', 100);
            $table->integer('indice_de_orden')->default(0);
            $table->string('sw_dian', 10)->nullable();
            $table->integer('tipo_id_conexus')->nullable();
            $table->timestamps();
        });

        // Insertar datos iniciales
        DB::table('tipo_id_terceros')->insert([
            ['tipo_id_tercero' => 'SV', 'descripcion' => 'Salvo Conducto', 'indice_de_orden' => 0, 'sw_dian' => null, 'tipo_id_conexus' => null],
            ['tipo_id_tercero' => 'RC', 'descripcion' => 'REGISTRO CIVIL', 'indice_de_orden' => 3, 'sw_dian' => 'RC', 'tipo_id_conexus' => 11],
            ['tipo_id_tercero' => 'TI', 'descripcion' => 'TARJETA DE IDENTIDAD', 'indice_de_orden' => 2, 'sw_dian' => 'TI', 'tipo_id_conexus' => 12],
            ['tipo_id_tercero' => 'CC', 'descripcion' => 'CEDULA DE CIUDADANIA', 'indice_de_orden' => 1, 'sw_dian' => 'CC', 'tipo_id_conexus' => 13],
            ['tipo_id_tercero' => 'CE', 'descripcion' => 'CEDULA DE EXTRANJERIA', 'indice_de_orden' => 2, 'sw_dian' => 'CE', 'tipo_id_conexus' => 22],
            ['tipo_id_tercero' => 'NIT', 'descripcion' => 'N. IDENTIFICACION TRIBUTARIO', 'indice_de_orden' => 0, 'sw_dian' => 'NIT', 'tipo_id_conexus' => 31],
            ['tipo_id_tercero' => 'PA', 'descripcion' => 'PASAPORTE', 'indice_de_orden' => 4, 'sw_dian' => 'PASAPORTE', 'tipo_id_conexus' => 41],
            ['tipo_id_tercero' => 'PE', 'descripcion' => 'P. especial de permanencia', 'indice_de_orden' => 0, 'sw_dian' => null, 'tipo_id_conexus' => null],
            ['tipo_id_tercero' => 'CD', 'descripcion' => 'Carné Diplomático', 'indice_de_orden' => 0, 'sw_dian' => null, 'tipo_id_conexus' => null],
            ['tipo_id_tercero' => '0', 'descripcion' => 'TERCERO GENERICO', 'indice_de_orden' => 9, 'sw_dian' => null, 'tipo_id_conexus' => null],
            ['tipo_id_tercero' => 'AS', 'descripcion' => 'ADULTO SIN IDENTIFICACION', 'indice_de_orden' => 6, 'sw_dian' => null, 'tipo_id_conexus' => null],
            ['tipo_id_tercero' => 'CN', 'descripcion' => 'Certificado de Nacido vivo', 'indice_de_orden' => 0, 'sw_dian' => null, 'tipo_id_conexus' => null],
            ['tipo_id_tercero' => 'MS', 'descripcion' => 'MENOR SIN IDENTIFICACION', 'indice_de_orden' => 5, 'sw_dian' => null, 'tipo_id_conexus' => null],
            ['tipo_id_tercero' => 'PT', 'descripcion' => 'Permiso Transitorio', 'indice_de_orden' => 0, 'sw_dian' => null, 'tipo_id_conexus' => null],
            ['tipo_id_tercero' => 'SC', 'descripcion' => 'Salvoconducto', 'indice_de_orden' => 0, 'sw_dian' => null, 'tipo_id_conexus' => null],
            ['tipo_id_tercero' => 'DE', 'descripcion' => 'DE', 'indice_de_orden' => 11, 'sw_dian' => null, 'tipo_id_conexus' => null],
            ['tipo_id_tercero' => 'PEP', 'descripcion' => 'Permiso Especial de Permanencia', 'indice_de_orden' => 0, 'sw_dian' => 'PEP', 'tipo_id_conexus' => 47],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_id_terceros');
    }
};

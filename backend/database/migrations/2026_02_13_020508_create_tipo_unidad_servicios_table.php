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
        Schema::create('tipo_unidad_servicios', function (Blueprint $table) {
            $table->string('codigo', 20)->primary();
            $table->string('descripcion', 100);
            $table->integer('orden')->default(0);
            $table->string('sw_estado', 1)->default('1');
            $table->timestamps();
        });

        // Insertar datos iniciales
        DB::table('tipo_unidad_servicios')->insert([
            ['codigo' => 'UNIDAD', 'descripcion' => 'Unidad', 'orden' => 1, 'sw_estado' => '1'],
            ['codigo' => 'HORAS', 'descripcion' => 'Horas', 'orden' => 2, 'sw_estado' => '1'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_unidad_servicios');
    }
};

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
        Schema::create('tipo_pais', function (Blueprint $table) {
            $table->string('tipo_pais_id', 4)->primary();
            $table->string('pais', 100);
            $table->char('bloqueado_edicion', 1)->default('0');
            $table->string('tipo_pais_alterno', 4)->nullable();
            $table->timestamps();
        });

        Schema::create('tipo_dptos', function (Blueprint $table) {
            $table->string('tipo_pais_id', 4);
            $table->string('tipo_dpto_id', 4);
            $table->string('departamento', 100);
            $table->string('codigo_dpto_dian', 10)->nullable();
            $table->string('nombre_dpto_dian', 100)->nullable();
            $table->timestamps();

            $table->primary(['tipo_pais_id', 'tipo_dpto_id']);
            $table->foreign('tipo_pais_id')->references('tipo_pais_id')->on('tipo_pais')->onDelete('cascade');
        });

        Schema::create('tipo_mpios', function (Blueprint $table) {
            $table->string('tipo_pais_id', 4);
            $table->string('tipo_dpto_id', 4);
            $table->string('tipo_mpio_id', 4);
            $table->string('municipio', 100);
            $table->string('codigo_mpio_dian', 10)->nullable();
            $table->string('nombre_mpio_dian', 100)->nullable();
            $table->timestamps();

            $table->primary(['tipo_pais_id', 'tipo_dpto_id', 'tipo_mpio_id']);
            $table->foreign(['tipo_pais_id', 'tipo_dpto_id'])->references(['tipo_pais_id', 'tipo_dpto_id'])->on('tipo_dptos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_mpios');
        Schema::dropIfExists('tipo_dptos');
        Schema::dropIfExists('tipo_pais');
    }
};

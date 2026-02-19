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
        Schema::table('terceros', function (Blueprint $table) {
            // First, ensure columns are correct lengths to match the location tables
            $table->string('tipo_pais_id', 4)->change();
            $table->string('tipo_dpto_id', 4)->change();
            $table->string('tipo_mpio_id', 4)->change();

            // Add the composite foreign keys
            $table->foreign('tipo_pais_id')->references('tipo_pais_id')->on('tipo_pais')->onDelete('cascade');
            $table->foreign(['tipo_pais_id', 'tipo_dpto_id'])->references(['tipo_pais_id', 'tipo_dpto_id'])->on('tipo_dptos')->onDelete('cascade');
            $table->foreign(['tipo_pais_id', 'tipo_dpto_id', 'tipo_mpio_id'])->references(['tipo_pais_id', 'tipo_dpto_id', 'tipo_mpio_id'])->on('tipo_mpios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('terceros', function (Blueprint $table) {
            $table->dropForeign(['tipo_pais_id', 'tipo_dpto_id', 'tipo_mpio_id']);
            $table->dropForeign(['tipo_pais_id', 'tipo_dpto_id']);
            $table->dropForeign(['tipo_pais_id']);
        });
    }
};

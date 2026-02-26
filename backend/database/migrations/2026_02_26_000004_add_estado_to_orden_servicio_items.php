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
        Schema::table('orden_servicio_items', function (Blueprint $table) {
            $table->char('estado', 1)->default('1')->comment('1=Activo, 0=Inactivo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden_servicio_items', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};

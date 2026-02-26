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
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->decimal('porcentaje_ret_fuente', 5, 2)
                  ->default(0)
                  ->after('porcentaje_soltec')
                  ->comment('Porcentaje de retención en la fuente (0.1, 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 6, 7, 10, 11, 20)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->dropColumn('porcentaje_ret_fuente');
        });
    }
};
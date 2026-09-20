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
            $table->decimal('porcentaje_descuento', 5, 2)
                  ->default(0)
                  ->nullable()
                  ->after('porcentaje_soltec')
                  ->comment('Porcentaje de descuento (%) aplicado al item, heredado de la cotización');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden_servicio_items', function (Blueprint $table) {
            $table->dropColumn('porcentaje_descuento');
        });
    }
};

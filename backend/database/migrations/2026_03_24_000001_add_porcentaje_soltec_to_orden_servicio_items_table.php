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
            $table->decimal('porcentaje_soltec', 5, 2)
                  ->default(0)
                  ->nullable()
                  ->after('observaciones')
                  ->comment('Porcentaje Soltec (%) visual por item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden_servicio_items', function (Blueprint $table) {
            $table->dropColumn('porcentaje_soltec');
        });
    }
};

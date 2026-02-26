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
        Schema::table('fac_facturas', function (Blueprint $table) {
            if (!Schema::hasColumn('fac_facturas', 'fecha_periodo_inicio')) {
                $table->date('fecha_periodo_inicio')->nullable()->after('observacion');
            }
            if (!Schema::hasColumn('fac_facturas', 'fecha_periodo_fin')) {
                $table->date('fecha_periodo_fin')->nullable()->after('fecha_periodo_inicio');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fac_facturas', function (Blueprint $table) {
            if (Schema::hasColumn('fac_facturas', 'fecha_periodo_inicio')) {
                $table->dropColumn('fecha_periodo_inicio');
            }
            if (Schema::hasColumn('fac_facturas', 'fecha_periodo_fin')) {
                $table->dropColumn('fecha_periodo_fin');
            }
        });
    }
};
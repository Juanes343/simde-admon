<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fac_facturas', function (Blueprint $table) {
            if (!Schema::hasColumn('fac_facturas', 'porcentaje_ret_fuente')) {
                $table->decimal('porcentaje_ret_fuente', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('fac_facturas', 'valor_ret_fuente')) {
                $table->decimal('valor_ret_fuente', 18, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('fac_facturas', function (Blueprint $table) {
            $table->dropColumn(['porcentaje_ret_fuente', 'valor_ret_fuente']);
        });
    }
};
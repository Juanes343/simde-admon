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
            $table->text('observaciones')
                  ->nullable()
                  ->after('orden')
                  ->comment('Observaciones específicas del item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden_servicio_items', function (Blueprint $table) {
            $table->dropColumn('observaciones');
        });
    }
};

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
        Schema::table('servicios', function (Blueprint $table) {
            $table->unsignedBigInteger('impuesto_id')->nullable()->after('precio_unitario');
            
            // Foreign key
            $table->foreign('impuesto_id')
                  ->references('impuesto_id')
                  ->on('impuestos')
                  ->onDelete('restrict');
            
            // Index
            $table->index('impuesto_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            $table->dropForeign(['impuesto_id']);
            $table->dropColumn('impuesto_id');
        });
    }
};
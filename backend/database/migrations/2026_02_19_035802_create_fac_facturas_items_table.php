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
        Schema::create('fac_facturas_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('factura_fiscal_id'); // Referencia al ID surrogate de fac_facturas
            $table->integer('orden_servicio_id');
            $table->integer('item_id'); // Referencia a item_id de orden_servicio_items
            
            $table->foreign('factura_fiscal_id')->references('factura_fiscal_id')->on('fac_facturas')->onDelete('cascade');
            // Nota: item_id y orden_servicio_id son llaves de otras tablas, 
            // pero no forzamos FK rígida si las llaves no son del mismo tipo (ej integer vs bigint).
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fac_facturas_items');
    }
};

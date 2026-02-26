<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('impuestos', function (Blueprint $table) {
            $table->id('impuesto_id');
            $table->string('nombre', 100);
            $table->decimal('porcentaje', 5, 2)->comment('Porcentaje del impuesto');
            $table->text('descripcion')->nullable();
            $table->char('sw_estado', 1)->default('1')->comment('1=Activo, 0=Inactivo');
            $table->timestamp('fecha_registro')->useCurrent();
            
            // Indexes
            $table->index('sw_estado');
            $table->index('porcentaje');
        });

        // Insertar impuestos base
        DB::table('impuestos')->insert([
            [
                'nombre' => 'IVA 0% (Exento)',
                'porcentaje' => 0.00,
                'descripcion' => 'Productos y servicios exentos de IVA',
                'sw_estado' => '1',
                'fecha_registro' => now(),
            ],
            [
                'nombre' => 'IVA 5%',
                'porcentaje' => 5.00,
                'descripcion' => 'IVA reducido del 5%',
                'sw_estado' => '1',
                'fecha_registro' => now(),
            ],
            [
                'nombre' => 'IVA 16%',
                'porcentaje' => 16.00,
                'descripcion' => 'IVA del 16%',
                'sw_estado' => '1',
                'fecha_registro' => now(),
            ],
            [
                'nombre' => 'IVA 19%',
                'porcentaje' => 19.00,
                'descripcion' => 'IVA general del 19%',
                'sw_estado' => '1',
                'fecha_registro' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('impuestos');
    }
};
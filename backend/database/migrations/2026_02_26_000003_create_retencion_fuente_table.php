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
        Schema::create('retencion_fuente', function (Blueprint $table) {
            $table->bigIncrements('retencion_id');
            $table->decimal('porcentaje', 5, 2)->unique()->comment('Porcentaje de retención');
            $table->string('descripcion', 100)->nullable()->comment('Descripción del porcentaje');
            $table->char('sw_estado', 1)->default('1')->comment('1=Activo, 0=Inactivo');
            $table->timestamp('fecha_registro')->useCurrent();
            
            $table->index('sw_estado');
            $table->index('porcentaje');
        });

        // Insertar datos iniciales
        DB::table('retencion_fuente')->insert([
            ['porcentaje' => 0, 'descripcion' => 'Sin retención', 'sw_estado' => '1'],
            ['porcentaje' => 0.1, 'descripcion' => 'Retención 0.1%', 'sw_estado' => '1'],
            ['porcentaje' => 0.5, 'descripcion' => 'Retención 0.5%', 'sw_estado' => '1'],
            ['porcentaje' => 1, 'descripcion' => 'Retención 1%', 'sw_estado' => '1'],
            ['porcentaje' => 1.5, 'descripcion' => 'Retención 1.5%', 'sw_estado' => '1'],
            ['porcentaje' => 2, 'descripcion' => 'Retención 2%', 'sw_estado' => '1'],
            ['porcentaje' => 2.5, 'descripcion' => 'Retención 2.5%', 'sw_estado' => '1'],
            ['porcentaje' => 3, 'descripcion' => 'Retención 3%', 'sw_estado' => '1'],
            ['porcentaje' => 3.5, 'descripcion' => 'Retención 3.5%', 'sw_estado' => '1'],
            ['porcentaje' => 4, 'descripcion' => 'Retención 4%', 'sw_estado' => '1'],
            ['porcentaje' => 6, 'descripcion' => 'Retención 6%', 'sw_estado' => '1'],
            ['porcentaje' => 7, 'descripcion' => 'Retención 7%', 'sw_estado' => '1'],
            ['porcentaje' => 10, 'descripcion' => 'Retención 10%', 'sw_estado' => '1'],
            ['porcentaje' => 11, 'descripcion' => 'Retención 11%', 'sw_estado' => '1'],
            ['porcentaje' => 20, 'descripcion' => 'Retención 20%', 'sw_estado' => '1'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retencion_fuente');
    }
};

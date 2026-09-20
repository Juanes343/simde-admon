<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('system_modulos')->insert([
            [
                'id'          => 'cotizaciones',
                'titulo'      => 'Cotizaciones',
                'icono'       => 'fa-file-alt',
                'descripcion' => 'Gestión de cotizaciones y conversión a órdenes de servicio.',
                'activo'      => true,
                'orden'       => 8,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('system_modulos')->where('id', 'cotizaciones')->delete();
    }
};

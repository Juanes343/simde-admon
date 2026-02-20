<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('documentos')->insert([
            'documento_id' => 4,
            'empresa_id' => '01',
            'tipo_doc_general_id' => 'FV01',
            'prefijo' => 'FE',
            'sw_estado' => '1',
            'numeracion' => 3598,
            'numero_digitos' => 9,
            'texto1' => 'INTERFAZADO ELECTRONICAMENTE CON EL SOFTWARE SIIS.',
            'texto2' => 'CONCESIONARIO: SIMDE S.A.S. NIT 901300510-0',
            'texto3' => 'Realizar el pago en la cuenta de CUENTA CORRIENTE N° 0861000100003408 - BANCO BBVA COLOMBIA. A nombre de: Soporte Implementación y Desarrollo sas Nit. 901.300.510.',
            'mensaje' => 'RESOLUCION DIAN 18764049992655',
            'descripcion' => 'FACTURACION ELECTRONICA',
            'sw_contabiliza' => 1,
            'sw_costos' => 1,
            'usuario_id' => 2,
            'sw_total_costo_sistema' => 1,
            'clase_documento' => 0,
            'resolucion_dian' => '18764096453598',
            'fecha_inicio_resolucion' => '2021-12-01',
            'fecha_fin_resolucion' => '2025-08-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

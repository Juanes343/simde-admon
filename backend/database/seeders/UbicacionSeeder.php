<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UbicacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Paises
        DB::table('tipo_pais')->insert([
            ['tipo_pais_id' => 'CO', 'pais' => 'COLOMBIA', 'bloqueado_edicion' => '1', 'tipo_pais_alterno' => null],
            ['tipo_pais_id' => 'US', 'pais' => 'ESTADOS UNIDOS', 'bloqueado_edicion' => '1', 'tipo_pais_alterno' => null],
        ]);

        // Departamentos
        DB::table('tipo_dptos')->insert([
            ['tipo_pais_id' => 'CO', 'tipo_dpto_id' => '76', 'departamento' => 'VALLE DEL CAUCA', 'codigo_dpto_dian' => '76', 'nombre_dpto_dian' => 'Valle del Cauca'],
            ['tipo_pais_id' => 'CO', 'tipo_dpto_id' => '11', 'departamento' => 'BOGOTA D.C.', 'codigo_dpto_dian' => '11', 'nombre_dpto_dian' => 'Bogotá D.C.'],
            ['tipo_pais_id' => 'CO', 'tipo_dpto_id' => '05', 'departamento' => 'ANTIOQUIA', 'codigo_dpto_dian' => '05', 'nombre_dpto_dian' => 'Antioquia'],
        ]);

        // Municipios
        DB::table('tipo_mpios')->insert([
            ['tipo_pais_id' => 'CO', 'tipo_dpto_id' => '76', 'tipo_mpio_id' => '001', 'municipio' => 'CALI', 'codigo_mpio_dian' => '76001', 'nombre_mpio_dian' => 'CALI'],
            ['tipo_pais_id' => 'CO', 'tipo_dpto_id' => '11', 'tipo_mpio_id' => '001', 'municipio' => 'BOGOTA D.C.', 'codigo_mpio_dian' => '11001', 'nombre_mpio_dian' => 'BOGOTA D.C.'],
            ['tipo_pais_id' => 'CO', 'tipo_dpto_id' => '05', 'tipo_mpio_id' => '001', 'municipio' => 'MEDELLIN', 'codigo_mpio_dian' => '05001', 'nombre_mpio_dian' => 'MEDELLIN'],
            ['tipo_pais_id' => 'CO', 'tipo_dpto_id' => '76', 'tipo_mpio_id' => '109', 'municipio' => 'BUENAVENTURA', 'codigo_mpio_dian' => '76109', 'nombre_mpio_dian' => 'BUENAVENTURA'],
        ]);
    }
}

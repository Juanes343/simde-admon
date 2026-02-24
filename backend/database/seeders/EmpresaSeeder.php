<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empresa;

class EmpresaSeeder extends Seeder
{
    /**
     * Corre el seeder de base de datos.
     */
    public function run(): void
    {
        Empresa::updateOrCreate(
            ['empresa_id' => '01'],
            [
                'tipo_id_tercero'     => 'NIT',
                'id'                  => '901300510',
                'digito_verificacion' => '0',
                'razon_social'        => 'SIMDE S.A.S.',
                'representante_legal' => 'DARLINSON RONDÓN CÓRDOBA',
                'codigo_sgsss'        => '1', // SGSSS
                'tipo_pais_id'        => 'CO',
                'tipo_dpto_id'        => '76', // Valle del Cauca (Ejemplo)
                'tipo_mpio_id'        => '001', // Cali (Ejemplo)
                'direccion'           => 'CALLE 8 6-79 OF 402',
                'telefonos'           => '4850430',
            ]
        );
    }
}
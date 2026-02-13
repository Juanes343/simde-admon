<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoIdTercero extends Model
{
    protected $table = 'tipo_id_terceros';
    protected $primaryKey = 'tipo_id_tercero';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tipo_id_tercero',
        'descripcion',
        'indice_de_orden',
        'sw_dian',
        'tipo_id_conexus',
    ];
}

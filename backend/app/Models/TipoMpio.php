<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoMpio extends Model
{
    use HasFactory;

    protected $table = 'tipo_mpios';
    protected $primaryKey = 'tipo_mpio_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tipo_pais_id',
        'tipo_dpto_id',
        'tipo_mpio_id',
        'municipio',
        'codigo_mpio_dian',
        'nombre_mpio_dian',
    ];

    public function departamento()
    {
        return $this->belongsTo(TipoDpto::class, 'tipo_dpto_id', 'tipo_dpto_id');
    }
}

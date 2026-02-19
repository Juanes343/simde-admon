<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoDpto extends Model
{
    use HasFactory;

    protected $table = 'tipo_dptos';
    protected $primaryKey = 'tipo_dpto_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tipo_pais_id',
        'tipo_dpto_id',
        'departamento',
        'codigo_dpto_dian',
        'nombre_dpto_dian',
    ];

    public function pais()
    {
        return $this->belongsTo(TipoPais::class, 'tipo_pais_id', 'tipo_pais_id');
    }

    public function municipios()
    {
        return $this->hasMany(TipoMpio::class, 'tipo_dpto_id', 'tipo_dpto_id');
    }
}

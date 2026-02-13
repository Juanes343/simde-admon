<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoUnidadServicio extends Model
{
    protected $table = 'tipo_unidad_servicios';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'descripcion',
        'orden',
        'sw_estado',
    ];

    /**
     * Scope para obtener solo tipos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('sw_estado', '1');
    }
}

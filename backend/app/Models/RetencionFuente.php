<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetencionFuente extends Model
{
    protected $table = 'retencion_fuente';
    protected $primaryKey = 'retencion_id';
    public $timestamps = false;

    protected $fillable = [
        'porcentaje',
        'descripcion',
        'sw_estado',
    ];

    protected $casts = [
        'porcentaje' => 'decimal:2',
        'fecha_registro' => 'datetime',
    ];

    /**
     * Scope para retenciones activas
     */
    public function scopeActivas($query)
    {
        return $query->where('sw_estado', '1')->orderBy('porcentaje', 'asc');
    }
}
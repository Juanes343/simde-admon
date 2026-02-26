<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Impuesto extends Model
{
    protected $table = 'impuestos';
    protected $primaryKey = 'impuesto_id';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'porcentaje',
        'descripcion',
        'sw_estado',
    ];

    protected $casts = [
        'porcentaje' => 'decimal:2',
        'fecha_registro' => 'datetime',
    ];

    /**
     * Scope para impuestos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('sw_estado', '1');
    }

    /**
     * Relación con servicios
     */
    public function servicios()
    {
        return $this->hasMany(Servicio::class, 'impuesto_id', 'impuesto_id');
    }
}

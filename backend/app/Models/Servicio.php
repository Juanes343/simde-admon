<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    protected $table = 'servicios';
    protected $primaryKey = 'servicio_id';
    public $timestamps = false;

    protected $fillable = [
        'nombre_servicio',
        'descripcion',
        'cantidad',
        'tipo_unidad',
        'precio_unitario',
        'impuesto_id',
        'sw_estado',
        'usuario_id',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'fecha_registro' => 'datetime',
    ];

    /**
     * Relación con el usuario que creó el servicio
     */
    public function usuario()
    {
        return $this->belongsTo(SystemUsuario::class, 'usuario_id', 'usuario_id');
    }

    /**
     * Relación con el impuesto
     */
    public function impuesto()
    {
        return $this->belongsTo(Impuesto::class, 'impuesto_id', 'impuesto_id');
    }

    /**
     * Scope para servicios activos
     */
    public function scopeActivos($query)
    {
        return $query->where('sw_estado', '1');
    }

    /**
     * Scope para búsqueda
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where(function($q) use ($termino) {
            $q->where('nombre_servicio', 'like', "%{$termino}%")
              ->orWhere('descripcion', 'like', "%{$termino}%");
        });
    }
}

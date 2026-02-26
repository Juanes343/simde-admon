<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenServicioItem extends Model
{
    protected $table = 'orden_servicio_items';
    protected $primaryKey = 'item_id';

    protected $fillable = [
        'orden_servicio_id',
        'servicio_id',
        'nombre_servicio',
        'descripcion',
        'cantidad',
        'tipo_unidad',
        'precio_unitario',
        'subtotal',
        'orden',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Relación con orden de servicio
     */
    public function ordenServicio()
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id', 'orden_servicio_id');
    }

    /**
     * Relación con servicio
     */
    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'servicio_id', 'servicio_id');
    }

    /**
     * Calcular subtotal automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->subtotal = $item->cantidad * $item->precio_unitario;
        });
    }
}

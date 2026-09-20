<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CotizacionItem extends Model
{
    protected $table      = 'cotizacion_items';
    protected $primaryKey = 'item_id';

    protected $fillable = [
        'cotizacion_id',
        'servicio_id',
        'referencia',
        'descripcion',
        'cantidad',
        'tipo_unidad',
        'precio_unitario',
        'porcentaje_descuento',
        'porcentaje_ret_fuente',
        'impuesto_id',
        'subtotal',
        'total',
        'orden',
        'estado',
        'porcentaje_soltec',
        'observaciones',
    ];

    protected $casts = [
        'cantidad'             => 'decimal:2',
        'precio_unitario'      => 'decimal:2',
        'porcentaje_descuento' => 'decimal:2',
        'porcentaje_ret_fuente'=> 'decimal:2',
        'subtotal'             => 'decimal:2',
        'total'                => 'decimal:2',
        'porcentaje_soltec'    => 'decimal:2',
    ];

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id', 'cotizacion_id');
    }

    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'servicio_id', 'servicio_id');
    }

    public function impuesto()
    {
        return $this->belongsTo(Impuesto::class, 'impuesto_id', 'impuesto_id');
    }
}

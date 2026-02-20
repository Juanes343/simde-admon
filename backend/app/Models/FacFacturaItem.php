<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacFacturaItem extends Model
{
    protected $table = 'fac_facturas_items';

    protected $fillable = [
        'factura_fiscal_id',
        'orden_servicio_id',
        'item_id',
    ];

    public function factura()
    {
        return $this->belongsTo(FacFactura::class, 'factura_fiscal_id', 'factura_fiscal_id');
    }

    public function ordenServicioItem()
    {
        return $this->belongsTo(OrdenServicioItem::class, 'item_id', 'item_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaCreditoItem extends Model
{
    protected $table = 'notas_credito_items';

    protected $fillable = [
        'nota_credito_id',
        'codigo_item',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'porcentaje_impuesto',
        'valor_impuesto',
        'total',
    ];

    public function notaCredito()
    {
        return $this->belongsTo(NotaCredito::class, 'nota_credito_id', 'id');
    }
}

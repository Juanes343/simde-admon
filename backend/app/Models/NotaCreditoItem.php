<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaCreditoItem extends Model
{
    protected $table = 'notas_credito_items';

    protected $fillable = [
        'nota_credito_id',
        'item_id',
        'valor',
    ];

    public function notaCredito()
    {
        return $this->belongsTo(NotaCredito::class, 'nota_credito_id', 'id');
    }
}

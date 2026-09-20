<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlPago extends Model
{
    protected $table = 'control_pagos';

    protected $fillable = [
        'empresa_id',
        'factura_fiscal_id',
        'tipo_id_tercero',
        'tercero_id',
        'valor_pago',
        'valor_retencion',
        'valor_ica',
        'valor_reteiva',
        'fecha_pago',
        'tipo_pago',
        'observaciones',
        'estado',
        'usuario_id',
    ];

    protected $casts = [
        'valor_pago'      => 'float',
        'valor_retencion' => 'float',
        'valor_ica'       => 'float',
        'valor_reteiva'   => 'float',
        'fecha_pago'      => 'date',
    ];

    public function tercero()
    {
        return $this->belongsTo(Tercero::class, 'tercero_id', 'tercero_id');
    }

    public function factura()
    {
        return $this->belongsTo(\App\Models\FacFactura::class, 'factura_fiscal_id', 'factura_fiscal_id');
    }
}

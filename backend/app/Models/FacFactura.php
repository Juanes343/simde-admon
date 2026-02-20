<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacFactura extends Model
{
    protected $table = 'fac_facturas';
    protected $primaryKey = 'factura_fiscal_id'; // Nuestro surrogate ID

    protected $fillable = [
        'empresa_id',
        'prefijo',
        'factura_fiscal',
        'estado',
        'usuario_id',
        'fecha_registro',
        'total_factura',
        'gravamen',
        'valor_cargos',
        'valor_cuota_paciente',
        'valor_cuota_moderadora',
        'descuento',
        'plan_id',
        'tipo_id_tercero',
        'tercero_id',
        'sw_clase_factura',
        'concepto',
        'total_capitacion_real',
        'documento_id',
        'tipo_factura',
        'documento_contable_id',
        'saldo',
        'fecha_vencimiento_factura',
        'retencion_fuente',
        'sw_proceso',
        'rango',
        'sw_imp_copia',
        'observacion',
        'impuesto_cree',
        'reteica',
        'sw_factory',
        'sw_dificil_cobro',
        'sw_proceso_juridico',
        'sw_deterioro',
        'impuesto_4x100',
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
        'total_factura' => 'decimal:2',
        'total_capitacion_real' => 'decimal:2',
        'saldo' => 'decimal:2',
        'fecha_vencimiento_factura' => 'date',
    ];

    protected $appends = ['tercero'];

    /**
     * Obtener el tercero asociado (composite key)
     */
    public function getTerceroAttribute()
    {
        return Tercero::where('tipo_id_tercero', $this->tipo_id_tercero)
                     ->where('tercero_id', $this->tercero_id)
                     ->first();
    }

    public function items()
    {
        return $this->hasMany(FacFacturaItem::class, 'factura_fiscal_id', 'factura_fiscal_id');
    }

    public function tercero()
    {
        return $this->belongsTo(Tercero::class, 'tercero_id', 'tercero_id');
    }
}

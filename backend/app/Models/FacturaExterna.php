<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacturaExterna extends Model
{
    protected $table = 'facturas_externas';

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
        'uuid_dataico',
        'cufe',
        'estado_electronico',
        'response_dataico',
        'fecha_respuesta_dataico',
        'xml_url',
        'pdf_url',
    ];

    protected $casts = [
        'fecha_registro'           => 'datetime',
        'fecha_vencimiento_factura' => 'date',
        'total_factura'            => 'decimal:2',
        'gravamen'                 => 'decimal:2',
        'valor_cargos'             => 'decimal:2',
        'valor_cuota_paciente'     => 'decimal:2',
        'valor_cuota_moderadora'   => 'decimal:2',
        'descuento'                => 'decimal:2',
        'total_capitacion_real'    => 'decimal:2',
        'saldo'                    => 'decimal:2',
        'retencion_fuente'         => 'decimal:2',
        'impuesto_cree'            => 'decimal:2',
        'reteica'                  => 'decimal:2',
        'impuesto_4x100'           => 'decimal:2',
        'response_dataico'         => 'array',
        'fecha_respuesta_dataico'  => 'datetime',
    ];

    public function tercero()
    {
        return $this->belongsTo(Tercero::class, 'tercero_id', 'tercero_id');
    }
}

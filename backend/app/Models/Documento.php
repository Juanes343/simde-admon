<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    protected $table = 'documentos';
    protected $primaryKey = 'documento_id';
    public $incrementing = false; // El usuario dio un ID (4) así que lo tratamos como entero no autoincremental si desea
    
    protected $fillable = [
        'documento_id',
        'empresa_id',
        'tipo_doc_general_id',
        'prefijo',
        'sw_estado',
        'numeracion',
        'numero_digitos',
        'texto1',
        'texto2',
        'texto3',
        'mensaje',
        'descripcion',
        'sw_contabiliza',
        'pie_pagina_factura',
        'abr_contabilidad',
        'sw_costos',
        'usuario_id',
        'fecha_registro',
        'sw_doc_bodega',
        'sw_manejo_costo',
        'sw_automatico',
        'sw_estado_interfazado',
        'sw_total_costo_sistema',
        'tipo_comprobante',
        'clase_documento',
        'sw_cuentas_servicios',
        'resolucion_dian',
        'fecha_inicio_resolucion',
        'fecha_fin_resolucion',
        'rango_resolucion_inicio',
        'rango_resolucion_fin',
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
        'fecha_inicio_resolucion' => 'date',
        'fecha_fin_resolucion' => 'date',
        'numeracion' => 'integer',
        'numero_digitos' => 'integer',
        'sw_estado' => 'string',
    ];
}

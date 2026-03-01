<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Documento;

class NotaCredito extends Model
{
    protected $table = 'notas_credito';

    protected $fillable = [
        'empresa_id',
        'prefijo',
        'nota_credito_id',
        'prefijo_factura',
        'factura_fiscal',
        'concepto_id',
        'valor_nota',
        'observacion',
        'tipo_id_tercero',
        'tercero_id',
        'tipo_factura',
        'estado',
        'tipo_nota',
        'alcance',
        'cufe',
        'uuid',
        'fecha_envio',
        'fecha_aceptacion',
        'respuesta_dataico',
    ];

    protected $casts = [
        'valor_nota' => 'decimal:2',
        'fecha_envio' => 'datetime',
        'fecha_aceptacion' => 'datetime',
        'respuesta_dataico' => 'array',
    ];

    /**
     * Relación: Pertenece a un concepto de nota crédito
     */
    public function concepto()
    {
        return $this->belongsTo(NotaCreditoConcepto::class, 'concepto_id');
    }

    /**
     * Relación: Tiene muchos items
     */
    public function items()
    {
        return $this->hasMany(NotaCreditoItem::class, 'nota_credito_id', 'id');
    }

    /**
     * Relación: Pertenece a una factura electrónica (auditoría)
     */
    public function auditoria()
    {
        return $this->hasOne(AuditoriaDataIco::class, 'prefijo_siis', 'prefijo')
                    ->where('factura_siis', function ($query) {
                        $query->select('factura_fiscal')->from('notas_credito')->limit(1);
                    });
    }

    /**
     * Scope: Filtrar por empresa
     */
    public function scopePorEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    /**
     * Scope: Filtrar por estado
     */
    public function scopeEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope: Pendientes de envío
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'PENDIENTE');
    }

    /**
     * Scope: Aceptadas por DIAN
     */
    public function scopeAceptadas($query)
    {
        return $query->where('estado', 'ACEPTADO');
    }

    /**
     * Genera el número de nota crédito siguiendo secuencia desde la tabla documentos
     */
    public static function generarSiguienteNumero($empresaId, $prefijo)
    {
        // Buscar el documento correspondiente al prefijo y empresa
        $documento = Documento::where('empresa_id', $empresaId)
                              ->where('prefijo', $prefijo)
                              ->first();

        if ($documento) {
            // Incrementar la numeración en el documento
            $documento->numeracion += 1;
            $documento->save();
            
            return $documento->numeracion;
        }

        // Fallback por si no existe el documento (aunque debería existir)
        $ultima = self::where('empresa_id', $empresaId)
                      ->where('prefijo', $prefijo)
                      ->orderByDesc('nota_credito_id')
                      ->first();

        return $ultima ? $ultima->nota_credito_id + 1 : 1;
    }

    /**
     * Obtiene el número prefijado (ej: NC00001)
     */
    public function getPrefixedNumberAttribute()
    {
        return $this->prefijo . str_pad($this->nota_credito_id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Obtiene la factura referenciada
     */
    public function getFacturaReferenciada()
    {
        return [
            'prefijo' => $this->prefijo_factura,
            'numero' => $this->factura_fiscal,
            'tipo' => $this->tipo_factura,
        ];
    }
}

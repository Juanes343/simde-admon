<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotaCreditoConcepto extends Model
{
    use SoftDeletes;

    protected $table = 'notas_credito_conceptos';

    protected $fillable = [
        'empresa_id',
        'descripcion',
        'sw_naturaleza',
        'sw_activo',
    ];

    protected $casts = [
        'sw_activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación con Empresa
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id', 'empresa_id');
    }

    /**
     * Scope: Solo conceptos activos
     */
    public function scopeActivo($query)
    {
        return $query->where('sw_activo', true);
    }

    /**
     * Scope: Solo conceptos de crédito
     */
    public function scopeCredito($query)
    {
        return $query->where('sw_naturaleza', 'C');
    }

    /**
     * Scope: Por empresa
     */
    public function scopePorEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    /**
     * Scope: Buscar descripción
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where('descripcion', 'like', "%{$termino}%");
    }
}

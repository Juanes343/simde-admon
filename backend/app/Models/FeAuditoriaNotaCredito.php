<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeAuditoriaNotaCredito extends Model
{
    protected $table = 'fe_auditoria_notas_credito';

    protected $fillable = [
        'prefijo_siis',
        'nota_credito_siis',
        'dian_status',
        'numero',
        'issue_date',
        'xml_url',
        'payment_date',
        'customer_status',
        'pdf_url',
        'email_status',
        'cufe',
        'uuid',
        'qrcode',
        'prefix',
        'resolution_number',
        'json_siis',
        'dian_messages',
        'dataico_response',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'payment_date' => 'datetime',
        'dataico_response' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope: Por estado DIAN
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('dian_status', $estado);
    }

    /**
     * Scope: Por nota crédito
     */
    public function scopePorNotaCredito($query, $prefijo, $numero)
    {
        return $query->where('prefijo_siis', $prefijo)
                     ->where('nota_credito_siis', $numero);
    }

    /**
     * Verificar si fue aceptada por DIAN
     */
    public function estaAceptada(): bool
    {
        return $this->dian_status === 'DIAN_ACEPTADO';
    }

    /**
     * Obtener la respuesta de DATAICO
     */
    public function obtenerRespuesta()
    {
        return $this->dataico_response;
    }
}

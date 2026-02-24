<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriaDataIco extends Model
{
    protected $table = 'fe_auditoria_dataico';
    protected $primaryKey = 'id_auditoria_dataico';
    public $timestamps = false;

    protected $fillable = [
        'prefijo',
        'factura_fiscal_id',
        'numero',
        'dian_status',
        'customer_status',
        'email_status',
        'cufe',
        'uuid',
        'issue_date',
        'payment_date',
        'xml_url',
        'pdf_url',
        'qrcode',
        'json_respuesta',
        'fecha_registro',
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
        'json_respuesta' => 'array',
    ];

    /**
     * Relación con la factura fiscal
     */
    public function factura(): BelongsTo
    {
        return $this->belongsTo(FacFactura::class, 'factura_fiscal_id', 'factura_fiscal_id');
    }

    /**
     * Obtener auditorías por CUFE
     */
    public static function findByCufe(string $cufe): ?self
    {
        return self::where('cufe', $cufe)->first();
    }

    /**
     * Obtener auditorías por UUID
     */
    public static function findByUuid(string $uuid): ?self
    {
        return self::where('uuid', $uuid)->first();
    }

    /**
     * Obtener auditorías aceptadas por DIAN
     */
    public static function dianAceptadas()
    {
        return self::where('dian_status', 'DIAN_ACEPTADO');
    }

    /**
     * Obtener auditorías rechazadas por DIAN
     */
    public static function dianRechazadas()
    {
        return self::where('dian_status', 'DIAN_RECHAZADO');
    }
}

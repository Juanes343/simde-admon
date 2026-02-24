<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacFactura extends Model
{
    protected $table = 'fac_facturas';
    protected $primaryKey = 'factura_fiscal_id';
    public $timestamps = false;

    protected $fillable = [
        'prefijo',
        'numero_factura',
        'tipo_factura',
        'estado_electronico',
        'cufe',
        'uuid_dataico',
        'response_dataico',
        'fecha_respuesta_dataico',
        'total_factura',
        'fecha_registro',
        'fecha_periodo_inicio',
        'fecha_periodo_fin',
        'concepto',
        'tercero_id',
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
        'fecha_respuesta_dataico' => 'datetime',
        'fecha_periodo_inicio' => 'datetime',
        'fecha_periodo_fin' => 'datetime',
        'response_dataico' => 'array',
        'total_factura' => 'decimal:2',
    ];

    /**
     * Relación con los ítems de la factura
     */
    public function items(): HasMany
    {
        return $this->hasMany(FacFacturaItem::class, 'factura_fiscal_id', 'factura_fiscal_id');
    }

    /**
     * Relación con el tercero (cliente)
     */
    public function tercero(): BelongsTo
    {
        return $this->belongsTo(Tercero::class, 'tercero_id', 'tercero_id');
    }

    /**
     * Relación con los registros de auditoría
     */
    public function auditorias(): HasMany
    {
        return $this->hasMany(AuditoriaDataIco::class, 'factura_fiscal_id', 'factura_fiscal_id');
    }

    /**
     * Obtener la última auditoría para esta factura
     */
    public function ultimaAuditoria()
    {
        return $this->auditorias()->latest('fecha_registro')->first();
    }

    /**
     * Verificar si la factura fue aceptada por DIAN
     */
    public function estaAceptadaDian(): bool
    {
        return $this->estado_electronico === 'ACEPTADA';
    }

    /**
     * Verificar si la factura fue rechazada por DIAN
     */
    public function estaRechazadaDian(): bool
    {
        return $this->estado_electronico === 'RECHAZADA';
    }

    /**
     * Obtener la URL del PDF desde DataIco
     */
    public function obtenerUrlPdf(): ?string
    {
        $auditoria = $this->ultimaAuditoria();
        return $auditoria?->pdf_url;
    }

    /**
     * Obtener la URL del XML desde DataIco
     */
    public function obtenerUrlXml(): ?string
    {
        $auditoria = $this->ultimaAuditoria();
        return $auditoria?->xml_url;
    }
}

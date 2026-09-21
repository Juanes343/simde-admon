<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';
    protected $primaryKey = 'cotizacion_id';

    protected $fillable = [
        'numero_cotizacion',
        'tipo_id_tercero',
        'tercero_id',
        'fecha_emision',
        'fecha_vencimiento',
        'metodo_pago',
        'tipo_pago',
        'orden_compra',
        'subtotal',
        'descuento_total',
        'impuestos_total',
        'total',
        'notas',
        'sw_estado',
        'orden_servicio_id',
        'usuario_id',
        'token_aprobacion',
        'token_aprobacion_expira_en',
        'datos_orden',
        'firma_cliente',
        'firmante_nombre',
        'firmante_documento',
        'firma_ip',
        'fecha_firma',
        'motivo_rechazo',
        'fecha_rechazo',
    ];

    // El token y la imagen de la firma no viajan en las respuestas JSON del panel
    protected $hidden = ['token_aprobacion', 'firma_cliente'];

    protected $casts = [
        'fecha_emision'     => 'date',
        'fecha_vencimiento' => 'date',
        'subtotal'          => 'decimal:2',
        'descuento_total'   => 'decimal:2',
        'impuestos_total'   => 'decimal:2',
        'total'             => 'decimal:2',
        'datos_orden'                => 'array',
        'token_aprobacion_expira_en' => 'datetime',
        'fecha_firma'                => 'datetime',
        'fecha_rechazo'              => 'datetime',
    ];

    protected $appends = ['tercero'];

    /**
     * Obtener el tercero asociado (clave compuesta)
     */
    public function getTerceroAttribute()
    {
        if (!array_key_exists('tercero_cached', $this->relations)) {
            $tercero = Tercero::where('tipo_id_tercero', $this->tipo_id_tercero)
                ->where('tercero_id', $this->tercero_id)
                ->first();
            $this->setRelation('tercero_cached', $tercero);
        }
        return $this->getRelation('tercero_cached');
    }

    /**
     * Relación con ítems de la cotización
     */
    public function items()
    {
        return $this->hasMany(CotizacionItem::class, 'cotizacion_id', 'cotizacion_id')
                    ->orderBy('orden');
    }

    /**
     * Relación con usuario
     */
    public function usuario()
    {
        return $this->belongsTo(SystemUsuario::class, 'usuario_id', 'usuario_id');
    }

    /**
     * Relación con orden de servicio (cuando fue convertida)
     */
    public function ordenServicio()
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id', 'orden_servicio_id');
    }

    /**
     * Generar número de cotización: COT-YYYY-XXXXXX
     */
    public static function generarNumeroCotizacion(): string
    {
        $year   = now()->format('Y');
        $prefix = "COT-{$year}-";
        $last   = static::where('numero_cotizacion', 'like', "{$prefix}%")
            ->orderBy('numero_cotizacion', 'desc')
            ->value('numero_cotizacion');

        $sequence = $last ? intval(substr($last, -6)) + 1 : 1;
        return $prefix . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Scope: cotizaciones que no están rechazadas ni convertidas
     */
    public function scopeActivas($query)
    {
        return $query->whereNotIn('sw_estado', ['rechazada', 'convertida']);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrdenServicio extends Model
{
    /**
     * Generar un token único para la firma del cliente
     */
    public function generarTokenFirma()
    {
        $this->signature_token = Str::random(60);
        $this->signature_token_expires_at = now()->addHours(24);
        $this->save();
        
        return $this->signature_token;
    }

    /**
     * Verificar si el token es válido
     */
    public function esTokenValido($token)
    {
        return $this->signature_token === $token && 
               ($this->signature_token_expires_at === null || $this->signature_token_expires_at > now()) &&
               $this->fecha_firma === null;
    }

    /**
     * Invalidar token al firmar
     */
    public function firmar($base64Image)
    {
        $this->firma_tercero = $base64Image;
        $this->fecha_firma = now();
        $this->signature_token = null; // Invalidate token
        $this->signature_token_expires_at = null;
        $this->save();
    }

    use \Illuminate\Database\Eloquent\Concerns\HasUlids; // If using ULIDs, otherwise leave out. Assuming standard integer ID for now based on earlier file reads.

    protected $table = 'ordenes_servicio';
    protected $primaryKey = 'orden_servicio_id';

    protected $fillable = [
        'tipo_id_tercero',
        'tercero_id',
        'numero_orden',
        'fecha_inicio',
        'fecha_fin',
        'sw_prorroga_automatica',
        'periodo_facturacion_dias',
        'porcentaje_soltec',
        'porcentaje_ret_fuente',
        'sw_estado',
        'observaciones',
        'usuario_id',
        'signature_token',
        'signature_token_expires_at',
        'firma_tercero',
        'fecha_firma',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'porcentaje_soltec' => 'decimal:2',
        'porcentaje_ret_fuente' => 'decimal:2',
        'signature_token_expires_at' => 'datetime',
        'fecha_firma' => 'datetime',
    ];

    protected $appends = ['tercero'];

    /**
     * Obtener el tercero asociado (composite key)
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
     * Relación con items de la orden
     */
    public function items()
    {
        return $this->hasMany(OrdenServicioItem::class, 'orden_servicio_id', 'orden_servicio_id');
    }

    /**
     * Relación con usuario
     */
    public function usuario()
    {
        return $this->belongsTo(SystemUsuario::class, 'usuario_id', 'usuario_id');
    }

    /**
     * Scope para obtener órdenes activas
     */
    public function scopeActivas($query)
    {
        return $query->where('sw_estado', '1');
    }

    /**
     * Scope para obtener órdenes por tercero
     */
    public function scopePorTercero($query, $tipo_id_tercero, $tercero_id)
    {
        return $query->where('tipo_id_tercero', $tipo_id_tercero)
                     ->where('tercero_id', $tercero_id);
    }

    /**
     * Verificar si la orden permite facturar en una fecha dada
     */
    public function permiteFacturar($fecha = null)
    {
        $fecha = $fecha ?? now();
        
        // Si está inactiva, no permite facturar
        if ($this->sw_estado !== '1') {
            return false;
        }

        // Si tiene prórroga automática, siempre permite facturar
        if ($this->sw_prorroga_automatica === '1') {
            return true;
        }

        // Si no tiene prórroga, solo permite facturar si está dentro del rango de fechas
        return $fecha >= $this->fecha_inicio && $fecha <= $this->fecha_fin;
    }

    /**
     * Calcular total de la orden
     */
    public function calcularTotal()
    {
        // Si los items ya están cargados, usar la colección
        if ($this->relationLoaded('items')) {
            return $this->items->sum('subtotal');
        }
        
        // Si no están cargados, hacer query
        return $this->items()->sum('subtotal');
    }

    /**
     * Generar número de orden
     */
    public static function generarNumeroOrden()
    {
        $año = date('Y');
        $ultimaOrden = self::where('numero_orden', 'like', "OS-{$año}-%")->orderBy('numero_orden', 'desc')->first();
        
        if ($ultimaOrden) {
            $ultimoNumero = (int) substr($ultimaOrden->numero_orden, -6);
            $nuevoNumero = $ultimoNumero + 1;
        } else {
            $nuevoNumero = 1;
        }
        
        return "OS-{$año}-" . str_pad($nuevoNumero, 6, '0', STR_PAD_LEFT);
    }
}

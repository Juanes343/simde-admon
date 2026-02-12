<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tercero extends Model
{
    use HasFactory;

    protected $table = 'terceros';
    protected $primaryKey = ['tipo_id_tercero', 'tercero_id'];
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tipo_id_tercero',
        'tercero_id',
        'tipo_pais_id',
        'tipo_dpto_id',
        'tipo_mpio_id',
        'direccion',
        'telefono',
        'fax',
        'email',
        'celular',
        'sw_persona_juridica',
        'cal_cli',
        'usuario_id',
        'fecha_registro',
        'busca_persona',
        'nombre_tercero',
        'dv',
        'sw_tipo_sociedad',
        'sw_interfazado',
        'sw_reteica',
        'nombre_tercero_abreviado',
        'sw_domiciliado',
        'apartado_aereo',
        'direccion2',
        'telefono2',
        'calificacion_tercero_id',
        'sw_estado',
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'sw_responsable_iva',
        'sw_convenio',
        'dias_credito',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_registro' => 'datetime',
    ];

    /**
     * Relación con usuario
     */
    public function usuario()
    {
        return $this->belongsTo(SystemUsuario::class, 'usuario_id', 'usuario_id');
    }

    /**
     * Override para manejar clave compuesta
     */
    protected function setKeysForSaveQuery($query)
    {
        $keys = $this->getKeyName();
        if (!is_array($keys)) {
            return parent::setKeysForSaveQuery($query);
        }

        foreach ($keys as $keyName) {
            $query->where($keyName, '=', $this->getKeyForSaveQuery($keyName));
        }

        return $query;
    }

    /**
     * Get the value for save query
     */
    protected function getKeyForSaveQuery($keyName = null)
    {
        if (is_null($keyName)) {
            $keyName = $this->getKeyName();
        }

        if (isset($this->original[$keyName])) {
            return $this->original[$keyName];
        }

        return $this->getAttribute($keyName);
    }
}

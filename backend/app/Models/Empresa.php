<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    /**
     * La tabla asociada al modelo.
     * 
     * @var string
     */
    protected $table = 'empresas';

    /**
     * La clave primaria de la tabla.
     * 
     * @var string
     */
    protected $primaryKey = 'empresa_id';

    /**
     * Indica si el ID es autoincremental.
     * 
     * @var bool
     */
    public $incrementing = false;

    /**
     * El tipo de identificación de la clave primaria.
     * 
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Atributos que son asignables masivamente.
     * 
     * @var array
     */
    protected $fillable = [
        'empresa_id',
        'tipo_id_tercero',
        'id',
        'razon_social',
        'representante_legal',
        'codigo_sgsss',
        'tipo_pais_id',
        'tipo_dpto_id',
        'tipo_mpio_id',
        'direccion',
        'telefonos',
        'digito_verificacion',
    ];

    /**
     * Los atributos que deben ser casteados.
     * 
     * @var array
     */
    protected $casts = [
        'empresa_id' => 'string',
        'id' => 'string',
        'digito_verificacion' => 'string',
    ];

    /**
     * Timestamps automáticos de Laravel.
     */
    public $timestamps = true;
}

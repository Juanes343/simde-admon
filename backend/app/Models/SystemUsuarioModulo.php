<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemUsuarioModulo extends Model
{
    protected $table = 'system_usuarios_modulos';

    protected $fillable = [
        'usuario_id',
        'modulo_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function usuario()
    {
        return $this->belongsTo(SystemUsuario::class, 'usuario_id', 'usuario_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class SystemUsuario extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'system_usuarios';
    protected $primaryKey = 'usuario_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'usuario',
        'nombre',
        'descripcion',
        'passwd',
        'sw_admin',
        'activo',
        'fecha_caducidad_contrasena',
        'fecha_caducidad_cuenta',
        'caducidad_contrasena',
        'codigo_alterno',
        'telefono',
        'tel_celular',
        'indicativo',
        'extension',
        'email',
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'firma',
        'funcion_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'passwd',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_caducidad_contrasena' => 'datetime',
        'fecha_caducidad_cuenta' => 'datetime',
    ];

    /**
     * Get the password for authentication.
     */
    public function getAuthPassword()
    {
        return $this->passwd;
    }

    /**
     * Relación con terceros
     */
    public function terceros()
    {
        return $this->hasMany(Tercero::class, 'usuario_id', 'usuario_id');
    }
}

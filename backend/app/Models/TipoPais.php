<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoPais extends Model
{
    use HasFactory;

    protected $table = 'tipo_pais';
    protected $primaryKey = 'tipo_pais_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tipo_pais_id',
        'pais',
        'bloqueado_edicion',
        'tipo_pais_alterno',
    ];

    public function departamentos()
    {
        return $this->hasMany(TipoDpto::class, 'tipo_pais_id', 'tipo_pais_id');
    }
}

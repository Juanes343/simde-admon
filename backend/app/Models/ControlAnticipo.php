<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlAnticipo extends Model
{
    protected $table = 'control_anticipos';
    protected $primaryKey = 'id';

    protected $fillable = [
        'empresa_id',
        'tipo_id_tercero',
        'tercero_id',
        'saldo',
    ];

    protected $casts = [
        'saldo' => 'decimal:2',
    ];

    public function tercero()
    {
        return $this->belongsTo(Tercero::class, 'tercero_id', 'tercero_id')
                    ->where('tipo_id_tercero', $this->tipo_id_tercero);
    }
}

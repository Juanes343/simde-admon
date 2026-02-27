<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditoriaNotaCredito extends Model
{
    protected $table = 'fe_auditoria_notas_credito';

    protected $fillable = [
        'prefijo_siis',
        'nota_credito_siis',
        'dian_status',
        'numero',
        'issue_date',
        'xml_url',
        'payment_date',
        'customer_status',
        'pdf_url',
        'email_status',
        'cufe',
        'uuid',
        'qrcode',
        'prefix',
        'resolution_number',
        'json_siis',
        'dian_messages',
        'dataico_response',
    ];

    protected $casts = [
        'json_siis' => 'array',
        'dataico_response' => 'array',
        'dian_messages' => 'array',
    ];
}

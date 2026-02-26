<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fe_auditoria_notas_credito', function (Blueprint $table) {
            $table->id();
            $table->string('prefijo_siis', 50)->index();
            $table->bigInteger('nota_credito_siis')->index();
            $table->string('dian_status', 50)->nullable()->comment('DIAN_ACEPTADO, DIAN_RECHAZADO, DIAN_EN_PROCESO');
            $table->string('numero', 50)->nullable();
            $table->dateTime('issue_date')->nullable();
            $table->string('xml_url', 500)->nullable();
            $table->dateTime('payment_date')->nullable();
            $table->string('customer_status', 50)->nullable();
            $table->string('pdf_url', 500)->nullable();
            $table->string('email_status', 50)->nullable();
            $table->string('cufe', 100)->nullable()->unique();
            $table->string('uuid', 100)->nullable()->unique();
            $table->string('qrcode', 500)->nullable();
            $table->string('prefix', 50)->nullable();
            $table->string('resolution_number', 50)->nullable();
            $table->longText('json_siis')->nullable();
            $table->longText('dian_messages')->nullable();
            $table->json('dataico_response')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index(['prefijo_siis', 'nota_credito_siis']);
            $table->index('dian_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fe_auditoria_notas_credito');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fe_auditoria_dataico', function (Blueprint $table) {
            $table->id('id_auditoria_dataico');
            $table->string('prefijo', 50);
            $table->unsignedBigInteger('factura_fiscal_id');
            $table->string('numero', 100);
            $table->string('dian_status', 100)->nullable(); // DIAN_ACEPTADO, DIAN_RECHAZADO, DIAN_EN_PROCESO
            $table->string('customer_status', 100)->nullable(); // CLIENTE_PENDIENTE, CLIENTE_ACEPTADO, etc
            $table->string('email_status', 100)->nullable(); // CORREO_RECIBIDO, CORREO_ENVIADO, etc
            $table->text('cufe')->nullable(); // CUFE único del DIAN
            $table->text('uuid')->nullable(); // UUID de DataIco
            $table->string('issue_date', 100)->nullable(); // Fecha emisión
            $table->string('payment_date', 100)->nullable(); // Fecha vencimiento
            $table->text('xml_url')->nullable(); // URL para descargar XML
            $table->text('pdf_url')->nullable(); // URL para descargar PDF
            $table->text('qrcode')->nullable(); // Datos del código QR
            $table->longText('json_respuesta')->nullable(); // Respuesta completa de DataIco
            $table->timestamp('fecha_registro')->useCurrent();
            
            $table->foreign('factura_fiscal_id')
                ->references('factura_fiscal_id')
                ->on('fac_facturas')
                ->onDelete('cascade');
            
            $table->index('cufe');
            $table->index('uuid');
            $table->index('dian_status');
            $table->index('fecha_registro');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fe_auditoria_dataico');
    }
};

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
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->string('signature_token', 64)->nullable()->after('sw_estado')->unique();
            $table->dateTime('signature_token_expires_at')->nullable()->after('signature_token');
            $table->text('firma_tercero')->nullable()->after('signature_token_expires_at')->comment('Base64 signature image');
            $table->dateTime('fecha_firma')->nullable()->after('firma_tercero');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->dropColumn([
                'signature_token', 
                'signature_token_expires_at', 
                'firma_tercero', 
                'fecha_firma'
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->string('firmante_nombre', 150)->nullable()->after('fecha_firma');
            $table->string('firmante_documento', 50)->nullable()->after('firmante_nombre');
            $table->string('firma_ip', 45)->nullable()->after('firmante_documento');
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->dropColumn(['firmante_nombre', 'firmante_documento', 'firma_ip']);
        });
    }
};

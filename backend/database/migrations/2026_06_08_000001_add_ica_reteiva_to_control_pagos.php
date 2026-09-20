<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('control_pagos', function (Blueprint $table) {
            $table->decimal('valor_ica', 14, 2)->default(0)->after('valor_retencion');
            $table->decimal('valor_reteiva', 14, 2)->default(0)->after('valor_ica');
        });
    }

    public function down(): void
    {
        Schema::table('control_pagos', function (Blueprint $table) {
            $table->dropColumn(['valor_ica', 'valor_reteiva']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_usuarios_modulos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->string('modulo_id', 50);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('usuario_id')
                  ->references('usuario_id')
                  ->on('system_usuarios')
                  ->onDelete('cascade');

            $table->unique(['usuario_id', 'modulo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_usuarios_modulos');
    }
};
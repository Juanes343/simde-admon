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
        Schema::create('system_usuarios', function (Blueprint $table) {
            $table->id('usuario_id');
            $table->string('usuario', 25)->default('');
            $table->string('nombre', 60)->default('');
            $table->string('descripcion', 255)->default('');
            $table->string('passwd', 255)->default('');
            $table->char('sw_admin', 1)->default('0');
            $table->char('activo', 1)->default('1');
            $table->timestamp('fecha_caducidad_contrasena')->nullable();
            $table->timestamp('fecha_caducidad_cuenta')->nullable();
            $table->smallInteger('caducidad_contrasena')->nullable()->default(0);
            $table->string('codigo_alterno', 30)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('tel_celular', 30)->nullable();
            $table->string('indicativo', 3)->nullable();
            $table->string('extension', 6)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('primer_nombre', 20)->nullable();
            $table->string('segundo_nombre', 30)->nullable();
            $table->string('primer_apellido', 20)->nullable();
            $table->string('segundo_apellido', 30)->nullable();
            $table->string('firma', 30)->nullable();
            $table->integer('funcion_id')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();

            $table->unique('usuario');
            $table->unique('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_usuarios');
    }
};

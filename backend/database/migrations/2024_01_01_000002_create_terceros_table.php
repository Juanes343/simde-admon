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
        Schema::create('terceros', function (Blueprint $table) {
            $table->string('tipo_id_tercero', 3);
            $table->string('tercero_id', 32);
            $table->string('tipo_pais_id', 4);
            $table->string('tipo_dpto_id', 4);
            $table->string('tipo_mpio_id', 4);
            $table->string('direccion', 100);
            $table->string('telefono', 30)->nullable();
            $table->string('fax', 15)->nullable();
            $table->string('email', 60)->nullable();
            $table->string('celular', 15)->nullable();
            $table->char('sw_persona_juridica', 1)->default('0');
            $table->char('cal_cli', 1)->default('0');
            $table->unsignedBigInteger('usuario_id');
            $table->timestamp('fecha_registro')->useCurrent();
            $table->string('busca_persona', 25)->nullable();
            $table->string('nombre_tercero', 100);
            $table->char('dv', 1)->nullable();
            $table->char('sw_tipo_sociedad', 1)->default('0');
            $table->char('sw_interfazado', 1)->default('0');
            $table->char('sw_reteica', 1)->default('0');
            $table->string('nombre_tercero_abreviado', 100)->nullable();
            $table->char('sw_domiciliado', 1)->default('0');
            $table->string('apartado_aereo', 100)->nullable();
            $table->string('direccion2', 100)->nullable();
            $table->string('telefono2', 30)->nullable();
            $table->integer('calificacion_tercero_id')->nullable();
            $table->char('sw_estado', 1)->default('1');
            $table->string('primer_nombre', 32)->nullable();
            $table->string('segundo_nombre', 32)->nullable();
            $table->string('primer_apellido', 32)->nullable();
            $table->string('segundo_apellido', 32)->nullable();
            $table->char('sw_responsable_iva', 1)->nullable()->default('0');
            $table->char('sw_convenio', 1)->nullable();
            $table->smallInteger('dias_credito')->nullable();
            $table->timestamps();

            $table->primary(['tipo_id_tercero', 'tercero_id']);
            $table->foreign('usuario_id')->references('usuario_id')->on('system_usuarios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terceros');
    }
};

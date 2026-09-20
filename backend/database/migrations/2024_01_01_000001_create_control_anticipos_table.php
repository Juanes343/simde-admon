<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateControlAnticiposTable extends Migration
{
    public function up()
    {
        Schema::create('control_anticipos', function (Blueprint $table) {
            $table->id();
            $table->string('empresa_id', 10);
            $table->string('tipo_id_tercero', 5);
            $table->string('tercero_id', 20);
            $table->decimal('saldo', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['empresa_id', 'tipo_id_tercero', 'tercero_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('control_anticipos');
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_modulos', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('titulo', 100);
            $table->string('icono', 50)->nullable();
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });

        DB::table('system_modulos')->insert([
            ['id'=>'terceros',    'titulo'=>'Terceros',             'icono'=>'fa-users',          'descripcion'=>'Gestión de terceros, clientes y proveedores.', 'activo'=>true, 'orden'=>1, 'created_at'=>now(), 'updated_at'=>now()],
            ['id'=>'servicios',   'titulo'=>'Servicios',            'icono'=>'fa-cogs',           'descripcion'=>'Parametrización de servicios.',                'activo'=>true, 'orden'=>2, 'created_at'=>now(), 'updated_at'=>now()],
            ['id'=>'ordenes',     'titulo'=>'Órdenes de Servicio',  'icono'=>'fa-clipboard-list', 'descripcion'=>'Gestión de órdenes de servicio.',              'activo'=>true, 'orden'=>3, 'created_at'=>now(), 'updated_at'=>now()],
            ['id'=>'facturacion', 'titulo'=>'Facturación',          'icono'=>'fa-file-invoice',   'descripcion'=>'Generación y consulta de facturas.',           'activo'=>true, 'orden'=>4, 'created_at'=>now(), 'updated_at'=>now()],
            ['id'=>'notas',       'titulo'=>'Notas Crédito/Débito', 'icono'=>'fa-file-signature', 'descripcion'=>'Gestión de notas crédito y débito.',           'activo'=>true, 'orden'=>5, 'created_at'=>now(), 'updated_at'=>now()],
            ['id'=>'usuarios',    'titulo'=>'Gestión de Usuarios',  'icono'=>'fa-user-shield',    'descripcion'=>'Administración de usuarios y permisos.',       'activo'=>true, 'orden'=>6, 'created_at'=>now(), 'updated_at'=>now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_modulos');
    }
};
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
        Schema::table('inventario', function (Blueprint $table) {
            $table->index(['estado', 'estado_registro'], 'inventario_estado_registro_idx');
            $table->index('num_serie', 'inventario_num_serie_idx');
            $table->index('fecha_registro', 'inventario_fecha_registro_idx');
        });

        Schema::table('solicitud', function (Blueprint $table) {
            $table->index(['estado', 'tipo_movimiento'], 'solicitud_estado_tipo_idx');
            $table->index('fecha_solicitud', 'solicitud_fecha_solicitud_idx');
        });

        Schema::table('mantenimiento', function (Blueprint $table) {
            $table->index('estado', 'mantenimiento_estado_idx');
            $table->index('fecha_solicitud', 'mantenimiento_fecha_solicitud_idx');
        });

        Schema::table('tipo_material', function (Blueprint $table) {
            $table->index('familia', 'tipo_material_familia_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tipo_material', function (Blueprint $table) {
            $table->dropIndex('tipo_material_familia_idx');
        });

        Schema::table('mantenimiento', function (Blueprint $table) {
            $table->dropIndex('mantenimiento_estado_idx');
            $table->dropIndex('mantenimiento_fecha_solicitud_idx');
        });

        Schema::table('solicitud', function (Blueprint $table) {
            $table->dropIndex('solicitud_estado_tipo_idx');
            $table->dropIndex('solicitud_fecha_solicitud_idx');
        });

        Schema::table('inventario', function (Blueprint $table) {
            $table->dropIndex('inventario_estado_registro_idx');
            $table->dropIndex('inventario_num_serie_idx');
            $table->dropIndex('inventario_fecha_registro_idx');
        });
    }
};


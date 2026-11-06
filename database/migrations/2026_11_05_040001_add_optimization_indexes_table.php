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
            $table->index('tipo_propiedad', 'inventario_tipo_propiedad_idx');
            $table->index('id_usuario_baja', 'inventario_id_usuario_baja_idx');
        });

        Schema::table('historial_estados', function (Blueprint $table) {
            $table->index(['id_inventario', 'fecha_cambio'], 'historial_estados_inv_fecha_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historial_estados', function (Blueprint $table) {
            $table->dropIndex('historial_estados_inv_fecha_idx');
        });

        Schema::table('inventario', function (Blueprint $table) {
            $table->dropIndex('inventario_tipo_propiedad_idx');
            $table->dropIndex('inventario_id_usuario_baja_idx');
        });
    }
};

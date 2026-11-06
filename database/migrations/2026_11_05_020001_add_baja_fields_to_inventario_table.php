<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `inventario` MODIFY COLUMN `estado` VARCHAR(50) NOT NULL DEFAULT 'Disponible'");
        }

        Schema::table('inventario', function (Blueprint $table) {
            if (! Schema::hasColumn('inventario', 'fecha_baja')) {
                $table->timestamp('fecha_baja')->nullable();
            }
            if (! Schema::hasColumn('inventario', 'motivo_baja')) {
                $table->string('motivo_baja', 100)->nullable();
            }
            if (! Schema::hasColumn('inventario', 'observaciones_baja')) {
                $table->text('observaciones_baja')->nullable();
            }
            if (! Schema::hasColumn('inventario', 'id_usuario_baja')) {
                $table->foreignId('id_usuario_baja')->nullable()->constrained('users', 'id')->nullOnDelete()->cascadeOnUpdate();
            }
            if (! Schema::hasColumn('inventario', 'estado_previo_baja')) {
                $table->string('estado_previo_baja', 50)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventario', function (Blueprint $table) {
            if (Schema::hasColumn('inventario', 'id_usuario_baja')) {
                $table->dropForeign(['id_usuario_baja']);
                $table->dropColumn('id_usuario_baja');
            }
            if (Schema::hasColumn('inventario', 'motivo_baja')) {
                $table->dropColumn('motivo_baja');
            }
            if (Schema::hasColumn('inventario', 'observaciones_baja')) {
                $table->dropColumn('observaciones_baja');
            }
            if (Schema::hasColumn('inventario', 'estado_previo_baja')) {
                $table->dropColumn('estado_previo_baja');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `inventario` MODIFY COLUMN `estado` ENUM('Disponible', 'Asignado', 'En Mantenimiento', 'Dañado', 'Baja', 'Devuelto a Proveedor') NOT NULL DEFAULT 'Disponible'");
        }
    }
};

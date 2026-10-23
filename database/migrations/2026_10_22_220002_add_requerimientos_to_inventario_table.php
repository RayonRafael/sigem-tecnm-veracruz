<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('inventario')) {
            return;
        }

        Schema::table('inventario', function (Blueprint $table) {
            if (!Schema::hasColumn('inventario', 'subtipo')) {
                $table->string('subtipo', 100)->nullable()->after('num_serie');
            }
            if (!Schema::hasColumn('inventario', 'fecha_asignacion')) {
                $table->date('fecha_asignacion')->nullable()->after('fecha_registro');
            }
            if (!Schema::hasColumn('inventario', 'fecha_cambio_area')) {
                $table->date('fecha_cambio_area')->nullable()->after('fecha_asignacion');
            }
            if (!Schema::hasColumn('inventario', 'ram')) {
                $table->string('ram', 100)->nullable();
            }
            if (!Schema::hasColumn('inventario', 'disco')) {
                $table->string('disco', 100)->nullable();
            }
            if (!Schema::hasColumn('inventario', 'procesador')) {
                $table->string('procesador', 100)->nullable();
            }
            if (!Schema::hasColumn('inventario', 'mac_address')) {
                $table->string('mac_address', 50)->nullable();
            }
            if (!Schema::hasColumn('inventario', 'ip_address')) {
                $table->string('ip_address', 50)->nullable();
            }
        });

        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement("ALTER TABLE inventario MODIFY COLUMN estado ENUM('Disponible', 'Asignado', 'Reasignado', 'En Mantenimiento', 'Dañado', 'Baja', 'Devuelto a Proveedor') NOT NULL DEFAULT 'Disponible'");
            } catch (\Exception $e) {
                Log::warning('No se pudo modificar enum estado en inventario: ' . $e->getMessage());
            }
        }

        // Migración de datos existentes desde especificaciones (JSON) a las columnas propias
        try {
            $registros = DB::table('inventario')->get();
            foreach ($registros as $row) {
                try {
                    $raw = $row->especificaciones ?? '';
                    $esp = is_array($raw) ? $raw : json_decode($raw, true);
                    if (is_array($esp)) {
                        DB::table('inventario')
                            ->where('id_inventario', $row->id_inventario)
                            ->update([
                                'ram' => $esp['ram'] ?? null,
                                'disco' => $esp['disco'] ?? null,
                                'procesador' => $esp['procesador'] ?? null,
                                'mac_address' => $esp['mac'] ?? $esp['mac_address'] ?? null,
                                'ip_address' => $esp['ip'] ?? $esp['ip_address'] ?? null,
                            ]);
                    }
                } catch (\Exception $e) {
                    Log::warning("No se pudo migrar especificaciones del equipo {$row->id_inventario}: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error general al migrar datos de especificaciones: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('inventario')) {
            return;
        }

        Schema::table('inventario', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['subtipo', 'fecha_asignacion', 'fecha_cambio_area', 'ram', 'disco', 'procesador', 'mac_address', 'ip_address'] as $col) {
                if (Schema::hasColumn('inventario', $col)) {
                    $columnsToDrop[] = $col;
                }
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement("ALTER TABLE inventario MODIFY COLUMN estado ENUM('Disponible', 'Asignado', 'En Mantenimiento', 'Dañado', 'Baja', 'Devuelto a Proveedor') NOT NULL DEFAULT 'Disponible'");
            } catch (\Exception $e) {
                Log::warning('No se pudo revertir enum estado en inventario: ' . $e->getMessage());
            }
        }
    }
};

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
        if (! Schema::hasTable('inventario')) {
            return;
        }

        Schema::table('inventario', function (Blueprint $table) {
            if (! Schema::hasColumn('inventario', 'aula')) {
                $table->string('aula', 50)->nullable()->after('area_id');
            }
        });

        // Backfill seguro para TiDB Cloud (MySQL) y SQLite
        $salones = Schema::hasTable('salones')
            ? DB::table('salones')->pluck('nombre', 'id')->all()
            : [];

        $registros = DB::table('inventario')
            ->select(['id_inventario', 'salon_id', 'especificaciones', 'ubicacion_fisica', 'aula'])
            ->get();

        foreach ($registros as $row) {
            if (! empty($row->aula)) {
                continue; // Ya tiene aula
            }

            $specs = is_string($row->especificaciones)
                ? (json_decode($row->especificaciones, true) ?: [])
                : (is_array($row->especificaciones) ? $row->especificaciones : []);

            $aula = $specs['cambio_nueva_aula']
                ?? $specs['aula']
                ?? ($row->salon_id && isset($salones[$row->salon_id]) ? $salones[$row->salon_id] : null);

            if (empty($aula) && ! empty($row->ubicacion_fisica)
                && preg_match('/Aula\s+([A-Za-z0-9_-]+)/i', $row->ubicacion_fisica, $m)) {
                $aula = $m[1];
            }

            if (! empty($aula)) {
                DB::table('inventario')
                    ->where('id_inventario', $row->id_inventario)
                    ->update(['aula' => substr(trim((string) $aula), 0, 50)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('inventario') && Schema::hasColumn('inventario', 'aula')) {
            Schema::table('inventario', function (Blueprint $table) {
                $table->dropColumn('aula');
            });
        }
    }
};


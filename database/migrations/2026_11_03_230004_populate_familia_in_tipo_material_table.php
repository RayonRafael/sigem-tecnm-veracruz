<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tipo_material') || !Schema::hasColumn('tipo_material', 'familia')) {
            return;
        }

        $familias = [
            8  => 'Periféricos y pantallas',      // Muse
            9  => 'Componentes internos',        // Memoria(Ram)
            10 => 'Consumibles y limpieza',       // Aire comprimido
            11 => 'Componentes internos',        // Disco duro
            12 => 'Componentes internos',        // SSD
            13 => 'Consumibles y limpieza',       // Tóner
            14 => 'Equipos completos',            // CPU
            15 => 'Periféricos y pantallas',      // Monitor
            16 => 'Redes y telecomunicaciones',   // Antena(USB)
            17 => 'Redes y telecomunicaciones',   // Antena(WIFI)
            18 => 'Redes y telecomunicaciones',   // Antena(RED)
            19 => 'Consumibles y limpieza',       // Espuma limpiadores
            20 => 'Consumibles y limpieza',       // Alcohol isopropílico
            21 => 'Componentes internos',        // Adaptador de disco duro
            22 => 'Componentes internos',        // Adaptador de SSD
            23 => 'Periféricos y pantallas',      // Teclado
            24 => 'Consumibles y limpieza',       // Toalla
            25 => 'Componentes internos',        // Fuente de poder
            26 => 'Redes y telecomunicaciones',   // Cable(UTP)
            27 => 'Redes y telecomunicaciones',   // Cable(K6)
            28 => 'Cableado y conectividad',      // Cable de video(HDMI)
            29 => 'Consumibles y limpieza',       // Batería(UPS)
            30 => 'Consumibles y limpieza',       // Limpiador de circuitos
            31 => 'Cableado y conectividad',      // Cable de impresora
            32 => 'Cableado y conectividad',      // Cable de corriente
            33 => 'Componentes internos',        // Disipadores
            34 => 'Periféricos y pantallas',      // Bocinas
            35 => 'Accesorios y almacenamiento',  // Abrazadores
            36 => 'Herramientas y mobiliario',    // Desarmador para laptop
            37 => 'Herramientas y mobiliario',    // Desarmador de cruz
            38 => 'Herramientas y mobiliario',    // Pinzas de corte
            39 => 'Herramientas y mobiliario',    // Pinzas de punta
            40 => 'Herramientas y mobiliario',    // Brocha antiestática
            41 => 'Herramientas y mobiliario',    // Ponchadores
            42 => 'Accesorios y almacenamiento',  // Cinchos
            43 => 'Cableado y conectividad',      // Cargador de laptop
            44 => 'Consumibles y limpieza',       // Limpiador para pantallas
            45 => 'Cableado y conectividad',      // Extensores USB
            46 => 'Periféricos y pantallas',      // Elector de DVD externo
            47 => 'Redes y telecomunicaciones',   // POE
        ];

        foreach ($familias as $id => $familia) {
            DB::table('tipo_material')
                ->where('id_tipodematerial', $id)
                ->update(['familia' => $familia]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tipo_material') && Schema::hasColumn('tipo_material', 'familia')) {
            DB::table('tipo_material')->update(['familia' => null]);
        }
    }
};

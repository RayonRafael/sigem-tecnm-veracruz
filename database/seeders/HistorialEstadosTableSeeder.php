<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HistorialEstadosTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        // Candado: nunca sembrar datos de ejemplo en produccion ni sobrescribir datos existentes.
        // (Antes aqui se hacia un delete() de la tabla completa.)
        if (app()->isProduction() || \DB::table('historial_estados')->exists()) {
            return;
        }

    }
}

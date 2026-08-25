<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MantenimientoTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('mantenimiento')->delete();
        
        \DB::table('mantenimiento')->insert(array (
            0 => 
            array (
                'id_mantenimiento' => 1,
                'id_inventario' => 2,
                'id_usuario_solicita' => 2,
                'nombre_tecnico' => 'Alumno Servicio Social',
                'num_control_tecnico' => '20240001',
                'tipo_servicio' => 'Servicio Social',
                'tipo_mantenimiento' => 'Correctivo',
                'descripcion_falla' => 'Se rompio',
                'descripcion_trabajo' => NULL,
                'fecha_solicitud' => '2026-08-14 00:00:00',
                'fecha_inicio' => NULL,
                'fecha_fin' => NULL,
                'estado' => 'Pendiente Revision Admin',
                'observaciones' => 'Se le callo al wey y se rompio',
                'created_at' => '2026-08-14 17:21:40',
                'updated_at' => '2026-08-14 17:21:40',
            ),
        ));
        
        
    }
}
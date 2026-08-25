<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PermissionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('permissions')->delete();
        
        \DB::table('permissions')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'inventario.ver',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'inventario.crear',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'inventario.editar',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'inventario.eliminar',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            4 => 
            array (
                'id' => 5,
                'name' => 'solicitudes.ver',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            5 => 
            array (
                'id' => 6,
                'name' => 'solicitudes.crear',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            6 => 
            array (
                'id' => 7,
                'name' => 'solicitudes.autorizar',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            7 => 
            array (
                'id' => 8,
                'name' => 'solicitudes.completar',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            8 => 
            array (
                'id' => 9,
                'name' => 'mantenimiento.ver',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            9 => 
            array (
                'id' => 10,
                'name' => 'mantenimiento.crear',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            10 => 
            array (
                'id' => 11,
                'name' => 'mantenimiento.editar',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            11 => 
            array (
                'id' => 12,
                'name' => 'mantenimiento.completar',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            12 => 
            array (
                'id' => 13,
                'name' => 'catalogos.ver',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            13 => 
            array (
                'id' => 14,
                'name' => 'catalogos.crear',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            14 => 
            array (
                'id' => 15,
                'name' => 'catalogos.editar',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            15 => 
            array (
                'id' => 16,
                'name' => 'catalogos.eliminar',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            16 => 
            array (
                'id' => 17,
                'name' => 'bitacora.ver',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            17 => 
            array (
                'id' => 18,
                'name' => 'reportes.inventario',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            18 => 
            array (
                'id' => 19,
                'name' => 'reportes.solicitudes',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            19 => 
            array (
                'id' => 20,
                'name' => 'reportes.mantenimiento',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            20 => 
            array (
                'id' => 21,
                'name' => 'aprobaciones.ver',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            21 => 
            array (
                'id' => 22,
                'name' => 'aprobaciones.aprobar',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            22 => 
            array (
                'id' => 23,
                'name' => 'aprobaciones.rechazar',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            23 => 
            array (
                'id' => 24,
                'name' => 'usuarios.ver',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            24 => 
            array (
                'id' => 25,
                'name' => 'usuarios.crear',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            25 => 
            array (
                'id' => 26,
                'name' => 'usuarios.editar',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
            26 => 
            array (
                'id' => 27,
                'name' => 'usuarios.eliminar',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:25',
                'updated_at' => '2026-08-10 15:21:25',
            ),
        ));
        
        
    }
}
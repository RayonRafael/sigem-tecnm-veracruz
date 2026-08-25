<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('roles')->delete();
        
        \DB::table('roles')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Administrador',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:26',
                'updated_at' => '2026-08-10 15:21:26',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Servicio Social',
                'guard_name' => 'web',
                'created_at' => '2026-08-10 15:21:26',
                'updated_at' => '2026-08-10 15:21:26',
            ),
        ));
        
        
    }
}
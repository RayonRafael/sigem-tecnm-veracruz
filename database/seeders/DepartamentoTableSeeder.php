<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DepartamentoTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('departamento')->delete();
        
        \DB::table('departamento')->insert(array (
            0 => 
            array (
                'id_departamento' => 1,
                'nombre' => 'Direcciónn',
                'deleted_at' => '2026-08-10 16:05:49',
                'created_at' => '2026-08-10 15:37:42',
                'updated_at' => '2026-08-10 16:05:49',
            ),
            1 => 
            array (
                'id_departamento' => 2,
                'nombre' => 'División de Estudios Profesionales ',
                'deleted_at' => NULL,
                'created_at' => '2026-08-10 15:45:46',
                'updated_at' => '2026-08-10 15:45:46',
            ),
            2 => 
            array (
                'id_departamento' => 3,
            'nombre' => 'División de Estudios de Posgrado e Investigación (DEPI) ',
                'deleted_at' => NULL,
                'created_at' => '2026-08-10 15:47:40',
                'updated_at' => '2026-08-10 15:47:40',
            ),
            3 => 
            array (
                'id_departamento' => 4,
                'nombre' => 'Departamento de Ingeniería en Sistemas y Computación ',
                'deleted_at' => NULL,
                'created_at' => '2026-08-10 15:51:13',
                'updated_at' => '2026-08-10 15:51:13',
            ),
            4 => 
            array (
                'id_departamento' => 5,
                'nombre' => 'Departamento de Ciencias Básicas ',
                'deleted_at' => NULL,
                'created_at' => '2026-08-10 15:52:37',
                'updated_at' => '2026-08-10 15:52:37',
            ),
            5 => 
            array (
                'id_departamento' => 6,
                'nombre' => 'Departamento de Ingeniería Eléctrica y Electrónica ',
                'deleted_at' => NULL,
                'created_at' => '2026-08-10 15:54:50',
                'updated_at' => '2026-08-10 15:54:50',
            ),
            6 => 
            array (
                'id_departamento' => 7,
                'nombre' => 'Departamento de Ingeniería Química y Bioquímica ',
                'deleted_at' => NULL,
                'created_at' => '2026-08-10 15:56:31',
                'updated_at' => '2026-08-10 15:56:31',
            ),
            7 => 
            array (
                'id_departamento' => 8,
                'nombre' => 'Departamento de Ingeniería Metal-Mecánica ',
                'deleted_at' => NULL,
                'created_at' => '2026-08-10 15:57:47',
                'updated_at' => '2026-08-10 15:57:47',
            ),
            8 => 
            array (
                'id_departamento' => 9,
                'nombre' => 'Departamento de Ingeniería Industrial ',
                'deleted_at' => NULL,
                'created_at' => '2026-08-10 16:00:24',
                'updated_at' => '2026-08-10 16:00:24',
            ),
            9 => 
            array (
                'id_departamento' => 10,
                'nombre' => 'Departamento de Ciencias Económico-Administrativas ',
                'deleted_at' => NULL,
                'created_at' => '2026-08-10 16:01:02',
                'updated_at' => '2026-08-10 16:01:02',
            ),
            10 => 
            array (
                'id_departamento' => 11,
                'nombre' => 'Departamento de Desarrollo Académico ',
                'deleted_at' => NULL,
                'created_at' => '2026-08-10 16:01:39',
                'updated_at' => '2026-08-10 16:01:39',
            ),
            11 => 
            array (
                'id_departamento' => 12,
                'nombre' => 'Dirección',
                'deleted_at' => NULL,
                'created_at' => '2026-08-10 16:08:42',
                'updated_at' => '2026-08-10 16:08:42',
            ),
        ));
        
        
    }
}
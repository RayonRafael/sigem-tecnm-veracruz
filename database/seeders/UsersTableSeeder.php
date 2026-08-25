<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('users')->delete();
        
        \DB::table('users')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Administrador',
                'email' => 'admin@tecnm.edu.mx',
                'email_verified_at' => NULL,
                'password' => '$2y$12$N3cHKk0QNnh/yLHv8B0f/.ns2MQ/Gztnbd/w3.qnbjcKZ.vw4zz3.',
                'remember_token' => NULL,
                'created_at' => '2026-08-10 15:21:28',
                'updated_at' => '2026-08-10 15:21:28',
                'apellido_paterno' => NULL,
                'apellido_materno' => NULL,
                'num_control' => NULL,
                'carrera' => NULL,
                'RFC' => NULL,
                'tipo_usuario' => 'Administrador',
                'activo' => 1,
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Alumno Servicio Social',
                'email' => 'servicio@tecnm.edu.mx',
                'email_verified_at' => NULL,
                'password' => '$2y$12$wY4PoH1i84OQ6bJ9Oq8eteMfv7AqJzn.aaJkW7wwywBmeN/sVzMKC',
                'remember_token' => NULL,
                'created_at' => '2026-08-10 15:21:29',
                'updated_at' => '2026-08-10 15:21:29',
                'apellido_paterno' => NULL,
                'apellido_materno' => NULL,
                'num_control' => '20240001',
                'carrera' => 'Ingeniería en Sistemas Computacionales',
                'RFC' => NULL,
                'tipo_usuario' => 'Servicio',
                'activo' => 1,
            ),
        ));
        
        
    }
}
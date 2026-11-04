<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ServicioSocialUserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = app()->environment('testing') ? 'servicio123' : Str::random(24);

        $servicio = User::updateOrCreate(
            ['email' => 'servicio@tecnm.edu.mx'],
            [
                'name' => 'Alumno Servicio Social',
                'password' => Hash::make(env('SEED_SERVICIO_PASSWORD', $defaultPassword)),
                'tipo_usuario' => 'Servicio',
                'num_control' => '20240001',
                'carrera' => 'Ingeniería en Sistemas Computacionales',
                'activo' => true,
            ]
        );

        $servicio->assignRole('Servicio Social');
    }
}

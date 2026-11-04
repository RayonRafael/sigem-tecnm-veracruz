<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = app()->environment('testing') ? 'admin123' : Str::random(24);

        $admin = User::updateOrCreate(
            ['email' => 'admin@tecnm.edu.mx'],
            [
                'name' => 'Administrador',
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', $defaultPassword)),
                'tipo_usuario' => 'Administrador',
                'activo' => true,
            ]
        );

        $admin->assignRole('Administrador');
    }
}

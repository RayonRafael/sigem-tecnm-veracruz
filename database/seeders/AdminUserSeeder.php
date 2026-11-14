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
        if (app()->isProduction()) {
            return; // No tocar usuarios en producción
        }

        $defaultPassword = app()->environment('local', 'testing') ? 'admin123' : Str::random(24);

        // Solo crear si NO existe — nunca sobrescribir
        $admin = User::firstOrCreate(
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

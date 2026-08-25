<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
            ServicioSocialUserSeeder::class,
        ]);
        $this->call(AreaTableSeeder::class);
        $this->call(BitacoraSistemaTableSeeder::class);
        $this->call(DepartamentoTableSeeder::class);
        $this->call(DetalleSolicitudTableSeeder::class);
        $this->call(HistorialEstadosTableSeeder::class);
        $this->call(InventarioTableSeeder::class);
        $this->call(MantenimientoTableSeeder::class);
        $this->call(MarcaMaterialTableSeeder::class);
        $this->call(MaterialTableSeeder::class);
        $this->call(ModelHasPermissionsTableSeeder::class);
        $this->call(ModelHasRolesTableSeeder::class);
        $this->call(PermissionsTableSeeder::class);
        $this->call(ProveedoresTableSeeder::class);
        $this->call(ReceptorTableSeeder::class);
        $this->call(RoleHasPermissionsTableSeeder::class);
        $this->call(RolesTableSeeder::class);
        $this->call(SolicitudTableSeeder::class);
        $this->call(TipoMaterialTableSeeder::class);
        $this->call(UnidadMedidaTableSeeder::class);
        $this->call(UsersTableSeeder::class);
    }
}

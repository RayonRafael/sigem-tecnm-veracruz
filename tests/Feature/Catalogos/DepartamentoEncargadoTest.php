<?php

namespace Tests\Feature\Catalogos;

use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartamentoEncargadoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(ServicioSocialUserSeeder::class);
    }

    private function getAdminUser(): User
    {
        return User::where('email', 'admin@tecnm.edu.mx')->first();
    }

    public function test_campo_encargado_en_departamento_fillable_y_persistencia(): void
    {
        $fillable = (new Departamento())->getFillable();
        $this->assertContains('encargado', $fillable);

        $depto = Departamento::create([
            'nombre' => 'Departamento de Metal-Mecánica Especial',
            'encargado' => 'Ing. Roberto Fuentes',
        ]);

        $this->assertDatabaseHas('departamento', [
            'id_departamento' => $depto->id_departamento,
            'nombre' => 'Departamento de Metal-Mecánica Especial',
            'encargado' => 'Ing. Roberto Fuentes',
        ]);

        $this->assertEquals('Ing. Roberto Fuentes', $depto->fresh()->encargado);
    }

    public function test_relacion_departamento_con_inventarios(): void
    {
        $admin = $this->getAdminUser();

        $depto = Departamento::create([
            'nombre' => 'Departamento de Sistemas Avanzados',
            'encargado' => 'Dra. Laura Montes',
        ]);

        $inv1 = Inventario::create([
            'num_serie' => 'SN-DEP-001',
            'modelo' => 'ThinkCentre',
            'departamento_id' => $depto->id_departamento,
            'id_usuario' => $admin->id,
            'tipo_propiedad' => 'Propio',
            'estado' => 'Disponible',
            'fecha_registro' => now(),
        ]);

        $inv2 = Inventario::create([
            'num_serie' => 'SN-DEP-002',
            'modelo' => 'ProDesk',
            'departamento_id' => $depto->id_departamento,
            'id_usuario' => $admin->id,
            'tipo_propiedad' => 'Propio',
            'estado' => 'Disponible',
            'fecha_registro' => now(),
        ]);

        $this->assertTrue(method_exists($depto, 'inventarios'));
        $inventarios = $depto->inventarios;

        $this->assertCount(2, $inventarios);
        $this->assertTrue($inventarios->contains('id_inventario', $inv1->id_inventario));
        $this->assertTrue($inventarios->contains('id_inventario', $inv2->id_inventario));
    }
}

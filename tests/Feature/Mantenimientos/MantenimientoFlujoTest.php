<?php

namespace Tests\Feature\Mantenimientos;

use App\Filament\Resources\MantenimientoResource as AdminMantenimientoResource;
use App\Filament\ServicioSocial\Resources\MantenimientoResource as SSMantenimientoResource;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Material;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MantenimientoFlujoTest extends TestCase
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

    private function getServicioSocialUser(): User
    {
        return User::where('email', 'servicio@tecnm.edu.mx')->first();
    }

    private function crearInventario(User $user): Inventario
    {
        return Inventario::create([
            'num_serie' => 'SN-MANT-TEST',
            'modelo' => 'OptiPlex',
            'tipo_propiedad' => 'Propio',
            'estado' => 'Disponible',
            'fecha_registro' => now(),
            'id_usuario' => $user->id,
        ]);
    }

    public function test_servicio_social_puede_crear_mantenimiento(): void
    {
        $ssUser = $this->getServicioSocialUser();
        $this->actingAs($ssUser);

        $inv = $this->crearInventario($ssUser);

        $mant = Mantenimiento::create([
            'id_inventario' => $inv->id_inventario,
            'id_usuario_solicita' => $ssUser->id,
            'tipo_mantenimiento' => 'Correctivo',
            'tipo_servicio' => 'Personal Técnico',
            'nombre_tecnico' => 'Carlos López',
            'descripcion_falla' => 'Pantalla parpadea',
            'fecha_solicitud' => now(),
            'estado' => 'Pendiente Revision Admin',
        ]);

        $this->assertDatabaseHas('mantenimiento', [
            'id_mantenimiento' => $mant->id_mantenimiento,
            'tipo_mantenimiento' => 'Correctivo',
            'id_usuario_solicita' => $ssUser->id,
        ]);
    }

    public function test_mantenimiento_crea_estado_en_revision(): void
    {
        $ssUser = $this->getServicioSocialUser();
        $inv = $this->crearInventario($ssUser);

        $mant = Mantenimiento::create([
            'id_inventario' => $inv->id_inventario,
            'id_usuario_solicita' => $ssUser->id,
            'tipo_mantenimiento' => 'Preventivo',
            'tipo_servicio' => 'Personal Técnico',
            'nombre_tecnico' => 'Carlos López',
            'fecha_solicitud' => now(),
            'estado' => 'Pendiente Revision Admin',
        ]);

        $this->assertEquals('Pendiente Revision Admin', $mant->estado);
    }

    public function test_servicio_social_ve_solo_sus_propios_mantenimientos(): void
    {
        $ssUser = $this->getServicioSocialUser();
        $adminUser = $this->getAdminUser();

        $inv = $this->crearInventario($adminUser);

        $mantSS = Mantenimiento::create([
            'id_inventario' => $inv->id_inventario,
            'id_usuario_solicita' => $ssUser->id,
            'tipo_mantenimiento' => 'Correctivo',
            'tipo_servicio' => 'Personal Técnico',
            'nombre_tecnico' => 'Carlos López',
            'fecha_solicitud' => now(),
            'estado' => 'Pendiente Revision Admin',
        ]);

        $mantAdmin = Mantenimiento::create([
            'id_inventario' => $inv->id_inventario,
            'id_usuario_solicita' => $adminUser->id,
            'tipo_mantenimiento' => 'Preventivo',
            'tipo_servicio' => 'Personal Técnico',
            'nombre_tecnico' => 'Carlos López',
            'fecha_solicitud' => now(),
            'estado' => 'Pendiente Revision Admin',
        ]);

        Filament::setCurrentPanel(Filament::getPanel('servicio-social'));
        $this->actingAs($ssUser);

        $query = SSMantenimientoResource::getEloquentQuery();
        $idsVisibles = $query->pluck('id_mantenimiento')->all();

        $this->assertContains($mantSS->id_mantenimiento, $idsVisibles);
        $this->assertNotContains($mantAdmin->id_mantenimiento, $idsVisibles);
    }

    public function test_admin_puede_autorizar_mantenimiento(): void
    {
        $adminUser = $this->getAdminUser();
        $inv = $this->crearInventario($adminUser);

        $mant = Mantenimiento::create([
            'id_inventario' => $inv->id_inventario,
            'id_usuario_solicita' => $adminUser->id,
            'tipo_mantenimiento' => 'Correctivo',
            'tipo_servicio' => 'Personal Técnico',
            'nombre_tecnico' => 'Carlos López',
            'fecha_solicitud' => now(),
            'estado' => 'Pendiente Revision Admin',
        ]);

        $this->actingAs($adminUser);
        $mant->update([
            'estado' => 'En proceso',
            'fecha_inicio' => now(),
        ]);

        $this->assertEquals('En proceso', $mant->fresh()->estado);
        $this->assertNotNull($mant->fresh()->fecha_inicio);
    }

    public function test_admin_puede_completar_mantenimiento(): void
    {
        $adminUser = $this->getAdminUser();
        $inv = $this->crearInventario($adminUser);

        $mant = Mantenimiento::create([
            'id_inventario' => $inv->id_inventario,
            'id_usuario_solicita' => $adminUser->id,
            'tipo_mantenimiento' => 'Preventivo',
            'tipo_servicio' => 'Personal Técnico',
            'nombre_tecnico' => 'Carlos López',
            'fecha_solicitud' => now(),
            'estado' => 'En proceso',
            'fecha_inicio' => now(),
        ]);

        $this->actingAs($adminUser);
        $mant->update([
            'estado' => 'Completado',
            'fecha_fin' => now(),
        ]);

        $this->assertEquals('Completado', $mant->fresh()->estado);
        $this->assertNotNull($mant->fresh()->fecha_fin);
    }
}

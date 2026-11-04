<?php

namespace Tests\Feature\Roles;

use App\Filament\Pages\ConfiguracionPage;
use App\Filament\Pages\ReportesPage;
use App\Filament\ServicioSocial\Pages\CatalogosPage;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesBloque4Test extends TestCase
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

    public function test_servicio_social_accede_a_catalogos_en_modo_solo_lectura(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('servicio-social'));
        $this->actingAs($this->getServicioSocialUser())
            ->get(CatalogosPage::getUrl())
            ->assertSuccessful()
            ->assertSee('Solo lectura')
            ->assertSee('No puedes modificarla — el administrador la mantiene actualizada');
    }

    public function test_servicio_social_no_accede_a_configuracion_admin_bitacora(): void
    {
        // El usuario de servicio social no tiene acceso al panel de administración ni a su bitácora
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $response = $this->actingAs($this->getServicioSocialUser())
            ->get(ConfiguracionPage::getUrl());

        $this->assertTrue(in_array($response->status(), [403, 302]));
    }

    public function test_servicio_social_no_accede_a_preferencias_admin(): void
    {
        // SS intentando acceder al endpoint de configuración del Admin es denegado
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $response = $this->actingAs($this->getServicioSocialUser())
            ->get('/admin/configuracion');

        $this->assertTrue(in_array($response->status(), [403, 302]));
    }

    public function test_admin_accede_a_todo_sin_restricciones(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = $this->getAdminUser();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertSuccessful();

        $this->actingAs($admin)
            ->get(ConfiguracionPage::getUrl())
            ->assertSuccessful();

        $this->actingAs($admin)
            ->get(ReportesPage::getUrl())
            ->assertSuccessful();
    }
}


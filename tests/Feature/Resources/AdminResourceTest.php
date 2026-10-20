<?php

namespace Tests\Feature\Resources;

use App\Filament\Pages\ConfiguracionPage;
use App\Filament\Pages\ReportesPage;
use App\Filament\Resources\AreaResource;
use App\Filament\Resources\DepartamentoResource;
use App\Filament\Resources\InventarioResource;
use App\Filament\Resources\MantenimientoResource;
use App\Filament\Resources\MaterialResource;
use App\Filament\Resources\ProveedorResource;
use App\Filament\Resources\ReceptorResource;
use App\Filament\Resources\SolicitudResource;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(ServicioSocialUserSeeder::class);
    }

    private function getAdminUser()
    {
        return User::where('email', 'admin@tecnm.edu.mx')->first();
    }

    public function test_admin_can_access_areas_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser())
            ->get(AreaResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_admin_can_access_departamentos_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser())
            ->get(DepartamentoResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_admin_can_access_materiales_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser())
            ->get(MaterialResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_admin_can_access_inventarios_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser())
            ->get(InventarioResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_admin_can_access_solicitudes_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser())
            ->get(SolicitudResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_admin_can_access_mantenimientos_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser())
            ->get(MantenimientoResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_admin_can_access_proveedores_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser())
            ->get(ProveedorResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_admin_can_access_receptores_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser())
            ->get(ReceptorResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_admin_can_access_users_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser())
            ->get(UserResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_admin_can_access_reportes_page()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser())
            ->get(ReportesPage::getUrl())
            ->assertSuccessful();
    }

    public function test_admin_can_toggle_and_view_reportes()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        \Livewire\Livewire::actingAs($this->getAdminUser())
            ->test(ReportesPage::class)
            ->assertSee('Reportes')
            ->assertSee('Genera reportes del inventario')
            ->call('toggleVisibilidad', 'inventario_categoria')
            ->assertSet('visibilidadServicioSocial.inventario_categoria', false)
            ->call('verReporte', 'inventario_categoria')
            ->assertSet('reporteActivo', 'inventario_categoria')
            ->assertSee('Inventario por categoría')
            ->call('cerrarReporte')
            ->assertSet('reporteActivo', null);
    }

    public function test_admin_can_access_configuracion_page()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser())
            ->get(ConfiguracionPage::getUrl())
            ->assertSuccessful();
    }

    public function test_admin_can_interact_with_configuracion_tabs()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        \Livewire\Livewire::actingAs($this->getAdminUser())
            ->test(ConfiguracionPage::class)
            ->assertSee('Configuración & Catálogos')
            ->assertSee('Catálogos')
            ->assertSee('Bitácora')
            ->assertSee('Preferencias')
            ->call('setCatalogo', 'areas')
            ->assertSet('catalogoActivo', 'areas')
            ->call('setTab', 'bitacora')
            ->assertSet('tabActiva', 'bitacora')
            ->assertSee('Bitácora de actividades')
            ->call('setTab', 'preferencias')
            ->assertSet('tabActiva', 'preferencias')
            ->call('seleccionarRegistrosPorHoja', 50)
            ->assertSet('registrosPorHoja', 50)
            ->call('guardarPreferencias')
            ->assertSee('Preferencias guardadas');
    }
}

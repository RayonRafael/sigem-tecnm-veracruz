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
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\Receptor;
use App\Models\Solicitud;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
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

    public function test_command_palette_shows_sections_when_query_is_empty()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        \Livewire\Livewire::actingAs($this->getAdminUser())
            ->test(\App\Livewire\CommandPalette::class)
            ->assertSee('IR A')
            ->assertSee('Inventario general')
            ->assertSee('Solicitudes')
            ->assertSee('Mantenimiento')
            ->assertSee('Reportes')
            ->assertSee('Configuración & Catálogos')
            ->assertDontSee('⌘K')
            ->assertDontSee('Ctrl+K');
    }

    public function test_command_palette_open_and_close()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        \Livewire\Livewire::actingAs($this->getAdminUser())
            ->test(\App\Livewire\CommandPalette::class)
            ->assertSet('abierto', false)
            ->call('abrir')
            ->assertSet('abierto', true)
            ->call('cerrar')
            ->assertSet('abierto', false);
    }

    public function test_command_palette_search_with_accent_tolerance()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        \Livewire\Livewire::actingAs($this->getAdminUser())
            ->test(\App\Livewire\CommandPalette::class)
            ->set('q', 'reportes')
            ->assertSee('Reportes')
            ->set('q', 'configuracion')
            ->assertSee('Configuración & Catálogos')
            ->set('q', 'configuración')
            ->assertSee('Configuración & Catálogos');
    }

    public function test_topbar_contains_search_field_without_shortcut_hints()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $response = $this->actingAs($this->getAdminUser())
            ->get(AreaResource::getUrl('index'));

        $response->assertSuccessful();
        $response->assertSee('sigem-topbar-search');
        $response->assertSee('Buscar en SIGEM…');
        $response->assertDontSee('⌘K');
        $response->assertDontSee('Ctrl+K');
    }

    public function test_command_palette_searches_models_and_returns_grouped_results()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $user = $this->getAdminUser();

        // 1. Crear Material e Inventario
        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pieza']);
        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'Dell']);
        $tipo = TipoMaterial::firstOrCreate(['nombre' => 'Equipo de Cómputo']);
        $material = Material::create([
            'nombre' => 'Servidor PowerEdge',
            'id_unidad' => $unidad->id_unidad,
            'id_marca' => $marca->id_marca,
            'id_tipodematerial' => $tipo->id_tipodematerial,
            'stock_actual' => 1,
            'requiere_control_individual' => 1,
        ]);
        $inventario = Inventario::create([
            'num_serie' => 'SRV-DELL-9988',
            'id_producto' => $material->id_producto,
            'id_usuario' => $user->id,
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'ubicacion_fisica' => 'Edificio B - Centro de Cómputo',
            'fecha_registro' => now(),
        ]);

        // 2. Crear Solicitud
        $depto = \App\Models\Departamento::firstOrCreate(['nombre' => 'Sistemas']);
        $area = \App\Models\Area::firstOrCreate(['nombre' => 'Laboratorio', 'id_departamento' => $depto->id_departamento]);
        $receptor = Receptor::firstOrCreate([
            'nombre' => 'Carlos',
            'apellido_paterno' => 'González',
            'apellido_materno' => 'López',
            'id_area' => $area->id_area,
        ]);
        $solicitud = Solicitud::create([
            'id_usuario' => $user->id,
            'id_receptor' => $receptor->id_receptor,
            'tipo_movimiento' => 'Asignacion Permanente',
            'estado' => 'Autorizado',
            'fecha_solicitud' => now(),
            'observaciones' => 'Solicitud de prueba buscador',
        ]);

        // 3. Crear Mantenimiento
        $mantenimiento = Mantenimiento::create([
            'id_inventario' => $inventario->id_inventario,
            'id_usuario_solicita' => $user->id,
            'nombre_tecnico' => 'Ing. Juan Ramírez',
            'tipo_servicio' => 'Personal Técnico',
            'tipo_mantenimiento' => 'Preventivo',
            'fecha_solicitud' => now(),
            'estado' => 'En proceso',
        ]);

        // Búsqueda de equipo
        \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\CommandPalette::class)
            ->set('q', 'SRV-DELL')
            ->assertSee('EQUIPOS')
            ->assertSee('SRV-DELL-9988 · Servidor PowerEdge')
            ->assertSee('Edificio B - Centro de Cómputo');

        // Búsqueda de solicitud
        \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\CommandPalette::class)
            ->set('q', 'Asignación')
            ->assertSee('SOLICITUDES')
            ->assertSee('Asignacion Permanente');

        // Búsqueda de solicitud tolerante a acentos
        \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\CommandPalette::class)
            ->set('q', 'asignacion')
            ->assertSee('SOLICITUDES')
            ->assertSee('Asignacion Permanente');

        // Búsqueda de mantenimiento
        \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\CommandPalette::class)
            ->set('q', 'Ramírez')
            ->assertSee('MANTENIMIENTOS')
            ->assertSee('Preventivo');

        // Búsqueda de mantenimiento sin acento
        \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\CommandPalette::class)
            ->set('q', 'ramirez')
            ->assertSee('MANTENIMIENTOS')
            ->assertSee('Preventivo');
    }

    public function test_pagination_preference_is_connected_to_tables()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $user = $this->getAdminUser();

        // 1. Probar valor predeterminado
        session()->forget('sigem_registros_por_hoja');
        \Illuminate\Support\Facades\Cache::forget('sigem_registros_por_hoja_' . $user->id);
        $this->actingAs($user);
        $this->assertEquals(10, \App\Support\SigemPagination::getPageOption());

        // 2. Simular cambio de preferencia a 50
        session(['sigem_registros_por_hoja' => 50]);
        $this->assertEquals(50, \App\Support\SigemPagination::getPageOption());
        $this->assertContains(50, \App\Support\SigemPagination::getPageOptions());

        // 3. Verificar que la tabla de Inventario resuelve 50 como defaultPaginationPageOption
        $page = new \App\Filament\Resources\InventarioResource\Pages\ListInventarios();
        $table = \App\Filament\Resources\InventarioResource::table(new \Filament\Tables\Table($page));
        $this->assertEquals(50, $table->getDefaultPaginationPageOption());

        // 4. Verificar en Solicitud y Mantenimiento
        $pageSol = new \App\Filament\Resources\SolicitudResource\Pages\ListSolicituds();
        $tableSol = \App\Filament\Resources\SolicitudResource::table(new \Filament\Tables\Table($pageSol));
        $this->assertEquals(50, $tableSol->getDefaultPaginationPageOption());

        $pageMant = new \App\Filament\Resources\MantenimientoResource\Pages\ListMantenimientos();
        $tableMant = \App\Filament\Resources\MantenimientoResource::table(new \Filament\Tables\Table($pageMant));
        $this->assertEquals(50, $tableMant->getDefaultPaginationPageOption());
    }

    public function test_list_inventarios_summary_clicks_filter_by_estado_and_categoria()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $user = $this->getAdminUser();

        \Livewire\Livewire::actingAs($user)
            ->test(\App\Filament\Resources\InventarioResource\Pages\ListInventarios::class)
            ->assertSet('tableFilters.estado.value', null)
            ->call('filtrarPorEstado', 'Disponible')
            ->assertSet('tableFilters.estado.value', 'Disponible')
            ->call('filtrarPorEstado', 'Disponible')
            ->assertSet('tableFilters.estado.value', null)
            ->call('filtrarPorCategoria', 'Laptop')
            ->assertSet('tableSearch', 'Laptop')
            ->call('filtrarPorCategoria', 'Laptop')
            ->assertSet('tableSearch', '');
    }
}




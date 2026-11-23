<?php

namespace Tests\Feature\Panel;

use App\Filament\Pages\ConfiguracionPage;
use App\Filament\Pages\Dashboard as AdminDashboard;
use App\Filament\Pages\ReportesPage as AdminReportesPage;
use App\Filament\Resources\InventarioResource\Pages\ListInventarios;
use App\Filament\ServicioSocial\Pages\Dashboard as SsDashboard;
use App\Filament\ServicioSocial\Pages\ReportesPage as SsReportesPage;
use App\Livewire\CommandPalette;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\DetalleSolicitud;
use App\Models\HistorialEstado;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\Proveedor;
use App\Models\Receptor;
use App\Models\Salon;
use App\Models\Solicitud;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use App\Support\SigemReportExporter;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PostRefactorAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $ss;
    private Departamento $depto;
    private Area $area;
    private Salon $salon;
    private Material $material;
    private Proveedor $proveedor;
    private Receptor $receptor;
    private Inventario $equipoPropio;
    private Inventario $equipoRentado;
    private Inventario $equipoBaja;
    private Inventario $equipoCambioArea;
    private Solicitud $solicitud;
    private Mantenimiento $mantenimiento;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(ServicioSocialUserSeeder::class);

        $this->admin = User::where('email', 'admin@tecnm.edu.mx')->firstOrFail();
        $this->ss = User::where('email', 'servicio@tecnm.edu.mx')->firstOrFail();

        $this->depto = Departamento::create(['nombre' => 'Depto Sistemas', 'encargado' => 'Dr. Test']);
        $this->area = Area::create(['nombre' => 'Laboratorio Redes', 'id_departamento' => $this->depto->id_departamento]);
        $this->salon = Salon::create(['nombre' => 'R101', 'area_id' => $this->area->id_area]);

        $marca = MarcaMaterial::create(['nombre' => 'Dell']);
        $tipo = TipoMaterial::create(['nombre' => 'Equipo de Cómputo', 'familia' => 'Equipos completos']);
        $unidad = UnidadMedida::create(['nombre' => 'Pieza']);
        $this->material = Material::create([
            'nombre' => 'OptiPlex 7090',
            'id_marca' => $marca->id_marca,
            'id_tipodematerial' => $tipo->id_tipodematerial,
            'id_unidad' => $unidad->id_unidad,
            'stock_minimo' => 1,
            'stock_maximo' => 100,
        ]);

        $this->proveedor = Proveedor::create([
            'nombre_empresa' => 'TechRent SA',
            'rfc' => 'TRE200101AAA',
            'activo' => true,
        ]);

        $this->receptor = Receptor::create([
            'nombre' => 'Roberto',
            'apellido_paterno' => 'Hernández',
            'apellido_materno' => 'García',
            'cargo' => 'Docente',
            'id_area' => $this->area->id_area,
        ]);

        $this->equipoPropio = Inventario::create([
            'id_producto' => $this->material->id_producto,
            'codigo' => 'EQ-AUDIT-001',
            'num_serie' => 'SN-AUDIT-001',
            'estado' => 'Disponible',
            'tipo_propiedad' => 'Propio',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'salon_id' => $this->salon->id_salon,
            'aula' => 'R101',
            'fecha_registro' => now()->subMonths(6),
            'id_usuario' => $this->admin->id,
            'especificaciones' => ['receptor_nombre' => 'Roberto Hernández García'],
        ]);

        $this->equipoRentado = Inventario::create([
            'id_producto' => $this->material->id_producto,
            'codigo' => 'EQ-RENT-002',
            'num_serie' => 'SN-RENT-002',
            'estado' => 'Disponible',
            'tipo_propiedad' => 'Rentado',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'proveedor_id' => $this->proveedor->id_proveedor,
            'fecha_registro' => now()->subYears(2)->subMonths(11),
            'id_usuario' => $this->admin->id,
            'especificaciones' => ['renta_fecha_fin' => now()->addDays(20)->format('Y-m-d')],
        ]);

        $this->equipoBaja = Inventario::create([
            'id_producto' => $this->material->id_producto,
            'codigo' => 'EQ-BAJA-003',
            'num_serie' => 'SN-BAJA-003',
            'estado' => 'Dado de baja',
            'tipo_propiedad' => 'Propio',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'fecha_registro' => now()->subMonths(12),
            'fecha_baja' => now()->subDays(2),
            'motivo_baja' => 'Obsolescencia',
            'id_usuario' => $this->admin->id,
        ]);

        $this->equipoCambioArea = Inventario::create([
            'id_producto' => $this->material->id_producto,
            'codigo' => 'EQ-CAMB-004',
            'num_serie' => 'SN-CAMB-004',
            'estado' => 'Cambio de área',
            'tipo_propiedad' => 'Propio',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'fecha_registro' => now()->subMonths(4),
            'fecha_cambio_area' => now()->subDay(),
            'id_usuario' => $this->admin->id,
            'especificaciones' => ['cambio_origen_depto' => 'Depto Industrial'],
        ]);

        $this->solicitud = Solicitud::create([
            'id_usuario' => $this->ss->id,
            'id_receptor' => $this->receptor->id_receptor,
            'tipo_movimiento' => 'Asignacion Permanente',
            'fecha_solicitud' => now(),
            'estado' => 'Autorizado',
            'observaciones' => 'Prueba de auditoría',
        ]);
        DetalleSolicitud::create([
            'id_solicitud' => $this->solicitud->id_solicitud,
            'id_producto' => $this->material->id_producto,
            'cantidad' => 1,
        ]);

        $this->mantenimiento = Mantenimiento::create([
            'id_inventario' => $this->equipoPropio->id_inventario,
            'id_usuario_solicita' => $this->ss->id,
            'tipo_mantenimiento' => 'Preventivo',
            'tipo_servicio' => 'Servicio Social',
            'nombre_tecnico' => 'Técnico Prueba',
            'num_control_tecnico' => '20240001',
            'descripcion_falla' => 'Limpieza interna',
            'descripcion_trabajo' => 'Se realizó limpieza completa',
            'estado' => 'Completado',
            'fecha_solicitud' => now()->subDays(3),
            'fecha_fin' => now(),
        ]);

        HistorialEstado::create([
            'id_inventario' => $this->equipoPropio->id_inventario,
            'id_usuario' => $this->admin->id,
            'estado_anterior' => 'Disponible',
            'estado_nuevo' => 'Cambio de área',
            'motivo_cambio' => 'Cambio de área: Depto Industrial → Depto Sistemas',
            'fecha_cambio' => now(),
        ]);
    }

    public function test_panels_register_with_valid_id_even_with_cached_components_check(): void
    {
        $adminPanel = Filament::getPanel('admin');
        $ssPanel = Filament::getPanel('servicio-social');

        $this->assertSame('admin', $adminPanel->getId());
        $this->assertSame('servicio-social', $ssPanel->getId());
        $this->assertTrue($adminPanel->isDefault());
        $this->assertFalse($ssPanel->isDefault());
    }

    public function test_all_admin_pages_return_200(): void
    {
        // #1 Login
        $this->get('/admin/login')->assertOk();

        // #2 - #22 Authenticated Admin routes
        $urls = [
            '/admin',
            '/admin/inventarios',
            '/admin/inventarios/create',
            '/admin/inventarios/' . $this->equipoPropio->id_inventario . '/edit',
            '/admin/solicituds',
            '/admin/mantenimientos',
            '/admin/reportes',
            '/admin/configuracion',
            '/admin/profile',
            '/admin/users',
            '/admin/departamentos',
            '/admin/areas',
            '/admin/receptors',
            '/admin/materials',
            '/admin/marca-materials',
            '/admin/tipo-materials',
            '/admin/unidad-medidas',
            '/admin/proveedors',
        ];

        foreach ($urls as $url) {
            $resp = $this->actingAs($this->admin)->get($url);
            $this->assertSame(200, $resp->getStatusCode(), "Admin URL failed: {$url}");
        }
    }

    public function test_all_servicio_social_pages_return_200(): void
    {
        // #23 SS Login
        $this->get('/servicio-social/login')->assertOk();

        // #24 - #29 Authenticated SS routes
        $urls = [
            '/servicio-social',
            '/servicio-social/inventarios',
            '/servicio-social/solicituds',
            '/servicio-social/mantenimientos',
            '/servicio-social/reportes',
            '/servicio-social/catalogos',
        ];

        foreach ($urls as $url) {
            $resp = $this->actingAs($this->ss)->get($url);
            $this->assertSame(200, $resp->getStatusCode(), "SS URL failed: {$url}");
        }
    }

    public function test_livewire_tabs_and_slideovers_render_without_errors(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->admin);

        // #4, #5, #6 Inventario create/edit/view table actions
        Livewire::test(ListInventarios::class)
            ->assertOk()
            ->assertTableActionExists('view')
            ->assertTableActionExists('edit')
            ->mountTableAction('view', $this->equipoPropio)
            ->assertOk();

        // #9, #10, #11 Reportes 3 tabs + subtabs + Chart.js (#43)
        Livewire::test(AdminReportesPage::class)
            ->assertOk()
            ->call('cambiarPestanaPrincipal', 'formatos')
            ->assertSet('pestanaPrincipal', 'formatos')
            ->call('cambiarPestanaFormato', 'filtrar')
            ->assertSet('pestanaFormatoActiva', 'filtrar')
            ->call('cambiarPestanaPrincipal', 'nuevos')
            ->assertSet('pestanaPrincipal', 'nuevos')
            ->call('cambiarSubpestanaReportes', 'rentas')
            ->assertSet('subpestanaReportes', 'rentas');

        // #12 Configuracion 3 tabs
        Livewire::test(ConfiguracionPage::class)
            ->assertOk()
            ->call('setTab', 'bitacora')
            ->assertSet('tabActiva', 'bitacora')
            ->call('setTab', 'preferencias')
            ->assertSet('tabActiva', 'preferencias');

        // #24 & #28 SS Dashboard & Reportes
        Filament::setCurrentPanel(Filament::getPanel('servicio-social'));
        $this->actingAs($this->ss);
        Livewire::test(SsDashboard::class)->assertOk();
        Livewire::test(SsReportesPage::class)
            ->assertOk()
            ->call('cambiarPestanaPrincipal', 'formatos')
            ->assertSet('pestanaPrincipal', 'formatos')
            ->call('cambiarPestanaPrincipal', 'nuevos')
            ->assertSet('pestanaPrincipal', 'nuevos');
    }

    public function test_critical_functionalities_30_to_45(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->admin);

        // #30 Chained filters, #31 Per-page selection, #32/#33/#35 Side panel (Cambio propiedad & Baja), #34 Excel export
        Livewire::test(AdminDashboard::class)
            ->set('tableColumnSearches.departamento', $this->depto->nombre)
            ->assertSet('tableColumnSearches.area', '')
            ->set('tableColumnSearches.area', $this->area->nombre)
            ->assertSet('tableColumnSearches.aula', '')
            ->call('toggleSeleccionarPagina')
            ->assertSet('showSidePanel', true)
            ->assertNotSet('selectedRows', [])
            ->call('setSlideOverStep', 'cambio')
            ->assertSet('slideOverStep', 'cambio')
            ->set('cambioNuevoEstado', 'Cambio de propiedad')
            ->set('cambioNuevoTipoPropiedad', 'Rentado')
            ->call('ejecutarCambioSlideOver')
            ->assertOk()
            ->call('toggleSeleccionarPagina')
            ->call('setSlideOverStep', 'baja')
            ->assertSet('slideOverStep', 'baja')
            ->set('bajaMotivo', 'Obsolescencia')
            ->call('ejecutarBajaSlideOver')
            ->assertOk()
            ->set('selectedRows', [(string) $this->equipoPropio->id_inventario])
            ->call('exportarSeleccion')
            ->assertFileDownloaded();

        $this->assertSame('Rentado', $this->equipoPropio->fresh()->tipo_propiedad);

        // #36 Receptor in create/edit slide-over
        Livewire::test(ListInventarios::class)
            ->mountTableAction('edit', $this->equipoPropio)
            ->assertTableActionDataSet(['receptor_id' => null])
            ->assertOk();

        // #37 Rentas tracking + ALL 29 reports across all 5 builders + #43 Chart.js + versioned cache
        $verBefore = SigemReportExporter::cacheVersion();
        foreach (SigemReportExporter::getCatalogo() as $sub => $reps) {
            foreach (array_keys($reps) as $repId) {
                $res = SigemReportExporter::getReportData($repId);
                $this->assertArrayHasKey('rows', $res, "Report failed: {$repId}");
                $this->assertArrayHasKey('headers', $res, "Report missing headers: {$repId}");
            }
        }
        $rentas = SigemReportExporter::getReportData('rent_seguimiento');
        $this->assertGreaterThanOrEqual(1, $rentas['total']);
        $chartRep = SigemReportExporter::getReportData('estadisticas_propio_vs_rentado');
        $this->assertTrue($chartRep['has_chart']);
        $this->assertIsArray($chartRep['chart']);
        SigemReportExporter::clearCache();
        $this->assertSame($verBefore + 1, SigemReportExporter::cacheVersion());

        // #38 - #42 All 5 PDF institutional formats
        $reportes = Livewire::test(AdminReportesPage::class);
        foreach (['entrega', 'orden_trabajo', 'lista_verificacion', 'baja', 'cambio_area'] as $fmt) {
            $reportes->call('descargarFormatoInstitucional', $fmt)->assertFileDownloaded();
        }

        // #44 Command Palette
        Livewire::test(CommandPalette::class)
            ->call('abrir')
            ->set('q', 'EQ-AUDIT-001')
            ->assertOk()
            ->assertSee('EQ-AUDIT-001');

        // #45 Compiled CSS theme exists, includes Mi Perfil styles, 11-column table rules and No tiene toggle
        $cssPath = public_path('css/sigem-theme.css');
        $this->assertFileExists($cssPath);
        $this->assertGreaterThan(100_000, filesize($cssPath));
        $cssContent = file_get_contents($cssPath);
        $this->assertStringContainsString('.sigem-profile-card', $cssContent);
        $this->assertStringContainsString('.sigem-profile-avatar', $cssContent);
        $this->assertStringContainsString('.fi-ta-table:has(th:nth-child(9))', $cssContent);
        $this->assertStringContainsString('.sigem-no-tiene-toggle', $cssContent);

        // Favicon SVG con type explícito y ruta relativa segura para Render HTTPS
        $solicitudesHtml = $this->actingAs($this->admin)->get('/admin/solicituds')->assertOk()->getContent();
        $this->assertStringContainsString('type="image/svg+xml" href="/images/sigem-icon.svg?v=2"', $solicitudesHtml);

        // Slide-over Registrar equipo renderiza el selector visible "No tiene" con clase sigem-no-tiene-toggle
        Livewire::test(AdminDashboard::class)
            ->mountAction('registrarEquipo')
            ->assertSee('sigem-no-tiene-toggle', false)
            ->assertSee('No tiene');
    }
}

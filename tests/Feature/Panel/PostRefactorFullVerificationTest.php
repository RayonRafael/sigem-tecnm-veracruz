<?php

namespace Tests\Feature\Panel;

use App\Filament\Pages\ConfiguracionPage;
use App\Filament\Pages\Dashboard as AdminDashboard;
use App\Filament\Pages\EditProfile;
use App\Filament\Pages\ReportesPage as AdminReportesPage;
use App\Filament\ServicioSocial\Pages\Dashboard as SsDashboard;
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
use App\Support\SigemExcelExporter;
use App\Support\SigemPdfGenerator;
use App\Support\SigemReportExporter;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;
use ZipArchive;

class PostRefactorFullVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $ss;
    private Departamento $deptoSistemas;
    private Departamento $deptoIndustrial;
    private Area $areaRedes;
    private Area $areaManufactura;
    private Salon $salonR101;
    private Material $material;
    private Proveedor $proveedor;
    private Receptor $receptor;
    private Inventario $equipoPropio;
    private Inventario $equipoRentadoVigente;
    private Inventario $equipoRentadoPorVencer;
    private Inventario $equipoRentadoVencido;
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

        $this->deptoSistemas = Departamento::create(['nombre' => 'Depto Sistemas', 'encargado' => 'Dr. Sistemas']);
        $this->deptoIndustrial = Departamento::create(['nombre' => 'Depto Industrial', 'encargado' => 'Dr. Industrial']);
        $this->areaRedes = Area::create(['nombre' => 'Laboratorio Redes', 'id_departamento' => $this->deptoSistemas->id_departamento]);
        $this->areaManufactura = Area::create(['nombre' => 'Taller Manufactura', 'id_departamento' => $this->deptoIndustrial->id_departamento]);
        $this->salonR101 = Salon::create(['nombre' => 'R101', 'area_id' => $this->areaRedes->id_area]);

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
            'id_area' => $this->areaRedes->id_area,
        ]);

        $this->equipoPropio = Inventario::create([
            'id_producto' => $this->material->id_producto,
            'codigo' => 'EQ-VER-001',
            'num_serie' => 'SN-VER-001',
            'estado' => 'Disponible',
            'tipo_propiedad' => 'Propio',
            'departamento_id' => $this->deptoSistemas->id_departamento,
            'area_id' => $this->areaRedes->id_area,
            'salon_id' => $this->salonR101->id_salon,
            'aula' => 'R101',
            'ip_address' => '192.168.10.15',
            'mac_address' => 'AA:BB:CC:DD:EE:01',
            'fecha_registro' => now()->subMonths(6),
            'id_usuario' => $this->admin->id,
            'especificaciones' => ['receptor_nombre' => 'Roberto Hernández García'],
        ]);

        $this->equipoRentadoVigente = Inventario::create([
            'id_producto' => $this->material->id_producto,
            'codigo' => 'EQ-RENT-VIG',
            'num_serie' => 'SN-RENT-VIG',
            'estado' => 'Disponible',
            'tipo_propiedad' => 'Rentado',
            'departamento_id' => $this->deptoSistemas->id_departamento,
            'area_id' => $this->areaRedes->id_area,
            'proveedor_id' => $this->proveedor->id_proveedor,
            'fecha_registro' => now()->subMonths(3),
            'id_usuario' => $this->admin->id,
            'especificaciones' => ['renta_fecha_fin' => now()->addMonths(18)->format('Y-m-d')],
        ]);

        $this->equipoRentadoPorVencer = Inventario::create([
            'id_producto' => $this->material->id_producto,
            'codigo' => 'EQ-RENT-PRX',
            'num_serie' => 'SN-RENT-PRX',
            'estado' => 'Disponible',
            'tipo_propiedad' => 'Rentado',
            'departamento_id' => $this->deptoIndustrial->id_departamento,
            'area_id' => $this->areaManufactura->id_area,
            'proveedor_id' => $this->proveedor->id_proveedor,
            'fecha_registro' => now()->subYears(2)->subMonths(10),
            'id_usuario' => $this->admin->id,
            'especificaciones' => ['renta_fecha_fin' => now()->addDays(25)->format('Y-m-d')],
        ]);

        $this->equipoRentadoVencido = Inventario::create([
            'id_producto' => $this->material->id_producto,
            'codigo' => 'EQ-RENT-VEN',
            'num_serie' => 'SN-RENT-VEN',
            'estado' => 'En Mantenimiento',
            'tipo_propiedad' => 'Rentado',
            'departamento_id' => $this->deptoIndustrial->id_departamento,
            'area_id' => $this->areaManufactura->id_area,
            'proveedor_id' => $this->proveedor->id_proveedor,
            'fecha_registro' => now()->subYears(3)->subDays(10),
            'id_usuario' => $this->admin->id,
            'especificaciones' => ['renta_fecha_fin' => now()->subDays(10)->format('Y-m-d')],
        ]);

        $this->equipoBaja = Inventario::create([
            'id_producto' => $this->material->id_producto,
            'codigo' => 'EQ-BAJA-001',
            'num_serie' => 'SN-BAJA-001',
            'estado' => 'Dado de baja',
            'tipo_propiedad' => 'Propio',
            'departamento_id' => $this->deptoSistemas->id_departamento,
            'area_id' => $this->areaRedes->id_area,
            'fecha_registro' => now()->subMonths(12),
            'fecha_baja' => now()->subDays(2),
            'motivo_baja' => 'Obsolescencia',
            'id_usuario' => $this->admin->id,
        ]);

        Inventario::create([
            'id_producto' => $this->material->id_producto,
            'codigo' => 'EQ-DANO-001',
            'num_serie' => 'SN-DANO-001',
            'estado' => 'Dañado',
            'tipo_propiedad' => 'Propio',
            'departamento_id' => $this->deptoSistemas->id_departamento,
            'area_id' => $this->areaRedes->id_area,
            'fecha_registro' => now()->subMonths(8),
            'id_usuario' => $this->admin->id,
        ]);

        $this->equipoCambioArea = Inventario::create([
            'id_producto' => $this->material->id_producto,
            'codigo' => 'EQ-CAMB-001',
            'num_serie' => 'SN-CAMB-001',
            'estado' => 'Cambio de área',
            'tipo_propiedad' => 'Propio',
            'departamento_id' => $this->deptoSistemas->id_departamento,
            'area_id' => $this->areaRedes->id_area,
            'aula' => 'R101',
            'fecha_registro' => now()->subMonths(4),
            'fecha_cambio_area' => now()->subDay(),
            'id_usuario' => $this->admin->id,
            'especificaciones' => [
                'cambio_origen_depto' => 'Depto Industrial',
                'cambio_origen_area' => 'Taller Manufactura',
                'cambio_motivo' => 'Reasignación por demanda académica',
            ],
        ]);

        $this->solicitud = Solicitud::create([
            'id_usuario' => $this->ss->id,
            'id_receptor' => $this->receptor->id_receptor,
            'tipo_movimiento' => 'Asignacion Permanente',
            'fecha_solicitud' => now(),
            'estado' => 'Autorizado',
            'observaciones' => 'Entrega de equipo verificado',
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
            'nombre_tecnico' => 'Técnico Verificación',
            'num_control_tecnico' => '20240099',
            'descripcion_falla' => 'Mantenimiento preventivo semestral',
            'descripcion_trabajo' => 'Limpieza general y actualización de sistema',
            'estado' => 'Completado',
            'fecha_solicitud' => now()->subDays(3),
            'fecha_fin' => now(),
        ]);

        HistorialEstado::create([
            'id_inventario' => $this->equipoPropio->id_inventario,
            'id_usuario' => $this->admin->id,
            'estado_anterior' => 'Disponible',
            'estado_nuevo' => 'Cambio de área',
            'motivo_cambio' => 'Traslado laboratorio',
            'fecha_cambio' => now(),
        ]);

        activity()
            ->causedBy($this->admin)
            ->performedOn($this->equipoPropio)
            ->event('updated')
            ->log('Actualización de equipo EQ-VER-001');
    }

    /**
     * Parte 1 & 2: Verifica los 10 eventos críticos Livewire end-to-end.
     */
    public function test_part2_all_10_livewire_events_work(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->admin);

        // Crear equipos extra para probar selección SOLO de la página actual (perPage=10 vs 12 equipos)
        for ($i = 1; $i <= 7; $i++) {
            Inventario::create([
                'id_producto' => $this->material->id_producto,
                'codigo' => sprintf('EQ-PAG-%03d', $i),
                'num_serie' => sprintf('SN-PAG-%03d', $i),
                'estado' => 'Disponible',
                'tipo_propiedad' => 'Propio',
                'departamento_id' => $this->deptoSistemas->id_departamento,
                'area_id' => $this->areaRedes->id_area,
                'fecha_registro' => now()->subDays($i),
                'id_usuario' => $this->admin->id,
            ]);
        }

        // Eventos #1 (filtros encadenados), #2 (checkbox abre side-panel), #3 (seleccionar solo página), #6 (cambio/baja/modificar)
        $dash = Livewire::test(AdminDashboard::class)
            // #1 Seleccionar depto -> área se filtra y aula/área previas se limpian
            ->set('tableColumnSearches.area', 'Taller Manufactura')
            ->set('tableColumnSearches.departamento', 'Depto Sistemas')
            ->assertSet('tableColumnSearches.area', '')
            ->assertSet('tableColumnSearches.aula', '')
            ->assertSee('Laboratorio Redes')
            ->call('resetTableColumnSearches')
            // #2 Marcar 1 checkbox -> abre side-panel con ese equipo
            ->set('selectedRows', [(string) $this->equipoPropio->id_inventario])
            ->assertSet('showSidePanel', true)
            ->assertSee('EQ-VER-001')
            ->assertSee('Cambio de estado o área')
            ->call('deseleccionarTodos')
            ->assertSet('showSidePanel', false)
            // #3 Toggle seleccionar página -> marca SOLO 10 (página actual), no los 13 totales
            ->call('toggleSeleccionarPagina')
            ->assertSet('selectAll', true)
            ->assertSet('showSidePanel', true);

        $selectedPage = $dash->get('selectedRows');
        $this->assertCount(10, $selectedPage, 'toggleSeleccionarPagina debe seleccionar solo la página actual (10)');

        // #6 Side-panel steps: cambio, baja, modificar + ejecución real de modificar
        $dash->call('deseleccionarTodos')
            ->set('selectedRows', [(string) $this->equipoPropio->id_inventario])
            ->call('setSlideOverStep', 'cambio')
            ->assertSee('Nuevo estado')
            ->call('setSlideOverStep', 'baja')
            ->assertSee('Motivo de baja')
            ->call('setSlideOverStep', 'modificar')
            ->assertSee('Campo a modificar')
            ->set('modificarCampo', 'ubicacion_fisica')
            ->set('modificarNuevoValor', 'Depto Sistemas > Laboratorio Redes > R101')
            ->call('ejecutarModificarSlideOver')
            ->assertNotified();

        $this->assertSame('Depto Sistemas > Laboratorio Redes > R101', $this->equipoPropio->fresh()->ubicacion_fisica);

        // #4 Acciones de fila (ver, editar, eliminar) + #5 Slide-over crear & editar guardan en BD
        Livewire::test(AdminDashboard::class)
            ->call('verEquipo', $this->equipoPropio->id_inventario)
            ->assertActionMounted('verEquipo');

        Livewire::test(AdminDashboard::class)
            ->call('editarEquipo', $this->equipoPropio->id_inventario)
            ->assertActionMounted('editarEquipo')
            ->setActionData([
                'num_serie' => 'SN-VER-001-EDIT',
                'ip_address' => '192.168.10.99',
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors()
            ->assertNotified();

        $this->assertSame('SN-VER-001-EDIT', $this->equipoPropio->fresh()->num_serie);

        // #4 Eliminar acción montada correctamente
        Livewire::test(AdminDashboard::class)
            ->call('eliminarEquipo', $this->equipoPropio->id_inventario)
            ->assertActionMounted('eliminarEquipo');

        // #5 Crear equipo desde slide-over registrarEquipo
        Livewire::test(AdminDashboard::class)
            ->mountAction('registrarEquipo')
            ->assertActionMounted('registrarEquipo')
            ->setActionData([
                'id_producto' => $this->material->id_producto,
                'cantidad' => 1,
                'estado' => 'Disponible',
                'tipo_propiedad' => 'Propio',
                'departamento_id' => $this->deptoSistemas->id_departamento,
                'area_id' => $this->areaRedes->id_area,
                'aula' => 'R102',
                'num_serie' => 'SN-NEW-999',
                'especificaciones' => [
                    'cpu_marca' => 'Dell',
                    'monitor_sn' => 'MON-999',
                    'monitor_marca' => 'Dell',
                    'teclado_sn' => 'TEC-999',
                    'teclado_marca' => 'Dell',
                    'mouse_sn' => 'MOU-999',
                    'mouse_marca' => 'Dell',
                ],
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors()
            ->assertNotified();

        $this->assertDatabaseHas('inventario', ['num_serie' => 'SN-NEW-999', 'aula' => 'R102']);

        // #7 Filtros encadenados en Reportes (Depto -> Área, sin Aula) + #8 Pestañas en Reportes
        $rep = Livewire::test(AdminReportesPage::class)
            ->call('cambiarPestanaPrincipal', 'nuevos')
            ->assertSet('pestanaPrincipal', 'nuevos')
            ->assertDontSee('filtroAula', false)
            ->set('filtroDepartamentoId', (string) $this->deptoSistemas->id_departamento)
            ->assertSet('filtroAreaId', '')
            ->call('cambiarSubpestanaReportes', 'estadisticas')
            ->assertSet('subpestanaReportes', 'estadisticas')
            ->call('cambiarPestanaPrincipal', 'formatos')
            ->assertSet('pestanaPrincipal', 'formatos')
            ->call('cambiarPestanaPrincipal', 'principales')
            ->assertSet('pestanaPrincipal', 'principales')
            ->call('toggleVisibilidad', 'inventario_categoria')
            ->assertNotified();

        $this->assertArrayHasKey($this->areaRedes->id_area, $rep->instance()->areasParaFiltros);
        $this->assertArrayNotHasKey($this->areaManufactura->id_area, $rep->instance()->areasParaFiltros);

        // #9 Configuración tabs + filtroDepartamento en areas y receptores + #10 guardar preferencias
        Livewire::test(ConfiguracionPage::class)
            ->assertSet('tabActiva', 'catalogos')
            ->call('setCatalogo', 'areas')
            ->assertSee('wire:model.live="filtroDepartamento"', false)
            ->call('setCatalogo', 'receptores')
            ->assertSee('wire:model.live="filtroDepartamento"', false)
            ->call('setTab', 'bitacora')
            ->assertSet('tabActiva', 'bitacora')
            ->assertSee('Actualización de equipo EQ-VER-001')
            ->call('setTab', 'preferencias')
            ->assertSet('tabActiva', 'preferencias')
            ->call('seleccionarRegistrosPorHoja', 20)
            ->call('guardarPreferencias')
            ->assertNotified();

        // #10 wire:submit en Mi Perfil (EditProfile)
        Livewire::test(EditProfile::class)
            ->fillForm([
                'name' => 'Admin Verificado',
                'email' => $this->admin->email,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame('Admin Verificado', $this->admin->fresh()->name);
    }

    /**
     * Parte 3 & 5: Verifica los 20 elementos visuales en DOM/CSS, contenido dinámico y formatos de fecha/hora.
     */
    public function test_part3_and_part5_visual_dom_dynamic_content_and_date_formats(): void
    {
        // Dashboard HTML (/admin)
        $dashHtml = $this->actingAs($this->admin)->get('/admin')->assertOk()->getContent();
        // #1 Sidebar + Topbar hooks + scripts
        $this->assertStringContainsString('sigem-brand-logo', $dashHtml);
        $this->assertStringContainsString('sigem-hamburger', $dashHtml);
        $this->assertStringContainsString('sigem-network.js', $dashHtml);
        $this->assertStringContainsString('sigem-titles.js', $dashHtml);
        $this->assertStringContainsString('sigem-theme.css', $dashHtml);
        // #2 Tabla Dashboard (11 columnas + renta = 12)
        foreach (['DEPARTAMENTO', 'EQUIPO', 'CATEGORÍA', 'ESTADO', 'ESTADO RENTA', 'ÁREA', 'AULA', 'NO. DE SERIE', 'IP', 'MAC', 'ACCIONES'] as $col) {
            $this->assertStringContainsString($col, $dashHtml, "Falta columna {$col} en Dashboard");
        }
        // #3 Acciones de fila (ver, editar, eliminar)
        $this->assertStringContainsString('wire:click="verEquipo(', $dashHtml);
        $this->assertStringContainsString('wire:click="editarEquipo(', $dashHtml);
        $this->assertStringContainsString('wire:click="eliminarEquipo(', $dashHtml);
        // #10 KPIs Dashboard (4 cards) + #11 Quick cards (4 acciones + semáforo rentas)
        $this->assertStringContainsString('sigem-stats-grid', $dashHtml);
        $this->assertStringContainsString('sigem-quick-grid', $dashHtml);
        $this->assertStringContainsString('Equipos en renta', $dashHtml);
        $this->assertStringContainsString('🟢 1 vigente', $dashHtml);
        $this->assertStringContainsString('🟡 1 por vencer', $dashHtml);
        $this->assertStringContainsString('🔴 1 vencida', $dashHtml);
        // #12 Badges de estado + #13 Badges de renta + tooltip DD/MM/AAAA + días enteros
        $this->assertStringContainsString('sigem-pill ok', $dashHtml);
        $this->assertStringContainsString('sigem-pill warn', $dashHtml);
        $this->assertStringContainsString('sigem-pill danger', $dashHtml);
        $this->assertStringContainsString('🟢 Vigente', $dashHtml);
        $this->assertStringContainsString('🟡 Por vencer', $dashHtml);
        $this->assertStringContainsString('🔴 Vencida', $dashHtml);
        $expectedRentaFin = $this->equipoRentadoPorVencer->renta_fecha_fin->format('d/m/Y');
        $this->assertMatchesRegularExpression('/^\d{2}\/\d{2}\/\d{4}$/', $expectedRentaFin);
        $this->assertIsInt($this->equipoRentadoPorVencer->renta_dias_restantes);
        $this->assertStringContainsString("Vence el {$expectedRentaFin} ({$this->equipoRentadoPorVencer->renta_dias_restantes} días)", $dashHtml);

        // Mi Perfil (/admin/profile) — #4, #5, #6, #7
        $profileHtml = $this->actingAs($this->admin)->get('/admin/profile')->assertOk()->getContent();
        foreach (['sigem-profile-wrapper', 'sigem-profile-card', 'sigem-profile-accent', 'sigem-profile-logos', 'sigem-logo.svg', 'tecnm-logo.webp', 'itv-logo.webp', 'sigem-profile-avatar', 'Mi Perfil'] as $cls) {
            $this->assertStringContainsString($cls, $profileHtml, "Falta {$cls} en /admin/profile");
        }

        // Solicitudes (#8) y Mantenimientos (#9)
        $this->actingAs($this->admin)->get('/admin/solicituds')->assertOk()->assertSee('fi-ta-table');
        $this->actingAs($this->admin)->get('/admin/mantenimientos')->assertOk()->assertSee('fi-ta-table');

        // Reportes (#14 cards con toggles + #15 Chart.js + Parte 5.1 #3,#4,#5)
        $repHtml = $this->actingAs($this->admin)->get('/admin/reportes')->assertOk()->getContent();
        $this->assertStringContainsString('sigem-reportes-charts.js', $repHtml);
        $this->assertStringContainsString('chart.umd.min.js', $repHtml);
        $this->assertStringContainsString('sigem-rep-card', $repHtml);
        $this->assertStringContainsString('toggleVisibilidad', $repHtml);

        // Verificar los 6 reportes principales tienen estructura y datos
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->admin);
        $repComp = Livewire::test(AdminReportesPage::class);
        $this->assertCount(6, $repComp->instance()->getReportes());
        foreach ($repComp->instance()->getReportes() as $r) {
            $data = $repComp->instance()->getDatosReporte($r['id']);
            $this->assertNotEmpty($data['headers'], "Reporte principal sin headers: {$r['id']}");
            $this->assertNotEmpty($data['rows'], "Reporte principal sin filas: {$r['id']}");
        }

        // Verificar Reportes -> Nuevos gráficas con colores (datasets & backgroundColor)
        $chartCount = 0;
        foreach (SigemReportExporter::getCatalogo() as $sub => $reps) {
            foreach (array_keys($reps) as $repId) {
                $d = SigemReportExporter::getReportData($repId);
                if (! empty($d['has_chart'])) {
                    $chartCount++;
                    $this->assertNotEmpty($d['chart']['datasets'], "Gráfica sin datasets: {$repId}");
                    $this->assertNotEmpty($d['chart']['datasets'][0]['backgroundColor'] ?? null, "Gráfica sin colores: {$repId}");
                }
            }
        }
        $this->assertGreaterThanOrEqual(12, $chartCount);

        // Verificar Reportes -> Formatos tablas con datos + fechas DD/MM/AAAA
        $repComp->call('cambiarPestanaPrincipal', 'formatos');
        foreach (['entrega', 'orden_trabajo', 'lista_verificacion', 'baja', 'cambio_area'] as $fmtTipo) {
            $repComp->set('formatoFiltroTipo', $fmtTipo);
            $filas = $repComp->instance()->getFilasFiltradasFormatos();
            $this->assertGreaterThanOrEqual(1, $filas->count(), "Formato {$fmtTipo} sin filas");
            $this->assertMatchesRegularExpression('/^\d{2}\/\d{2}\/\d{4}$/', $filas->first()['fecha'], "Formato de fecha inválido en {$fmtTipo}");
        }

        // Configuración (#19 + Parte 5.1 #7 Bitácora DD/MM/AAAA HH:MM)
        $cfg = Livewire::test(ConfiguracionPage::class)->call('setTab', 'bitacora');
        $bitacora = $cfg->instance()->getBitacoraData();
        $this->assertNotEmpty($bitacora);
        $this->assertMatchesRegularExpression('/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}$/', $bitacora[0]['fecha']);

        // Command Palette (#20)
        Livewire::test(CommandPalette::class)
            ->call('abrir')
            ->set('q', 'EQ-VER-001')
            ->assertSee('EQ-VER-001');

        // Dashboard Servicio Social funciona igual (limpiar sesión de admin previa)
        $this->flushSession();
        auth()->logout();
        Filament::setCurrentPanel(Filament::getPanel('servicio-social'));
        $ssHtml = $this->actingAs($this->ss)->get('/servicio-social')->assertOk()->getContent();
        $this->assertStringContainsString('sigemDashboardSS', $ssHtml);
    }

    /**
     * Parte 6: Verifica las 7 descargas reales (.xlsx, .csv, y los 5 PDFs de 1 página con campos completos).
     */
    public function test_part6_all_7_downloads_produce_valid_files(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->admin);

        // 1. Excel Inventario (.xlsx válido con columnas correctas)
        $dashInstance = Livewire::test(AdminDashboard::class)->instance();
        $xlsxResp = SigemExcelExporter::exportarInventario($dashInstance);
        $this->assertInstanceOf(BinaryFileResponse::class, $xlsxResp);
        $xlsxPath = $xlsxResp->getFile()->getPathname();
        $this->assertStringStartsWith("PK\x03\x04", file_get_contents($xlsxPath, false, null, 0, 4));
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($xlsxPath) === true);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        $this->assertNotFalse($sheetXml);
        $this->assertStringContainsString('EQ-VER-001', $sheetXml);
        $this->assertStringContainsString('OptiPlex 7090', $sheetXml);
        @unlink($xlsxPath);

        // 2. CSV Reporte (.csv válido con BOM UTF-8, encabezados y filas)
        $repInstance = Livewire::test(AdminReportesPage::class)->instance();
        $csvResp = $repInstance->exportarReporte('inventario_categoria', 'csv');
        $this->assertInstanceOf(StreamedResponse::class, $csvResp);
        ob_start();
        $csvResp->sendContent();
        $csvContent = ob_get_clean();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csvContent);
        $csvLines = array_values(array_filter(explode("\n", trim($csvContent))));
        $this->assertGreaterThanOrEqual(2, count($csvLines), 'CSV debe tener encabezados + filas');
        $this->assertStringContainsString('Equipo de Cómputo', $csvContent);

        // 3–7. Los 5 PDFs institucionales (válidos %PDF-, exactamente 1 página c/u)
        $pdfCases = [
            'entrega' => SigemPdfGenerator::descargarActaEntrega($this->solicitud),
            'orden_trabajo' => SigemPdfGenerator::descargarOrdenTrabajo($this->mantenimiento),
            'lista_verificacion' => SigemPdfGenerator::descargarListaVerificacion($this->mantenimiento),
            'baja' => SigemPdfGenerator::descargarFormatoBaja($this->equipoBaja),
            'cambio_area' => SigemPdfGenerator::descargarCambioArea($this->equipoCambioArea),
        ];

        $pageCounts = [];
        foreach ($pdfCases as $nombre => $resp) {
            $this->assertInstanceOf(StreamedResponse::class, $resp, "PDF {$nombre} no devolvió StreamedResponse");
            ob_start();
            $resp->sendContent();
            $pdfBytes = ob_get_clean();
            $this->assertStringStartsWith('%PDF-', $pdfBytes, "PDF {$nombre} no tiene cabecera %PDF-");
            $this->assertGreaterThan(2000, strlen($pdfBytes), "PDF {$nombre} demasiado pequeño");
            preg_match('/\/Type\s*\/Pages\b[^>]*\/Count\s+(\d+)/s', $pdfBytes, $m);
            $pageCounts[$nombre] = isset($m[1]) ? (int) $m[1] : preg_match_all('/\/Type\s*\/Page\b(?!s)/', $pdfBytes);
        }

        $this->assertSame([
            'entrega' => 1,
            'orden_trabajo' => 1,
            'lista_verificacion' => 1,
            'baja' => 1,
            'cambio_area' => 1,
        ], $pageCounts, 'Todos los 5 PDFs institucionales deben ser exactamente de 1 página');
    }
}

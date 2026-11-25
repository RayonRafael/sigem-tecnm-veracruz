<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Filament\Pages\ConfiguracionPage;
use App\Filament\Pages\ReportesPage;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\Proveedor;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use App\Services\ConfigBitacoraService;
use App\Support\SigemReportCatalog;
use App\Support\SigemReportExporter;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Fase4ReportesBitacoraTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $perm = Permission::firstOrCreate(['name' => RoleEnum::PERM_ACCESS_ADMIN, 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => RoleEnum::ADMIN->value, 'guard_name' => 'web']);
        $role->givePermissionTo($perm);

        $this->admin = User::factory()->create([
            'name' => 'Admin Fase4',
            'tipo_usuario' => RoleEnum::ADMIN->value,
            'activo' => true,
        ]);
        $this->admin->assignRole($role);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        SigemReportExporter::clearCache();
    }

    public function test_bitacora_muestra_columna_equipo_en_eventos_inventario_y_guion_en_no_inventario(): void
    {
        $this->actingAs($this->admin);

        $depto = Departamento::create(['nombre' => 'Sistemas Bitácora']);
        $area = Area::create(['nombre' => 'Redes', 'id_departamento' => $depto->id_departamento]);
        $unidad = UnidadMedida::create(['nombre' => 'Pieza']);
        $marca = MarcaMaterial::create(['nombre' => 'Dell']);
        $tipo = TipoMaterial::create(['nombre' => 'Cómputo']);
        $material = Material::create([
            'nombre' => 'Vostro 3470',
            'id_unidad' => $unidad->id_unidad,
            'id_marca' => $marca->id_marca,
            'id_tipodematerial' => $tipo->id_tipodematerial,
            'requiere_control_individual' => true,
        ]);

        // 1. Crear, editar y eliminar equipo -> todos deben reportar código o S/N
        $inv = Inventario::create([
            'num_serie' => '24J29Z2',
            'codigo' => 'DELL-VOSTRO-014',
            'id_producto' => $material->id_producto,
            'id_usuario' => $this->admin->id,
            'departamento_id' => $depto->id_departamento,
            'area_id' => $area->id_area,
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'fecha_registro' => now(),
        ]);
        $inv->update(['estado' => 'Asignado']);
        $inv->delete();

        // 2. Evento de equipo SIN código (solo num_serie): simular registro legado sin código
        $invSoloSn = Inventario::create([
            'num_serie' => 'SN-SOLO-999',
            'codigo' => 'TEMP-SN-999',
            'id_producto' => $material->id_producto,
            'id_usuario' => $this->admin->id,
            'departamento_id' => $depto->id_departamento,
            'area_id' => $area->id_area,
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'fecha_registro' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('inventario')
            ->where('id_inventario', $invSoloSn->id_inventario)
            ->update(['codigo' => null]);
        $invSoloSn->refresh();
        $invSoloSn->update(['estado' => 'En Mantenimiento']);

        // 3. Evento NO-inventario (catálogo Proveedor + login sin subject)
        Proveedor::create(['nombre_empresa' => 'Prov Bitacora', 'activo' => true]);
        Activity::create([
            'log_name' => 'default',
            'description' => 'Inicio de sesión',
            'event' => 'login',
            'causer_type' => User::class,
            'causer_id' => $this->admin->id,
        ]);

        $rows = ConfigBitacoraService::getBitacoraData();
        $this->assertNotEmpty($rows);

        $equiposRegistrados = array_column($rows, 'equipo');
        $this->assertContains('DELL-VOSTRO-014', $equiposRegistrados);
        $this->assertContains('SN-SOLO-999', $equiposRegistrados);
        $this->assertContains('—', $equiposRegistrados);

        // Verificar búsqueda por código de equipo en Bitácora
        $filtrados = ConfigBitacoraService::getBitacoraData(['search' => 'DELL-VOSTRO-014']);
        $this->assertGreaterThanOrEqual(3, count($filtrados));
        foreach ($filtrados as $f) {
            $this->assertSame('DELL-VOSTRO-014', $f['equipo']);
        }

        // Verificar renderizado de tabla en ConfiguracionPage (6 columnas: Fecha..Equipo..Detalle)
        $cfg = Livewire::actingAs($this->admin)
            ->test(ConfiguracionPage::class)
            ->call('setTab', 'bitacora')
            ->assertSee('DELL-VOSTRO-014')
            ->assertSee('SN-SOLO-999');

        $html = $cfg->html();
        $cols = ['<th>Fecha</th>', '<th>Usuario</th>', '<th>Rol</th>', '<th>Acción</th>', '<th>Equipo</th>', '<th>Detalle</th>'];
        $prevPos = -1;
        foreach ($cols as $col) {
            $pos = strpos($html, $col);
            $this->assertNotFalse($pos, "Falta encabezado {$col} en Bitácora.");
            $this->assertGreaterThan($prevPos, $pos, "Orden incorrecto de columna {$col} en Bitácora.");
            $prevPos = $pos;
        }

        $this->assertNotNull($invSoloSn->id_inventario);
    }

    public function test_12_graficas_tienen_titulos_descriptivos_y_unidades_en_chart_js(): void
    {
        $esperados = [
            'inventario_por_departamento' => ['Equipos por departamento', 'equipos'],
            'inventario_por_tipo' => ['Equipos por tipo de material', 'equipos'],
            'inventario_por_estado' => ['Equipos por estado operativo', 'equipos'],
            'estadisticas_depto_mas_equipos' => ['Top departamentos con más equipos', 'equipos'],
            'estadisticas_mas_movidos' => ['Equipos con más movimientos', 'movimientos'],
            'estadisticas_propio_vs_rentado' => ['Distribución de propiedad: Propio vs Rentado', 'equipos'],
            'mantenimiento_depto_pide_mas' => ['Departamentos que solicitan más mantenimiento', 'mantenimientos'],
            'mantenimiento_por_mes' => ['Mantenimientos por mes', 'mantenimientos'],
            'rentas_proximas_vencer' => ['Rentas próximas a vencer', 'equipos'],
            'rentas_gasto_mensual' => ['Gasto mensual en rentas', 'MXN'],
            'historico_cambios_area' => ['Cambios de área por mes', 'cambios'],
            'historico_actividad_usuario' => ['Actividad por usuario', 'acciones'],
        ];

        $this->assertCount(12, $esperados);

        foreach ($esperados as $repId => [$tituloEsperado, $unidadEsperada]) {
            $data = SigemReportExporter::getReportData($repId);
            $this->assertTrue($data['has_chart'], "{$repId} debe tener gráfica.");
            $this->assertSame($tituloEsperado, $data['chart_title'], "Título de gráfica incorrecto en {$repId}.");
            $this->assertSame($unidadEsperada, $data['chart_unit'], "Unidad incorrecta en {$repId}.");
            $this->assertTrue($data['chart']['options']['plugins']['title']['display'] ?? false);
            $this->assertSame($tituloEsperado, $data['chart']['options']['plugins']['title']['text'] ?? null);
        }

        // Verificar que sigem-reportes-charts.js preserva plugins.title, unidades y plugin de barras con 0
        $js = file_get_contents(public_path('js/sigem-reportes-charts.js'));
        $this->assertStringContainsString('sigemZeroBarAndValueLabels', $js);
        $this->assertStringContainsString('formatValueWithUnit', $js);
        $this->assertStringContainsString('incomingTitle', $js);
    }

    public function test_graficas_por_departamento_incluyen_todos_los_departamentos_con_cero_equipos(): void
    {
        $nombresDeptos = [
            'Ciencias Básicas',
            'Ciencias Económico-Administrativas',
            'Desarrollo Académico',
            'Dirección',
            'División de Estudios de Posgrado',
            'División de Estudios Profesionales',
            'Ingeniería Eléctrica y Electrónica',
            'Ingeniería Industrial',
            'Ingeniería Metal-Mecánica',
            'Ingeniería Química y Bioquímica',
            'Ingeniería en Sistemas y Computación',
        ];

        $deptos = [];
        foreach ($nombresDeptos as $nom) {
            $deptos[$nom] = Departamento::create(['nombre' => $nom]);
        }

        $unidad = UnidadMedida::create(['nombre' => 'Pieza']);
        $marca = MarcaMaterial::create(['nombre' => 'HP']);
        $tipo = TipoMaterial::create(['nombre' => 'Cómputo']);
        $material = Material::create([
            'nombre' => 'ProDesk',
            'id_unidad' => $unidad->id_unidad,
            'id_marca' => $marca->id_marca,
            'id_tipodematerial' => $tipo->id_tipodematerial,
            'requiere_control_individual' => true,
        ]);

        // Solo 2 departamentos tienen equipos; los otros 9 (incluyendo Química y Bioquímica) tienen 0
        Inventario::create([
            'num_serie' => 'DEP-001',
            'codigo' => 'HP-PD-001',
            'id_producto' => $material->id_producto,
            'id_usuario' => $this->admin->id,
            'departamento_id' => $deptos['Ingeniería en Sistemas y Computación']->id_departamento,
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'fecha_registro' => now(),
        ]);
        Inventario::create([
            'num_serie' => 'DEP-002',
            'codigo' => 'HP-PD-002',
            'id_producto' => $material->id_producto,
            'id_usuario' => $this->admin->id,
            'departamento_id' => $deptos['Dirección']->id_departamento,
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'fecha_registro' => now(),
        ]);

        SigemReportExporter::clearCache();

        // 1. Por Departamento (inventario_por_departamento) -> 11 departamentos en tabla y gráfica
        $repInvDepto = SigemReportExporter::getReportData('inventario_por_departamento');
        $this->assertCount(11, $repInvDepto['rows']);
        $this->assertCount(11, $repInvDepto['chart']['labels']);
        $this->assertContains('Ingeniería Química y Bioquímica', $repInvDepto['chart']['labels']);

        $idxQuimInv = array_search('Ingeniería Química y Bioquímica', $repInvDepto['chart']['labels'], true);
        $this->assertSame(0, $repInvDepto['chart']['datasets'][0]['data'][$idxQuimInv]);

        // 2. Top departamentos con más equipos (estadisticas_depto_mas_equipos) -> también incluye los 11 (con 0)
        $repTopDepto = SigemReportExporter::getReportData('estadisticas_depto_mas_equipos');
        $this->assertCount(11, $repTopDepto['rows']);
        $this->assertCount(11, $repTopDepto['chart']['labels']);
        $this->assertContains('Ingeniería Química y Bioquímica', $repTopDepto['chart']['labels']);

        $idxQuimTop = array_search('Ingeniería Química y Bioquímica', $repTopDepto['chart']['labels'], true);
        $this->assertSame(0, $repTopDepto['chart']['datasets'][0]['data'][$idxQuimTop]);
    }

    public function test_filtros_fecha_ocultos_en_reportes_no_historicos_y_visibles_en_historicos(): void
    {
        $esperadosConFechas = [
            'estadisticas_antiguos',
            'estadisticas_mas_movidos',
            'mantenimiento_depto_pide_mas',
            'mantenimiento_tipo_mas_frecuente',
            'mantenimiento_por_mes',
            'mantenimiento_tiempo_promedio',
            'mantenimiento_historial_equipo',
            'rentas_gasto_mensual',
            'historico_cambios_area',
            'historico_bajas',
            'historico_entregas',
            'historico_actividad_usuario',
            'historico_general',
        ];

        $todos = [];
        $conFechas = [];
        foreach (SigemReportCatalog::getCatalogo() as $subReportes) {
            foreach ($subReportes as $id => $meta) {
                $todos[] = $id;
                $this->assertArrayHasKey('usa_fechas', $meta, "Falta flag usa_fechas en {$id}");
                if ($meta['usa_fechas']) {
                    $conFechas[] = $id;
                }
            }
        }

        $this->assertCount(29, $todos);
        sort($esperadosConFechas);
        sort($conFechas);
        $this->assertSame($esperadosConFechas, $conFechas);

        // UI Livewire: sub-pestañas sin fechas (inventario y rentas) NO renderizan filtroFechaDesde
        Livewire::actingAs($this->admin)
            ->test(ReportesPage::class)
            ->call('cambiarPestanaPrincipal', 'nuevos')
            ->call('cambiarSubpestanaReportes', 'inventario')
            ->assertDontSee('filtroFechaDesde', false)
            ->assertDontSee('filtroFechaHasta', false)
            ->call('cambiarSubpestanaReportes', 'rentas')
            ->assertDontSee('filtroFechaDesde', false)
            ->assertDontSee('filtroFechaHasta', false)
            // Sub-pestaña historico (todos usan fechas) SÍ renderiza Desde/Hasta visibles
            ->call('cambiarSubpestanaReportes', 'historico')
            ->assertSee('filtroFechaDesde', false)
            ->assertSee('filtroFechaHasta', false)
            ->assertDontSee('sigem-filter-fecha" style="display: none;"', false)
            // Sub-pestaña graficas (abre inventario_por_departamento sin fechas -> ocultos inicialmente vía style + x-show)
            ->call('cambiarSubpestanaReportes', 'graficas')
            ->assertSee('reportesConFechas', false)
            ->assertSee('style="display: none;"', false);
    }
}

<?php

namespace Tests\Feature\Reportes;

use App\Filament\Pages\ReportesPage;
use App\Models\Area;
use App\Models\Departamento;
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
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class NuevosReportes28Test extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Departamento $depto;
    protected Area $area;
    protected Salon $salon;
    protected Material $material;
    protected Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(ServicioSocialUserSeeder::class);

        $this->adminUser = User::where('email', 'admin@tecnm.edu.mx')->first();

        $this->depto = Departamento::firstOrCreate(['nombre' => 'Centro de Cómputo']);
        $this->area = Area::firstOrCreate([
            'nombre' => 'Laboratorio A',
            'id_departamento' => $this->depto->id_departamento,
        ]);
        $this->salon = Salon::firstOrCreate([
            'nombre' => 'LC-101',
            'area_id' => $this->area->id_area,
        ]);

        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'Dell']);
        $tipo = TipoMaterial::firstOrCreate(['nombre' => 'Computadora']);
        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pieza']);

        $this->material = Material::create([
            'nombre' => 'OptiPlex 7090',
            'id_marca' => $marca->id_marca,
            'id_tipodematerial' => $tipo->id_tipodematerial,
            'id_unidad' => $unidad->id_unidad,
            'stock_actual' => 5,
        ]);

        $this->proveedor = Proveedor::create([
            'nombre_empresa' => 'Arrendadora Tecnológica SA de CV',
            'rfc' => 'ATE120304AB1',
            'activo' => true,
        ]);
    }

    public function test_catalogo_contiene_28_reportes_en_5_subpestanas(): void
    {
        $catalogo = SigemReportExporter::getCatalogo();
        $subpestanas = SigemReportExporter::getSubpestanas();

        $this->assertCount(5, $catalogo);
        $this->assertCount(5, $subpestanas);

        $this->assertArrayHasKey('inventario', $catalogo);
        $this->assertArrayHasKey('estadisticas', $catalogo);
        $this->assertArrayHasKey('mantenimiento', $catalogo);
        $this->assertArrayHasKey('rentas', $catalogo);
        $this->assertArrayHasKey('historico', $catalogo);

        $this->assertCount(6, $catalogo['inventario'], 'Inventario debe tener 6 reportes');
        $this->assertCount(6, $catalogo['estadisticas'], 'Estadísticas debe tener 6 reportes');
        $this->assertCount(6, $catalogo['mantenimiento'], 'Mantenimiento debe tener 6 reportes');
        $this->assertCount(5, $catalogo['rentas'], 'Rentas debe tener 5 reportes');
        $this->assertCount(5, $catalogo['historico'], 'Histórico debe tener 5 reportes');

        $totalReportes = 0;
        foreach ($catalogo as $grupo) {
            $totalReportes += count($grupo);
        }
        $this->assertEquals(28, $totalReportes, 'El catálogo total debe contener exactamente 28 reportes');
    }

    public function test_subpestana_inventario_genera_los_6_reportes_correctamente(): void
    {
        $invPropio = Inventario::create([
            'id_producto' => $this->material->id_producto,
            'codigo' => 'INV-TEST-001',
            'num_serie' => 'SN-TEST-001',
            'estado' => 'Disponible',
            'tipo_propiedad' => 'Propio',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'salon_id' => $this->salon->id,
            'aula' => 'LC-101',
            'fecha_registro' => now(),
            'id_usuario' => $this->adminUser->id,
        ]);

        $reportesInv = [
            'inventario_general',
            'inventario_por_departamento',
            'inventario_por_area',
            'inventario_por_tipo',
            'inventario_por_estado',
            'inventario_por_aula',
        ];

        foreach ($reportesInv as $idRep) {
            $data = SigemReportExporter::getReportData($idRep);
            $this->assertNotEmpty($data['headers'], "El reporte {$idRep} debe tener encabezados definidos");
            $this->assertIsArray($data['rows'], "El reporte {$idRep} debe retornar filas");
            $this->assertGreaterThanOrEqual(1, count($data['rows']), "El reporte {$idRep} debe contener al menos 1 fila");
        }
    }

    public function test_subpestana_estadisticas_genera_los_6_reportes_correctamente(): void
    {
        Inventario::create([
            'id_producto' => $this->material->id_producto,
            'codigo' => 'INV-ESTAD-001',
            'num_serie' => 'SN-ESTAD-001',
            'estado' => 'Disponible',
            'tipo_propiedad' => 'Propio',
            'fecha_registro' => '2023-01-15',
            'fecha_factura' => '2023-01-10',
            'id_usuario' => $this->adminUser->id,
        ]);

        $reportesEst = [
            'estadisticas_antiguos',
            'estadisticas_sin_asignar',
            'estadisticas_depto_mas_equipos',
            'estadisticas_mas_movidos',
            'estadisticas_propio_vs_rentado',
            'estadisticas_resumen_general',
        ];

        foreach ($reportesEst as $idRep) {
            $data = SigemReportExporter::getReportData($idRep);
            $this->assertNotEmpty($data['headers'], "El reporte {$idRep} debe tener encabezados definidos");
            $this->assertIsArray($data['rows'], "El reporte {$idRep} debe retornar filas");
        }

        // Resumen general debe tener exactamente 1 fila con 6 totales
        $resumen = SigemReportExporter::getReportData('estadisticas_resumen_general');
        $this->assertCount(1, $resumen['rows']);
        $this->assertCount(6, $resumen['rows'][0]);
    }

    public function test_subpestana_mantenimiento_genera_los_6_reportes_correctamente(): void
    {
        $inv = Inventario::create([
            'id_producto' => $this->material->id_producto,
            'codigo' => 'INV-MANT-001',
            'num_serie' => 'SN-MANT-001',
            'estado' => 'En mantenimiento',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'fecha_registro' => now(),
            'id_usuario' => $this->adminUser->id,
        ]);

        Mantenimiento::create([
            'id_inventario' => $inv->id_inventario,
            'id_usuario_solicita' => $this->adminUser->id,
            'tipo_mantenimiento' => 'Correctivo',
            'tipo_servicio' => 'Personal Técnico',
            'nombre_tecnico' => 'Ing. Soporte',
            'descripcion_falla' => 'Ventilador ruidoso',
            'descripcion_trabajo' => 'Limpieza y lubricación',
            'fecha_solicitud' => '2026-08-01',
            'fecha_fin' => '2026-08-03',
            'estado' => 'Completado',
        ]);

        $reportesMant = [
            'mantenimiento_depto_pide_mas',
            'mantenimiento_tipo_mas_frecuente',
            'mantenimiento_por_mes',
            'mantenimiento_en_curso',
            'mantenimiento_tiempo_promedio',
            'mantenimiento_historial_equipo',
        ];

        foreach ($reportesMant as $idRep) {
            $data = SigemReportExporter::getReportData($idRep);
            $this->assertNotEmpty($data['headers'], "El reporte {$idRep} debe tener encabezados definidos");
            $this->assertIsArray($data['rows'], "El reporte {$idRep} debe retornar filas");
        }
    }

    public function test_subpestana_rentas_genera_los_5_reportes_correctamente(): void
    {
        Inventario::create([
            'id_producto' => $this->material->id_producto,
            'codigo' => 'INV-RENTA-001',
            'num_serie' => 'SN-RENTA-001',
            'estado' => 'Disponible',
            'tipo_propiedad' => 'Rentado',
            'id_proveedor' => $this->proveedor->id_proveedor,
            'fecha_inicio_renta' => now()->subMonths(6),
            'fecha_fin_renta' => now()->addDays(30), // Próxima a vencer
            'fecha_registro' => now(),
            'id_usuario' => $this->adminUser->id,
            'especificaciones' => ['costo_renta' => 1500.00],
        ]);

        $reportesRentas = [
            'rentas_equipos',
            'rentas_proximas_vencer',
            'rentas_vencidas',
            'rentas_gasto_mensual',
            'rentas_por_proveedor',
        ];

        foreach ($reportesRentas as $idRep) {
            $data = SigemReportExporter::getReportData($idRep);
            $this->assertNotEmpty($data['headers'], "El reporte {$idRep} debe tener encabezados definidos");
            $this->assertIsArray($data['rows'], "El reporte {$idRep} debe retornar filas");
        }
    }

    public function test_subpestana_historico_genera_los_5_reportes_correctamente(): void
    {
        $inv = Inventario::create([
            'id_producto' => $this->material->id_producto,
            'codigo' => 'INV-HIST-001',
            'num_serie' => 'SN-HIST-001',
            'estado' => 'Dado de baja',
            'motivo_baja' => 'Obsolescencia tecnológica',
            'fecha_baja' => now(),
            'fecha_registro' => now(),
            'id_usuario' => $this->adminUser->id,
        ]);

        HistorialEstado::create([
            'id_inventario' => $inv->id_inventario,
            'id_usuario' => $this->adminUser->id,
            'estado_anterior' => 'Disponible',
            'estado_nuevo' => 'Dado de baja',
            'motivo_cambio' => 'Cambio de área: Centro de Cómputo → Almacén General',
            'ubicacion_fisica' => 'Almacén General',
            'fecha_cambio' => now(),
        ]);

        $reportesHist = [
            'historico_cambios_area',
            'historico_bajas',
            'historico_entregas',
            'historico_actividad_usuario',
            'historico_general',
        ];

        foreach ($reportesHist as $idRep) {
            $data = SigemReportExporter::getReportData($idRep);
            $this->assertNotEmpty($data['headers'], "El reporte {$idRep} debe tener encabezados definidos");
            $this->assertIsArray($data['rows'], "El reporte {$idRep} debe retornar filas");
        }
    }

    public function test_exportar_excel_y_csv_retornan_respuestas_validas(): void
    {
        Inventario::create([
            'id_producto' => $this->material->id_producto,
            'codigo' => 'INV-EXP-001',
            'estado' => 'Disponible',
            'tipo_propiedad' => 'Propio',
            'fecha_registro' => now(),
            'id_usuario' => $this->adminUser->id,
        ]);

        // 1. Exportar XLSX
        $responseXlsx = SigemReportExporter::export('inventario_general', [], 'xlsx');
        $this->assertInstanceOf(BinaryFileResponse::class, $responseXlsx);
        $this->assertEquals(200, $responseXlsx->getStatusCode());
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', (string) $responseXlsx->headers->get('content-type'));

        // 2. Exportar CSV
        $responseCsv = SigemReportExporter::export('inventario_general', [], 'csv');
        $this->assertInstanceOf(StreamedResponse::class, $responseCsv);
        $this->assertEquals(200, $responseCsv->getStatusCode());
        $this->assertStringContainsString('text/csv', (string) $responseCsv->headers->get('content-type'));
    }

    public function test_ruta_web_exportacion_funciona_con_autenticacion(): void
    {
        // 1. Sin autenticación -> 302 a login o 403
        $responseGuest = $this->get('/reportes/export/inventario_general');
        $this->assertTrue(in_array($responseGuest->getStatusCode(), [302, 403], true));

        // 2. Con usuario autenticado -> 200 stream
        $this->actingAs($this->adminUser);
        $responseAuth = $this->get('/reportes/export/inventario_general?formato=xlsx');
        $this->assertEquals(200, $responseAuth->getStatusCode());

        $responseAuthCsv = $this->get('/reportes/export/inventario_por_departamento?formato=csv');
        $this->assertEquals(200, $responseAuthCsv->getStatusCode());
    }

    public function test_livewire_reportes_page_pestañas_y_subpestanas_funcionan(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $inv = Inventario::create([
            'id_producto' => $this->material->id_producto,
            'codigo' => 'INV-CAMBIO-001',
            'estado' => 'Disponible',
            'fecha_cambio_area' => now(),
            'fecha_registro' => now(),
            'id_usuario' => $this->adminUser->id,
            'especificaciones' => ['cambio_origen_depto' => 'Sistemas'],
        ]);

        $livewire = Livewire::test(ReportesPage::class)
            // Verificar pestaña principal por defecto
            ->assertSet('pestanaPrincipal', 'formatos')
            ->assertSee('Formatos Institucionales (PDF)')
            ->assertSee('Formato de Cambio de Área')
            // Cambiar a pestaña Nuevos Reportes
            ->call('cambiarPestanaPrincipal', 'nuevos_reportes')
            ->assertSet('pestanaPrincipal', 'nuevos_reportes')
            ->assertSee('Inventario General')
            ->assertSee('Por Departamento')
            // Cambiar sub-pestaña a Mantenimiento
            ->call('cambiarSubpestanaReportes', 'mantenimiento')
            ->assertSet('subpestanaReportes', 'mantenimiento')
            ->assertSee('Depto que pide más')
            ->assertSee('En mantenimiento')
            // Cambiar sub-pestaña a Rentas
            ->call('cambiarSubpestanaReportes', 'rentas')
            ->assertSet('subpestanaReportes', 'rentas')
            ->assertSee('Equipos en renta')
            ->assertSee('Próximas a vencer')
            // Cambiar sub-pestaña a Histórico
            ->call('cambiarSubpestanaReportes', 'historico')
            ->assertSet('subpestanaReportes', 'historico')
            ->assertSee('Cambios de área')
            ->assertSee('Bajas')
            // Abrir vista previa
            ->call('verNuevoReporte', 'historico_bajas')
            ->assertSet('nuevoReporteActivo', 'historico_bajas')
            ->assertSee('Vista previa')
            // Cerrar vista previa
            ->call('cerrarNuevoReporte')
            ->assertSet('nuevoReporteActivo', null);

        // Volver a formatos y descargar Cambio de Área
        $livewire->call('cambiarPestanaPrincipal', 'formatos')
            ->call('descargarFormatoInstitucional', 'cambio_area');
    }
}

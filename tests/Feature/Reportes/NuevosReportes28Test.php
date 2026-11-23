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
        $this->assertCount(6, $catalogo['rentas'], 'Rentas debe tener 6 reportes (incluye Seguimiento de Rentas)');
        $this->assertCount(5, $catalogo['historico'], 'Histórico debe tener 5 reportes');

        $totalReportes = 0;
        foreach ($catalogo as $grupo) {
            $totalReportes += count($grupo);
        }
        $this->assertEquals(29, $totalReportes, 'El catálogo total debe contener 29 reportes');
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
            ->assertSet('pestanaPrincipal', 'principales')
            ->assertSee('Reportes Principales')
            ->call('cambiarPestanaPrincipal', 'formatos')
            ->assertSet('pestanaPrincipal', 'formatos')
            ->assertSee('Formatos PDF')
            // Cambiar a pestaña Nuevos Reportes (vista híbrida: gráficas arriba + acordeón abajo)
            ->call('cambiarPestanaPrincipal', 'nuevos')
            ->assertSet('pestanaPrincipal', 'nuevos')
            ->assertSee('29 con gráficas')
            ->assertSee('sigem-section-divider', false)
            ->assertSee('GRÁFICAS')
            ->assertSee('TABLAS')
            ->assertSee('3 reportes')
            ->assertSee('Inventario General')
            ->assertSee('Por Departamento')
            ->assertSee("openId: 'inventario_general'", false)
            ->assertSee('Marca')
            ->assertSee('Modelo')
            ->assertSee('Serie')
            ->assertDontSee('wire:click="verNuevoReporte(', false)
            ->assertDontSee('sigem-reporte-visor', false)
            ->assertDontSee('Vista previa (top 5):')
            // Cambiar sub-pestaña a Mantenimiento (2 gráficas + 4 tablas)
            ->call('cambiarSubpestanaReportes', 'mantenimiento')
            ->assertSet('subpestanaReportes', 'mantenimiento')
            ->assertSee('2 reportes')
            ->assertSee('4 reportes')
            ->assertSee('Depto que pide más')
            ->assertSee('En mantenimiento')
            // Cambiar sub-pestaña a Rentas (2 gráficas + 4 tablas)
            ->call('cambiarSubpestanaReportes', 'rentas')
            ->assertSet('subpestanaReportes', 'rentas')
            ->assertSee('2 reportes')
            ->assertSee('4 reportes')
            ->assertSee('Equipos en renta')
            ->assertSee('Próximas a vencer')
            // Cambiar sub-pestaña a Histórico (2 gráficas + 3 tablas)
            ->call('cambiarSubpestanaReportes', 'historico')
            ->assertSet('subpestanaReportes', 'historico')
            ->assertSee('2 reportes')
            ->assertSee('3 reportes')
            ->assertSee('Cambios de área')
            ->assertSee('Bajas')
            ->assertSee("openId: 'historico_bajas'", false);

        // Volver a formatos y descargar Cambio de Área
        $livewire->call('cambiarPestanaPrincipal', 'formatos')
            ->call('descargarFormatoInstitucional', 'cambio_area');
    }

    public function test_los_6_reportes_originales_estan_restaurados_en_la_pagina(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $livewire = Livewire::test(ReportesPage::class)
            ->assertSee('Reportes Principales del Inventario')
            ->assertSee('sigem-rep-accordion', false)
            ->assertSee("openId: 'inventario_categoria'", false)
            ->assertSee('sigem-rep-acc-badge', false)
            ->assertSee('▼')
            ->assertSee('▶')
            ->assertSee('Inventario por categoría')
            ->assertSee('Equipos por área')
            ->assertSee('Equipos en mantenimiento')
            ->assertSee('Equipos con solicitud de baja')
            ->assertSee('Solicitudes de material')
            ->assertSee('Movimientos SS')
            ->assertSee('Visible para Servicio Social')
            ->assertSee('Categoría')
            ->assertSee('↓ Excel')
            ->assertSee('↓ CSV')
            ->assertDontSee('wire:click="verReporte(', false);

        // Toggle visibilidad SS funciona
        $estadoInicial = $livewire->get('visibilidadServicioSocial')['inventario_categoria'] ?? true;
        $livewire->call('toggleVisibilidad', 'inventario_categoria');
        $this->assertEquals(! $estadoInicial, $livewire->get('visibilidadServicioSocial')['inventario_categoria']);
    }

    public function test_reportes_principales_paginan_cuando_hay_mas_de_10_filas(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        for ($i = 1; $i <= 14; $i++) {
            activity()
                ->causedBy($this->adminUser)
                ->event('updated')
                ->log(sprintf('Evento paginación #%02d', $i));
        }

        Livewire::test(ReportesPage::class)
            ->assertSee('Mostrando 1–10 de')
            ->assertSee('Página 1 de')
            ->call('cambiarPaginaReportePrincipal', 'movimientos_recientes', 2)
            ->assertSet('paginasReportesPrincipales.movimientos_recientes', 2)
            ->assertSee('Página 2 de');
    }

    public function test_nuevos_reportes_paginan_todos_los_registros_de_10_en_10_y_toggle_ss(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        for ($i = 1; $i <= 13; $i++) {
            Inventario::create([
                'codigo' => sprintf('PAG-NR-%02d', $i),
                'num_serie' => sprintf('SN-NR-%02d', $i),
                'id_producto' => $this->material->id_producto,
                'departamento_id' => $this->depto->id_departamento,
                'area_id' => $this->area->id_area,
                'estado' => 'Disponible',
                'tipo_propiedad' => 'Propio',
                'fecha_registro' => now()->subDays($i),
                'id_usuario' => $this->adminUser->id,
            ]);
        }

        SigemReportExporter::clearCache();

        Livewire::test(ReportesPage::class)
            ->call('cambiarPestanaPrincipal', 'nuevos')
            ->assertSee('sigem-new-rep-charts-stack', false)
            ->assertSee('sigem-chart-radial-wrap', false)
            ->assertSee('sigem-pill ok', false)
            ->assertSee('sigem-pill info', false)
            ->assertSee('sigem-col-num', false)
            ->assertDontSee('filtroAula', false)
            ->assertSee('Mostrando 1–10 de 13 registros')
            ->assertSee('Página 1 de 2')
            ->call('cambiarPaginaNuevoReporte', 'inventario_general', 2)
            ->assertSet('paginasNuevosReportes.inventario_general', 2)
            ->assertSee('Mostrando 11–13 de 13 registros')
            ->assertSee('Página 2 de 2')
            ->call('toggleVisibilidad', 'inventario_general')
            ->assertSet('visibilidadServicioSocial.inventario_general', false);
    }

    public function test_exactamente_12_reportes_tienen_grafica_y_16_son_solo_tabla(): void
    {
        $catalogo = SigemReportExporter::getCatalogo();
        $conGrafica = [];
        $soloTabla = [];

        foreach ($catalogo as $sub => $reportes) {
            foreach ($reportes as $id => $meta) {
                if (! empty($meta['has_chart'])) {
                    $conGrafica[$id] = $meta;
                } else {
                    $soloTabla[$id] = $meta;
                }
            }
        }

        $this->assertCount(12, $conGrafica, 'Debe haber exactamente 12 reportes con gráfica');
        $this->assertCount(17, $soloTabla, 'Debe haber exactamente 17 reportes solo tabla (incluye rent_seguimiento)');

        $esperadosConGrafica = [
            'inventario_por_departamento',   // inv_depto
            'inventario_por_tipo',           // inv_tipo
            'inventario_por_estado',         // inv_estado
            'estadisticas_depto_mas_equipos',// est_top_deptos
            'estadisticas_mas_movidos',      // est_movidos
            'estadisticas_propio_vs_rentado',// est_propiedad
            'mantenimiento_depto_pide_mas',  // mant_top_deptos
            'mantenimiento_por_mes',         // mant_por_mes
            'rentas_proximas_vencer',        // rent_por_vencer
            'rentas_gasto_mensual',          // rent_gasto_mensual
            'historico_cambios_area',        // hist_cambios_area
            'historico_actividad_usuario',   // hist_actividad_usuario
        ];

        foreach ($esperadosConGrafica as $idEsperado) {
            $this->assertArrayHasKey($idEsperado, $conGrafica, "El reporte {$idEsperado} debe tener gráfica");
            $data = SigemReportExporter::getReportData($idEsperado);
            $this->assertTrue($data['has_chart'], "Data de {$idEsperado} debe marcar has_chart=true");
            $this->assertNotNull($data['chart'], "Data de {$idEsperado} debe contener chart");
            $this->assertArrayHasKey('type', $data['chart']);
            $this->assertArrayHasKey('labels', $data['chart']);
            $this->assertArrayHasKey('datasets', $data['chart']);
            $this->assertArrayHasKey('options', $data['chart']);
            $this->assertNotEmpty($data['totales_label']);
            $this->assertNotEmpty($data['top5_headers']);
        }

        $esperadosSoloTabla = [
            'inventario_general',
            'inventario_por_area',
            'inventario_por_aula',
            'estadisticas_antiguos',
            'estadisticas_sin_asignar',
            'estadisticas_resumen_general',
            'mantenimiento_tipo_mas_frecuente',
            'mantenimiento_en_curso',
            'mantenimiento_tiempo_promedio',
            'mantenimiento_historial_equipo',
            'rentas_equipos',
            'rentas_vencidas',
            'rentas_por_proveedor',
            'rent_seguimiento',
            'historico_bajas',
            'historico_entregas',
            'historico_general',
        ];

        foreach ($esperadosSoloTabla as $idEsperado) {
            $this->assertArrayHasKey($idEsperado, $soloTabla, "El reporte {$idEsperado} debe ser solo tabla");
            $data = SigemReportExporter::getReportData($idEsperado);
            $this->assertFalse($data['has_chart'], "Data de {$idEsperado} debe marcar has_chart=false");
            $this->assertNull($data['chart']);
            $this->assertNotEmpty($data['totales_label']);
            $this->assertNotEmpty($data['top5_headers']);
        }
    }

    public function test_formatos_institucionales_pdf_tienen_sistema_de_pestanas_con_contadores(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $component = Livewire::test(ReportesPage::class)
            ->call('cambiarPestanaPrincipal', 'formatos')
            ->assertSee('📄 Formatos Institucionales (PDF)')
            ->assertSee('📋 Entrega')
            ->assertSee('🔧 Orden')
            ->assertSee('✅ Lista')
            ->assertSee('⚠️ Baja')
            ->assertSee('🔄 Cambio')
            ->assertSee('🔍 Filtrar existentes');

        // Pestaña inicial por defecto: Entrega (formato tabla)
        $component->assertSee('Entrega de Equipo')
            ->assertSee('Receptor');

        // Cambiar a Orden de Trabajo (formato tabla)
        $component->call('cambiarPestanaFormato', 'orden_trabajo')
            ->assertSee('Orden de Trabajo')
            ->assertSee('Tipo servicio');

        // Cambiar a Lista Verificación (formato tabla)
        $component->call('cambiarPestanaFormato', 'lista_verificacion')
            ->assertSee('Lista Verificación')
            ->assertSee('Atendido por');

        // Cambiar a Baja (formato tabla)
        $component->call('cambiarPestanaFormato', 'baja')
            ->assertSee('Trámite de Baja')
            ->assertSee('Motivo');

        // Cambiar a Cambio de Área (formato tabla)
        $component->call('cambiarPestanaFormato', 'cambio_area')
            ->assertSee('Cambio de Área')
            ->assertSee('Origen')
            ->assertSee('Destino');

        // Cambiar a Filtrar formatos existentes (formato tabla)
        $component->call('cambiarPestanaFormato', 'filtrar')
            ->assertSee('Filtrar formatos existentes')
            ->assertSee('Busca y descarga formatos ya generados')
            ->assertSee('Descargar seleccionados')
            ->assertDontSee('Filtros de regeneración');
    }

    public function test_ruta_web_abrir_y_descargar_formatos_pdf_funciona(): void
    {
        $this->actingAs($this->adminUser);

        $receptor = \App\Models\Receptor::create([
            'nombre' => 'Juan',
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => 'Gómez',
            'id_area' => $this->area->id_area,
        ]);

        $solicitud = Solicitud::create([
            'id_usuario' => $this->adminUser->id,
            'id_receptor' => $receptor->id_receptor,
            'tipo_movimiento' => 'Asignacion Permanente',
            'estado' => 'Autorizado',
            'fecha_solicitud' => now(),
        ]);

        // Probar apertura (?preview=1 inline)
        $resPreview = $this->get(route('reportes.pdf', [
            'tipo' => 'entrega',
            'id' => $solicitud->id_solicitud,
            'preview' => 1,
        ]));
        $resPreview->assertOk();
        $this->assertStringContainsString('inline', (string) $resPreview->headers->get('content-disposition'));
        $this->assertEquals('application/pdf', $resPreview->headers->get('content-type'));

        // Probar descarga (?download=1 attachment)
        $resDownload = $this->get(route('reportes.pdf', [
            'tipo' => 'entrega',
            'id' => $solicitud->id_solicitud,
            'download' => 1,
        ]));
        $resDownload->assertOk();
        $this->assertStringContainsString('attachment', (string) $resDownload->headers->get('content-disposition'));
        $this->assertEquals('application/pdf', $resDownload->headers->get('content-type'));
    }
}

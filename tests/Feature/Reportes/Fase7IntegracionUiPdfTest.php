<?php

namespace Tests\Feature\Reportes;

use App\Filament\Pages\Dashboard as AdminDashboard;
use App\Filament\Pages\ReportesPage as AdminReportesPage;
use App\Filament\Resources\InventarioResource\Pages\ListInventarios;
use App\Filament\Resources\MantenimientoResource\Pages\ListMantenimientos;
use App\Filament\Resources\SolicitudResource\Pages\ListSolicituds;
use App\Filament\ServicioSocial\Pages\ReportesPage as SSReportesPage;
use App\Filament\ServicioSocial\Resources\MantenimientoResource\Pages\ListMantenimientos as SSListMantenimientos;
use App\Filament\ServicioSocial\Resources\SolicitudResource\Pages\ListSolicituds as SSListSolicituds;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\DetalleSolicitud;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\Receptor;
use App\Models\Salon;
use App\Models\Solicitud;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class Fase7IntegracionUiPdfTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $ssUser;
    protected Departamento $depto;
    protected Departamento $deptoSecundario;
    protected Area $area;
    protected Salon $salon;
    protected Material $material;
    protected Receptor $receptor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(ServicioSocialUserSeeder::class);

        $this->adminUser = User::where('email', 'admin@tecnm.edu.mx')->first();
        $this->ssUser = User::where('email', 'servicio@tecnm.edu.mx')->first();

        $this->depto = Departamento::firstOrCreate(
            ['nombre' => 'Centro de Cómputo'],
            ['encargado' => 'Héctor Pérez Ortiz']
        );
        $this->deptoSecundario = Departamento::firstOrCreate(
            ['nombre' => 'Servicios Escolares'],
            ['encargado' => 'María López Ruiz']
        );

        $this->area = Area::firstOrCreate([
            'nombre' => 'Laboratorio L1',
            'id_departamento' => $this->depto->id_departamento,
        ]);
        $this->salon = Salon::firstOrCreate([
            'nombre' => 'Aula 1',
            'area_id' => $this->area->id_area,
        ]);

        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'Dell']);
        $tipo = TipoMaterial::firstOrCreate(['nombre' => 'Computadora']);
        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pieza']);

        $this->material = Material::firstOrCreate(
            ['nombre' => 'OptiPlex 7090'],
            [
                'descripcion' => 'Equipo de escritorio institucional',
                'modelo' => '7090',
                'id_marca' => $marca->id_marca,
                'id_tipodematerial' => $tipo->id_tipodematerial,
                'id_unidad' => $unidad->id_unidad,
                'stock_actual' => 5,
                'stock_minimo' => 1,
                'requiere_control_individual' => 1,
            ]
        );

        $this->receptor = Receptor::firstOrCreate([
            'nombre' => 'Laura',
            'apellido_paterno' => 'Mendoza',
            'apellido_materno' => 'Castro',
            'id_area' => $this->area->id_area,
        ]);
    }

    protected function crearInventario(array $overrides = []): Inventario
    {
        return Inventario::create(array_merge([
            'id_producto' => $this->material->id_producto,
            'id_usuario' => $this->adminUser->id,
            'codigo' => 'INV-' . strtoupper(substr(uniqid(), -6)),
            'num_serie' => 'SN-' . strtoupper(uniqid()),
            'modelo' => 'OptiPlex 7090',
            'especificacion_texto' => 'Equipo de prueba Fase 7',
            'tipo_propiedad' => 'Propio',
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'aprobado' => true,
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'salon_id' => $this->salon->id,
            'fecha_registro' => now(),
        ], $overrides));
    }

    protected function captureStreamedPdf(StreamedResponse $response): string
    {
        ob_start();
        $response->sendContent();

        return (string) ob_get_clean();
    }

    protected function assertLivewirePdfDownloaded(object $testable, string $expectedFilename): string
    {
        $testable->assertFileDownloaded($expectedFilename);
        $decoded = base64_decode((string) data_get($testable->effects, 'download.content'));
        $this->assertStringStartsWith('%PDF', $decoded);

        return $decoded;
    }

    public function test_ruta_y_infolist_formato_baja_visible_solo_para_bajas(): void
    {
        $invBaja = $this->crearInventario([
            'estado' => 'Dado de baja',
            'motivo_baja' => 'Daño irreparable',
            'observaciones_baja' => 'Corto circuito en placa base',
            'fecha_baja' => '2026-09-10',
        ]);
        $invSolicitado = $this->crearInventario([
            'estado' => 'Solicitud de baja',
            'motivo_baja' => 'Obsolescencia',
            'observaciones_baja' => 'Equipo obsoleto',
            'fecha_baja' => '2026-09-11',
        ]);
        $invDisponible = $this->crearInventario(['estado' => 'Disponible']);

        $this->get(route('sigem.pdf.baja', ['inventario' => $invBaja]))
            ->assertForbidden();

        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $respBaja = $this->get(route('sigem.pdf.baja', ['inventario' => $invBaja]));
        $respBaja->assertOk();
        $respBaja->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $respBaja->streamedContent());

        $respSolicitado = $this->get(route('sigem.pdf.baja', ['inventario' => $invSolicitado]));
        $respSolicitado->assertOk();
        $this->assertStringStartsWith('%PDF', $respSolicitado->streamedContent());

        $this->get(route('sigem.pdf.baja', ['inventario' => $invDisponible]))
            ->assertNotFound();

        $urlBaja = route('sigem.pdf.baja', ['inventario' => $invBaja]);
        Livewire::test(ListInventarios::class)
            ->mountTableAction('view', $invBaja)
            ->assertSee('Formato de Baja')
            ->assertSeeHtml($urlBaja);

        Livewire::test(ListInventarios::class)
            ->mountTableAction('view', $invDisponible)
            ->assertDontSee('Formato de Baja');
    }

    public function test_acciones_masivas_muestra_y_descarga_formatos_baja_lote(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $baja1 = $this->crearInventario([
            'estado' => 'Dado de baja',
            'motivo_baja' => 'Daño irreparable',
            'fecha_baja' => '2026-09-12',
        ]);
        $baja2 = $this->crearInventario([
            'estado' => 'Solicitud de baja',
            'motivo_baja' => 'Obsolescencia',
            'fecha_baja' => '2026-09-13',
        ]);
        $disp1 = $this->crearInventario(['estado' => 'Disponible']);
        $disp2 = $this->crearInventario(['estado' => 'Disponible']);

        Livewire::test(AdminDashboard::class)
            ->set('selectedRows', [(string) $disp1->id_inventario, (string) $disp2->id_inventario])
            ->assertDontSee('Formatos de Baja (lote)');

        $dash = Livewire::test(AdminDashboard::class)
            ->set('selectedRows', [(string) $baja1->id_inventario, (string) $baja2->id_inventario])
            ->assertSee('Formatos de Baja (lote)');

        $response = $dash->instance()->descargarFormatosBajaLote();
        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertStringContainsString('tramites-baja-lote.pdf', (string) $response->headers->get('content-disposition'));

        $pdfBytes = $this->captureStreamedPdf($response);
        $this->assertStringStartsWith('%PDF', $pdfBytes);
        $this->assertMatchesRegularExpression('/\/Type \/Pages\b.*?\/Count 2\b/s', $pdfBytes);

        $bulk = Livewire::test(ListInventarios::class)
            ->callTableBulkAction('formatosBajaLote', [$baja1, $baja2]);
        $bulkPdf = $this->assertLivewirePdfDownloaded($bulk, 'tramites-baja-lote.pdf');
        $this->assertMatchesRegularExpression('/\/Type \/Pages\b.*?\/Count 2\b/s', $bulkPdf);
    }

    public function test_mantenimiento_botones_orden_trabajo_y_lista_verificacion_admin_y_ss(): void
    {
        $inv = $this->crearInventario(['estado' => 'Mantenimiento']);

        $mantPendiente = Mantenimiento::create([
            'id_inventario' => $inv->id_inventario,
            'id_usuario_solicita' => $this->ssUser->id,
            'tipo_mantenimiento' => 'Correctivo',
            'tipo_servicio' => 'Servicio Social',
            'nombre_tecnico' => 'Técnico Pruebas',
            'descripcion_falla' => 'No enciende fuente de poder',
            'fecha_solicitud' => '2026-09-15',
            'estado' => 'Pendiente Revision Admin',
        ]);

        $mantCompletado = Mantenimiento::create([
            'id_inventario' => $inv->id_inventario,
            'id_usuario_solicita' => $this->ssUser->id,
            'tipo_mantenimiento' => 'Preventivo',
            'tipo_servicio' => 'Personal Técnico',
            'nombre_tecnico' => 'Técnico Verificado',
            'descripcion_falla' => 'Mantenimiento general',
            'descripcion_trabajo' => 'Limpieza interna y cambio de pasta térmica',
            'fecha_solicitud' => '2026-09-16',
            'fecha_fin' => '2026-09-17',
            'estado' => 'Completado',
        ]);

        $mantCancelado = Mantenimiento::create([
            'id_inventario' => $inv->id_inventario,
            'id_usuario_solicita' => $this->ssUser->id,
            'tipo_mantenimiento' => 'Correctivo',
            'tipo_servicio' => 'Servicio Social',
            'nombre_tecnico' => 'Técnico Cancelado',
            'descripcion_falla' => 'Cancelado por usuario',
            'fecha_solicitud' => '2026-09-18',
            'estado' => 'Cancelado',
        ]);

        // Admin panel
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $adminOt = Livewire::test(ListMantenimientos::class)
            ->assertTableActionVisible('ordenTrabajo', $mantPendiente)
            ->assertTableActionVisible('ordenTrabajo', $mantCompletado)
            ->assertTableActionHidden('ordenTrabajo', $mantCancelado)
            ->assertTableActionHidden('listaVerificacion', $mantPendiente)
            ->assertTableActionVisible('listaVerificacion', $mantCompletado)
            ->assertTableActionHidden('listaVerificacion', $mantCancelado)
            ->callTableAction('ordenTrabajo', $mantPendiente);
        $this->assertLivewirePdfDownloaded($adminOt, "orden-trabajo-{$mantPendiente->id_mantenimiento}.pdf");

        $adminLv = Livewire::test(ListMantenimientos::class)
            ->callTableAction('listaVerificacion', $mantCompletado);
        $this->assertLivewirePdfDownloaded($adminLv, "lista-verificacion-{$mantCompletado->id_mantenimiento}.pdf");

        Livewire::test(ListMantenimientos::class)
            ->mountTableAction('view', $mantCompletado)
            ->assertSeeHtml('ordenTrabajoDetalle')
            ->assertSeeHtml('listaVerificacionDetalle')
            ->assertSee('Orden de Trabajo')
            ->assertSee('Lista Verificación');

        Livewire::test(ListMantenimientos::class)
            ->mountTableAction('view', $mantPendiente)
            ->assertSeeHtml('ordenTrabajoDetalle')
            ->assertDontSeeHtml('listaVerificacionDetalle');

        // Servicio Social panel
        $this->actingAs($this->ssUser);
        Filament::setCurrentPanel(Filament::getPanel('servicio-social'));

        $ssOt = Livewire::test(SSListMantenimientos::class)
            ->assertTableActionVisible('ordenTrabajo', $mantPendiente)
            ->assertTableActionVisible('listaVerificacion', $mantCompletado)
            ->callTableAction('ordenTrabajo', $mantPendiente);
        $this->assertLivewirePdfDownloaded($ssOt, "orden-trabajo-{$mantPendiente->id_mantenimiento}.pdf");
    }

    public function test_solicitud_boton_acta_entrega_admin_y_ss(): void
    {
        $inv = $this->crearInventario(['estado' => 'Asignado']);

        $solAutorizada = Solicitud::create([
            'id_usuario' => $this->ssUser->id,
            'id_receptor' => $this->receptor->id_receptor,
            'tipo_movimiento' => 'Asignacion Permanente',
            'fecha_solicitud' => '2026-09-20',
            'estado' => 'Autorizado',
            'observaciones' => 'Entrega con cable HDMI y cargador',
        ]);
        DetalleSolicitud::create([
            'id_solicitud' => $solAutorizada->id_solicitud,
            'id_producto' => $this->material->id_producto,
            'id_inventario' => $inv->id_inventario,
            'cantidad' => 1,
        ]);

        $solRechazada = Solicitud::create([
            'id_usuario' => $this->ssUser->id,
            'id_receptor' => $this->receptor->id_receptor,
            'tipo_movimiento' => 'Asignacion Temporal',
            'fecha_solicitud' => '2026-09-21',
            'estado' => 'Rechazado',
        ]);

        // Admin panel
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $adminSol = Livewire::test(ListSolicituds::class)
            ->assertTableActionVisible('actaEntrega', $solAutorizada)
            ->assertTableActionHidden('actaEntrega', $solRechazada)
            ->callTableAction('actaEntrega', $solAutorizada);
        $this->assertLivewirePdfDownloaded($adminSol, "acta-entrega-{$solAutorizada->id_solicitud}.pdf");

        Livewire::test(ListSolicituds::class)
            ->mountTableAction('view', $solAutorizada)
            ->assertSee('Acta de Entrega');

        // Servicio Social panel
        $this->actingAs($this->ssUser);
        Filament::setCurrentPanel(Filament::getPanel('servicio-social'));

        $ssSol = Livewire::test(SSListSolicituds::class)
            ->assertTableActionVisible('actaEntrega', $solAutorizada)
            ->assertTableActionHidden('actaEntrega', $solRechazada)
            ->callTableAction('actaEntrega', $solAutorizada);
        $this->assertLivewirePdfDownloaded($ssSol, "acta-entrega-{$solAutorizada->id_solicitud}.pdf");
    }

    public function test_reportes_preserva_6_tarjetas_excel_y_agrega_formatos_institucionales_pdf(): void
    {
        $invBajaDepto1 = $this->crearInventario([
            'estado' => 'Dado de baja',
            'motivo_baja' => 'Obsolescencia',
            'fecha_baja' => '2026-09-22',
            'departamento_id' => $this->depto->id_departamento,
        ]);

        $areaSecundaria = Area::firstOrCreate([
            'nombre' => 'Ventanillas',
            'id_departamento' => $this->deptoSecundario->id_departamento,
        ]);
        $invBajaDepto2 = $this->crearInventario([
            'codigo' => 'INV-BAJA-02',
            'estado' => 'Dado de baja',
            'motivo_baja' => 'Daño irreparable',
            'fecha_baja' => '2026-09-23',
            'departamento_id' => $this->deptoSecundario->id_departamento,
            'area_id' => $areaSecundaria->id_area,
        ]);

        $invParaMant = $this->crearInventario(['estado' => 'En Mantenimiento']);
        $invParaEntrega = $this->crearInventario(['estado' => 'Asignado']);

        $mantComp = Mantenimiento::create([
            'id_inventario' => $invParaMant->id_inventario,
            'id_usuario_solicita' => $this->adminUser->id,
            'tipo_mantenimiento' => 'Correctivo',
            'tipo_servicio' => 'Personal Técnico',
            'nombre_tecnico' => 'Técnico Sistemas',
            'descripcion_falla' => 'Revisión disco duro',
            'descripcion_trabajo' => 'Reemplazo SSD',
            'fecha_solicitud' => '2026-09-22',
            'fecha_fin' => '2026-09-23',
            'estado' => 'Completado',
        ]);

        $solEntrega = Solicitud::create([
            'id_usuario' => $this->adminUser->id,
            'id_receptor' => $this->receptor->id_receptor,
            'tipo_movimiento' => 'Asignacion Permanente',
            'fecha_solicitud' => '2026-09-24',
            'estado' => 'Autorizado',
        ]);
        DetalleSolicitud::create([
            'id_solicitud' => $solEntrega->id_solicitud,
            'id_producto' => $this->material->id_producto,
            'id_inventario' => $invParaEntrega->id_inventario,
            'cantidad' => 1,
        ]);

        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $page = new AdminReportesPage();
        $reportes = $page->getReportes();
        $this->assertCount(6, $reportes);
        $this->assertEquals([
            'inventario_categoria',
            'equipos_area',
            'equipos_mantenimiento',
            'equipos_baja_danados',
            'solicitudes_tipo_estado',
            'movimientos_recientes',
        ], array_column($reportes, 'id'));

        $formatos = $page->getFormatosInstitucionales();
        $this->assertCount(4, $formatos);
        $this->assertEquals([
            'entrega',
            'orden_trabajo',
            'lista_verificacion',
            'baja',
        ], array_column($formatos, 'id'));

        $livewire = Livewire::test(AdminReportesPage::class)
            ->assertSee('Formatos Institucionales (PDF)')
            ->assertSee('Entrega de Equipo')
            ->assertSee('Orden de Trabajo')
            ->assertSee('Lista Verificación')
            ->assertSee('Trámite de Baja')
            ->assertSee('Generar seleccionados');

        $livewire->call('descargarFormatoInstitucional', 'entrega');
        $this->assertLivewirePdfDownloaded($livewire, "acta-entrega-{$solEntrega->id_solicitud}.pdf");

        $livewire->call('descargarFormatoInstitucional', 'orden_trabajo');
        $this->assertLivewirePdfDownloaded($livewire, "orden-trabajo-{$mantComp->id_mantenimiento}.pdf");

        $livewire->call('descargarFormatoInstitucional', 'lista_verificacion');
        $this->assertLivewirePdfDownloaded($livewire, "lista-verificacion-{$mantComp->id_mantenimiento}.pdf");

        $livewire->call('descargarFormatoInstitucional', 'baja');
        $this->assertLivewirePdfDownloaded($livewire, 'tramites-baja-lote.pdf');

        // Verificar filtros por departamento y generación filtrada
        $livewire
            ->set('formatoDepartamentoId', (string) $this->deptoSecundario->id_departamento)
            ->set('formatoFiltroTipo', 'baja')
            ->call('generarFormatosFiltrados');
        $this->assertLivewirePdfDownloaded($livewire, "tramite-baja-{$invBajaDepto2->codigo}.pdf");

        // Verificar Servicio Social ReportesPage
        $this->actingAs($this->ssUser);
        Filament::setCurrentPanel(Filament::getPanel('servicio-social'));

        Livewire::test(SSReportesPage::class)
            ->assertSee('Formatos Institucionales (PDF)')
            ->assertSee('Trámite de Baja');
    }
}

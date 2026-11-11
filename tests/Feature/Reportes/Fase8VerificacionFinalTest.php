<?php

namespace Tests\Feature\Reportes;

use App\Filament\Pages\ReportesPage as AdminReportesPage;
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
use App\Support\SigemBajaFormatHelper;
use App\Support\SigemPdfGenerator;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class Fase8VerificacionFinalTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $ssUser;
    protected Departamento $deptoCentro;
    protected Departamento $deptoSecundario;
    protected Area $areaCentro;
    protected Area $areaSecundario;
    protected Salon $salonCentro;
    protected Salon $salonSecundario;
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

        $this->deptoCentro = Departamento::firstOrCreate(
            ['nombre' => 'Centro de Cómputo'],
            ['encargado' => 'Mtro. Encargado Centro']
        );
        $this->deptoSecundario = Departamento::firstOrCreate(
            ['nombre' => 'Servicios Escolares'],
            ['encargado' => 'Lic. Encargado Escolares']
        );

        $this->areaCentro = Area::firstOrCreate([
            'nombre' => 'Laboratorio L2',
            'id_departamento' => $this->deptoCentro->id_departamento,
        ]);
        $this->areaSecundario = Area::firstOrCreate([
            'nombre' => 'Ventanillas',
            'id_departamento' => $this->deptoSecundario->id_departamento,
        ]);

        $this->salonCentro = Salon::firstOrCreate([
            'nombre' => 'Sala L2',
            'area_id' => $this->areaCentro->id_area,
        ]);
        $this->salonSecundario = Salon::firstOrCreate([
            'nombre' => 'Cubículo 1',
            'area_id' => $this->areaSecundario->id_area,
        ]);

        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'Dell']);
        $tipo = TipoMaterial::firstOrCreate(['nombre' => 'Computadora']);
        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pieza']);

        $this->material = Material::firstOrCreate(
            ['nombre' => 'Latitude 5430'],
            [
                'descripcion' => 'Equipo portátil institucional',
                'modelo' => '5430',
                'id_marca' => $marca->id_marca,
                'id_tipodematerial' => $tipo->id_tipodematerial,
                'id_unidad' => $unidad->id_unidad,
                'stock_actual' => 6,
                'stock_minimo' => 1,
                'requiere_control_individual' => 1,
            ]
        );

        $this->receptor = Receptor::firstOrCreate([
            'nombre' => 'Carlos',
            'apellido_paterno' => 'Ramírez',
            'apellido_materno' => 'Soto',
            'id_area' => $this->areaCentro->id_area,
        ]);
    }

    protected function crearInventario(array $overrides = []): Inventario
    {
        return Inventario::create(array_merge([
            'id_producto' => $this->material->id_producto,
            'id_usuario' => $this->adminUser->id,
            'codigo' => '11612008118000016802300' . random_int(1000, 9999),
            'num_serie' => 'SN-F8-' . strtoupper(substr(uniqid(), -6)),
            'modelo' => '5430',
            'especificacion_texto' => 'Laptop Core i5',
            'tipo_propiedad' => 'Propio',
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'aprobado' => true,
            'departamento_id' => $this->deptoCentro->id_departamento,
            'area_id' => $this->areaCentro->id_area,
            'salon_id' => $this->salonCentro->id,
            'fecha_registro' => now(),
        ], $overrides));
    }

    protected function extraerTextoPdf(string $binaryPdf): string
    {
        $texto = '';
        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $binaryPdf, $matches)) {
            foreach ($matches[1] as $stream) {
                $decoded = @zlib_decode($stream);
                $chunk = ($decoded !== false) ? $decoded : $stream;

                if (! str_contains($chunk, 'BT')) {
                    continue;
                }

                $limpio = str_replace(["\x00", "\\(", "\\)", "\\r", "\\n"], ['', '(', ')', "\r", "\n"], $chunk);
                $limpio = preg_replace('/[^\x20-\x7E\r\n]/', '', $limpio);
                $texto .= "\n" . $limpio;
            }
        }

        return $texto;
    }

    // ============================================
    // INTEGRACIÓN: Los 4 formatos generan PDFs válidos
    // ============================================

    public function test_los_4_formatos_generan_pdfs_validos(): void
    {
        $invEntrega = $this->crearInventario(['estado' => 'Asignado']);
        $solicitud = Solicitud::create([
            'id_usuario' => $this->adminUser->id,
            'id_receptor' => $this->receptor->id_receptor,
            'tipo_movimiento' => 'Asignacion Permanente',
            'fecha_solicitud' => '2026-10-01',
            'estado' => 'Autorizado',
            'observaciones' => 'Entrega verificada Fase 8',
        ]);
        DetalleSolicitud::create([
            'id_solicitud' => $solicitud->id_solicitud,
            'id_producto' => $this->material->id_producto,
            'id_inventario' => $invEntrega->id_inventario,
            'cantidad' => 1,
        ]);

        $invMant = $this->crearInventario(['estado' => 'Disponible']);
        $mantenimiento = Mantenimiento::create([
            'id_inventario' => $invMant->id_inventario,
            'id_usuario_solicita' => $this->ssUser->id,
            'tipo_mantenimiento' => 'Correctivo',
            'tipo_servicio' => 'Servicio Social',
            'nombre_tecnico' => 'Técnico Fase 8',
            'descripcion_falla' => 'Falla en ventilador',
            'descripcion_trabajo' => 'Reemplazo de ventilador y limpieza',
            'fecha_solicitud' => '2026-10-02',
            'fecha_fin' => '2026-10-03',
            'estado' => 'Completado',
        ]);

        $invBaja = $this->crearInventario([
            'estado' => 'Dado de baja',
            'motivo_baja' => 'Obsolescencia',
            'observaciones_baja' => 'Equipo obsoleto Fase 8',
            'fecha_baja' => '2026-10-04',
        ]);

        $pdfs = [
            SigemPdfGenerator::generarActaEntrega($solicitud)->output(),
            SigemPdfGenerator::generarOrdenTrabajo($mantenimiento)->output(),
            SigemPdfGenerator::generarListaVerificacion($mantenimiento)->output(),
            SigemPdfGenerator::generarFormatoBaja($invBaja)->output(),
        ];

        foreach ($pdfs as $contenido) {
            $this->assertNotEmpty($contenido);
            $this->assertStringStartsWith('%PDF', $contenido);
            $this->assertMatchesRegularExpression('/\/Type \/Pages\b.*?\/Count \d+\b/s', $contenido);
        }
    }

    public function test_formatos_usan_datos_reales_de_bd(): void
    {
        $deptoDinamico = Departamento::create([
            'nombre' => 'Departamento de Ciencias Dinamicas',
            'encargado' => 'Dr. Ernesto Prueba Uno',
        ]);
        $areaDinamica = Area::create([
            'nombre' => 'Sala Ciencias',
            'id_departamento' => $deptoDinamico->id_departamento,
        ]);
        $salonDinamico = Salon::create([
            'nombre' => 'Cubiculo C1',
            'area_id' => $areaDinamica->id_area,
        ]);

        $inv = $this->crearInventario([
            'departamento_id' => $deptoDinamico->id_departamento,
            'area_id' => $areaDinamica->id_area,
            'salon_id' => $salonDinamico->id,
        ]);
        $mantenimiento = Mantenimiento::create([
            'id_inventario' => $inv->id_inventario,
            'id_usuario_solicita' => $this->ssUser->id,
            'tipo_mantenimiento' => 'Preventivo',
            'tipo_servicio' => 'Servicio Social',
            'nombre_tecnico' => 'Técnico BD',
            'descripcion_falla' => 'Revisión periódica dinámica',
            'fecha_solicitud' => '2026-10-05',
            'estado' => 'Pendiente Revision Admin',
        ]);

        $textoInicial = $this->extraerTextoPdf(SigemPdfGenerator::generarOrdenTrabajo($mantenimiento)->output());
        $this->assertStringContainsString('Departamento de Ciencias Dinamicas', $textoInicial);

        $deptoDinamico->update(['nombre' => 'Departamento Actualizado Sigem']);
        $mantenimiento->refresh();

        $textoActualizado = $this->extraerTextoPdf(SigemPdfGenerator::generarOrdenTrabajo($mantenimiento)->output());
        $this->assertStringContainsString('Departamento Actualizado Sigem', $textoActualizado);
        $this->assertStringNotContainsString('Departamento de Ciencias Dinamicas', $textoActualizado);
    }

    // ============================================
    // RUTAS: Las rutas de descarga funcionan
    // ============================================

    public function test_ruta_formato_baja_funciona(): void
    {
        $invBaja = $this->crearInventario([
            'estado' => 'Dado de baja',
            'motivo_baja' => 'Daño irreparable',
            'fecha_baja' => '2026-10-06',
        ]);
        $invSolicitado = $this->crearInventario([
            'estado' => 'Solicitud de baja',
            'motivo_baja' => 'Destrucción',
            'fecha_baja' => '2026-10-06',
        ]);

        $this->actingAs($this->adminUser);

        $respBaja = $this->get(route('sigem.pdf.baja', ['inventario' => $invBaja]));
        $respBaja->assertOk();
        $respBaja->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $respBaja->streamedContent());

        $respSol = $this->get(route('sigem.pdf.baja', ['inventario' => $invSolicitado]));
        $respSol->assertOk();
        $respSol->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $respSol->streamedContent());
    }

    public function test_ruta_formato_baja_valida_permisos(): void
    {
        $invBaja = $this->crearInventario([
            'estado' => 'Dado de baja',
            'motivo_baja' => 'Obsolescencia',
            'fecha_baja' => '2026-10-07',
        ]);
        $invDisponible = $this->crearInventario(['estado' => 'Disponible']);

        $this->get(route('sigem.pdf.baja', ['inventario' => $invBaja]))
            ->assertForbidden();

        $this->actingAs($this->adminUser);

        $this->get(route('sigem.pdf.baja', ['inventario' => $invDisponible]))
            ->assertNotFound();
    }

    // ============================================
    // REPORTES: Sección de formatos existe
    // ============================================

    public function test_reportes_mantiene_6_tarjetas_excel(): void
    {
        $page = new AdminReportesPage();
        $reportes = $page->getReportes();

        $this->assertCount(6, $reportes);
        $this->assertSame([
            'inventario_categoria',
            'equipos_area',
            'equipos_mantenimiento',
            'equipos_baja_danados',
            'solicitudes_tipo_estado',
            'movimientos_recientes',
        ], array_column($reportes, 'id'));
    }

    public function test_reportes_tiene_seccion_formatos_pdf(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $page = new AdminReportesPage();
        $formatos = $page->getFormatosInstitucionales();

        $this->assertCount(4, $formatos);
        $this->assertSame([
            'entrega',
            'orden_trabajo',
            'lista_verificacion',
            'baja',
        ], array_column($formatos, 'id'));

        Livewire::test(AdminReportesPage::class)
            ->assertSee('Formatos Institucionales (PDF)')
            ->assertSee('Entrega de Equipo')
            ->assertSee('Orden de Trabajo')
            ->assertSee('Lista Verificación')
            ->assertSee('Trámite de Baja')
            ->assertSee('Filtros de regeneración');
    }

    public function test_reportes_filtros_historicos_funcionan(): void
    {
        $bajaCentroAgosto = $this->crearInventario([
            'codigo' => 'BAJA-CENTRO-AGO',
            'estado' => 'Dado de baja',
            'motivo_baja' => 'Obsolescencia',
            'fecha_baja' => '2026-08-10',
            'departamento_id' => $this->deptoCentro->id_departamento,
            'area_id' => $this->areaCentro->id_area,
            'salon_id' => $this->salonCentro->id,
        ]);
        $bajaEscolarOct = $this->crearInventario([
            'codigo' => 'BAJA-ESC-OCT',
            'estado' => 'Dado de baja',
            'motivo_baja' => 'Daño irreparable',
            'fecha_baja' => '2026-10-15',
            'departamento_id' => $this->deptoSecundario->id_departamento,
            'area_id' => $this->areaSecundario->id_area,
            'salon_id' => $this->salonSecundario->id,
        ]);

        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $component = Livewire::test(AdminReportesPage::class);

        $component
            ->set('formatoFechaDesde', '2026-10-01')
            ->set('formatoFechaHasta', '2026-10-31');

        $bajasOct = $component->instance()->getInventariosBajaParaFormato()->pluck('id_inventario')->all();
        $this->assertContains($bajaEscolarOct->id_inventario, $bajasOct);
        $this->assertNotContains($bajaCentroAgosto->id_inventario, $bajasOct);

        $component
            ->call('limpiarFiltrosFormatos')
            ->set('formatoDepartamentoId', (string) $this->deptoCentro->id_departamento);

        $bajasCentro = $component->instance()->getInventariosBajaParaFormato()->pluck('id_inventario')->all();
        $this->assertContains($bajaCentroAgosto->id_inventario, $bajasCentro);
        $this->assertNotContains($bajaEscolarOct->id_inventario, $bajasCentro);

        $component->call('limpiarFiltrosFormatos');
        $todas = $component->instance()->getInventariosBajaParaFormato()->pluck('id_inventario')->all();
        $this->assertContains($bajaCentroAgosto->id_inventario, $todas);
        $this->assertContains($bajaEscolarOct->id_inventario, $todas);
    }

    // ============================================
    // MOTIVOS BAJA: 8 opciones + mapeo correcto
    // ============================================

    public function test_motivos_baja_tiene_8_opciones(): void
    {
        $opciones = SigemBajaFormatHelper::motivosBajaOptions();

        $this->assertCount(8, $opciones);
        $this->assertSame([
            'Daño irreparable',
            'Obsolescencia',
            'Pérdida',
            'Robo',
            'Destrucción',
            'Reaprovechamiento',
            'Desalojo',
            'Otro',
        ], array_keys($opciones));
    }

    public function test_mapeo_causas_cubre_las_7_casillas(): void
    {
        $base = SigemBajaFormatHelper::mapearCausas(null);
        $clavesEsperadas = [
            'asignar_a_otra_area',
            'destruccion',
            'inutilidad',
            'robo_extravio',
            'reaprovechamiento',
            'danado_inservible',
            'desalojo',
        ];
        $this->assertSame($clavesEsperadas, array_keys($base));
        foreach ($base as $causa) {
            $this->assertFalse($causa['marcada']);
            $this->assertNotEmpty($causa['label']);
        }

        $mapaEsperado = [
            'Daño irreparable' => 'danado_inservible',
            'Obsolescencia' => 'inutilidad',
            'Robo' => 'robo_extravio',
            'Pérdida' => 'robo_extravio',
            'Destrucción' => 'destruccion',
            'Reaprovechamiento' => 'reaprovechamiento',
            'Desalojo' => 'desalojo',
        ];

        foreach ($mapaEsperado as $motivo => $claveMarcada) {
            $resultado = SigemBajaFormatHelper::mapearCausas($motivo);
            $this->assertTrue($resultado[$claveMarcada]['marcada'], "Falló el mapeo para motivo: {$motivo}");
            $totalMarcadas = collect($resultado)->where('marcada', true)->count();
            $this->assertSame(1, $totalMarcadas);
        }

        $otro = SigemBajaFormatHelper::mapearCausas('Otro');
        $this->assertSame(0, collect($otro)->where('marcada', true)->count());

        foreach ($clavesEsperadas as $clave) {
            $this->assertNotSame($clave, SigemBajaFormatHelper::etiquetaCausa($clave));
        }
    }

    // ============================================
    // CONFIG: Datos institucionales configurables
    // ============================================

    public function test_config_sigem_tiene_datos_institucionales(): void
    {
        $cfg = config('sigem');

        $this->assertIsArray($cfg);
        $this->assertSame('Instituto Tecnológico de Veracruz', $cfg['institucion']['nombre'] ?? null);
        $this->assertSame('Tecnológico Nacional de México', $cfg['institucion']['tecnm'] ?? null);
        $this->assertSame('Secretaría de Educación Pública', $cfg['institucion']['sep'] ?? null);
        $this->assertSame('Centro de Cómputo', $cfg['centro_computo']['nombre'] ?? null);
        $this->assertNotEmpty($cfg['subdireccion']['nombre'] ?? null);
        $this->assertNotEmpty($cfg['almacen']['oficina'] ?? null);
        $this->assertNotEmpty($cfg['recursos_materiales']['nombre'] ?? null);
        $this->assertSame('ISO 9001: 2015', $cfg['normas']['iso_9001'] ?? null);
        $this->assertSame('ISO 14001: 2015', $cfg['normas']['iso_14001'] ?? null);
        $this->assertSame('letter', $cfg['pdf']['tamaño'] ?? null);
        $this->assertSame('portrait', $cfg['pdf']['orientacion'] ?? null);
    }

    public function test_jefe_departamento_se_toma_de_bd(): void
    {
        $this->deptoCentro->update(['encargado' => 'Ing. Roberto Jefe Dinámico']);

        $institucional = SigemPdfGenerator::datosInstitucionales();
        $this->assertSame('Ing. Roberto Jefe Dinámico', $institucional['centro_computo']['jefe']);

        $inv = $this->crearInventario([
            'estado' => 'Dado de baja',
            'motivo_baja' => 'Obsolescencia',
            'fecha_baja' => '2026-10-08',
            'departamento_id' => $this->deptoCentro->id_departamento,
        ]);
        $mant = Mantenimiento::create([
            'id_inventario' => $inv->id_inventario,
            'id_usuario_solicita' => $this->ssUser->id,
            'tipo_mantenimiento' => 'Correctivo',
            'tipo_servicio' => 'Servicio Social',
            'nombre_tecnico' => 'Técnico Verificación',
            'descripcion_falla' => 'Prueba jefe BD',
            'descripcion_trabajo' => 'Validación realizada',
            'fecha_solicitud' => '2026-10-08',
            'fecha_fin' => '2026-10-08',
            'estado' => 'Completado',
        ]);

        $datosLista = SigemPdfGenerator::datosListaVerificacion($mant);
        $this->assertSame('Ing. Roberto Jefe Dinámico', $datosLista['jefeCentroComputo']);

        $htmlBaja = view('pdf.formato-baja', SigemPdfGenerator::datosFormatosBaja([$inv]))->render();
        $this->assertStringContainsString('Ing. Roberto Jefe Dinámico', $htmlBaja);
    }

    // ============================================
    // LOGOS: Todos los logos institucionales presentes
    // ============================================

    public function test_logos_institucionales_disponibles(): void
    {
        $logos = config('sigem.logos');
        $clavesEsperadas = ['sep', 'tecnm', 'itv', 'sigem', 'iso_9001', 'iso_14001'];

        foreach ($clavesEsperadas as $clave) {
            $rutaRelativa = $logos[$clave] ?? null;
            $this->assertNotEmpty($rutaRelativa, "Falta ruta en config('sigem.logos') para {$clave}");

            $rutaAbsoluta = public_path($rutaRelativa);
            $this->assertTrue(File::exists($rutaAbsoluta), "No existe el logo {$rutaAbsoluta}");

            $dataUri = SigemPdfGenerator::logoDataUri($rutaRelativa);
            $this->assertNotNull($dataUri);
            $this->assertStringStartsWith('data:image/', $dataUri);
            $this->assertStringContainsString(';base64,', $dataUri);
        }
    }
}


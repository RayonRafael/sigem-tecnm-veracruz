<?php

namespace Tests\Feature\Reportes;

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
use App\Models\Solicitud;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use App\Support\SigemLegacyReportBuilder;
use App\Support\SigemPdfGenerator;
use App\Support\SigemReportExporter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class CamposCompletosReportesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Departamento $depto;
    protected Area $area;
    protected Material $material;
    protected Proveedor $proveedor;
    protected Receptor $receptor;

    protected function setUp(): void
    {
        parent::setUp();

        SigemReportExporter::clearCache();

        $this->admin = User::factory()->create(['name' => 'Admin Reportes']);

        $this->depto = Departamento::create([
            'nombre' => 'Sistemas y Computación',
            'encargado' => 'M.C. Laura Méndez',
        ]);

        $this->area = Area::create([
            'nombre' => 'Soporte Técnico',
            'id_departamento' => $this->depto->id_departamento,
            'responsable' => 'Ing. Carlos Ruiz',
        ]);

        $tipo = TipoMaterial::create(['nombre' => 'Computadora']);
        $marca = MarcaMaterial::create(['nombre' => 'Dell']);
        $unidad = UnidadMedida::create(['nombre' => 'Pieza']);

        $this->material = Material::create([
            'nombre' => 'Dell OptiPlex 7090',
            'modelo' => '7090-MT',
            'id_tipodematerial' => $tipo->id_tipodematerial,
            'id_marca' => $marca->id_marca,
            'id_unidad' => $unidad->id_unidad,
            'stock_actual' => 10,
            'requiere_control_individual' => 1,
        ]);

        $this->proveedor = Proveedor::create([
            'nombre_empresa' => 'Arrendadora Tec Veracruz',
        ]);

        $this->receptor = Receptor::create([
            'nombre' => 'Juan',
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => 'Gómez',
            'id_area' => $this->area->id_area,
            'departamento' => $this->depto->nombre,
        ]);
    }

    protected function crearEquipo(array $overrides = []): Inventario
    {
        $createdAt = $overrides['created_at'] ?? Carbon::create(2026, 8, 15, 14, 30, 0);
        unset($overrides['created_at']);

        $inv = Inventario::create(array_merge([
            'codigo' => 'DELL-001',
            'id_producto' => $this->material->id_producto,
            'num_serie' => 'SN-REP-001',
            'modelo' => '7090-MT',
            'tipo_propiedad' => 'Propio',
            'estado' => 'Asignado',
            'estado_registro' => 'Aprobado',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'aula' => 'L101',
            'fecha_registro' => '2026-08-15',
            'id_usuario' => $this->admin->id,
            'especificaciones' => [
                'id_receptor' => $this->receptor->id_receptor,
                'receptor_nombre' => 'Juan Pérez Gómez',
            ],
        ], $overrides));

        Inventario::where('id_inventario', $inv->id_inventario)->update([
            'created_at' => $createdAt,
        ]);

        return $inv->fresh();
    }

    public function test_nuevos_reportes_incluyen_los_6_campos_requeridos_y_formato_hora_24h(): void
    {
        $propio = $this->crearEquipo([
            'codigo' => 'DELL-PROP-01',
            'tipo_propiedad' => 'Propio',
            'fecha_registro' => '2026-08-15',
            'created_at' => Carbon::create(2026, 8, 15, 14, 30, 0),
        ]);

        $rentadoSinReceptor = $this->crearEquipo([
            'codigo' => 'HP-RENT-02',
            'num_serie' => 'SN-RENT-02',
            'tipo_propiedad' => 'Rentado',
            'id_proveedor' => $this->proveedor->id_proveedor,
            'fecha_registro' => '2026-07-10',
            'fecha_inicio_renta' => '2026-07-10',
            'fecha_fin_renta' => Carbon::now()->addDays(20)->format('Y-m-d'),
            'especificaciones' => [
                'renta_fecha_fin' => Carbon::now()->addDays(20)->format('Y-m-d'),
            ],
            'created_at' => Carbon::create(2026, 7, 10, 9, 15, 0),
        ]);

        // Mantenimiento activo para reportes de mantenimiento
        $mant = Mantenimiento::create([
            'id_inventario' => $propio->id_inventario,
            'tipo_servicio' => 'Personal Técnico',
            'tipo_mantenimiento' => 'Correctivo',
            'estado' => 'En proceso',
            'fecha_solicitud' => '2026-08-16',
            'descripcion_falla' => 'Fuente dañada',
            'nombre_tecnico' => 'Téc. Sosa',
            'id_usuario_solicita' => $this->admin->id,
        ]);
        Mantenimiento::where('id_mantenimiento', $mant->id_mantenimiento)->update([
            'created_at' => Carbon::create(2026, 8, 16, 16, 45, 0),
        ]);

        // Historial para cambios de área / historial general
        HistorialEstado::create([
            'id_inventario' => $propio->id_inventario,
            'estado_anterior' => 'Disponible',
            'estado_nuevo' => 'Asignado',
            'fecha_cambio' => Carbon::create(2026, 8, 17, 11, 20, 0),
            'motivo_cambio' => 'Cambio de área: Bodega → Soporte Técnico',
            'id_usuario' => $this->admin->id,
        ]);

        // Baja para histórico de bajas
        $baja = $this->crearEquipo([
            'codigo' => 'DELL-BAJA-03',
            'num_serie' => 'SN-BAJA-03',
            'estado' => 'Baja',
            'fecha_baja' => Carbon::create(2026, 8, 18, 13, 5, 0),
            'motivo_baja' => 'Obsolescencia',
        ]);

        SigemReportExporter::clearCache();

        // 1. Inventario General
        $invGen = SigemReportExporter::getReportData('inventario_general');
        $this->assertSame(
            ['Código', 'Equipo', 'Tipo', 'Marca', 'Modelo', 'Estado', 'Área', 'Depto', 'Aula', 'Receptor', 'Fecha', 'Hora', 'Tipo Propiedad', 'Serie'],
            $invGen['headers']
        );
        $filaPropio = collect($invGen['rows'])->firstWhere(0, 'DELL-PROP-01');
        $this->assertSame('Soporte Técnico', $filaPropio[6]);
        $this->assertSame('Sistemas y Computación', $filaPropio[7]);
        $this->assertSame('Juan Pérez Gómez', $filaPropio[9]);
        $this->assertSame('15/08/2026', $filaPropio[10]);
        $this->assertSame('14:30', $filaPropio[11]);
        $this->assertSame('Propio', $filaPropio[12]);

        // Sin receptor muestra "—" y Rentado
        $filaRentado = collect($invGen['rows'])->firstWhere(0, 'HP-RENT-02');
        $this->assertSame('—', $filaRentado[9]);
        $this->assertSame('10/07/2026', $filaRentado[10]);
        $this->assertSame('09:15', $filaRentado[11]);
        $this->assertSame('Rentado', $filaRentado[12]);

        // 2. Mantenimiento en curso
        $mantCurso = SigemReportExporter::getReportData('mantenimiento_en_curso');
        $this->assertSame(
            ['Código', 'Equipo', 'Área', 'Depto', 'Receptor', 'Fecha entrada', 'Hora', 'Tipo Propiedad'],
            $mantCurso['headers']
        );
        $this->assertSame('Soporte Técnico', $mantCurso['rows'][0][2]);
        $this->assertSame('Sistemas y Computación', $mantCurso['rows'][0][3]);
        $this->assertSame('Juan Pérez Gómez', $mantCurso['rows'][0][4]);
        $this->assertSame('16/08/2026', $mantCurso['rows'][0][5]);
        $this->assertSame('16:45', $mantCurso['rows'][0][6]);
        $this->assertSame('Propio', $mantCurso['rows'][0][7]);

        // 3. Seguimiento de Rentas (conserva Estado en índice 6 y agrega los 5 campos al final)
        $rentSeg = SigemReportExporter::getReportData('rent_seguimiento');
        $this->assertCount(1, $rentSeg['rows']);
        $this->assertSame('Soporte Técnico', $rentSeg['rows'][0][7]);
        $this->assertSame('Sistemas y Computación', $rentSeg['rows'][0][8]);
        $this->assertSame('—', $rentSeg['rows'][0][9]);
        $this->assertSame('09:15', $rentSeg['rows'][0][10]);
        $this->assertSame('Rentado', $rentSeg['rows'][0][11]);

        // 4. Histórico de bajas
        $histBajas = SigemReportExporter::getReportData('historico_bajas');
        $filaBaja = collect($histBajas['rows'])->firstWhere(0, $baja->codigo);
        $this->assertSame('Soporte Técnico', $filaBaja[2]);
        $this->assertSame('Sistemas y Computación', $filaBaja[3]);
        $this->assertSame('Juan Pérez Gómez', $filaBaja[4]);
        $this->assertSame('18/08/2026', $filaBaja[5]);
        $this->assertSame('13:05', $filaBaja[6]);
        $this->assertSame('Propio', $filaBaja[7]);

        // 5. Histórico general y cambios de área
        $histCambios = SigemReportExporter::getReportData('historico_cambios_area');
        $this->assertSame('17/08/2026', $histCambios['rows'][0][0]);
        $this->assertSame('11:20', $histCambios['rows'][0][1]);
        $this->assertSame('Soporte Técnico', $histCambios['rows'][0][3]);
        $this->assertSame('Sistemas y Computación', $histCambios['rows'][0][4]);
        $this->assertSame('Juan Pérez Gómez', $histCambios['rows'][0][5]);
        $this->assertSame('Propio', $histCambios['rows'][0][6]);
    }

    public function test_reportes_legacy_incluyen_area_depto_receptor_fecha_hora_y_tipo_propiedad(): void
    {
        $inv = $this->crearEquipo([
            'codigo' => 'LEG-001',
            'estado' => 'Dañado',
            'tipo_propiedad' => 'Rentado',
            'fecha_registro' => '2026-08-15',
            'created_at' => Carbon::create(2026, 8, 15, 8, 5, 0),
        ]);

        $mant = Mantenimiento::create([
            'id_inventario' => $inv->id_inventario,
            'tipo_servicio' => 'Servicio Social',
            'tipo_mantenimiento' => 'Preventivo',
            'estado' => 'Solicitado',
            'fecha_solicitud' => '2026-08-19',
            'descripcion_falla' => 'Limpieza interna',
            'nombre_tecnico' => 'Téc. Vega',
            'id_usuario_solicita' => $this->admin->id,
        ]);
        Mantenimiento::where('id_mantenimiento', $mant->id_mantenimiento)->update([
            'created_at' => Carbon::create(2026, 8, 19, 10, 40, 0),
        ]);

        // Equipos en mantenimiento (xlsx y csv)
        $repMantXlsx = SigemLegacyReportBuilder::generar('equipos_mantenimiento', 'xlsx');
        $this->assertSame(
            ['Código', 'Equipo', 'Categoría', 'Área', 'Departamento', 'Aula', 'Receptor', 'Fecha', 'Hora', 'Tipo Propiedad'],
            $repMantXlsx['headers']
        );
        $this->assertSame('Soporte Técnico', $repMantXlsx['rows'][0]['area']);
        $this->assertSame('Sistemas y Computación', $repMantXlsx['rows'][0]['departamento']);
        $this->assertSame('Juan Pérez Gómez', $repMantXlsx['rows'][0]['receptor']);
        $this->assertSame('19/08/2026', $repMantXlsx['rows'][0]['fecha']);
        $this->assertSame('10:40', $repMantXlsx['rows'][0]['hora']);
        $this->assertSame('Rentado', $repMantXlsx['rows'][0]['tipo_propiedad']);

        $repMantCsv = SigemLegacyReportBuilder::generar('equipos_mantenimiento', 'csv');
        $this->assertContains('Área', $repMantCsv['headers']);
        $this->assertContains('Departamento', $repMantCsv['headers']);
        $this->assertContains('Receptor', $repMantCsv['headers']);
        $this->assertContains('Hora', $repMantCsv['headers']);
        $this->assertContains('Tipo Propiedad', $repMantCsv['headers']);

        // Equipos con solicitud de baja (xlsx y csv)
        $repBajaXlsx = SigemLegacyReportBuilder::generar('equipos_baja_danados', 'xlsx');
        $this->assertSame(
            ['Código', 'Equipo', 'Categoría', 'Área', 'Departamento', 'Receptor', 'Fecha', 'Hora', 'Tipo Propiedad'],
            $repBajaXlsx['headers']
        );
        $this->assertSame('Soporte Técnico', $repBajaXlsx['rows'][0]['area']);
        $this->assertSame('Sistemas y Computación', $repBajaXlsx['rows'][0]['departamento']);
        $this->assertSame('Juan Pérez Gómez', $repBajaXlsx['rows'][0]['receptor']);
        $this->assertSame('15/08/2026', $repBajaXlsx['rows'][0]['fecha']);
        $this->assertSame('08:05', $repBajaXlsx['rows'][0]['hora']);
        $this->assertSame('Rentado', $repBajaXlsx['rows'][0]['tipo_propiedad']);
    }

    public function test_exportacion_excel_y_csv_contiene_todos_los_campos_en_encabezados_y_filas(): void
    {
        $this->crearEquipo([
            'codigo' => 'EXP-777',
            'tipo_propiedad' => 'Rentado',
            'fecha_registro' => '2026-08-20',
            'created_at' => Carbon::create(2026, 8, 20, 17, 25, 0),
        ]);

        SigemReportExporter::clearCache();

        // Export CSV
        $csvResp = SigemReportExporter::export('inventario_general', [], 'csv');
        $this->assertInstanceOf(StreamedResponse::class, $csvResp);

        ob_start();
        $csvResp->sendContent();
        $csvContenido = (string) ob_get_clean();

        $this->assertStringContainsString('Área', $csvContenido);
        $this->assertStringContainsString('Depto', $csvContenido);
        $this->assertStringContainsString('Receptor', $csvContenido);
        $this->assertStringContainsString('Fecha', $csvContenido);
        $this->assertStringContainsString('Hora', $csvContenido);
        $this->assertStringContainsString('Tipo Propiedad', $csvContenido);
        $this->assertStringContainsString('Soporte Técnico', $csvContenido);
        $this->assertStringContainsString('Sistemas y Computación', $csvContenido);
        $this->assertStringContainsString('Juan Pérez Gómez', $csvContenido);
        $this->assertStringContainsString('20/08/2026', $csvContenido);
        $this->assertStringContainsString('17:25', $csvContenido);
        $this->assertStringContainsString('Rentado', $csvContenido);

        // Export XLSX
        $xlsxResp = SigemReportExporter::export('inventario_general', [], 'xlsx');
        $this->assertInstanceOf(BinaryFileResponse::class, $xlsxResp);
        $this->assertSame(200, $xlsxResp->getStatusCode());
    }

    public function test_los_5_formatos_pdf_incluyen_campos_completos_y_generan_pdf_valido(): void
    {
        $this->actingAs($this->admin);

        $inv = $this->crearEquipo([
            'codigo' => 'PDF-900',
            'tipo_propiedad' => 'Rentado',
            'fecha_registro' => '2026-08-15',
            'fecha_baja' => Carbon::create(2026, 8, 21, 12, 0, 0),
            'motivo_baja' => 'Daño irreparable',
            'created_at' => Carbon::create(2026, 8, 15, 14, 30, 0),
        ]);

        $mant = Mantenimiento::create([
            'id_inventario' => $inv->id_inventario,
            'tipo_servicio' => 'Personal Técnico',
            'tipo_mantenimiento' => 'Correctivo',
            'estado' => 'Completado',
            'fecha_solicitud' => '2026-08-16',
            'descripcion_falla' => 'No enciende',
            'nombre_tecnico' => 'Téc. Ramírez',
            'id_usuario_solicita' => $this->admin->id,
        ]);
        Mantenimiento::where('id_mantenimiento', $mant->id_mantenimiento)->update([
            'created_at' => Carbon::create(2026, 8, 16, 15, 10, 0),
        ]);
        $mant->refresh();

        $solicitud = Solicitud::create([
            'id_usuario' => $this->admin->id,
            'id_receptor' => $this->receptor->id_receptor,
            'tipo_movimiento' => 'Asignacion Permanente',
            'fecha_solicitud' => '2026-08-18',
            'estado' => 'Autorizado',
        ]);
        Solicitud::where('id_solicitud', $solicitud->id_solicitud)->update([
            'created_at' => Carbon::create(2026, 8, 18, 10, 5, 0),
        ]);
        $solicitud->refresh();

        DetalleSolicitud::create([
            'id_solicitud' => $solicitud->id_solicitud,
            'id_producto' => $this->material->id_producto,
            'id_inventario' => $inv->id_inventario,
            'cantidad' => 1,
        ]);

        // 1. Formato Entrega
        $htmlEntrega = view('pdf.formato-entrega', SigemPdfGenerator::datosActaEntrega($solicitud))->render();
        $this->assertStringContainsString('Sistemas y Computación', $htmlEntrega);
        $this->assertStringContainsString('Soporte Técnico', $htmlEntrega);
        $this->assertStringContainsString('Juan Pérez Gómez', $htmlEntrega);
        $this->assertStringContainsString('18/08/2026', $htmlEntrega);
        $this->assertStringContainsString('10:05', $htmlEntrega);
        $this->assertStringContainsString('Rentado', $htmlEntrega);

        // 2. Formato Orden de Trabajo
        $htmlOrden = view('pdf.formato-orden-trabajo', SigemPdfGenerator::datosOrdenTrabajo($mant))->render();
        $this->assertStringContainsString('Sistemas y Computación', $htmlOrden);
        $this->assertStringContainsString('Soporte Técnico', $htmlOrden);
        $this->assertStringContainsString('Juan Pérez Gómez', $htmlOrden);
        $this->assertStringContainsString('16/08/2026', $htmlOrden);
        $this->assertStringContainsString('15:10', $htmlOrden);
        $this->assertStringContainsString('Rentado', $htmlOrden);

        // 3. Formato Lista de Verificación
        $htmlLista = view('pdf.formato-lista-verificacion', SigemPdfGenerator::datosListaVerificacion($mant))->render();
        $this->assertStringContainsString('Soporte Técnico', $htmlLista);
        $this->assertStringContainsString('Sistemas y Computación', $htmlLista);
        $this->assertStringContainsString('Juan Pérez Gómez', $htmlLista);
        $this->assertStringContainsString('15:10', $htmlLista);
        $this->assertStringContainsString('Rentado', $htmlLista);

        // 4. Formato Trámite de Baja
        $htmlBaja = view('pdf.formato-baja', SigemPdfGenerator::datosFormatosBaja([$inv]))->render();
        $this->assertStringContainsString('SISTEMAS Y COMPUTACIÓN', $htmlBaja);
        $this->assertStringContainsString('Soporte Técnico', $htmlBaja);
        $this->assertStringContainsString('Juan Pérez Gómez', $htmlBaja);
        $this->assertStringContainsString('21/08/2026', $htmlBaja);
        $this->assertStringContainsString('14:30', $htmlBaja);
        $this->assertStringContainsString('Rentado', $htmlBaja);

        // 5. Formato Cambio de Área
        $htmlCambio = view('pdf.formato-cambio-area', SigemPdfGenerator::datosCambioArea($inv))->render();
        $this->assertStringContainsString('Sistemas y Computación', $htmlCambio);
        $this->assertStringContainsString('Soporte Técnico', $htmlCambio);
        $this->assertStringContainsString('Juan Pérez Gómez', $htmlCambio);
        $this->assertStringContainsString('Rentado', $htmlCambio);
        $this->assertStringContainsString('hrs', $htmlCambio);
    }
}

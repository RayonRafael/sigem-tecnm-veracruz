<?php

namespace Tests\Feature\Reportes;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\DetalleSolicitud;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\Receptor;
use App\Models\Salon;
use App\Models\Solicitud;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use App\Support\SigemPdfGenerator;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Fase5FormatoEntregaTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Departamento $depto;
    protected Area $area;
    protected Salon $salon;
    protected MarcaMaterial $marca;
    protected Material $material;
    protected Receptor $receptor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(ServicioSocialUserSeeder::class);

        $this->adminUser = User::where('email', 'admin@tecnm.edu.mx')->first();

        $this->depto = Departamento::firstOrCreate(
            ['nombre' => 'Servicios Escolares'],
            ['encargado' => 'Jefe de Servicios Escolares']
        );

        $this->area = Area::firstOrCreate([
            'nombre' => 'Control Escolar',
            'id_departamento' => $this->depto->id_departamento,
        ]);

        $this->salon = Salon::firstOrCreate([
            'nombre' => 'Ventanilla 1',
            'area_id' => $this->area->id_area,
        ]);

        $this->marca = MarcaMaterial::firstOrCreate(['nombre' => 'TP-Link']);
        $tipo = TipoMaterial::firstOrCreate(['nombre' => 'Accesorio de Red']);
        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pieza']);

        $this->material = Material::firstOrCreate(
            ['nombre' => 'Antena USB WiFi'],
            [
                'descripcion' => 'Adaptador USB inalámbrico doble banda AC1300',
                'modelo' => 'Archer T3U',
                'id_marca' => $this->marca->id_marca,
                'id_tipodematerial' => $tipo->id_tipodematerial,
                'id_unidad' => $unidad->id_unidad,
                'stock_actual' => 4,
                'stock_minimo' => 1,
                'requiere_control_individual' => 1,
            ]
        );

        $this->receptor = Receptor::firstOrCreate([
            'nombre' => 'Julio',
            'apellido_paterno' => 'Ávila',
            'apellido_materno' => 'Hernández',
            'id_area' => $this->area->id_area,
        ]);
    }

    private function crearSolicitudEntrega(array $solOverrides = [], array $invOverrides = []): Solicitud
    {
        $inventario = Inventario::create(array_merge([
            'id_producto' => $this->material->id_producto,
            'id_usuario' => $this->adminUser->id,
            'codigo' => 'INV-ANT-001',
            'num_serie' => '224C150002032',
            'modelo' => 'AC1300',
            'especificacion_texto' => 'Antena USB WiFi AC1300 alta ganancia',
            'tipo_propiedad' => 'Propio',
            'estado' => 'Disponible',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'salon_id' => $this->salon->id,
            'fecha_registro' => now(),
            'observaciones_generales' => 'Se entrega con tapa protectora',
        ], $invOverrides));

        $solicitud = Solicitud::create(array_merge([
            'id_usuario' => $this->adminUser->id,
            'id_receptor' => $this->receptor->id_receptor,
            'tipo_movimiento' => 'Asignacion Permanente',
            'fecha_solicitud' => '2026-09-14',
            'estado' => 'Autorizado',
            'observaciones' => 'Se coloca antena en el equipo del docente.',
        ], $solOverrides));

        if (isset($solOverrides['created_at'])) {
            $solicitud->timestamps = false;
            $solicitud->created_at = $solOverrides['created_at'];
            $solicitud->save();
        }

        DetalleSolicitud::create([
            'id_solicitud' => $solicitud->id_solicitud,
            'id_producto' => $this->material->id_producto,
            'id_inventario' => $inventario->id_inventario,
            'cantidad' => 1,
        ]);

        return $solicitud->fresh();
    }

    /**
     * Extrae texto legible desde streams de contenido PDF.
     */
    private function extraerTextoPdf(string $binaryPdf): string
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

    public function test_genera_pdf_acta_entrega_con_datos_reales(): void
    {
        $this->actingAs($this->adminUser);
        $solicitud = $this->crearSolicitudEntrega();

        $pdf = SigemPdfGenerator::generarActaEntrega($solicitud);
        $this->assertNotNull($pdf);

        $output = $pdf->output();
        $this->assertNotEmpty($output);
        $this->assertStringStartsWith('%PDF', $output);
    }

    public function test_acta_entrega_muestra_bien(): void
    {
        $this->actingAs($this->adminUser);
        $solicitud = $this->crearSolicitudEntrega([], ['codigo' => 'EQ-WIFI-77']);

        $datos = SigemPdfGenerator::datosActaEntrega($solicitud);
        $html = view('pdf.formato-entrega', $datos)->render();

        $this->assertStringContainsString('Antena USB WiFi EQ-WIFI-77', $html);

        $textoPdf = $this->extraerTextoPdf(SigemPdfGenerator::generarActaEntrega($solicitud)->output());
        $this->assertStringContainsString('Antena USB WiFi', $textoPdf);
        $this->assertStringContainsString('EQ-WIFI-77', $textoPdf);
    }

    public function test_acta_entrega_muestra_unidad_organica(): void
    {
        $this->actingAs($this->adminUser);
        $solicitud = $this->crearSolicitudEntrega();

        $datos = SigemPdfGenerator::datosActaEntrega($solicitud);
        $html = view('pdf.formato-entrega', $datos)->render();

        $this->assertStringContainsString('Julio Ávila Hernández', $html);

        $textoPdf = $this->extraerTextoPdf(SigemPdfGenerator::generarActaEntrega($solicitud)->output());
        $this->assertStringContainsString('Julio', $textoPdf);
        $this->assertStringContainsString('Hern', $textoPdf);
    }

    public function test_acta_entrega_muestra_fecha_y_hora(): void
    {
        $this->actingAs($this->adminUser);
        $solicitud = $this->crearSolicitudEntrega([
            'fecha_solicitud' => '2026-09-14',
            'created_at' => '2026-09-14 11:00:00',
        ]);

        $datos = SigemPdfGenerator::datosActaEntrega($solicitud);
        $this->assertSame('14/09/2026', $datos['fecha']);
        $this->assertSame('11:00', $datos['hora']);

        $html = view('pdf.formato-entrega', $datos)->render();
        $this->assertStringContainsString('14/09/2026', $html);
        $this->assertStringContainsString('11:00', $html);

        $textoPdf = $this->extraerTextoPdf(SigemPdfGenerator::generarActaEntrega($solicitud)->output());
        $this->assertStringContainsString('14/09/2026', $textoPdf);
        $this->assertStringContainsString('11:00', $textoPdf);
    }

    public function test_acta_entrega_muestra_marca_modelo_serie(): void
    {
        $this->actingAs($this->adminUser);
        $solicitud = $this->crearSolicitudEntrega([], [
            'modelo' => 'AC1300-PRO',
            'num_serie' => 'SERIE-224C1500',
        ]);

        $datos = SigemPdfGenerator::datosActaEntrega($solicitud);
        $this->assertSame('TP-Link AC1300-PRO SERIE-224C1500', $datos['marcaModeloSerie']);

        $html = view('pdf.formato-entrega', $datos)->render();
        $this->assertStringContainsString('TP-Link AC1300-PRO SERIE-224C1500', $html);

        $textoPdf = $this->extraerTextoPdf(SigemPdfGenerator::generarActaEntrega($solicitud)->output());
        $this->assertStringContainsString('TP-Link AC1300-PRO SERIE-224C1500', $textoPdf);
    }

    public function test_acta_entrega_muestra_firmas(): void
    {
        $this->actingAs($this->adminUser);
        $solicitud = $this->crearSolicitudEntrega();

        $datos = SigemPdfGenerator::datosActaEntrega($solicitud);
        $html = view('pdf.formato-entrega', $datos)->render();

        $this->assertStringContainsString($this->adminUser->name, $html);
        $this->assertStringContainsString('Julio Ávila Hernández', $html);
        $this->assertStringContainsString('Nombre y firma de quien hace la entrega', $html);
        $this->assertStringContainsString('Nombre y firma de quien hace la recepción', $html);

        $textoPdf = $this->extraerTextoPdf(SigemPdfGenerator::generarActaEntrega($solicitud)->output());
        $this->assertStringContainsString($this->adminUser->name, $textoPdf);
    }

    public function test_acta_entrega_es_pdf_valido(): void
    {
        $this->actingAs($this->adminUser);
        $solicitud = $this->crearSolicitudEntrega();

        $datos = SigemPdfGenerator::datosActaEntrega($solicitud);
        $html = view('pdf.formato-entrega', $datos)->render();
        $this->assertStringContainsString('Calz. Miguel Ángel de Quevedo', $html);

        $pdf = SigemPdfGenerator::generarActaEntrega($solicitud);
        $output = $pdf->output();
        $this->assertStringStartsWith('%PDF', $output);

        $response = SigemPdfGenerator::descargarActaEntrega($solicitud);
        $this->assertStringContainsString(
            'acta-entrega-' . $solicitud->id_solicitud . '.pdf',
            $response->headers->get('content-disposition')
        );
    }
}


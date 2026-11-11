<?php

namespace Tests\Feature\Reportes;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\Salon;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use App\Support\SigemPdfGenerator;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Fase6FormatoBajaTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Departamento $depto;
    protected Area $area;
    protected Salon $salon;
    protected Material $material;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(ServicioSocialUserSeeder::class);

        $this->adminUser = User::where('email', 'admin@tecnm.edu.mx')->first();

        $this->depto = Departamento::firstOrCreate(
            ['nombre' => 'Centro de Cómputo'],
            ['encargado' => 'Héctor Pérez Ortiz']
        );

        $this->area = Area::firstOrCreate([
            'nombre' => 'Sala General',
            'id_departamento' => $this->depto->id_departamento,
        ]);

        $this->salon = Salon::firstOrCreate([
            'nombre' => 'S1',
            'area_id' => $this->area->id_area,
        ]);

        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'LITE-ON']);
        $tipo = TipoMaterial::firstOrCreate(['nombre' => 'Unidad Óptica']);
        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pieza']);

        $this->material = Material::firstOrCreate(
            ['nombre' => 'Lector de DVD'],
            [
                'id_marca' => $marca->id_marca,
                'id_tipodematerial' => $tipo->id_tipodematerial,
                'id_unidad' => $unidad->id_unidad,
                'stock_actual' => 2,
                'stock_minimo' => 1,
                'requiere_control_individual' => 1,
            ]
        );
    }

    private function crearEquipoBaja(array $overrides = []): Inventario
    {
        return Inventario::create(array_merge([
            'id_producto' => $this->material->id_producto,
            'id_usuario' => $this->adminUser->id,
            'codigo' => '116120081180000168023000008',
            'num_serie' => '406643510546',
            'modelo' => 'eBAU108-11',
            'especificacion_texto' => 'Lector externo USB',
            'tipo_propiedad' => 'Propio',
            'estado' => 'Dado de baja',
            'estado_registro' => 'Aprobado',
            'motivo_baja' => 'Daño irreparable',
            'observaciones_baja' => 'Lector de DVD de CPU (Irrecuperable)',
            'fecha_baja' => '2024-10-24',
            'especificaciones' => [
                'folio_sibsep' => 'SIB-2024-009',
                'posible_area_utilizacion' => 'Taller de Mantenimiento',
            ],
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'salon_id' => $this->salon->id,
            'fecha_registro' => now(),
        ], $overrides));
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

    public function test_genera_pdf_baja_individual_con_datos_reales(): void
    {
        $inventario = $this->crearEquipoBaja();

        $pdf = SigemPdfGenerator::generarFormatoBaja($inventario);
        $this->assertNotNull($pdf);

        $output = $pdf->output();
        $this->assertNotEmpty($output);
        $this->assertStringStartsWith('%PDF', $output);
        $this->assertMatchesRegularExpression('/\/Type \/Pages\b.*?\/Count 1\b/s', $output);
    }

    public function test_baja_muestra_descripcion_bien(): void
    {
        $inventario = $this->crearEquipoBaja([
            'especificacion_texto' => 'Lector externo USB negro',
        ]);

        $datos = SigemPdfGenerator::datosFormatosBaja([$inventario]);
        $html = view('pdf.formato-baja', $datos)->render();

        $this->assertStringContainsString('Lector de DVD Lector externo USB negro', $html);
        $this->assertStringContainsString('Lector de DVD de CPU (Irrecuperable)', $html);
        $this->assertStringContainsString('OBSERVACIÓN: LLENAR UN FORMATO POR CADA BIEN', $html);

        $textoPdf = $this->extraerTextoPdf(SigemPdfGenerator::generarFormatoBaja($inventario)->output());
        $this->assertStringContainsString('Lector de DVD', $textoPdf);
    }

    public function test_baja_muestra_marca_modelo_serie(): void
    {
        $inventario = $this->crearEquipoBaja([
            'modelo' => 'INSPIRON-620S',
            'num_serie' => 'CN19N1C0XQ',
        ]);

        $datos = SigemPdfGenerator::datosFormatosBaja([$inventario]);
        $html = view('pdf.formato-baja', $datos)->render();

        $this->assertStringContainsString('LITE-ON', $html);
        $this->assertStringContainsString('INSPIRON-620S', $html);
        $this->assertStringContainsString('CN19N1C0XQ', $html);

        $textoPdf = $this->extraerTextoPdf(SigemPdfGenerator::generarFormatoBaja($inventario)->output());
        $this->assertStringContainsString('LITE-ON', $textoPdf);
        $this->assertStringContainsString('INSPIRON-620S', $textoPdf);
        $this->assertStringContainsString('CN19N1C0XQ', $textoPdf);
    }

    public function test_baja_muestra_causa_marcada(): void
    {
        $inventario = $this->crearEquipoBaja([
            'motivo_baja' => 'Daño irreparable',
        ]);

        $datos = SigemPdfGenerator::datosFormatosBaja([$inventario]);
        $html = view('pdf.formato-baja', $datos)->render();

        $this->assertMatchesRegularExpression(
            '/<td class="causa-check">X<\/td>\s*<td class="causa-texto">DAÑADO E INSERVIBLE<\/td>/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<td class="causa-check">X<\/td>\s*<td class="causa-texto">DESTRUCCIÓN<\/td>/',
            $html
        );
    }

    public function test_baja_muestra_grilla_inventario(): void
    {
        $codigo = '116120081180000168023000008';
        $inventario = $this->crearEquipoBaja(['codigo' => $codigo]);

        $datos = SigemPdfGenerator::datosFormatosBaja([$inventario]);
        $html = view('pdf.formato-baja', $datos)->render();

        // Debe generar exactamente 28 celdas individuales dentro de .tabla-inventario
        $this->assertSame(1, preg_match('/<table class="tabla-inventario">(.*?)<\/table>/s', $html, $tableMatch));
        $this->assertSame(28, preg_match_all('/<td>(.*?)<\/td>/s', $tableMatch[1], $tdMatches));
        $this->assertSame('1', trim($tdMatches[1][0]));
        $this->assertSame('6', trim($tdMatches[1][2]));
        $this->assertSame('8', trim($tdMatches[1][26]));
        $this->assertSame("\u{00A0}", $tdMatches[1][27]);
    }

    public function test_baja_muestra_area_y_fecha(): void
    {
        $inventario = $this->crearEquipoBaja([
            'fecha_baja' => '2024-10-24',
        ]);

        $datos = SigemPdfGenerator::datosFormatosBaja([$inventario]);
        $html = view('pdf.formato-baja', $datos)->render();

        $this->assertStringContainsString('CENTRO DE CÓMPUTO', $html);
        $this->assertStringContainsString('24/10/2024', $html);
        $this->assertStringContainsString('Héctor Pérez Ortiz', $html);
        $this->assertStringContainsString('SIB-2024-009', $html);
        $this->assertStringContainsString('Taller de Mantenimiento', $html);

        $textoPdf = $this->extraerTextoPdf(SigemPdfGenerator::generarFormatoBaja($inventario)->output());
        $this->assertStringContainsString('24/10/2024', $textoPdf);
        $this->assertStringContainsString('TRAMITE DE BAJA', $textoPdf);
    }

    public function test_baja_lote_multipagina(): void
    {
        $inv1 = $this->crearEquipoBaja([
            'codigo' => 'INV-LOTE-001',
            'num_serie' => 'SERIE-BAJA-001',
            'motivo_baja' => 'Daño irreparable',
        ]);
        $inv2 = $this->crearEquipoBaja([
            'codigo' => 'INV-LOTE-002',
            'num_serie' => 'SERIE-BAJA-002',
            'motivo_baja' => 'Obsolescencia',
        ]);

        $pdf = SigemPdfGenerator::generarFormatosBaja([$inv1, $inv2]);
        $output = $pdf->output();

        $this->assertStringStartsWith('%PDF', $output);
        $this->assertMatchesRegularExpression('/\/Type \/Pages\b.*?\/Count 2\b/s', $output);

        $textoPdf = $this->extraerTextoPdf($output);
        $this->assertStringContainsString('SERIE-BAJA-001', $textoPdf);
        $this->assertStringContainsString('SERIE-BAJA-002', $textoPdf);

        $response = SigemPdfGenerator::descargarFormatosBajaLote([$inv1, $inv2]);
        $this->assertStringContainsString(
            'tramites-baja-lote.pdf',
            $response->headers->get('content-disposition')
        );
    }

    public function test_baja_es_pdf_valido(): void
    {
        $inventario = $this->crearEquipoBaja([
            'codigo' => '11612008-99',
        ]);

        $pdf = SigemPdfGenerator::generarFormatoBaja($inventario);
        $output = $pdf->output();
        $this->assertStringStartsWith('%PDF', $output);

        $response = SigemPdfGenerator::descargarFormatoBaja($inventario);
        $this->assertStringContainsString(
            'tramite-baja-11612008-99.pdf',
            $response->headers->get('content-disposition')
        );
    }
}


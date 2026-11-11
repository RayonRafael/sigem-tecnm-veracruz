<?php

namespace Tests\Feature\Reportes;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\Mantenimiento;
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

class Fase4FormatoListaVerificacionTest extends TestCase
{
    use RefreshDatabase;

    protected User $ssUser;
    protected Departamento $deptoCentroComputo;
    protected Departamento $deptoSolicitante;
    protected Area $area;
    protected Salon $salon;
    protected Material $material;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(ServicioSocialUserSeeder::class);

        $this->ssUser = User::where('email', 'servicio@tecnm.edu.mx')->first();

        $this->deptoCentroComputo = Departamento::firstOrCreate(
            ['nombre' => 'Centro de Cómputo'],
            ['encargado' => 'Miguel Ángel Pérez Cabada']
        );

        $this->deptoSolicitante = Departamento::firstOrCreate(
            ['nombre' => 'Departamento de Desarrollo Académico'],
            ['encargado' => 'Mtra. Laura Méndez']
        );

        $this->area = Area::firstOrCreate([
            'nombre' => 'Mantenimiento y Equipo',
            'id_departamento' => $this->deptoSolicitante->id_departamento,
        ]);

        $this->salon = Salon::firstOrCreate([
            'nombre' => 'Sala 1',
            'area_id' => $this->area->id_area,
        ]);

        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'HP']);
        $tipo = TipoMaterial::firstOrCreate(['nombre' => 'Computadora']);
        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pieza']);

        $this->material = Material::firstOrCreate(
            ['nombre' => 'ProDesk 400'],
            [
                'id_marca' => $marca->id_marca,
                'id_tipodematerial' => $tipo->id_tipodematerial,
                'id_unidad' => $unidad->id_unidad,
                'stock_actual' => 3,
                'stock_minimo' => 1,
                'requiere_control_individual' => 1,
            ]
        );
    }

    private function crearMantenimiento(array $overrides = []): Mantenimiento
    {
        $inventario = Inventario::create([
            'id_producto' => $this->material->id_producto,
            'id_usuario' => $this->ssUser->id,
            'num_serie' => 'SN-LV-' . uniqid(),
            'modelo' => 'ProDesk 400 G7',
            'tipo_propiedad' => 'Propio',
            'estado' => 'Disponible',
            'departamento_id' => $this->deptoSolicitante->id_departamento,
            'area_id' => $this->area->id_area,
            'salon_id' => $this->salon->id,
            'fecha_registro' => now(),
        ]);

        return Mantenimiento::create(array_merge([
            'id_inventario' => $inventario->id_inventario,
            'id_usuario_solicita' => $this->ssUser->id,
            'tipo_mantenimiento' => 'Correctivo',
            'tipo_servicio' => 'Servicio Social',
            'nombre_tecnico' => 'Rodrigo A Lopez C',
            'num_control_tecnico' => '21020045',
            'descripcion_falla' => 'Falla en sistema operativo',
            'descripcion_trabajo' => 'Se instalo Windows 10 y programas esenciales',
            'fecha_solicitud' => '2026-08-24',
            'fecha_fin' => '2026-08-25',
            'estado' => 'Completado',
        ], $overrides));
    }

    /**
     * Extrae texto legible ASCII/UTF-8 desde streams de contenido PDF.
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

    public function test_genera_pdf_lista_verificacion_con_datos_reales(): void
    {
        $mantenimiento = $this->crearMantenimiento();

        $pdf = SigemPdfGenerator::generarListaVerificacion($mantenimiento);
        $this->assertNotNull($pdf);

        $output = $pdf->output();
        $this->assertNotEmpty($output);
        $this->assertStringStartsWith('%PDF', $output);
    }

    public function test_lista_verificacion_muestra_hallazgo(): void
    {
        $mantenimiento = $this->crearMantenimiento([
            'descripcion_trabajo' => 'Se instalo Windows 10 y se instalaron Programas esenciales',
        ]);

        $datos = SigemPdfGenerator::datosListaVerificacion($mantenimiento);
        $html = view('pdf.formato-lista-verificacion', $datos)->render();
        $this->assertStringContainsString('Se instalo Windows 10 y se instalaron Programas esenciales', $html);

        $textoPdf = $this->extraerTextoPdf(SigemPdfGenerator::generarListaVerificacion($mantenimiento)->output());
        $this->assertStringContainsString('Windows 10', $textoPdf);

        // Fallback cuando no hay descripcion_trabajo
        $sinTrabajo = $this->crearMantenimiento([
            'descripcion_trabajo' => null,
            'observaciones' => null,
            'descripcion_falla' => 'Revision preventiva de fuente de poder',
        ]);
        $datosFallback = SigemPdfGenerator::datosListaVerificacion($sinTrabajo);
        $this->assertSame('Revision preventiva de fuente de poder', $datosFallback['hallazgo']);
    }

    public function test_lista_verificacion_muestra_atendido_segun_estado(): void
    {
        // Completado => X en SÍ
        $completado = $this->crearMantenimiento(['estado' => 'Completado']);
        $datosSi = SigemPdfGenerator::datosListaVerificacion($completado);
        $this->assertTrue($datosSi['atendidoSi']);

        $htmlSi = view('pdf.formato-lista-verificacion', $datosSi)->render();
        $this->assertStringContainsString('<td class="col-si">X</td>', $htmlSi);
        $this->assertStringContainsString('<td class="col-no"></td>', $htmlSi);

        // No Completado => X en NO
        $enProceso = $this->crearMantenimiento(['estado' => 'En proceso']);
        $datosNo = SigemPdfGenerator::datosListaVerificacion($enProceso);
        $this->assertFalse($datosNo['atendidoSi']);

        $htmlNo = view('pdf.formato-lista-verificacion', $datosNo)->render();
        $this->assertStringContainsString('<td class="col-si"></td>', $htmlNo);
        $this->assertStringContainsString('<td class="col-no">X</td>', $htmlNo);
    }

    public function test_lista_verificacion_muestra_tecnico(): void
    {
        $mantenimiento = $this->crearMantenimiento([
            'nombre_tecnico' => 'Rodrigo Alejo Lopez',
        ]);

        $datos = SigemPdfGenerator::datosListaVerificacion($mantenimiento);
        $html = view('pdf.formato-lista-verificacion', $datos)->render();

        $this->assertStringContainsString('Rodrigo Alejo Lopez', $html);
        $this->assertStringContainsString('Centro de Cómputo Realizó', $html);
        $this->assertMatchesRegularExpression('/<td>Vo\.Bo\. Recibió<\/td>\s*<td>&nbsp;<\/td>/', $html);

        $textoPdf = $this->extraerTextoPdf(SigemPdfGenerator::generarListaVerificacion($mantenimiento)->output());
        $this->assertStringContainsString('Rodrigo Alejo Lopez', $textoPdf);
    }

    public function test_lista_verificacion_es_pdf_valido(): void
    {
        $mantenimiento = $this->crearMantenimiento();

        $pdf = SigemPdfGenerator::generarListaVerificacion($mantenimiento);
        $output = $pdf->output();
        $this->assertStringStartsWith('%PDF', $output);

        $response = SigemPdfGenerator::descargarListaVerificacion($mantenimiento);
        $this->assertStringContainsString(
            'lista-verificacion-' . $mantenimiento->id_mantenimiento . '.pdf',
            $response->headers->get('content-disposition')
        );
    }

    public function test_lista_verificacion_muestra_datos_institucionales(): void
    {
        $mantenimiento = $this->crearMantenimiento([
            'fecha_solicitud' => '2026-08-20',
            'fecha_fin' => '2026-08-25',
        ]);

        $datos = SigemPdfGenerator::datosListaVerificacion($mantenimiento);
        $html = view('pdf.formato-lista-verificacion', $datos)->render();

        $this->assertStringContainsString('INSTITUTO TECNOLÓGICO DE VERACRUZ', $html);
        $this->assertStringContainsString('MANTENIMIENTO', $html);
        $this->assertStringContainsString('LISTA DE VERIFICACIÓN', $html);
        $this->assertStringContainsString('Miguel Ángel Pérez Cabada', $html);
        $this->assertStringContainsString('Subdirección de Planeación y Vinculación', $html);
        $this->assertStringContainsString('Mantenimiento y Equipo', $html);
        $this->assertStringContainsString('25/08/2026', $html);

        $textoPdf = $this->extraerTextoPdf(SigemPdfGenerator::generarListaVerificacion($mantenimiento)->output());
        $this->assertStringContainsString('LISTA DE VERIFICACI', $textoPdf);
        $this->assertStringContainsString('MANTENIMIENTO', $textoPdf);
        $this->assertStringContainsString('25/08/2026', $textoPdf);
    }
}

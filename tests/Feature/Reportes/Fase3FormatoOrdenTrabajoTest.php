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

class Fase3FormatoOrdenTrabajoTest extends TestCase
{
    use RefreshDatabase;

    protected User $ssUser;
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

        $this->ssUser = User::where('email', 'servicio@tecnm.edu.mx')->first();

        $this->depto = Departamento::firstOrCreate(
            ['nombre' => 'Departamento de Desarrollo Académico'],
            ['encargado' => 'Mtra. Laura Méndez']
        );
        $this->area = Area::firstOrCreate([
            'nombre' => 'Coordinación Académica',
            'id_departamento' => $this->depto->id_departamento,
        ]);
        $this->salon = Salon::firstOrCreate([
            'nombre' => 'Oficina 1',
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
            'num_serie' => 'SN-OT-' . uniqid(),
            'modelo' => 'ProDesk 400 G7',
            'tipo_propiedad' => 'Propio',
            'estado' => 'Disponible',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'salon_id' => $this->salon->id,
            'fecha_registro' => now(),
        ]);

        return Mantenimiento::create(array_merge([
            'id_inventario' => $inventario->id_inventario,
            'id_usuario_solicita' => $this->ssUser->id,
            'tipo_mantenimiento' => 'Correctivo',
            'tipo_servicio' => 'Servicio Social',
            'nombre_tecnico' => 'Rodrigo Alejo López',
            'num_control_tecnico' => '21020045',
            'descripcion_falla' => 'El equipo no enciende correctamente',
            'fecha_solicitud' => now()->toDateString(),
            'estado' => 'Pendiente Revision Admin',
        ], $overrides));
    }

    /**
     * Extrae texto legible UTF-8 desde los streams de contenido PDF (descartando fuentes/imágenes binarias).
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

                // DomPDF emite cadenas UTF-16BE con bytes nulos entre caracteres ASCII (\x00R\x00e...)
                // o escapados; eliminamos bytes nulos para reconstruir texto UTF-8 verificable.
                $limpio = str_replace(["\x00", "\\(", "\\)", "\\r", "\\n"], ['', '(', ')', "\r", "\n"], $chunk);
                $limpio = preg_replace('/[^\x20-\x7E\r\n]/', '', $limpio);
                $texto .= "\n" . $limpio;
            }
        }

        return $texto;
    }

    public function test_genera_pdf_orden_trabajo_con_datos_reales(): void
    {
        $mantenimiento = $this->crearMantenimiento([
            'descripcion_falla' => 'El equipo no enciende correctamente',
            'fecha_solicitud' => now()->toDateString(),
        ]);

        $pdf = SigemPdfGenerator::generarOrdenTrabajo($mantenimiento);
        $this->assertNotNull($pdf);

        $output = $pdf->output();
        $this->assertNotEmpty($output);
        $this->assertStringStartsWith('%PDF', $output);
    }

    public function test_orden_trabajo_muestra_descripcion_falla(): void
    {
        $mantenimiento = $this->crearMantenimiento([
            'descripcion_falla' => 'Reinstalar Sistema Operativo por falla critica',
        ]);

        $datos = SigemPdfGenerator::datosOrdenTrabajo($mantenimiento);
        $html = view('pdf.formato-orden-trabajo', $datos)->render();
        $this->assertStringContainsString('Reinstalar Sistema Operativo por falla critica', $html);

        $output = SigemPdfGenerator::generarOrdenTrabajo($mantenimiento)->output();
        $textoPdf = $this->extraerTextoPdf($output);
        $this->assertStringContainsString('Reinstalar', $textoPdf);
    }

    public function test_orden_trabajo_muestra_area_solicitante_y_datos_institucionales(): void
    {
        $mantenimiento = $this->crearMantenimiento();

        $datos = SigemPdfGenerator::datosOrdenTrabajo($mantenimiento);
        $html = view('pdf.formato-orden-trabajo', $datos)->render();

        $this->assertStringContainsString('Departamento de Desarrollo Académico', $html);
        $this->assertStringContainsString('Centro de Cómputo', $html);
        $this->assertStringContainsString('ISO 9001: 2015', $html);
        $this->assertStringContainsString('ISO 14001: 2015', $html);
        $this->assertStringContainsString($this->ssUser->name, $html);

        $output = SigemPdfGenerator::generarOrdenTrabajo($mantenimiento)->output();
        $textoPdf = $this->extraerTextoPdf($output);
        $this->assertStringContainsString('Desarrollo', $textoPdf);
        $this->assertStringContainsString('Centro de', $textoPdf);
    }

    public function test_orden_trabajo_fecha_formateada(): void
    {
        $mantenimiento = $this->crearMantenimiento([
            'fecha_solicitud' => '2026-08-25',
        ]);

        $datos = SigemPdfGenerator::datosOrdenTrabajo($mantenimiento);
        $html = view('pdf.formato-orden-trabajo', $datos)->render();
        $this->assertStringContainsString('25/08/2026', $html);

        $output = SigemPdfGenerator::generarOrdenTrabajo($mantenimiento)->output();
        $textoPdf = $this->extraerTextoPdf($output);
        $this->assertStringContainsString('25/08/2026', $textoPdf);
    }

    public function test_orden_trabajo_es_pdf_valido_y_descargable(): void
    {
        $mantenimiento = $this->crearMantenimiento();

        $pdf = SigemPdfGenerator::generarOrdenTrabajo($mantenimiento);
        $output = $pdf->output();
        $this->assertStringStartsWith('%PDF', $output);

        $response = SigemPdfGenerator::descargarOrdenTrabajo($mantenimiento);
        $this->assertStringContainsString(
            'orden-trabajo-' . $mantenimiento->id_mantenimiento . '.pdf',
            $response->headers->get('content-disposition')
        );
    }
}

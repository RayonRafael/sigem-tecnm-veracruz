<?php

namespace Tests\Feature\Pdf;

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

class FormatoCambioAreaTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $ssUser;
    protected Departamento $depto;
    protected Area $area;
    protected Salon $salon;
    protected Material $material;
    protected MarcaMaterial $marca;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(ServicioSocialUserSeeder::class);

        $this->adminUser = User::where('email', 'admin@tecnm.edu.mx')->first();
        $this->ssUser = User::where('email', 'servicio@tecnm.edu.mx')->first();

        $this->depto = Departamento::firstOrCreate(
            ['nombre' => 'Ingeniería Industrial'],
            ['encargado' => 'Israel de Jesús Cruz Mendoza']
        );

        $this->area = Area::firstOrCreate([
            'nombre' => 'Jefatura de Industrial',
            'id_departamento' => $this->depto->id_departamento,
        ]);

        $this->salon = Salon::firstOrCreate([
            'nombre' => 'Cubículo 1',
            'area_id' => $this->area->id_area,
        ]);

        $this->marca = MarcaMaterial::firstOrCreate(['nombre' => 'Dell']);
        $tipo = TipoMaterial::firstOrCreate(['nombre' => 'Computadora']);
        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pieza']);

        $this->material = Material::firstOrCreate(
            ['nombre' => 'OptiPlex 7040'],
            [
                'descripcion' => 'CPU OptiPlex 7040 Core i7 16GB RAM',
                'modelo' => '7040',
                'id_marca' => $this->marca->id_marca,
                'id_tipodematerial' => $tipo->id_tipodematerial,
                'id_unidad' => $unidad->id_unidad,
                'stock_actual' => 5,
                'stock_minimo' => 1,
                'requiere_control_individual' => 1,
            ]
        );
    }

    protected function crearEquipo(array $overrides = []): Inventario
    {
        return Inventario::create(array_merge([
            'id_producto' => $this->material->id_producto,
            'id_usuario' => $this->adminUser->id,
            'codigo' => 'INV-CA-' . strtoupper(substr(uniqid(), -6)),
            'num_serie' => 'BLLMCH2-' . strtoupper(substr(uniqid(), -4)),
            'modelo' => 'OptiPlex 7040',
            'especificacion_texto' => 'Windows 10, 1Tb Disco Duro, Core i7, 16Gb RAM DDR4',
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

    public function test_formato_cambio_area_se_genera_correctamente(): void
    {
        $user = User::factory()->create();
        $inventario = Inventario::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('pdf.cambio.area', $inventario));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->streamedContent());
    }

    public function test_formato_cambio_area_requiere_autenticacion(): void
    {
        $inventario = Inventario::factory()->create();

        $response = $this->get(route('pdf.cambio.area', $inventario));

        $response->assertStatus(302);
    }

    public function test_genera_pdf_cambio_area_con_datos_reales(): void
    {
        $this->actingAs($this->adminUser);
        $equipo = $this->crearEquipo();

        $pdf = SigemPdfGenerator::generarCambioArea($equipo);
        $this->assertNotNull($pdf);

        $output = $pdf->output();
        $this->assertNotEmpty($output);
        $this->assertStringStartsWith('%PDF', $output);
    }

    public function test_cambio_area_muestra_datos_equipo_origen_y_destino(): void
    {
        $this->actingAs($this->adminUser);
        $equipo = $this->crearEquipo([
            'num_serie' => 'SERIE-CAMBIO-999',
            'modelo' => 'OPTIPLEX-7040-X',
        ]);

        $datos = SigemPdfGenerator::datosCambioArea($equipo);
        $html = view('pdf.formato-cambio-area', $datos)->render();

        $this->assertStringContainsString('SERIE-CAMBIO-999', $html);
        $this->assertStringContainsString('Dell', $html);
        $this->assertStringContainsString('OPTIPLEX-7040-X', $html);
        $this->assertStringContainsString('se realizará el cambio de área del siguiente equipo', $html);
        $this->assertStringContainsString('Origen:', $html);
        $this->assertStringContainsString('Destino:', $html);
    }

    public function test_cambio_area_muestra_destinatario_y_firmas(): void
    {
        $this->actingAs($this->adminUser);
        $equipo = $this->crearEquipo();

        $datos = SigemPdfGenerator::datosCambioArea($equipo);
        $html = view('pdf.formato-cambio-area', $datos)->render();

        $this->assertStringContainsString('ISRAEL DE JESÚS CRUZ MENDOZA', $html);
        $this->assertStringContainsString('INGENIERÍA INDUSTRIAL', $html);
        $this->assertStringContainsString('ENTREGA', $html);
        $this->assertStringContainsString('RECIBE', $html);
        $this->assertStringContainsString('SOPORTE TÉCNICO', $html);
    }

    public function test_cambio_area_muestra_logos_y_footer(): void
    {
        $this->actingAs($this->adminUser);
        $equipo = $this->crearEquipo();

        $datos = SigemPdfGenerator::datosCambioArea($equipo);
        $html = view('pdf.formato-cambio-area', $datos)->render();

        $this->assertStringContainsString('Instituto Tecnológico de Veracruz', $html);
        $this->assertStringContainsString('Calz. Miguel Ángel de Quevedo', $html);
        $this->assertStringContainsString('web_veracruz@tecnm.mx', $html);
    }

    public function test_cambio_area_agrupa_componentes_de_estacion(): void
    {
        $this->actingAs($this->adminUser);
        $estacion = 'EST-COMP-101';

        $cpu = $this->crearEquipo([
            'estacion' => $estacion,
            'num_serie' => 'SN-CPU-101',
        ]);

        $teclado = $this->crearEquipo([
            'estacion' => $estacion,
            'num_serie' => 'SN-TEC-101',
        ]);

        $datos = SigemPdfGenerator::datosCambioArea($cpu);
        $html = view('pdf.formato-cambio-area', $datos)->render();

        $this->assertStringContainsString('SN-CPU-101', $html);
        $this->assertStringContainsString('SN-TEC-101', $html);
    }
}


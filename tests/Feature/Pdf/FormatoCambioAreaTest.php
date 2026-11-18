<?php

namespace Tests\Feature\Pdf;

use App\Filament\Concerns\ConModalesBajaCambio;
use App\Filament\Resources\InventarioResource;
use App\Filament\Resources\InventarioResource\Pages\ListInventarios;
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
use Filament\Facades\Filament;
use Filament\Infolists\Infolist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormatoCambioAreaTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $ssUser;
    protected Departamento $depto;
    protected Departamento $deptoDestino;
    protected Area $area;
    protected Area $areaDestino;
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

        $this->deptoDestino = Departamento::firstOrCreate(
            ['nombre' => 'Metal-Mecánica'],
            ['encargado' => 'Oscar Omar Montalvo Pérez']
        );

        $this->area = Area::firstOrCreate([
            'nombre' => 'Jefatura de Industrial',
            'id_departamento' => $this->depto->id_departamento,
        ]);

        $this->areaDestino = Area::firstOrCreate([
            'nombre' => 'Laboratorio de Metal-Mecánica',
            'id_departamento' => $this->deptoDestino->id_departamento,
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
            'procesador' => 'Intel Core i7-6700',
            'ram' => '16 GB DDR4',
            'disco' => '1 TB HDD',
            'sistema_operativo' => 'Windows 10 Pro',
            'observaciones_generales' => 'Equipo en excelentes condiciones operativas',
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

    public function test_infolist_muestra_jefe_de_departamento_y_campos_completos(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $equipo = $this->crearEquipo();

        $component = Livewire::test(ListInventarios::class)
            ->mountTableAction('view', (string) $equipo->getKey())
            ->assertSee('Jefe de Departamento')
            ->assertSee('Israel de Jesús Cruz Mendoza')
            ->assertSee('Descripción')
            ->assertSee('Fecha de registro');

        $this->assertNotNull($component);
    }

    public function test_cambio_area_usa_slideover_y_tiene_campo_jefe_destino(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $schema = ConModalesBajaCambio::getModalCambioAreaFormSchema(true);
        $nombresCampos = collect($schema)
            ->flatMap(fn ($component) => method_exists($component, 'getChildComponents') && ! empty($component->getChildComponents())
                ? $component->getChildComponents()
                : [$component])
            ->map(fn ($c) => method_exists($c, 'getName') ? $c->getName() : null)
            ->filter()
            ->values()
            ->all();

        $this->assertContains('departamento_actual', $nombresCampos);
        $this->assertContains('area_actual', $nombresCampos);
        $this->assertContains('departamento_id', $nombresCampos);
        $this->assertContains('area_id', $nombresCampos);
        $this->assertContains('aula', $nombresCampos);
        $this->assertContains('jefe_departamento', $nombresCampos);
        $this->assertContains('motivo', $nombresCampos);

        $equipo = $this->crearEquipo();
        $origenDepto = $equipo->departamento;
        $origenArea = $equipo->area;

        $equipo->cambiarAreaDirecto(
            $this->deptoDestino->id_departamento,
            $this->areaDestino->id_area,
            'X204',
            null,
            'Reubicación al laboratorio de Metal-Mecánica',
            $this->adminUser->id
        );

        ConModalesBajaCambio::guardarMetadatosCambioArea($equipo, [
            'departamento_id' => $this->deptoDestino->id_departamento,
            'area_id' => $this->areaDestino->id_area,
            'aula' => 'X204',
            'jefe_departamento' => 'Ing. Oscar Montalvo',
            'motivo' => 'Reubicación al laboratorio de Metal-Mecánica',
        ], $origenDepto, $origenArea);

        $equipoFresh = $equipo->fresh();
        $this->assertSame('Ing. Oscar Montalvo', $equipoFresh->especificaciones['jefe_destino']);
        $this->assertSame('Ingeniería Industrial', $equipoFresh->especificaciones['cambio_origen_depto']);
        $this->assertSame('Israel de Jesús Cruz Mendoza', $equipoFresh->especificaciones['cambio_jefe_origen']);
    }

    public function test_checklist_cambio_area_tiene_9_opciones_y_5_por_defecto(): void
    {
        $opciones = ConModalesBajaCambio::getCamposPdfCambioAreaOptions();
        $defaults = ConModalesBajaCambio::getCamposPdfCambioAreaDefaults();

        $this->assertCount(9, $opciones);
        $this->assertArrayHasKey('num_serie', $opciones);
        $this->assertArrayHasKey('origen', $opciones);
        $this->assertArrayHasKey('destino', $opciones);
        $this->assertArrayHasKey('nueva_aula', $opciones);
        $this->assertArrayHasKey('motivo', $opciones);
        $this->assertArrayHasKey('jefe_origen', $opciones);
        $this->assertArrayHasKey('jefe_destino', $opciones);
        $this->assertArrayHasKey('descripcion_extendida', $opciones);
        $this->assertArrayHasKey('fecha_cambio', $opciones);

        $this->assertSame(
            ['num_serie', 'origen', 'destino', 'jefe_destino', 'fecha_cambio'],
            $defaults
        );
    }

    public function test_pdf_cambio_area_respeta_cualquier_combinacion_del_checklist(): void
    {
        $this->actingAs($this->adminUser);
        $equipo = $this->crearEquipo([
            'num_serie' => 'SN-CHECKLIST-777',
            'especificaciones' => [
                'cambio_origen_depto' => 'Ingeniería Industrial',
                'cambio_origen_area' => 'Jefatura de Industrial',
                'cambio_jefe_origen' => 'Israel de Jesús Cruz Mendoza',
                'jefe_destino' => 'Oscar Omar Montalvo Pérez',
                'cambio_nueva_aula' => 'U006',
                'cambio_motivo' => 'Renovación de equipo docente',
            ],
        ]);

        // Caso 1: Ningún campo opcional marcado (solo elementos fijos)
        $datosVacio = SigemPdfGenerator::datosCambioArea($equipo, null, null, null, []);
        $htmlVacio = view('pdf.formato-cambio-area', $datosVacio)->render();

        // Fijos siempre presentes
        $this->assertStringContainsString('Instituto Tecnológico de Veracruz', $htmlVacio);
        $this->assertStringContainsString('OptiPlex 7040', $htmlVacio);
        $this->assertStringContainsString('ENTREGA', $htmlVacio);
        $this->assertStringContainsString('RECIBE', $htmlVacio);

        // Opcionales ocultos cuando están desmarcados
        $this->assertStringNotContainsString('SN-CHECKLIST-777', $htmlVacio);
        $this->assertStringNotContainsString('<span class="od-label">Origen:</span>', $htmlVacio);
        $this->assertStringNotContainsString('<span class="od-label">Destino:</span>', $htmlVacio);
        $this->assertStringNotContainsString('Nueva aula:', $htmlVacio);
        $this->assertStringNotContainsString('Motivo del cambio:', $htmlVacio);
        $this->assertStringNotContainsString('Jefe de Depto. origen:', $htmlVacio);
        $this->assertStringNotContainsString('CPU: Intel Core i7-6700', $htmlVacio);

        // Caso 2: Todos los 9 campos opcionales marcados
        $todos = array_keys(ConModalesBajaCambio::getCamposPdfCambioAreaOptions());
        $datosTodos = SigemPdfGenerator::datosCambioArea($equipo, null, null, null, $todos);
        $htmlTodos = view('pdf.formato-cambio-area', $datosTodos)->render();

        $this->assertStringContainsString('SN-CHECKLIST-777', $htmlTodos);
        $this->assertStringContainsString('Origen:', $htmlTodos);
        $this->assertStringContainsString('Destino:', $htmlTodos);
        $this->assertStringContainsString('Nueva aula:', $htmlTodos);
        $this->assertStringContainsString('U006', $htmlTodos);
        $this->assertStringContainsString('Motivo del cambio:', $htmlTodos);
        $this->assertStringContainsString('Renovación de equipo docente', $htmlTodos);
        $this->assertStringContainsString('Jefe de Depto. origen:', $htmlTodos);
        $this->assertStringContainsString('Israel de Jesús Cruz Mendoza', $htmlTodos);
        $this->assertStringContainsString('OSCAR OMAR MONTALVO PÉREZ', $htmlTodos);
        $this->assertStringContainsString('CPU: Intel Core i7-6700', $htmlTodos);

        // Verificar que la ruta HTTP acepta campos_pdf y genera PDF válido
        $resp = $this->get(route('pdf.cambio.area', [
            'inventario' => $equipo->getKey(),
            'campos_pdf' => ['motivo', 'nueva_aula'],
        ]));
        $resp->assertOk();
        $this->assertStringStartsWith('%PDF', $resp->streamedContent());
    }
}

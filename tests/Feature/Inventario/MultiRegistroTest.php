<?php

namespace Tests\Feature\Inventario;

use App\Filament\Resources\InventarioResource;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiRegistroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(ServicioSocialUserSeeder::class);
    }

    private function getAdminUser(): User
    {
        return User::where('email', 'admin@tecnm.edu.mx')->first();
    }

    public function test_crear_computadora_genera_4_registros_con_misma_estacion(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser());

        $depto = Departamento::firstOrCreate(['nombre' => 'Depto Sistemas']);
        $area = Area::firstOrCreate(['nombre' => 'Laboratorio A', 'id_departamento' => $depto->id_departamento]);

        $data = [
            'tipo_equipo' => 'pc',
            'estacion' => 'EST-101',
            'departamento_id' => $depto->id_departamento,
            'area_id' => $area->id_area,
            'num_serie' => 'CPU-SN-101',
            'modelo' => 'OptiPlex 7080',
            'observaciones_generales' => 'Equipo de laboratorio',
            'fecha_registro' => now(),
            'especificaciones' => [
                'aula' => 'Lab-1',
                'monitor_sn' => 'MON-SN-101',
                'monitor_marca' => 'Dell',
                'monitor_modelo' => 'P2419H',
                'teclado_sn' => 'TEC-SN-101',
                'teclado_marca' => 'Dell',
                'teclado_modelo' => 'KB216',
                'mouse_sn' => 'MOU-SN-101',
                'mouse_marca' => 'Dell',
                'mouse_modelo' => 'MS116',
                'cpu_marca' => 'Dell',
            ],
        ];

        $cpu = InventarioResource::registrarMultiplesEquipos($data);

        $this->assertNotNull($cpu);
        $this->assertEquals('CPU', $cpu->subtipo);

        // Verificar que se crearon los 4 registros en la base de datos para la estación
        $equiposEstacion = Inventario::where('estacion', 'EST-101')->get();
        $this->assertCount(4, $equiposEstacion);

        $subtipos = $equiposEstacion->pluck('subtipo')->sort()->values()->all();
        $this->assertEquals(['CPU', 'Monitor', 'Mouse', 'Teclado'], $subtipos);
    }

    public function test_crear_aio_genera_3_registros(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser());

        $depto = Departamento::firstOrCreate(['nombre' => 'Depto Sistemas']);
        $area = Area::firstOrCreate(['nombre' => 'Laboratorio A', 'id_departamento' => $depto->id_departamento]);

        $data = [
            'tipo_equipo' => 'aio',
            'estacion' => 'EST-AIO-202',
            'departamento_id' => $depto->id_departamento,
            'area_id' => $area->id_area,
            'num_serie' => 'AIO-SN-202',
            'modelo' => 'Inspiron 24',
            'fecha_registro' => now(),
            'especificaciones' => [
                'teclado_sn' => 'TEC-AIO-202',
                'teclado_marca' => 'HP',
                'teclado_modelo' => 'Slim',
                'mouse_sn' => 'MOU-AIO-202',
                'mouse_marca' => 'HP',
                'mouse_modelo' => 'Optical',
                'aio_marca' => 'HP',
            ],
        ];

        $aio = InventarioResource::registrarMultiplesEquipos($data);

        $this->assertNotNull($aio);
        $this->assertEquals('All in One', $aio->subtipo);

        $equiposEstacion = Inventario::where('estacion', 'EST-AIO-202')->get();
        $this->assertCount(3, $equiposEstacion);

        $subtipos = $equiposEstacion->pluck('subtipo')->sort()->values()->all();
        $this->assertEquals(['All in One', 'Mouse', 'Teclado'], $subtipos);
    }

    public function test_crear_equipo_individual_genera_1_registro(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser());

        $data = [
            'tipo_equipo' => 'mon',
            'num_serie' => 'MON-SOLO-303',
            'modelo' => 'UltraSharp 24',
            'fecha_registro' => now(),
            'especificaciones' => [
                'marca' => 'Dell',
            ],
        ];

        $monitor = InventarioResource::registrarMultiplesEquipos($data);

        $this->assertNotNull($monitor);
        $this->assertEquals('Monitor', $monitor->subtipo);

        $encontrados = Inventario::where('num_serie', 'MON-SOLO-303')->get();
        $this->assertCount(1, $encontrados);
    }

    public function test_no_tiene_excluye_componente_en_pc(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser());

        $data = [
            'tipo_equipo' => 'pc',
            'estacion' => 'EST-NO-MOUSE-404',
            'num_serie' => 'CPU-SN-404',
            'modelo' => 'OptiPlex 3080',
            'fecha_registro' => now(),
            'especificaciones' => [
                'monitor_sn' => 'MON-SN-404',
                'monitor_marca' => 'Dell',
                'teclado_sn' => 'TEC-SN-404',
                'teclado_marca' => 'Dell',
                'no_tiene_mouse' => true,
                'mouse_sn' => 'MOU-SN-IGNORADO',
                'cpu_marca' => 'Dell',
            ],
        ];

        InventarioResource::registrarMultiplesEquipos($data);

        $equiposEstacion = Inventario::where('estacion', 'EST-NO-MOUSE-404')->get();
        $this->assertCount(3, $equiposEstacion);

        $subtipos = $equiposEstacion->pluck('subtipo')->all();
        $this->assertNotContains('Mouse', $subtipos);
        $this->assertContains('CPU', $subtipos);
        $this->assertContains('Monitor', $subtipos);
        $this->assertContains('Teclado', $subtipos);
    }

    public function test_estacion_compartida_en_todos_los_componentes(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser());

        $data = [
            'tipo_equipo' => 'pc',
            'estacion' => 'EST-COMP-505',
            'num_serie' => 'CPU-SN-505',
            'fecha_registro' => now(),
            'especificaciones' => [
                'monitor_sn' => 'MON-SN-505',
                'teclado_sn' => 'TEC-SN-505',
                'mouse_sn' => 'MOU-SN-505',
            ],
        ];

        InventarioResource::registrarMultiplesEquipos($data);

        $registros = Inventario::where('estacion', 'EST-COMP-505')->get();
        $this->assertCount(4, $registros);

        foreach ($registros as $reg) {
            $this->assertEquals('EST-COMP-505', $reg->estacion);
        }
    }

    public function test_codigo_auto_generado_unico_por_registro(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser());

        $data = [
            'tipo_equipo' => 'pc',
            'estacion' => 'EST-COD-606',
            'num_serie' => 'CPU-COD-606',
            'modelo' => 'EliteDesk',
            'fecha_registro' => now(),
            'especificaciones' => [
                'cpu_marca' => 'HP',
                'monitor_sn' => 'MON-COD-606',
                'monitor_marca' => 'HP',
                'monitor_modelo' => 'V24i',
            ],
        ];

        InventarioResource::registrarMultiplesEquipos($data);

        $registros = Inventario::where('estacion', 'EST-COD-606')->get();
        $this->assertGreaterThanOrEqual(2, $registros->count());

        $codigos = [];
        foreach ($registros as $reg) {
            $this->assertNotEmpty($reg->codigo);
            $this->assertMatchesRegularExpression('/^[A-Z0-9]+-[A-Z0-9]+-\d{3}$/', $reg->codigo);
            $codigos[] = $reg->codigo;
        }

        // Códigos únicos sin duplicados
        $this->assertCount(count($codigos), array_unique($codigos));
    }

    public function test_material_auto_match_vincula_automaticamente_via_observer(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser());

        // Al crear inventario sin id_producto, el observer crea/busca material
        $inv = Inventario::create([
            'subtipo' => 'Monitor',
            'num_serie' => 'SN-MATCH-707',
            'modelo' => 'AutoMatchModel',
            'tipo_propiedad' => 'Propio',
            'estado' => 'Disponible',
            'fecha_registro' => now(),
            'id_usuario' => $this->getAdminUser()->id,
            'especificaciones' => [
                'marca' => 'Samsung Match',
            ],
        ]);

        $this->assertNotNull($inv->id_producto);
        $this->assertNotNull($inv->material);
        $this->assertEquals('Samsung Match', $inv->material->marca?->nombre);
    }
}

<?php

namespace Tests\Feature\Inventario;

use App\Filament\Resources\InventarioResource;
use App\Filament\Resources\InventarioResource\Pages\ListInventarios;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\HistorialEstado;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\Salon;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HistorialUbicacionTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Departamento $depto1;
    protected Departamento $depto2;
    protected Area $area1;
    protected Area $area2;
    protected Salon $salon1;
    protected Salon $salon2;
    protected Material $material;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(ServicioSocialUserSeeder::class);

        $this->adminUser = User::where('email', 'admin@tecnm.edu.mx')->first();

        $this->depto1 = Departamento::firstOrCreate(['nombre' => 'Sistemas y Computación']);
        $this->depto2 = Departamento::firstOrCreate(['nombre' => 'Ciencias Básicas']);

        $this->area1 = Area::firstOrCreate(['nombre' => 'Laboratorio de Redes', 'id_departamento' => $this->depto1->id_departamento]);
        $this->area2 = Area::firstOrCreate(['nombre' => 'Centro de Cómputo', 'id_departamento' => $this->depto2->id_departamento]);

        $this->salon1 = Salon::firstOrCreate(['nombre' => 'E103', 'area_id' => $this->area1->id_area]);
        $this->salon2 = Salon::firstOrCreate(['nombre' => 'E201', 'area_id' => $this->area2->id_area]);

        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'Dell']);
        $tipo = TipoMaterial::firstOrCreate(['nombre' => 'Equipo de Cómputo']);
        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pieza']);

        $this->material = Material::firstOrCreate(
            ['nombre' => 'Dell OptiPlex 7090'],
            [
                'id_marca' => $marca->id_marca,
                'id_tipodematerial' => $tipo->id_tipodematerial,
                'id_unidad' => $unidad->id_unidad,
                'stock_actual' => 10,
                'stock_minimo' => 1,
                'requiere_control_individual' => 1,
            ]
        );
    }

    private function crearEquipoBase(array $extra = []): Inventario
    {
        return Inventario::create(array_merge([
            'id_producto' => $this->material->id_producto,
            'id_usuario' => $this->adminUser->id,
            'num_serie' => 'SN-HIST-' . uniqid(),
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'departamento_id' => $this->depto1->id_departamento,
            'area_id' => $this->area1->id_area,
            'salon_id' => $this->salon1->id,
            'estacion' => 'EST-01',
            'fecha_registro' => now(),
        ], $extra));
    }

    public function test_crear_equipo_registra_ubicacion_inicial(): void
    {
        $this->actingAs($this->adminUser);

        $equipo = $this->crearEquipoBase();

        $historial = HistorialEstado::where('id_inventario', $equipo->id_inventario)->get();

        $this->assertCount(1, $historial);
        $registro = $historial->first();

        $this->assertEquals('Alta inicial en el inventario', $registro->motivo_cambio);
        $this->assertNull($registro->estado_anterior);
        $this->assertEquals('Disponible', $registro->estado_nuevo);
        $this->assertStringContainsString('Sistemas y Computación', $registro->ubicacion_fisica);
        $this->assertStringContainsString('Laboratorio de Redes', $registro->ubicacion_fisica);
        $this->assertStringContainsString('Aula E103', $registro->ubicacion_fisica);
        $this->assertStringContainsString('Estación EST-01', $registro->ubicacion_fisica);
    }

    public function test_cambiar_departamento_registra_historial(): void
    {
        $this->actingAs($this->adminUser);
        $equipo = $this->crearEquipoBase();

        $equipo->departamento_id = $this->depto2->id_departamento;
        $equipo->save();

        $historial = HistorialEstado::where('id_inventario', $equipo->id_inventario)->get();
        $this->assertCount(2, $historial);

        $cambio = $historial->last();
        $this->assertStringContainsString('Cambio de ubicación', $cambio->motivo_cambio);
        $this->assertStringContainsString('Ciencias Básicas', $cambio->ubicacion_fisica);
    }

    public function test_cambiar_area_registra_historial(): void
    {
        $this->actingAs($this->adminUser);
        $equipo = $this->crearEquipoBase();

        $equipo->area_id = $this->area2->id_area;
        $equipo->save();

        $historial = HistorialEstado::where('id_inventario', $equipo->id_inventario)->get();
        $this->assertCount(2, $historial);

        $cambio = $historial->last();
        $this->assertStringContainsString('Cambio de ubicación', $cambio->motivo_cambio);
        $this->assertStringContainsString('Centro de Cómputo', $cambio->ubicacion_fisica);
    }

    public function test_cambiar_aula_registra_historial(): void
    {
        $this->actingAs($this->adminUser);
        $equipo = $this->crearEquipoBase();

        $equipo->salon_id = $this->salon2->id;
        $equipo->save();

        $historial = HistorialEstado::where('id_inventario', $equipo->id_inventario)->get();
        $this->assertCount(2, $historial);

        $cambio = $historial->last();
        $this->assertStringContainsString('Cambio de ubicación', $cambio->motivo_cambio);
        $this->assertStringContainsString('Aula E201', $cambio->ubicacion_fisica);
    }

    public function test_cambiar_estacion_registra_historial(): void
    {
        $this->actingAs($this->adminUser);
        $equipo = $this->crearEquipoBase();

        $equipo->estacion = 'EST-99';
        $equipo->save();

        $historial = HistorialEstado::where('id_inventario', $equipo->id_inventario)->get();
        $this->assertCount(2, $historial);

        $cambio = $historial->last();
        $this->assertStringContainsString('Cambio de ubicación', $cambio->motivo_cambio);
        $this->assertStringContainsString('Estación EST-99', $cambio->ubicacion_fisica);
    }

    public function test_historial_ubicacion_incluye_usuario(): void
    {
        $this->actingAs($this->adminUser);
        $equipo = $this->crearEquipoBase();

        $equipo->estacion = 'EST-DOC-02';
        $equipo->save();

        $ultimo = HistorialEstado::where('id_inventario', $equipo->id_inventario)->latest('id_historial')->first();
        $this->assertEquals($this->adminUser->id, $ultimo->id_usuario);
        $this->assertEquals($this->adminUser->name, $ultimo->usuario->name);
    }

    public function test_historial_ubicacion_motivo_legible(): void
    {
        $this->actingAs($this->adminUser);
        $equipo = $this->crearEquipoBase();

        $equipo->salon_id = $this->salon2->id;
        $equipo->save();

        $ultimo = HistorialEstado::where('id_inventario', $equipo->id_inventario)->latest('id_historial')->first();

        // Formato esperado: "Cambio de ubicación: {anterior} → {nueva}"
        $this->assertMatchesRegularExpression('/^Cambio de ubicación: .+ → .+$/', $ultimo->motivo_cambio);
        $this->assertStringContainsString('Aula E103', $ultimo->motivo_cambio);
        $this->assertStringContainsString('Aula E201', $ultimo->motivo_cambio);
    }

    public function test_editar_sin_cambio_ubicacion_no_registra(): void
    {
        $this->actingAs($this->adminUser);
        $equipo = $this->crearEquipoBase();

        // Conteo inicial tras la creación
        $conteoInicial = HistorialEstado::where('id_inventario', $equipo->id_inventario)->count();
        $this->assertEquals(1, $conteoInicial);

        // Modificar solo campos de red / observaciones
        $equipo->ip_address = '192.168.10.50';
        $equipo->mac_address = '00:1B:44:11:3A:B7';
        $equipo->observaciones_generales = 'Mantenimiento preventivo de software realizado';
        $equipo->save();

        // Conteo debe mantenerse idéntico sin registrar nuevo movimiento
        $conteoFinal = HistorialEstado::where('id_inventario', $equipo->id_inventario)->count();
        $this->assertEquals(1, $conteoFinal);
    }

    public function test_historial_muestra_en_slideover(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->adminUser);

        $equipo = $this->crearEquipoBase();

        // Cambiar ubicación para tener 2 eventos
        $equipo->estacion = 'EST-VIEW-01';
        $equipo->save();

        $eventos = HistorialEstado::where('id_inventario', $equipo->id_inventario)->get();
        $this->assertCount(2, $eventos);

        Livewire::test(ListInventarios::class)
            ->mountTableAction('view', $equipo)
            ->assertTableActionMounted('view')
            ->assertSee('Historial')
            ->assertSee('Alta inicial en el inventario')
            ->assertSee('Cambio de ubicación')
            ->assertSee('EST-VIEW-01');
    }
}


<?php

namespace Tests\Feature\Inventario;

use App\Filament\Resources\InventarioResource;
use App\Filament\Resources\InventarioResource\Pages\ListInventarios;
use App\Filament\ServicioSocial\Resources\InventarioResource as SSInventarioResource;
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

class CambioAreaInventarioTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $ssUser;
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
        $this->ssUser = User::where('email', 'servicio@tecnm.edu.mx')->first();

        $this->depto1 = Departamento::firstOrCreate(['nombre' => 'Depto Sistemas']);
        $this->depto2 = Departamento::firstOrCreate(['nombre' => 'Depto Industrial']);

        $this->area1 = Area::firstOrCreate(['nombre' => 'Laboratorio Cómputo', 'id_departamento' => $this->depto1->id_departamento]);
        $this->area2 = Area::firstOrCreate(['nombre' => 'Planta Piloto', 'id_departamento' => $this->depto2->id_departamento]);

        $this->salon1 = Salon::firstOrCreate(['nombre' => 'LC1', 'area_id' => $this->area1->id_area]);
        $this->salon2 = Salon::firstOrCreate(['nombre' => 'PP2', 'area_id' => $this->area2->id_area]);

        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'Cisco']);
        $tipo = TipoMaterial::firstOrCreate(['nombre' => 'Switch']);
        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pieza']);

        $this->material = Material::firstOrCreate(
            ['nombre' => 'Cisco Catalyst 2960'],
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

    private function crearEquipo(string $estado = 'Disponible'): Inventario
    {
        return Inventario::create([
            'id_producto' => $this->material->id_producto,
            'id_usuario' => $this->adminUser->id,
            'num_serie' => 'SN-CAMBIO-' . uniqid(),
            'estado' => $estado,
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'departamento_id' => $this->depto1->id_departamento,
            'area_id' => $this->area1->id_area,
            'salon_id' => $this->salon1->id,
            'estacion' => 'EST-01',
            'fecha_registro' => now(),
        ]);
    }

    public function test_admin_cambiar_area_directo(): void
    {
        $this->actingAs($this->adminUser);
        $equipo = $this->crearEquipo();

        $equipo->cambiarAreaDirecto(
            $this->depto2->id_departamento,
            $this->area2->id_area,
            $this->salon2->id,
            'EST-02',
            'Reubicación de switch'
        );

        $equipoFresh = $equipo->fresh();
        $this->assertEquals($this->depto2->id_departamento, $equipoFresh->departamento_id);
        $this->assertEquals($this->area2->id_area, $equipoFresh->area_id);
        $this->assertEquals($this->salon2->id, $equipoFresh->salon_id);
        $this->assertEquals('EST-02', $equipoFresh->estacion);
        $this->assertEquals('Disponible', $equipoFresh->estado);
        $this->assertNotNull($equipoFresh->fecha_cambio_area);
    }

    public function test_admin_cambiar_area_registra_historial(): void
    {
        $this->actingAs($this->adminUser);
        $equipo = $this->crearEquipo();

        $equipo->cambiarAreaDirecto(
            $this->depto2->id_departamento,
            $this->area2->id_area,
            $this->salon2->id,
            'EST-02',
            'Movimiento estratégico'
        );

        $historial = HistorialEstado::where('id_inventario', $equipo->id_inventario)
            ->where('motivo_cambio', 'like', 'Cambio de área:%')
            ->first();

        $this->assertNotNull($historial);
        $this->assertEquals('Disponible', $historial->estado_anterior);
        $this->assertEquals('Disponible', $historial->estado_nuevo);
        $this->assertStringContainsString('Planta Piloto', $historial->ubicacion_fisica);
        $this->assertEquals($this->adminUser->id, $historial->id_usuario);
    }

    public function test_ss_solicitar_cambio_area(): void
    {
        $this->actingAs($this->ssUser);
        $equipo = $this->crearEquipo('Asignado');

        $equipo->solicitarCambioArea(
            $this->depto2->id_departamento,
            $this->area2->id_area,
            $this->salon2->id,
            'EST-99',
            'Falta de espacio en laboratorio'
        );

        $equipoFresh = $equipo->fresh();
        $this->assertEquals('Cambio de área', $equipoFresh->estado);
        $this->assertEquals('Asignado', $equipoFresh->estado_previo_cambio);
    }

    public function test_ss_solicitar_guarda_ubicacion_solicitada(): void
    {
        $this->actingAs($this->ssUser);
        $equipo = $this->crearEquipo();

        $equipo->solicitarCambioArea(
            $this->depto2->id_departamento,
            $this->area2->id_area,
            $this->salon2->id,
            'EST-99',
            'Motivo de prueba SS'
        );

        $equipoFresh = $equipo->fresh();
        $this->assertIsArray($equipoFresh->ubicacion_solicitada);
        $this->assertEquals($this->depto2->id_departamento, $equipoFresh->ubicacion_solicitada['departamento_id']);
        $this->assertEquals($this->area2->id_area, $equipoFresh->ubicacion_solicitada['area_id']);
        $this->assertEquals($this->salon2->id, $equipoFresh->ubicacion_solicitada['salon_id']);
        $this->assertEquals('EST-99', $equipoFresh->ubicacion_solicitada['estacion']);
        $this->assertEquals('Motivo de prueba SS', $equipoFresh->ubicacion_solicitada['motivo']);
    }

    public function test_admin_aprueba_cambio(): void
    {
        $equipo = $this->crearEquipo('Asignado');
        $equipo->solicitarCambioArea(
            $this->depto2->id_departamento,
            $this->area2->id_area,
            $this->salon2->id,
            'EST-FINAL',
            'Petición formal'
        );

        $this->actingAs($this->adminUser);
        $equipo->aprobarCambioArea($this->adminUser->id);

        $equipoFresh = $equipo->fresh();
        $this->assertEquals('Asignado', $equipoFresh->estado);
        $this->assertEquals($this->depto2->id_departamento, $equipoFresh->departamento_id);
        $this->assertEquals($this->area2->id_area, $equipoFresh->area_id);
        $this->assertEquals($this->salon2->id, $equipoFresh->salon_id);
        $this->assertEquals('EST-FINAL', $equipoFresh->estacion);
        $this->assertNull($equipoFresh->estado_previo_cambio);
        $this->assertNull($equipoFresh->ubicacion_solicitada);
    }

    public function test_admin_rechaza_cambio(): void
    {
        $equipo = $this->crearEquipo('Disponible');
        $equipo->solicitarCambioArea(
            $this->depto2->id_departamento,
            $this->area2->id_area,
            $this->salon2->id,
            'EST-RECH',
            'Petición que se rechazará'
        );

        $this->actingAs($this->adminUser);
        $equipo->rechazarCambioArea('No autorizado por falta de cableado', $this->adminUser->id);

        $equipoFresh = $equipo->fresh();
        $this->assertEquals('Disponible', $equipoFresh->estado);
        $this->assertEquals($this->depto1->id_departamento, $equipoFresh->departamento_id);
        $this->assertEquals($this->area1->id_area, $equipoFresh->area_id);
        $this->assertNull($equipoFresh->estado_previo_cambio);
        $this->assertNull($equipoFresh->ubicacion_solicitada);
    }

    public function test_aprobar_cambio_registra_historial(): void
    {
        $equipo = $this->crearEquipo();
        $equipo->solicitarCambioArea(
            $this->depto2->id_departamento,
            $this->area2->id_area,
            $this->salon2->id,
            'EST-APROB',
            'Cambio aprobado'
        );

        $equipo->aprobarCambioArea($this->adminUser->id);

        $historial = HistorialEstado::where('id_inventario', $equipo->id_inventario)
            ->where('motivo_cambio', 'like', 'Cambio de área aprobado:%')
            ->first();

        $this->assertNotNull($historial);
        $this->assertEquals('Cambio de área', $historial->estado_anterior);
        $this->assertEquals('Disponible', $historial->estado_nuevo);
        $this->assertStringContainsString('Planta Piloto', $historial->motivo_cambio);
    }

    public function test_rechazar_cambio_registra_historial(): void
    {
        $equipo = $this->crearEquipo('Asignado');
        $equipo->solicitarCambioArea(
            $this->depto2->id_departamento,
            $this->area2->id_area,
            $this->salon2->id,
            'EST-RECH2',
            'Petición'
        );

        $equipo->rechazarCambioArea('Sin cupo', $this->adminUser->id);

        $historial = HistorialEstado::where('id_inventario', $equipo->id_inventario)
            ->where('motivo_cambio', 'like', 'Cambio de área rechazado%')
            ->first();

        $this->assertNotNull($historial);
        $this->assertEquals('Cambio de área', $historial->estado_anterior);
        $this->assertEquals('Asignado', $historial->estado_nuevo);
        $this->assertStringContainsString('Sin cupo', $historial->motivo_cambio);
    }

    public function test_cambio_area_actualiza_ubicacion_fisica(): void
    {
        $equipo = $this->crearEquipo();
        $equipo->cambiarAreaDirecto(
            $this->depto2->id_departamento,
            $this->area2->id_area,
            $this->salon2->id,
            'EST-RECALC'
        );

        $equipoFresh = $equipo->fresh();
        $this->assertStringContainsString('Depto Industrial', $equipoFresh->ubicacion_fisica);
        $this->assertStringContainsString('Planta Piloto', $equipoFresh->ubicacion_fisica);
        $this->assertStringContainsString('PP2', $equipoFresh->ubicacion_fisica);
    }

    public function test_cambio_area_ss_no_puede_aprobar(): void
    {
        $this->actingAs($this->ssUser);
        $this->assertFalse(InventarioResource::esAdmin());
    }

    public function test_equipo_cambio_muestra_en_tabla(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->adminUser);

        $equipo = $this->crearEquipo();
        $equipo->solicitarCambioArea(
            $this->depto2->id_departamento,
            $this->area2->id_area,
            null,
            null,
            'Mover equipo'
        );

        Livewire::test(ListInventarios::class)
            ->assertSeeHtml('Cambio de área');
    }

    public function test_cambio_area_observador_no_duplica_historial(): void
    {
        $this->actingAs($this->adminUser);
        $equipo = $this->crearEquipo();

        // Limpiamos historial previo (ej. alta inicial)
        HistorialEstado::where('id_inventario', $equipo->id_inventario)->delete();

        $equipo->cambiarAreaDirecto(
            $this->depto2->id_departamento,
            $this->area2->id_area,
            $this->salon2->id,
            'EST-NO-DUP',
            'Cambio único'
        );

        $totalRegistros = HistorialEstado::where('id_inventario', $equipo->id_inventario)->count();
        $this->assertEquals(1, $totalRegistros, 'El observer no debe duplicar el historial de cambio de área.');
    }
}


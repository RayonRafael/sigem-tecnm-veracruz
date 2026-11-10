<?php

namespace Tests\Feature\Inventario;

use App\Enums\EstadoInventario;
use App\Filament\Pages\Dashboard as AdminDashboard;
use App\Filament\ServicioSocial\Pages\Dashboard as SSDashboard;
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

class AccionesMasivasInventarioTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $ssUser;
    protected Departamento $depto1;
    protected Departamento $depto2;
    protected Area $area1;
    protected Area $area2;
    protected Salon $salon1;
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
        $this->depto2 = Departamento::firstOrCreate(['nombre' => 'Depto Cómputo']);
        $this->area1 = Area::firstOrCreate(['nombre' => 'Laboratorio A', 'id_departamento' => $this->depto1->id_departamento]);
        $this->area2 = Area::firstOrCreate(['nombre' => 'Laboratorio B', 'id_departamento' => $this->depto2->id_departamento]);
        $this->salon1 = Salon::firstOrCreate(['nombre' => 'Aula 101', 'area_id' => $this->area1->id_area]);

        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'Dell']);
        $tipo = TipoMaterial::firstOrCreate(['nombre' => 'Computadora']);
        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pieza']);

        $this->material = Material::firstOrCreate(
            ['nombre' => 'OptiPlex 7090'],
            [
                'id_marca' => $marca->id_marca,
                'id_tipodematerial' => $tipo->id_tipodematerial,
                'id_unidad' => $unidad->id_unidad,
                'stock' => 10,
                'stock_minimo' => 2,
            ]
        );
    }

    protected function crearEquipo(array $overrides = []): Inventario
    {
        return Inventario::create(array_merge([
            'id_producto' => $this->material->id_producto,
            'id_usuario' => $this->adminUser->id_usuario ?? $this->adminUser->id,
            'num_serie' => 'SN-' . uniqid(),
            'departamento_id' => $this->depto1->id_departamento,
            'area_id' => $this->area1->id_area,
            'salon_id' => $this->salon1->id,
            'estado' => EstadoInventario::Disponible->value,
            'aprobado' => true,
            'estado_registro' => 'Aprobado',
            'aprobado_por' => $this->adminUser->id_usuario ?? $this->adminUser->id,
            'fecha_aprobacion' => now(),
            'fecha_registro' => now(),
            'ubicacion_fisica' => 'Depto Sistemas > Laboratorio A > Aula 101',
        ], $overrides));
    }

    public function test_admin_modificar_todos_estado(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $inv1 = $this->crearEquipo();
        $inv2 = $this->crearEquipo();

        Livewire::test(AdminDashboard::class)
            ->set('selectedRows', [(string) $inv1->id_inventario, (string) $inv2->id_inventario])
            ->callAction('modificarTodos', [
                'campo_a_modificar' => 'estado',
                'nuevo_estado' => EstadoInventario::EnMantenimiento->value,
            ])
            ->assertHasNoActionErrors();

        $this->assertEquals(EstadoInventario::EnMantenimiento->value, $inv1->fresh()->estado);
        $this->assertEquals(EstadoInventario::EnMantenimiento->value, $inv2->fresh()->estado);

        $this->assertDatabaseHas('historial_estados', [
            'id_inventario' => $inv1->id_inventario,
            'estado_nuevo' => EstadoInventario::EnMantenimiento->value,
        ]);
    }

    public function test_admin_modificar_todos_area(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $inv1 = $this->crearEquipo();
        $inv2 = $this->crearEquipo();

        Livewire::test(AdminDashboard::class)
            ->set('selectedRows', [(string) $inv1->id_inventario, (string) $inv2->id_inventario])
            ->callAction('modificarTodos', [
                'campo_a_modificar' => 'area',
                'nueva_area_id' => $this->area2->id_area,
            ])
            ->assertHasNoActionErrors();

        $this->assertEquals($this->area2->id_area, $inv1->fresh()->area_id);
        $this->assertEquals($this->area2->id_area, $inv2->fresh()->area_id);
    }

    public function test_admin_cambio_masivo_directo(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $inv1 = $this->crearEquipo();
        $inv2 = $this->crearEquipo();

        Livewire::test(AdminDashboard::class)
            ->set('selectedRows', [(string) $inv1->id_inventario, (string) $inv2->id_inventario])
            ->callAction('cambioMasivo', [
                'nueva_area_id' => $this->area2->id_area,
                'nuevo_estado' => EstadoInventario::Asignado->value,
                'motivo' => 'Reubicación por remodelación',
            ])
            ->assertHasNoActionErrors();

        $this->assertEquals($this->area2->id_area, $inv1->fresh()->area_id);
        $this->assertEquals(EstadoInventario::Asignado->value, $inv1->fresh()->estado);
        $this->assertEquals($this->area2->id_area, $inv2->fresh()->area_id);
        $this->assertEquals(EstadoInventario::Asignado->value, $inv2->fresh()->estado);
    }

    public function test_ss_cambio_masivo_solicita(): void
    {
        $this->actingAs($this->ssUser);
        Filament::setCurrentPanel(Filament::getPanel('servicio-social'));

        $inv1 = $this->crearEquipo();
        $inv2 = $this->crearEquipo();

        Livewire::test(SSDashboard::class)
            ->set('selectedRows', [(string) $inv1->id_inventario, (string) $inv2->id_inventario])
            ->callAction('cambioMasivo', [
                'nueva_area_id' => $this->area2->id_area,
                'motivo' => 'Solicitud cambio por el alumno',
            ])
            ->assertHasNoActionErrors();

        $this->assertEquals(EstadoInventario::CambioDeArea->value, $inv1->fresh()->estado);
        $this->assertEquals(EstadoInventario::CambioDeArea->value, $inv2->fresh()->estado);
    }

    public function test_cambio_masivo_ignora_equipos_no_aptos(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $invBaja = $this->crearEquipo(['estado' => EstadoInventario::DadoDeBaja->value]);
        $invNormal = $this->crearEquipo(['estado' => EstadoInventario::Disponible->value]);

        Livewire::test(AdminDashboard::class)
            ->set('selectedRows', [(string) $invBaja->id_inventario, (string) $invNormal->id_inventario])
            ->callAction('cambioMasivo', [
                'nueva_area_id' => $this->area2->id_area,
                'motivo' => 'Mover disponibles',
            ])
            ->assertHasNoActionErrors();

        $this->assertEquals(EstadoInventario::DadoDeBaja->value, $invBaja->fresh()->estado);
        $this->assertEquals($this->area1->id_area, $invBaja->fresh()->area_id);

        $this->assertEquals($this->area2->id_area, $invNormal->fresh()->area_id);
    }

    public function test_admin_baja_masiva_directa(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $inv1 = $this->crearEquipo();
        $inv2 = $this->crearEquipo();

        Livewire::test(AdminDashboard::class)
            ->set('selectedRows', [(string) $inv1->id_inventario, (string) $inv2->id_inventario])
            ->callAction('bajaMasiva', [
                'motivo_baja' => 'Obsolescencia',
                'observaciones_baja' => 'Equipos antiguos dados de baja',
            ])
            ->assertHasNoActionErrors();

        $this->assertEquals(EstadoInventario::DadoDeBaja->value, $inv1->fresh()->estado);
        $this->assertEquals('Obsolescencia', $inv1->fresh()->motivo_baja);
        $this->assertEquals(EstadoInventario::DadoDeBaja->value, $inv2->fresh()->estado);
        $this->assertEquals('Obsolescencia', $inv2->fresh()->motivo_baja);
    }

    public function test_ss_baja_masiva_solicita(): void
    {
        $this->actingAs($this->ssUser);
        Filament::setCurrentPanel(Filament::getPanel('servicio-social'));

        $inv1 = $this->crearEquipo();
        $inv2 = $this->crearEquipo();

        Livewire::test(SSDashboard::class)
            ->set('selectedRows', [(string) $inv1->id_inventario, (string) $inv2->id_inventario])
            ->callAction('bajaMasiva', [
                'motivo_baja' => 'Daño irreparable',
                'observaciones_baja' => 'Descarga eléctrica',
            ])
            ->assertHasNoActionErrors();

        $this->assertEquals(EstadoInventario::SolicitudDeBaja->value, $inv1->fresh()->estado);
        $this->assertEquals(EstadoInventario::SolicitudDeBaja->value, $inv2->fresh()->estado);
    }

    public function test_baja_masiva_ignora_ya_dados_de_baja(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $invBaja = $this->crearEquipo([
            'estado' => EstadoInventario::DadoDeBaja->value,
            'motivo_baja' => 'Robo',
        ]);
        $invNormal = $this->crearEquipo();

        Livewire::test(AdminDashboard::class)
            ->set('selectedRows', [(string) $invBaja->id_inventario, (string) $invNormal->id_inventario])
            ->callAction('bajaMasiva', [
                'motivo_baja' => 'Pérdida',
                'observaciones_baja' => 'Perdidos en traslado',
            ])
            ->assertHasNoActionErrors();

        $this->assertEquals('Robo', $invBaja->fresh()->motivo_baja);
        $this->assertEquals('Pérdida', $invNormal->fresh()->motivo_baja);
    }

    public function test_exportar_seleccion_descarga_xlsx(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $inv1 = $this->crearEquipo();
        $inv2 = $this->crearEquipo();

        $component = Livewire::test(AdminDashboard::class)
            ->set('selectedRows', [(string) $inv1->id_inventario]);

        $response = $component->instance()->exportarSeleccion();

        $this->assertNotNull($response);
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class, $response);
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('content-type'));
    }

    public function test_panel_lateral_seleccionar_y_deseleccionar(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $inv1 = $this->crearEquipo();
        $inv2 = $this->crearEquipo();

        $component = Livewire::test(AdminDashboard::class);

        // Seleccionar todo
        $component->set('selectAll', true);
        $this->assertContains((string) $inv1->id_inventario, $component->get('selectedRows'));
        $this->assertContains((string) $inv2->id_inventario, $component->get('selectedRows'));

        // Deseleccionar todo
        $component->call('deseleccionarTodos');
        $this->assertEmpty($component->get('selectedRows'));
        $this->assertFalse($component->get('selectAll'));
    }
}

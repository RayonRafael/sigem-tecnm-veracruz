<?php

namespace Tests\Feature\Inventario;

use App\Enums\EstadoInventario;
use App\Filament\Resources\InventarioResource;
use App\Filament\Resources\MantenimientoResource\Pages\ListMantenimientos;
use App\Filament\ServicioSocial\Resources\InventarioResource\Pages\CreateInventario as SSCreateInventario;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\MarcaMaterial;
use App\Models\Material;
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

class IntegridadFase1Y2Test extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $ssUser;
    protected Departamento $depto;
    protected Area $area;
    protected Material $material;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(ServicioSocialUserSeeder::class);

        $this->adminUser = User::where('email', 'admin@tecnm.edu.mx')->first();
        $this->ssUser = User::where('email', 'servicio@tecnm.edu.mx')->first();

        $this->depto = Departamento::firstOrCreate(['nombre' => 'Depto Sistemas'], ['encargado' => 'Jefe Sistemas']);
        $this->area = Area::firstOrCreate(['nombre' => 'Laboratorio 1', 'id_departamento' => $this->depto->id_departamento]);

        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'Dell']);
        $tipo = TipoMaterial::firstOrCreate(['nombre' => 'Monitor']);
        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pieza']);

        $this->material = Material::firstOrCreate(
            ['nombre' => 'Monitor Dell', 'id_marca' => $marca->id_marca, 'modelo' => 'E2216H'],
            [
                'id_tipodematerial' => $tipo->id_tipodematerial,
                'id_unidad' => $unidad->id_unidad,
                'requiere_control_individual' => true,
                'stock_minimo' => 1,
            ]
        );
    }

    public function test_editar_nombre_de_equipo_no_modifica_otros_equipos_del_mismo_material(): void
    {
        $equipo1 = Inventario::factory()->create([
            'id_producto' => $this->material->id_producto,
            'subtipo' => 'Monitor Dell',
            'modelo' => 'E2216H',
        ]);
        $equipo2 = Inventario::factory()->create([
            'id_producto' => $this->material->id_producto,
            'subtipo' => 'Monitor Dell',
            'modelo' => 'E2216H',
        ]);

        // Editar equipo1 cambiando nombre, marca y modelo
        InventarioResource::guardarCambiosEquipo($equipo1, [
            'nombre_equipo' => 'Monitor LG UltraWide',
            'marca' => 'LG',
            'modelo' => '29WN600',
        ]);

        $equipo1Fresh = $equipo1->fresh(['material.marca']);
        $equipo2Fresh = $equipo2->fresh(['material.marca']);

        // equipo1 tiene el nuevo material
        $this->assertSame('Monitor LG UltraWide', $equipo1Fresh->material->nombre);
        $this->assertSame('LG', $equipo1Fresh->material->marca->nombre);
        $this->assertSame('29WN600', $equipo1Fresh->material->modelo);

        // equipo2 conserva intacto su material original
        $this->assertSame($this->material->id_producto, $equipo2Fresh->id_producto);
        $this->assertSame('Monitor Dell', $equipo2Fresh->material->nombre);
        $this->assertSame('Dell', $equipo2Fresh->material->marca->nombre);
        $this->assertSame('E2216H', $equipo2Fresh->material->modelo);
    }

    public function test_servicio_social_crear_pc_registra_monitor_teclado_mouse_y_cpu(): void
    {
        $this->actingAs($this->ssUser);
        Filament::setCurrentPanel(Filament::getPanel('servicio-social'));

        $conteoInicial = Inventario::count();

        Livewire::test(SSCreateInventario::class)
            ->fillForm([
                'tipo_equipo' => 'pc',
                'departamento_id' => $this->depto->id_departamento,
                'area_id' => $this->area->id_area,
                'aula' => 'E101',
                'num_serie' => 'SN-SS-CPU-01',
                'modelo' => 'OptiPlex 7090',
                'especificacion_texto' => 'Funcional',
                'especificaciones' => [
                    'cpu_marca' => 'Dell',
                    'no_tiene_monitor' => false,
                    'monitor_sn' => 'SN-SS-MON-01',
                    'monitor_marca' => 'Dell',
                    'monitor_modelo' => 'E2216H',
                    'no_tiene_teclado' => false,
                    'teclado_sn' => 'SN-SS-TEC-01',
                    'teclado_marca' => 'Dell',
                    'teclado_modelo' => 'KB216',
                    'no_tiene_mouse' => false,
                    'mouse_sn' => 'SN-SS-MOU-01',
                    'mouse_marca' => 'Dell',
                    'mouse_modelo' => 'MS116',
                ],
                'estado' => 'Disponible',
                'tipo_propiedad' => 'Propio',
                'fecha_registro' => now()->format('Y-m-d'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame($conteoInicial + 4, Inventario::count());
        $this->assertDatabaseHas('inventario', ['num_serie' => 'SN-SS-CPU-01', 'subtipo' => 'CPU', 'aprobado' => false]);
        $this->assertDatabaseHas('inventario', ['num_serie' => 'SN-SS-MON-01', 'subtipo' => 'Monitor', 'aprobado' => false]);
        $this->assertDatabaseHas('inventario', ['num_serie' => 'SN-SS-TEC-01', 'subtipo' => 'Teclado', 'aprobado' => false]);
        $this->assertDatabaseHas('inventario', ['num_serie' => 'SN-SS-MOU-01', 'subtipo' => 'Mouse', 'aprobado' => false]);
    }

    public function test_rechazar_mantenimiento_no_deja_equipo_en_mantenimiento(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $equipo = Inventario::factory()->create([
            'estado' => EstadoInventario::Disponible->value,
        ]);

        // 1. Crear mantenimiento pendiente de revisión
        $mant = Mantenimiento::create([
            'id_inventario' => $equipo->id_inventario,
            'id_usuario_solicita' => $this->ssUser->id,
            'nombre_tecnico' => 'Alumno Servicio',
            'num_control_tecnico' => '21020001',
            'tipo_servicio' => 'Servicio Social',
            'tipo_mantenimiento' => 'Correctivo',
            'descripcion_falla' => 'Ruido en ventilador',
            'fecha_solicitud' => now(),
            'estado' => 'Pendiente Revision Admin',
        ]);

        // Al crearse pendiente, el equipo NO debe haber cambiado a En mantenimiento
        $this->assertSame(EstadoInventario::Disponible->value, $equipo->fresh()->estado);

        // 2. Si el equipo estaba En mantenimiento y el mantenimiento se cancela/rechaza, se restaura a Disponible
        $equipo->update(['estado' => EstadoInventario::EnMantenimiento->value]);

        $mant->update(['estado' => 'Cancelado']);

        $this->assertSame('Cancelado', $mant->fresh()->estado);
        $this->assertSame(EstadoInventario::Disponible->value, $equipo->fresh()->estado);
        $this->assertDatabaseHas('historial_estados', [
            'id_inventario' => $equipo->id_inventario,
            'estado_nuevo' => EstadoInventario::Disponible->value,
            'motivo_cambio' => 'Mantenimiento rechazado: equipo restaurado a Disponible',
        ]);
    }

    public function test_editar_equipo_permite_aula_de_hasta_50_caracteres(): void
    {
        $equipo = Inventario::factory()->create([
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
        ]);

        InventarioResource::guardarCambiosEquipo($equipo, [
            'aula' => 'Cubículo de Sistemas Computacionales 204',
        ]);

        $equipoFresh = $equipo->fresh();
        $this->assertSame('Cubículo de Sistemas Computacionales 204', $equipoFresh->aula);
        $this->assertSame('Cubículo de Sistemas Computacionales 204', $equipoFresh->especificaciones['aula']);
    }

    public function test_editar_equipo_guarda_observaciones_adicionales(): void
    {
        $equipo = Inventario::factory()->create([
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'observaciones_generales' => 'Observación inicial',
        ]);

        InventarioResource::guardarCambiosEquipo($equipo, [
            'observaciones_adicionales' => 'Equipo revisado con cableado nuevo',
        ]);

        $this->assertSame('Equipo revisado con cableado nuevo', $equipo->fresh()->observaciones_generales);
    }

    public function test_registro_funciona_sin_usuario_con_id_1(): void
    {
        // Asegurar que no exista el usuario con id = 1 y que haya un usuario con ID distinto (ej. 500)
        User::query()->where('id', 1)->delete();

        $usuarioAlto = User::factory()->create([
            'id' => 500,
            'email' => 'admin500@tecnm.edu.mx',
        ]);

        // Además poner en papelera la unidad 'Pieza' para verificar que autoMatchMaterial la restaura sin crash 1062
        UnidadMedida::where('nombre', 'Pieza')->delete();

        $this->actingAs($usuarioAlto);

        $equipo = InventarioResource::registrarMultiplesEquipos([
            'tipo_equipo' => 'lap',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'aula' => 'LAB-REDES-1',
            'num_serie' => 'SN-NO-ID1-001',
            'modelo' => 'Latitude 5420',
            'especificaciones' => [
                'marca' => 'Dell',
            ],
            'estado' => 'Disponible',
            'tipo_propiedad' => 'Propio',
            'fecha_registro' => now()->format('Y-m-d'),
        ]);

        $this->assertNotNull($equipo->id_inventario);
        $this->assertSame(500, $equipo->id_usuario);
        $this->assertDatabaseHas('historial_estados', [
            'id_inventario' => $equipo->id_inventario,
            'id_usuario' => 500,
        ]);
    }
}

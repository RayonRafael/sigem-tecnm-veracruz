<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Filament\Pages\ConfiguracionPage;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\InventarioResource;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\Proveedor;
use App\Models\Receptor;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Fase2ValidacionesUniqueTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $perm = Permission::firstOrCreate(['name' => RoleEnum::PERM_ACCESS_ADMIN, 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => RoleEnum::ADMIN->value, 'guard_name' => 'web']);
        $role->givePermissionTo($perm);

        $this->admin = User::factory()->create([
            'tipo_usuario' => RoleEnum::ADMIN->value,
            'activo' => true,
        ]);
        $this->admin->assignRole($role);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function crearBaseInventario(): array
    {
        $depto = Departamento::create(['nombre' => 'Sistemas Base']);
        $area = Area::create(['nombre' => 'Laboratorio Base', 'id_departamento' => $depto->id_departamento]);
        $unidad = UnidadMedida::create(['nombre' => 'Pieza Base']);
        $marca = MarcaMaterial::create(['nombre' => 'Dell Base']);
        $tipo = TipoMaterial::create(['nombre' => 'Cómputo Base']);
        $material = Material::create([
            'nombre' => 'Vostro 3470',
            'modelo' => 'VOSTRO3470',
            'id_unidad' => $unidad->id_unidad,
            'id_marca' => $marca->id_marca,
            'id_tipodematerial' => $tipo->id_tipodematerial,
            'requiere_control_individual' => true,
        ]);

        return [$depto, $area, $material, $marca, $tipo, $unidad];
    }

    // =========================================================================
    // PARTE 1: INVENTARIO — No. de serie (num_serie) ÚNICO
    // =========================================================================

    public function test_no_se_permite_registrar_equipo_con_serie_duplicada(): void
    {
        [$depto, $area, $material, $marca] = $this->crearBaseInventario();

        Inventario::create([
            'num_serie' => '24J29Z2',
            'codigo' => 'DELL-VOSTRO3470-014',
            'id_producto' => $material->id_producto,
            'id_usuario' => $this->admin->id,
            'departamento_id' => $depto->id_departamento,
            'area_id' => $area->id_area,
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'fecha_registro' => now(),
        ]);

        $testable = Livewire::actingAs($this->admin)
            ->test(Dashboard::class)
            ->callAction('registrarEquipo', [
                'tipo_equipo' => 'lap',
                'estado' => 'Disponible',
                'tipo_propiedad' => 'Propio',
                'num_serie' => '24J29Z2',
                'especificaciones' => ['marca' => $marca->nombre],
                'modelo' => 'Vostro 3470',
                'departamento_id' => $depto->id_departamento,
                'area_id' => $area->id_area,
                'fecha_registro' => now()->format('Y-m-d'),
            ])
            ->assertHasActionErrors(['num_serie']);

        $errores = $testable->errors()->get('mountedActionsData.0.num_serie');
        $this->assertNotEmpty($errores);
        $this->assertStringContainsString('24J29Z2', $errores[0]);
        $this->assertStringContainsString('DELL-VOSTRO3470-014', $errores[0]);
        $this->assertSame(1, Inventario::count());
    }

    public function test_no_se_permite_editar_equipo_a_serie_duplicada(): void
    {
        [$depto, $area, $material] = $this->crearBaseInventario();

        Inventario::create([
            'num_serie' => '24J29Z2',
            'codigo' => 'DELL-VOSTRO3470-014',
            'id_producto' => $material->id_producto,
            'id_usuario' => $this->admin->id,
            'departamento_id' => $depto->id_departamento,
            'area_id' => $area->id_area,
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'fecha_registro' => now(),
        ]);

        $equipo2 = Inventario::create([
            'num_serie' => 'OTRO-SN-999',
            'codigo' => 'DELL-VOSTRO3470-015',
            'id_producto' => $material->id_producto,
            'id_usuario' => $this->admin->id,
            'departamento_id' => $depto->id_departamento,
            'area_id' => $area->id_area,
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'fecha_registro' => now(),
        ]);

        // 1. Validación en formulario Livewire (EditFormTabs)
        Livewire::actingAs($this->admin)
            ->test(Dashboard::class)
            ->mountAction('editarEquipo', ['id' => $equipo2->id_inventario, 'record' => $equipo2->id_inventario])
            ->setActionData(['num_serie' => '24J29Z2'])
            ->callMountedAction()
            ->assertHasActionErrors(['num_serie']);

        // 2. Validación server-side directa en EditarEquipoAction
        $this->expectException(ValidationException::class);
        InventarioResource::guardarCambiosEquipo($equipo2, ['num_serie' => '24j29z2']);
    }

    public function test_no_se_permite_reusar_serie_de_equipo_soft_deleted(): void
    {
        [$depto, $area, $material, $marca] = $this->crearBaseInventario();

        $eliminado = Inventario::create([
            'num_serie' => 'SN-ELIMINADO-01',
            'codigo' => 'DELL-BAJA-001',
            'id_producto' => $material->id_producto,
            'id_usuario' => $this->admin->id,
            'departamento_id' => $depto->id_departamento,
            'area_id' => $area->id_area,
            'estado' => 'Dado de baja',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'fecha_registro' => now(),
        ]);
        $eliminado->delete();
        $this->assertSoftDeleted('inventario', ['id_inventario' => $eliminado->id_inventario]);

        Livewire::actingAs($this->admin)
            ->test(Dashboard::class)
            ->callAction('registrarEquipo', [
                'tipo_equipo' => 'lap',
                'estado' => 'Disponible',
                'tipo_propiedad' => 'Propio',
                'num_serie' => 'sn-eliminado-01',
                'especificaciones' => ['marca' => $marca->nombre],
                'modelo' => 'Vostro',
                'departamento_id' => $depto->id_departamento,
                'area_id' => $area->id_area,
                'fecha_registro' => now()->format('Y-m-d'),
            ])
            ->assertHasActionErrors(['num_serie']);

        $this->assertSame(0, Inventario::count());
    }

    public function test_se_permite_serie_vacia(): void
    {
        [$depto, $area, , $marca] = $this->crearBaseInventario();

        $eq1 = InventarioResource::registrarMultiplesEquipos([
            'tipo_equipo' => 'otro',
            'estado' => 'Disponible',
            'tipo_propiedad' => 'Propio',
            'num_serie' => null,
            'especificaciones' => ['marca' => $marca->nombre],
            'departamento_id' => $depto->id_departamento,
            'area_id' => $area->id_area,
            'fecha_registro' => now()->format('Y-m-d'),
            'id_usuario' => $this->admin->id,
        ]);

        $eq2 = InventarioResource::registrarMultiplesEquipos([
            'tipo_equipo' => 'otro',
            'estado' => 'Disponible',
            'tipo_propiedad' => 'Propio',
            'num_serie' => '',
            'especificaciones' => ['marca' => $marca->nombre],
            'departamento_id' => $depto->id_departamento,
            'area_id' => $area->id_area,
            'fecha_registro' => now()->format('Y-m-d'),
            'id_usuario' => $this->admin->id,
        ]);

        $this->assertNotNull($eq1->id_inventario);
        $this->assertNotNull($eq2->id_inventario);
        $this->assertSame(2, Inventario::count());
    }

    public function test_validacion_serie_es_case_insensitive(): void
    {
        [$depto, $area, $material, $marca] = $this->crearBaseInventario();

        Inventario::create([
            'num_serie' => '24J29Z2',
            'codigo' => 'DELL-VOSTRO3470-014',
            'id_producto' => $material->id_producto,
            'id_usuario' => $this->admin->id,
            'departamento_id' => $depto->id_departamento,
            'area_id' => $area->id_area,
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'fecha_registro' => now(),
        ]);

        try {
            InventarioResource::registrarMultiplesEquipos([
                'tipo_equipo' => 'lap',
                'estado' => 'Disponible',
                'tipo_propiedad' => 'Propio',
                'num_serie' => '  24j29z2  ',
                'especificaciones' => ['marca' => $marca->nombre],
                'departamento_id' => $depto->id_departamento,
                'area_id' => $area->id_area,
                'fecha_registro' => now()->format('Y-m-d'),
                'id_usuario' => $this->admin->id,
            ]);
            $this->fail('Debió lanzar ValidationException por serie duplicada case-insensitive.');
        } catch (ValidationException $e) {
            $msg = $e->errors()['num_serie'][0] ?? '';
            $this->assertStringContainsString('24j29z2', $msg);
            $this->assertStringContainsString('DELL-VOSTRO3470-014', $msg);
        }
    }

    public function test_no_se_permite_serie_duplicada_en_registro_multiple_pc(): void
    {
        [$depto, $area, $material, $marca] = $this->crearBaseInventario();

        Inventario::create([
            'num_serie' => 'MON-EXIST-01',
            'codigo' => 'DELL-MON-001',
            'id_producto' => $material->id_producto,
            'id_usuario' => $this->admin->id,
            'departamento_id' => $depto->id_departamento,
            'area_id' => $area->id_area,
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'fecha_registro' => now(),
        ]);

        // Caso A: componente del PC choca con serie existente en BD
        try {
            InventarioResource::registrarMultiplesEquipos([
                'tipo_equipo' => 'pc',
                'estado' => 'Disponible',
                'tipo_propiedad' => 'Propio',
                'num_serie' => 'CPU-NEW-01',
                'especificaciones' => [
                    'cpu_marca' => $marca->nombre,
                    'no_tiene_monitor' => false,
                    'monitor_sn' => 'mon-exist-01',
                    'monitor_marca' => $marca->nombre,
                    'no_tiene_teclado' => true,
                    'no_tiene_mouse' => true,
                ],
                'departamento_id' => $depto->id_departamento,
                'area_id' => $area->id_area,
                'fecha_registro' => now()->format('Y-m-d'),
                'id_usuario' => $this->admin->id,
            ]);
            $this->fail('Debió rechazar monitor_sn duplicado contra BD.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('especificaciones.monitor_sn', $e->errors());
        }

        // Caso B: dos componentes del MISMO PC tienen el mismo S/N (case-insensitive)
        try {
            InventarioResource::registrarMultiplesEquipos([
                'tipo_equipo' => 'pc',
                'estado' => 'Disponible',
                'tipo_propiedad' => 'Propio',
                'num_serie' => 'SAME-SN-100',
                'especificaciones' => [
                    'cpu_marca' => $marca->nombre,
                    'no_tiene_monitor' => false,
                    'monitor_sn' => 'same-sn-100',
                    'monitor_marca' => $marca->nombre,
                    'no_tiene_teclado' => true,
                    'no_tiene_mouse' => true,
                ],
                'departamento_id' => $depto->id_departamento,
                'area_id' => $area->id_area,
                'fecha_registro' => now()->format('Y-m-d'),
                'id_usuario' => $this->admin->id,
            ]);
            $this->fail('Debió rechazar S/N repetido entre componentes del mismo registro.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('num_serie', $e->errors());
        }

        // Ninguno de los dos intentos fallidos debe haber insertado registros parciales
        $this->assertSame(1, Inventario::count());
    }

    // =========================================================================
    // PARTE 2: LOS 8 CATÁLOGOS — Nombre ÚNICO
    // =========================================================================

    public function test_no_se_permite_crear_departamento_duplicado(): void
    {
        Departamento::create(['nombre' => 'Dirección']);

        $testable = Livewire::actingAs($this->admin)
            ->test(ConfiguracionPage::class)
            ->callAction('crearDepartamento', ['nombre' => 'Dirección'])
            ->assertHasActionErrors(['nombre']);

        $msg = $testable->errors()->get('mountedActionsData.0.nombre')[0] ?? '';
        $this->assertSame("Ya existe un departamento con el nombre 'Dirección'", $msg);
        $this->assertSame(1, Departamento::count());
    }

    public function test_no_se_permite_crear_area_duplicada(): void
    {
        $depto = Departamento::create(['nombre' => 'Ingeniería']);
        Area::create(['nombre' => 'AUTOCAD', 'id_departamento' => $depto->id_departamento]);

        $testable = Livewire::actingAs($this->admin)
            ->test(ConfiguracionPage::class)
            ->callAction('crearArea', [
                'nombre' => 'Autocad',
                'id_departamento' => $depto->id_departamento,
            ])
            ->assertHasActionErrors(['nombre']);

        $msg = $testable->errors()->get('mountedActionsData.0.nombre')[0] ?? '';
        $this->assertSame("Ya existe un área con el nombre 'Autocad'", $msg);
        $this->assertSame(1, Area::count());
    }

    public function test_no_se_permite_crear_material_duplicado(): void
    {
        [, , , $marca, $tipo, $unidad] = $this->crearBaseInventario();

        $testable = Livewire::actingAs($this->admin)
            ->test(ConfiguracionPage::class)
            ->callAction('crearMaterial', [
                'nombre' => 'vostro 3470',
                'id_marca' => $marca->id_marca,
                'id_tipodematerial' => $tipo->id_tipodematerial,
                'id_unidad' => $unidad->id_unidad,
                'stock_minimo' => 1,
                'requiere_control_individual' => true,
            ])
            ->assertHasActionErrors(['nombre']);

        $msg = $testable->errors()->get('mountedActionsData.0.nombre')[0] ?? '';
        $this->assertSame("Ya existe un material con el nombre 'vostro 3470'", $msg);
        $this->assertSame(1, Material::count());
    }

    public function test_no_se_permite_crear_marca_duplicada(): void
    {
        MarcaMaterial::create(['nombre' => 'DELL']);

        $testable = Livewire::actingAs($this->admin)
            ->test(ConfiguracionPage::class)
            ->callAction('crearMarca', ['nombre' => 'dell'])
            ->assertHasActionErrors(['nombre']);

        $msg = $testable->errors()->get('mountedActionsData.0.nombre')[0] ?? '';
        $this->assertSame("Ya existe una marca con el nombre 'dell'", $msg);
        $this->assertSame(1, MarcaMaterial::count());
    }

    public function test_no_se_permite_crear_tipo_duplicado(): void
    {
        TipoMaterial::create(['nombre' => 'Equipo de Cómputo']);

        $testable = Livewire::actingAs($this->admin)
            ->test(ConfiguracionPage::class)
            ->callAction('crearTipo', ['nombre' => 'equipo de cómputo'])
            ->assertHasActionErrors(['nombre']);

        $msg = $testable->errors()->get('mountedActionsData.0.nombre')[0] ?? '';
        $this->assertSame("Ya existe un tipo de material con el nombre 'equipo de cómputo'", $msg);
        $this->assertSame(1, TipoMaterial::count());
    }

    public function test_no_se_permite_crear_unidad_duplicada(): void
    {
        UnidadMedida::create(['nombre' => 'Pieza']);

        $testable = Livewire::actingAs($this->admin)
            ->test(ConfiguracionPage::class)
            ->callAction('crearUnidad', ['nombre' => 'PIEZA'])
            ->assertHasActionErrors(['nombre']);

        $msg = $testable->errors()->get('mountedActionsData.0.nombre')[0] ?? '';
        $this->assertSame("Ya existe una unidad de medida con el nombre 'PIEZA'", $msg);
        $this->assertSame(1, UnidadMedida::count());
    }

    public function test_no_se_permite_crear_proveedor_duplicado(): void
    {
        Proveedor::create(['nombre_empresa' => 'Soluciones Tech SA', 'activo' => true]);

        $testable = Livewire::actingAs($this->admin)
            ->test(ConfiguracionPage::class)
            ->callAction('crearProveedor', [
                'nombre_empresa' => 'soluciones tech sa',
                'activo' => true,
            ])
            ->assertHasActionErrors(['nombre_empresa']);

        $msg = $testable->errors()->get('mountedActionsData.0.nombre_empresa')[0] ?? '';
        $this->assertSame("Ya existe un proveedor con el nombre 'soluciones tech sa'", $msg);
        $this->assertSame(1, Proveedor::count());
    }

    public function test_no_se_permite_crear_receptor_duplicado(): void
    {
        $depto = Departamento::create(['nombre' => 'Académico']);
        $area = Area::create(['nombre' => 'Coordinación', 'id_departamento' => $depto->id_departamento]);

        Receptor::create([
            'nombre' => 'Roberto',
            'apellido_paterno' => 'Hernández',
            'apellido_materno' => 'García',
            'id_area' => $area->id_area,
        ]);

        // Mismo nombre completo (case-insensitive y acentos) -> bloqueado
        $testable = Livewire::actingAs($this->admin)
            ->test(ConfiguracionPage::class)
            ->callAction('crearReceptor', [
                'nombre' => 'ROBERTO',
                'apellido_paterno' => 'HERNÁNDEZ',
                'apellido_materno' => 'GARCÍA',
                'id_area' => $area->id_area,
            ])
            ->assertHasActionErrors(['nombre']);

        $msg = $testable->errors()->get('mountedActionsData.0.nombre')[0] ?? '';
        $this->assertSame("Ya existe un receptor con el nombre 'ROBERTO HERNÁNDEZ GARCÍA'", $msg);
        $this->assertSame(1, Receptor::count());

        // Otra persona con mismo primer nombre pero distintos apellidos -> permitido
        Livewire::actingAs($this->admin)
            ->test(ConfiguracionPage::class)
            ->callAction('crearReceptor', [
                'nombre' => 'Roberto',
                'apellido_paterno' => 'López',
                'apellido_materno' => 'Pérez',
                'id_area' => $area->id_area,
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(2, Receptor::count());
    }

    public function test_validacion_catalogos_es_case_insensitive_e_incluye_soft_deleted(): void
    {
        $dep = Departamento::create(['nombre' => 'Dirección']);
        $dep->delete();
        $this->assertSoftDeleted('departamento', ['id_departamento' => $dep->id_departamento]);

        // Incluso en MAYÚSCULAS con acento (DIRECCIÓN) y soft-deleted debe bloquear
        Livewire::actingAs($this->admin)
            ->test(ConfiguracionPage::class)
            ->callAction('crearDepartamento', ['nombre' => 'DIRECCIÓN'])
            ->assertHasActionErrors(['nombre']);

        $marca = MarcaMaterial::create(['nombre' => 'Lenovo']);
        $marca->delete();

        Livewire::actingAs($this->admin)
            ->test(ConfiguracionPage::class)
            ->callAction('crearMarca', ['nombre' => '  LENOVO  '])
            ->assertHasActionErrors(['nombre']);
    }

    public function test_se_permite_editar_mismo_registro_sin_conflicto(): void
    {
        [$depto, $area, $material] = $this->crearBaseInventario();
        $depto2 = Departamento::create(['nombre' => 'Dirección']);
        $marca = MarcaMaterial::create(['nombre' => 'HP']);
        $prov = Proveedor::create(['nombre_empresa' => 'CompuVer', 'activo' => true]);

        // 1. Editar Departamento manteniendo su mismo nombre (actualizando encargado) -> OK
        Livewire::actingAs($this->admin)
            ->test(ConfiguracionPage::class)
            ->mountAction('editarDepartamento', ['record' => $depto->id_departamento])
            ->setActionData([
                'nombre' => 'Sistemas Base',
                'encargado' => 'Dr. Nuevo Jefe',
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertSame('Dr. Nuevo Jefe', $depto->fresh()->encargado);

        // 2. Intentar renombrar Departamento al nombre de otro existente -> BLOQUEADO
        Livewire::actingAs($this->admin)
            ->test(ConfiguracionPage::class)
            ->mountAction('editarDepartamento', ['record' => $depto->id_departamento])
            ->setActionData(['nombre' => 'dirección'])
            ->callMountedAction()
            ->assertHasActionErrors(['nombre']);

        // 3. Editar Marca y Proveedor manteniendo su propio nombre -> OK
        Livewire::actingAs($this->admin)
            ->test(ConfiguracionPage::class)
            ->mountAction('editarMarca', ['record' => $marca->id_marca])
            ->setActionData(['nombre' => 'HP'])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        Livewire::actingAs($this->admin)
            ->test(ConfiguracionPage::class)
            ->mountAction('editarProveedor', ['record' => $prov->id_proveedor])
            ->setActionData([
                'nombre_empresa' => 'CompuVer',
                'contacto_nombre' => 'Ing. Ana',
                'activo' => true,
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        // 4. Editar Inventario manteniendo su propio S/N -> OK
        $equipo = Inventario::create([
            'num_serie' => 'OWN-SN-777',
            'codigo' => 'DELL-VOSTRO-077',
            'id_producto' => $material->id_producto,
            'id_usuario' => $this->admin->id,
            'departamento_id' => $depto->id_departamento,
            'area_id' => $area->id_area,
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'fecha_registro' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(Dashboard::class)
            ->mountAction('editarEquipo', ['id' => $equipo->id_inventario, 'record' => $equipo->id_inventario])
            ->setActionData([
                'num_serie' => 'OWN-SN-777',
                'estado' => 'Asignado',
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertSame('Asignado', $equipo->fresh()->estado);
        $this->assertNotNull($depto2->id_departamento);
    }
}

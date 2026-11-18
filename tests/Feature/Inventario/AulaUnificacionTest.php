<?php

namespace Tests\Feature\Inventario;

use App\Filament\Resources\InventarioResource;
use App\Models\Area;
use App\Models\Departamento;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AulaUnificacionTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Departamento $depto;
    protected Area $area;
    protected Material $material;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(ServicioSocialUserSeeder::class);

        $this->adminUser = User::where('email', 'admin@tecnm.edu.mx')->firstOrFail();
        $this->depto = Departamento::firstOrCreate(['nombre' => 'Sistemas y Computación']);
        $this->area = Area::firstOrCreate([
            'nombre' => 'Laboratorio de Cómputo',
            'id_departamento' => $this->depto->id_departamento,
        ]);

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

    public function test_migracion_agrega_columna_aula_y_hace_backfill(): void
    {
        $this->assertTrue(Schema::hasColumn('inventario', 'aula'));

        $salon = Salon::firstOrCreate([
            'nombre' => 'S303',
            'area_id' => $this->area->id_area,
        ]);

        // Insertar registros simulando las 4 fuentes previas con aula = NULL
        $idSpecAula = DB::table('inventario')->insertGetId([
            'id_producto' => $this->material->id_producto,
            'id_usuario' => $this->adminUser->id,
            'num_serie' => 'SN-BF-1',
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'salon_id' => null,
            'aula' => null,
            'especificaciones' => json_encode(['aula' => 'E101']),
            'fecha_registro' => now()->toDateString(),
        ]);

        $idCambioAula = DB::table('inventario')->insertGetId([
            'id_producto' => $this->material->id_producto,
            'id_usuario' => $this->adminUser->id,
            'num_serie' => 'SN-BF-2',
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'salon_id' => null,
            'aula' => null,
            'especificaciones' => json_encode(['aula' => 'E101', 'cambio_nueva_aula' => 'U202']),
            'fecha_registro' => now()->toDateString(),
        ]);

        $idSalonFk = DB::table('inventario')->insertGetId([
            'id_producto' => $this->material->id_producto,
            'id_usuario' => $this->adminUser->id,
            'num_serie' => 'SN-BF-3',
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'salon_id' => $salon->id,
            'aula' => null,
            'especificaciones' => null,
            'fecha_registro' => now()->toDateString(),
        ]);

        $idUbicacionRegex = DB::table('inventario')->insertGetId([
            'id_producto' => $this->material->id_producto,
            'id_usuario' => $this->adminUser->id,
            'num_serie' => 'SN-BF-4',
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'salon_id' => null,
            'aula' => null,
            'especificaciones' => null,
            'ubicacion_fisica' => 'Sistemas > Laboratorio > Aula M404',
            'fecha_registro' => now()->toDateString(),
        ]);

        // Re-ejecutar la migración de backfill
        $migration = include database_path('migrations/2026_11_18_000001_add_aula_column_to_inventario_table.php');
        $migration->up();

        $this->assertSame('E101', DB::table('inventario')->where('id_inventario', $idSpecAula)->value('aula'));
        $this->assertSame('U202', DB::table('inventario')->where('id_inventario', $idCambioAula)->value('aula'));
        $this->assertSame('S303', DB::table('inventario')->where('id_inventario', $idSalonFk)->value('aula'));
        $this->assertSame('M404', DB::table('inventario')->where('id_inventario', $idUbicacionRegex)->value('aula'));
    }

    public function test_cambio_de_area_guarda_aula_en_columna_fisica(): void
    {
        $this->actingAs($this->adminUser);

        $equipo = Inventario::create([
            'id_producto' => $this->material->id_producto,
            'id_usuario' => $this->adminUser->id,
            'num_serie' => 'SN-CA-AULA-01',
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'aula' => 'E101',
            'fecha_registro' => now(),
        ]);

        $deptoDestino = Departamento::firstOrCreate(['nombre' => 'Ingeniería Industrial']);
        $areaDestino = Area::firstOrCreate([
            'nombre' => 'Planta Piloto',
            'id_departamento' => $deptoDestino->id_departamento,
        ]);

        // Cambiar área con aula 'E201'
        $equipo->cambiarAreaDirecto(
            $deptoDestino->id_departamento,
            $areaDestino->id_area,
            'E201',
            null,
            'Reubicación a E201'
        );

        $equipo->refresh();
        $this->assertSame('E201', $equipo->aula);
        $this->assertSame('E201', $equipo->especificaciones['aula'] ?? null);
        $this->assertNotNull($equipo->salon_id);

        // Si se cambia a un área sin aula (null), salon_id y aula se limpian
        $equipo->cambiarAreaDirecto(
            $deptoDestino->id_departamento,
            $areaDestino->id_area,
            null,
            null,
            'Reubicación a oficina sin aula'
        );

        $equipo->refresh();
        $this->assertNull($equipo->aula);
        $this->assertNull($equipo->salon_id);
    }

    public function test_guardar_cambios_equipo_limpia_salon_id_si_aula_cambia(): void
    {
        $this->actingAs($this->adminUser);

        $salonViejo = Salon::firstOrCreate([
            'nombre' => 'E103',
            'area_id' => $this->area->id_area,
        ]);

        $equipo = Inventario::create([
            'id_producto' => $this->material->id_producto,
            'id_usuario' => $this->adminUser->id,
            'num_serie' => 'SN-EDIT-AULA-01',
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'salon_id' => $salonViejo->id,
            'aula' => 'E103',
            'fecha_registro' => now(),
        ]);

        $this->assertSame($salonViejo->id, $equipo->salon_id);

        // Editar el aula a 'NUEVA' (que no existe en la tabla salones)
        InventarioResource::guardarCambiosEquipo($equipo, [
            'area_id' => $this->area->id_area,
            'aula' => 'NUEVA',
        ]);

        $equipo->refresh();
        $this->assertSame('NUEVA', $equipo->aula);
        $this->assertSame('NUEVA', $equipo->especificaciones['aula'] ?? null);
        $this->assertNull($equipo->salon_id, 'salon_id debe limpiarse cuando la nueva aula no existe en salones.');
    }

    public function test_ordenamiento_por_aula_funciona_para_todos_los_equipos(): void
    {
        $this->actingAs($this->adminUser);

        $salonB = Salon::firstOrCreate([
            'nombre' => 'B200',
            'area_id' => $this->area->id_area,
        ]);

        // Equipo 1: sin salon_id, solo con columna aula = 'C300'
        $eqC = Inventario::create([
            'id_producto' => $this->material->id_producto,
            'id_usuario' => $this->adminUser->id,
            'num_serie' => 'SN-ORD-C',
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'salon_id' => null,
            'aula' => 'C300',
            'fecha_registro' => now(),
        ]);

        // Equipo 2: sin salon_id, solo con columna aula = 'A100'
        $eqA = Inventario::create([
            'id_producto' => $this->material->id_producto,
            'id_usuario' => $this->adminUser->id,
            'num_serie' => 'SN-ORD-A',
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'salon_id' => null,
            'aula' => 'A100',
            'fecha_registro' => now(),
        ]);

        // Equipo 3: con salon_id ('B200')
        $eqB = Inventario::create([
            'id_producto' => $this->material->id_producto,
            'id_usuario' => $this->adminUser->id,
            'num_serie' => 'SN-ORD-B',
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'salon_id' => $salonB->id,
            'aula' => 'B200',
            'fecha_registro' => now(),
        ]);

        $ordenAsc = InventarioResource::obtenerInventariosDashboardQuery([], 'aula', 'asc')
            ->whereIn('inventario.id_inventario', [$eqA->id_inventario, $eqB->id_inventario, $eqC->id_inventario])
            ->pluck('inventario.num_serie')
            ->all();

        $this->assertSame(['SN-ORD-A', 'SN-ORD-B', 'SN-ORD-C'], $ordenAsc);

        $ordenDesc = InventarioResource::obtenerInventariosDashboardQuery([], 'aula', 'desc')
            ->whereIn('inventario.id_inventario', [$eqA->id_inventario, $eqB->id_inventario, $eqC->id_inventario])
            ->pluck('inventario.num_serie')
            ->all();

        $this->assertSame(['SN-ORD-C', 'SN-ORD-B', 'SN-ORD-A'], $ordenDesc);
    }
}


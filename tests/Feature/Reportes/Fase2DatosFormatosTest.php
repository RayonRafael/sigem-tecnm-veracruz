<?php

namespace Tests\Feature\Reportes;

use App\Enums\EstadoInventario;
use App\Filament\Pages\Dashboard as AdminDashboard;
use App\Filament\Resources\MantenimientoResource\Pages\ListMantenimientos as AdminListMantenimientos;
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
use App\Support\SigemBajaFormatHelper;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Fase2DatosFormatosTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
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

        $this->adminUser = User::where('email', 'admin@tecnm.edu.mx')->first();
        $this->ssUser = User::where('email', 'servicio@tecnm.edu.mx')->first();

        $this->depto = Departamento::firstOrCreate(['nombre' => 'Centro de Cómputo'], ['encargado' => 'Jefe Demo']);
        $this->area = Area::firstOrCreate(['nombre' => 'Laboratorio Redes', 'id_departamento' => $this->depto->id_departamento]);
        $this->salon = Salon::firstOrCreate(['nombre' => 'L1', 'area_id' => $this->area->id_area]);

        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'Dell']);
        $tipo = TipoMaterial::firstOrCreate(['nombre' => 'Computadora']);
        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pieza']);

        $this->material = Material::firstOrCreate(
            ['nombre' => 'OptiPlex 7000'],
            [
                'id_marca' => $marca->id_marca,
                'id_tipodematerial' => $tipo->id_tipodematerial,
                'id_unidad' => $unidad->id_unidad,
                'stock_actual' => 5,
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
            'num_serie' => 'SN-F2-' . uniqid(),
            'estado' => $estado,
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'salon_id' => $this->salon->id,
            'fecha_registro' => now(),
        ]);
    }

    public function test_motivos_baja_tiene_8_opciones_y_conserva_las_5_existentes(): void
    {
        $opciones = SigemBajaFormatHelper::motivosBajaOptions();

        $this->assertCount(8, $opciones);

        // 5 motivos originales exactos
        foreach (['Daño irreparable', 'Obsolescencia', 'Pérdida', 'Robo', 'Otro'] as $original) {
            $this->assertArrayHasKey($original, $opciones);
            $this->assertSame($original, $opciones[$original]);
        }

        // 3 motivos nuevos del formato oficial
        foreach (['Destrucción', 'Reaprovechamiento', 'Desalojo'] as $nuevo) {
            $this->assertArrayHasKey($nuevo, $opciones);
            $this->assertSame($nuevo, $opciones[$nuevo]);
        }
    }

    public function test_helper_mapea_8_motivos_hacia_7_casillas_formato_baja(): void
    {
        $esperado = [
            'Daño irreparable' => 'danado_inservible',
            'Obsolescencia' => 'inutilidad',
            'Robo' => 'robo_extravio',
            'Pérdida' => 'robo_extravio',
            'Destrucción' => 'destruccion',
            'Reaprovechamiento' => 'reaprovechamiento',
            'Desalojo' => 'desalojo',
        ];

        foreach ($esperado as $motivo => $claveMarcada) {
            $causas = SigemBajaFormatHelper::mapearCausas($motivo);
            $this->assertCount(7, $causas);
            $this->assertTrue($causas[$claveMarcada]['marcada'], "Motivo '{$motivo}' debe marcar '{$claveMarcada}'");

            $marcadas = array_keys(array_filter($causas, fn (array $c) => $c['marcada']));
            $this->assertSame([$claveMarcada], $marcadas);
        }

        // 'Otro' y null no marcan ninguna de las 7 casillas
        foreach (['Otro', null] as $sinCasilla) {
            $causas = SigemBajaFormatHelper::mapearCausas($sinCasilla);
            $marcadas = array_filter($causas, fn (array $c) => $c['marcada']);
            $this->assertCount(0, $marcadas);
        }

        $this->assertSame('Dañado e inservible', SigemBajaFormatHelper::etiquetaCausa('danado_inservible'));
    }

    public function test_mantenimiento_tiene_relacion_usuario_solicita(): void
    {
        $inv = $this->crearEquipo();

        $mant = Mantenimiento::create([
            'id_inventario' => $inv->id_inventario,
            'id_usuario_solicita' => $this->ssUser->id,
            'tipo_mantenimiento' => 'Correctivo',
            'tipo_servicio' => 'Servicio Social',
            'nombre_tecnico' => 'Técnico Demo',
            'descripcion_falla' => 'No enciende',
            'fecha_solicitud' => now(),
            'estado' => 'Pendiente Revision Admin',
        ]);

        $this->assertInstanceOf(User::class, $mant->usuarioSolicita);
        $this->assertEquals($this->ssUser->id, $mant->usuarioSolicita->id);
    }

    public function test_mantenimiento_permite_editar_y_completar_descripcion_trabajo(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->adminUser);
        $inv = $this->crearEquipo();

        $mant = Mantenimiento::create([
            'id_inventario' => $inv->id_inventario,
            'id_usuario_solicita' => $this->ssUser->id,
            'tipo_mantenimiento' => 'Correctivo',
            'tipo_servicio' => 'Servicio Social',
            'nombre_tecnico' => 'Rodrigo López',
            'num_control_tecnico' => '20240001',
            'descripcion_falla' => 'Falla en sistema operativo',
            'fecha_solicitud' => now(),
            'estado' => 'En proceso',
            'fecha_inicio' => now(),
        ]);

        Livewire::test(AdminListMantenimientos::class)
            ->callTableAction('edit', $mant, [
                'tipo_mantenimiento' => 'Correctivo',
                'id_inventario' => $inv->id_inventario,
                'nombre_tecnico' => 'Rodrigo López',
                'num_control_tecnico' => '20240001',
                'descripcion_falla' => 'Falla en sistema operativo',
                'descripcion_trabajo' => 'Se reinstaló Windows 10',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertSame('Se reinstaló Windows 10', $mant->fresh()->descripcion_trabajo);

        Livewire::test(AdminListMantenimientos::class)
            ->callTableAction('completar', $mant, [
                'descripcion_trabajo' => 'Se reinstaló Windows 10 y paquetería esencial',
            ])
            ->assertHasNoTableActionErrors();

        $mant->refresh();
        $this->assertSame('Completado', $mant->estado);
        $this->assertSame('Se reinstaló Windows 10 y paquetería esencial', $mant->descripcion_trabajo);
    }

    public function test_baja_guarda_campos_opcionales_en_especificaciones_json(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->adminUser);

        $equipoConDatos = $this->crearEquipo();
        $equipoSinDatos = $this->crearEquipo();

        // Con folio_sibsep y posible_area_utilizacion
        Livewire::test(AdminDashboard::class)
            ->callAction('darBaja', [
                'motivo_baja' => 'Reaprovechamiento',
                'observaciones_baja' => 'Fuente funcional aprovechable',
                'folio_sibsep' => 'SIB-2026-0045',
                'posible_area_utilizacion' => $this->area->nombre,
            ], ['record' => $equipoConDatos->getKey()])
            ->assertHasNoActionErrors();

        $equipoConDatos->refresh();
        $this->assertSame('Dado de baja', $equipoConDatos->estado);
        $this->assertSame('Reaprovechamiento', $equipoConDatos->motivo_baja);
        $this->assertSame('SIB-2026-0045', $equipoConDatos->especificaciones['folio_sibsep'] ?? null);
        $this->assertSame($this->area->nombre, $equipoConDatos->especificaciones['posible_area_utilizacion'] ?? null);

        // Sin folio_sibsep ni posible_area_utilizacion (como en los formatos reales)
        Livewire::test(AdminDashboard::class)
            ->set('selectedRows', [(string) $equipoSinDatos->id_inventario])
            ->set('bajaMotivo', 'Destrucción')
            ->set('bajaObservaciones', 'Tarjeta electrónica dañada')
            ->call('ejecutarBajaSlideOver');

        $equipoSinDatos->refresh();
        $this->assertSame(EstadoInventario::DadoDeBaja->value, $equipoSinDatos->estado);
        $this->assertSame('Destrucción', $equipoSinDatos->motivo_baja);
        $this->assertArrayNotHasKey('folio_sibsep', $equipoSinDatos->especificaciones ?? []);
        $this->assertArrayNotHasKey('posible_area_utilizacion', $equipoSinDatos->especificaciones ?? []);
    }
}


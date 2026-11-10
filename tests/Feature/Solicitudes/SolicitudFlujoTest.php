<?php

namespace Tests\Feature\Solicitudes;

use App\Filament\Resources\SolicitudResource as AdminSolicitudResource;
use App\Filament\ServicioSocial\Resources\SolicitudResource as SSSolicitudResource;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\DetalleSolicitud;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\Receptor;
use App\Models\Solicitud;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SolicitudFlujoTest extends TestCase
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

    private function getServicioSocialUser(): User
    {
        return User::where('email', 'servicio@tecnm.edu.mx')->first();
    }

    private function getOrCreateArea(): Area
    {
        $depto = Departamento::firstOrCreate(['nombre' => 'Depto General']);
        return Area::firstOrCreate(['nombre' => 'Área General', 'id_departamento' => $depto->id_departamento]);
    }

    public function test_servicio_social_puede_crear_solicitud(): void
    {
        $ssUser = $this->getServicioSocialUser();
        $this->actingAs($ssUser);

        $depto = Departamento::firstOrCreate(['nombre' => 'Depto Ciencias']);
        $area = Area::firstOrCreate(['nombre' => 'Lab Química', 'id_departamento' => $depto->id_departamento]);
        $receptor = Receptor::firstOrCreate([
            'nombre' => 'Ana',
            'apellido_paterno' => 'Gómez',
            'apellido_materno' => 'Pérez',
            'id_area' => $area->id_area,
        ]);

        $solicitud = Solicitud::create([
            'id_usuario' => $ssUser->id,
            'id_receptor' => $receptor->id_receptor,
            'tipo_movimiento' => 'Asignacion Temporal', // Entrada
            'fecha_solicitud' => now(),
            'observaciones' => 'Solicitud de prueba SS',
        ]);

        $this->assertDatabaseHas('solicitud', [
            'id_solicitud' => $solicitud->id_solicitud,
            'id_usuario' => $ssUser->id,
            'tipo_movimiento' => 'Asignacion Temporal',
        ]);
    }

    public function test_solicitud_se_crea_con_estado_pendiente(): void
    {
        $ssUser = $this->getServicioSocialUser();
        $receptor = Receptor::firstOrCreate([
            'nombre' => 'Receptor Default',
            'apellido_paterno' => 'Paterno',
            'apellido_materno' => 'Materno',
            'id_area' => $this->getOrCreateArea()->id_area,
        ]);

        $solicitud = Solicitud::create([
            'id_usuario' => $ssUser->id,
            'id_receptor' => $receptor->id_receptor,
            'tipo_movimiento' => 'Asignacion Permanente', // Salida
            'fecha_solicitud' => now(),
        ]);

        $this->assertEquals('Pendiente', $solicitud->fresh()->estado);
    }

    public function test_servicio_social_ve_solo_sus_propias_solicitudes(): void
    {
        $ssUser = $this->getServicioSocialUser();
        $adminUser = $this->getAdminUser();

        $receptor = Receptor::firstOrCreate([
            'nombre' => 'Receptor Test',
            'apellido_paterno' => 'Paterno',
            'apellido_materno' => 'Materno',
            'id_area' => $this->getOrCreateArea()->id_area,
        ]);

        $solicitudSS = Solicitud::create([
            'id_usuario' => $ssUser->id,
            'id_receptor' => $receptor->id_receptor,
            'tipo_movimiento' => 'Asignacion Temporal',
            'fecha_solicitud' => now(),
        ]);

        $solicitudAdmin = Solicitud::create([
            'id_usuario' => $adminUser->id,
            'id_receptor' => $receptor->id_receptor,
            'tipo_movimiento' => 'Asignacion Permanente',
            'fecha_solicitud' => now(),
        ]);

        Filament::setCurrentPanel(Filament::getPanel('servicio-social'));
        $this->actingAs($ssUser);

        $query = SSSolicitudResource::getEloquentQuery();
        $idsVisibles = $query->pluck('id_solicitud')->all();

        $this->assertContains($solicitudSS->id_solicitud, $idsVisibles);
        $this->assertNotContains($solicitudAdmin->id_solicitud, $idsVisibles);
    }

    public function test_admin_ve_todas_las_solicitudes(): void
    {
        $ssUser = $this->getServicioSocialUser();
        $adminUser = $this->getAdminUser();

        $receptor = Receptor::firstOrCreate([
            'nombre' => 'Receptor Global',
            'apellido_paterno' => 'Paterno',
            'apellido_materno' => 'Materno',
            'id_area' => $this->getOrCreateArea()->id_area,
        ]);

        $solicitudSS = Solicitud::create([
            'id_usuario' => $ssUser->id,
            'id_receptor' => $receptor->id_receptor,
            'tipo_movimiento' => 'Asignacion Temporal',
            'fecha_solicitud' => now(),
        ]);

        $solicitudAdmin = Solicitud::create([
            'id_usuario' => $adminUser->id,
            'id_receptor' => $receptor->id_receptor,
            'tipo_movimiento' => 'Asignacion Permanente',
            'fecha_solicitud' => now(),
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($adminUser);

        $query = AdminSolicitudResource::getEloquentQuery();
        $idsVisibles = $query->pluck('id_solicitud')->all();

        $this->assertContains($solicitudSS->id_solicitud, $idsVisibles);
        $this->assertContains($solicitudAdmin->id_solicitud, $idsVisibles);
    }

    public function test_servicio_social_no_puede_autorizar_o_modificar_solicitud(): void
    {
        $ssUser = $this->getServicioSocialUser();
        $adminUser = $this->getAdminUser();

        $receptor = Receptor::firstOrCreate([
            'nombre' => 'Receptor Permiso',
            'apellido_paterno' => 'Paterno',
            'apellido_materno' => 'Materno',
            'id_area' => $this->getOrCreateArea()->id_area,
        ]);
        $solicitudAdmin = Solicitud::create([
            'id_usuario' => $adminUser->id,
            'id_receptor' => $receptor->id_receptor,
            'tipo_movimiento' => 'Asignacion Temporal',
            'fecha_solicitud' => now(),
            'estado' => 'Pendiente',
        ]);

        // SS no puede modificar ni eliminar solicitudes de otros usuarios
        $this->assertFalse($ssUser->can('update', $solicitudAdmin));
        $this->assertFalse($ssUser->can('delete', $solicitudAdmin));

        // SS tampoco puede modificar solicitudes autorizadas (no puede autorizar)
        $solicitudSSAutorizada = Solicitud::create([
            'id_usuario' => $ssUser->id,
            'id_receptor' => $receptor->id_receptor,
            'tipo_movimiento' => 'Asignacion Temporal',
            'fecha_solicitud' => now(),
            'estado' => 'Autorizado',
        ]);
        $this->assertFalse($ssUser->can('update', $solicitudSSAutorizada));

        // Admin sí puede autorizar y modificar
        $this->assertTrue($adminUser->can('update', $solicitudAdmin));
    }

    public function test_slide_over_nueva_solicitud_mapea_tipo_y_crea_detalle(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $adminUser = $this->getAdminUser();
        $this->actingAs($adminUser);

        $receptor = Receptor::firstOrCreate([
            'nombre' => 'Receptor Test',
            'apellido_paterno' => 'Paterno',
            'apellido_materno' => 'Materno',
            'id_area' => $this->getOrCreateArea()->id_area,
        ]);

        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pza']);
        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'Genérico']);
        $tipoMat = TipoMaterial::firstOrCreate(['nombre' => 'Consumible']);
        $material = Material::create([
            'nombre' => 'Material SlideOver Test',
            'id_unidad' => $unidad->id_unidad,
            'id_marca' => $marca->id_marca,
            'id_tipodematerial' => $tipoMat->id_tipodematerial,
            'stock_actual' => 10,
            'requiere_control_individual' => 0,
        ]);

        \Livewire\Livewire::test(\App\Filament\Resources\SolicitudResource\Pages\ListSolicituds::class)
            ->callAction('create', [
                'tipo_solicitud' => 'Entrada',
                'fecha_solicitud' => now()->toDateString(),
                'id_producto' => $material->id_producto,
                'observaciones' => 'Solicitud slide-over de prueba',
            ])
            ->assertHasNoActionErrors();

        $solicitud = Solicitud::latest('id_solicitud')->first();
        $this->assertNotNull($solicitud);
        $this->assertEquals('Asignacion Temporal', $solicitud->tipo_movimiento);
        $this->assertEquals('Pendiente', $solicitud->estado);
        $this->assertEquals($adminUser->id, $solicitud->id_usuario);

        $detalle = DetalleSolicitud::where('id_solicitud', $solicitud->id_solicitud)->first();
        $this->assertNotNull($detalle);
        $this->assertEquals($material->id_producto, $detalle->id_producto);
        $this->assertEquals(1, $detalle->cantidad);
    }

    public function test_slide_over_editar_solicitud_actualiza_detalle(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $adminUser = $this->getAdminUser();
        $this->actingAs($adminUser);

        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pza']);
        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'Genérico']);
        $tipoMat = TipoMaterial::firstOrCreate(['nombre' => 'Consumible']);
        $materialA = Material::create([
            'nombre' => 'Material A',
            'id_unidad' => $unidad->id_unidad,
            'id_marca' => $marca->id_marca,
            'id_tipodematerial' => $tipoMat->id_tipodematerial,
            'stock_actual' => 10,
            'requiere_control_individual' => 0,
        ]);
        $materialB = Material::create([
            'nombre' => 'Material B',
            'id_unidad' => $unidad->id_unidad,
            'id_marca' => $marca->id_marca,
            'id_tipodematerial' => $tipoMat->id_tipodematerial,
            'stock_actual' => 10,
            'requiere_control_individual' => 0,
        ]);

        $receptor = Receptor::firstOrCreate([
            'nombre' => 'Receptor SlideOver',
            'apellido_paterno' => 'Paterno',
            'apellido_materno' => 'Materno',
            'id_area' => $this->getOrCreateArea()->id_area,
        ]);

        $solicitud = Solicitud::create([
            'id_usuario' => $adminUser->id,
            'id_receptor' => $receptor->id_receptor,
            'tipo_movimiento' => 'Asignacion Permanente',
            'fecha_solicitud' => now(),
            'estado' => 'Pendiente',
            'observaciones' => 'Observación original',
        ]);

        DetalleSolicitud::create([
            'id_solicitud' => $solicitud->id_solicitud,
            'id_producto' => $materialA->id_producto,
            'cantidad' => 1,
        ]);

        \Livewire\Livewire::test(\App\Filament\Resources\SolicitudResource\Pages\ListSolicituds::class)
            ->callTableAction('edit', $solicitud, [
                'tipo_solicitud' => 'Traslado',
                'id_producto' => $materialB->id_producto,
                'observaciones' => 'Observación actualizada',
            ])
            ->assertHasNoTableActionErrors();

        $solicitud->refresh();
        $this->assertEquals('Renta Externa', $solicitud->tipo_movimiento);
        $this->assertEquals('Observación actualizada', $solicitud->observaciones);

        $detalle = $solicitud->detalles()->first();
        $this->assertNotNull($detalle);
        $this->assertEquals($materialB->id_producto, $detalle->id_producto);
    }

    public function test_ss_slide_over_crear_solicitud(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('servicio-social'));
        $ssUser = $this->getServicioSocialUser();
        $this->actingAs($ssUser);

        $receptor = Receptor::firstOrCreate([
            'nombre' => 'Receptor SS Test',
            'apellido_paterno' => 'Paterno',
            'apellido_materno' => 'Materno',
            'id_area' => $this->getOrCreateArea()->id_area,
        ]);

        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pza']);
        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'Genérico']);
        $tipoMat = TipoMaterial::firstOrCreate(['nombre' => 'Consumible']);
        $material = Material::create([
            'nombre' => 'Material SS SlideOver',
            'id_unidad' => $unidad->id_unidad,
            'id_marca' => $marca->id_marca,
            'id_tipodematerial' => $tipoMat->id_tipodematerial,
            'stock_actual' => 10,
            'requiere_control_individual' => 0,
        ]);

        \Livewire\Livewire::test(\App\Filament\ServicioSocial\Resources\SolicitudResource\Pages\ListSolicituds::class)
            ->callAction('create', [
                'tipo_solicitud' => 'Salida',
                'fecha_solicitud' => now()->toDateString(),
                'id_producto' => $material->id_producto,
                'observaciones' => 'Solicitud SS slide-over',
            ])
            ->assertHasNoActionErrors();

        $solicitud = Solicitud::latest('id_solicitud')->first();
        $this->assertNotNull($solicitud);
        $this->assertEquals('Asignacion Permanente', $solicitud->tipo_movimiento);
        $this->assertEquals('Pendiente', $solicitud->estado);
        $this->assertEquals($ssUser->id, $solicitud->id_usuario);

        $detalle = DetalleSolicitud::where('id_solicitud', $solicitud->id_solicitud)->first();
        $this->assertNotNull($detalle);
        $this->assertEquals($material->id_producto, $detalle->id_producto);
    }
}

<?php

namespace Tests\Feature\Inventario;

use App\Enums\EstadoInventario;
use App\Filament\Pages\Dashboard as AdminDashboard;
use App\Filament\ServicioSocial\Pages\Dashboard as SSDashboard;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\HistorialEstado;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\Proveedor;
use App\Models\Receptor;
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

    public function test_cambio_masivo_permite_cambiar_tipo_propiedad(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $inv1 = $this->crearEquipo(['tipo_propiedad' => 'Propio']);
        $inv2 = $this->crearEquipo(['tipo_propiedad' => 'Propio']);

        Livewire::test(AdminDashboard::class)
            ->set('selectedRows', [(string) $inv1->id_inventario, (string) $inv2->id_inventario])
            ->set('cambioNuevoTipoPropiedad', 'Rentado')
            ->set('cambioMotivo', 'Actualización a contrato de arrendamiento')
            ->call('ejecutarCambioSlideOver');

        $this->assertSame('Rentado', $inv1->fresh()->tipo_propiedad);
        $this->assertSame('Rentado', $inv2->fresh()->tipo_propiedad);

        $this->assertDatabaseHas('historial_estados', [
            'id_inventario' => $inv1->id_inventario,
            'motivo_cambio' => 'Cambio masivo de propiedad: Propio → Rentado: Actualización a contrato de arrendamiento',
        ]);
    }

    public function test_modificar_todos_permite_cambiar_tipo_propiedad(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $inv1 = $this->crearEquipo(['tipo_propiedad' => 'Propio']);
        $inv2 = $this->crearEquipo(['tipo_propiedad' => 'Rentado']);

        Livewire::test(AdminDashboard::class)
            ->set('selectedRows', [(string) $inv1->id_inventario, (string) $inv2->id_inventario])
            ->set('modificarCampo', 'tipo_propiedad')
            ->set('modificarNuevoValor', 'Rentado')
            ->call('ejecutarModificarSlideOver');

        $this->assertSame('Rentado', $inv1->fresh()->tipo_propiedad);
        $this->assertSame('Rentado', $inv2->fresh()->tipo_propiedad);

        $this->assertDatabaseHas('historial_estados', [
            'id_inventario' => $inv1->id_inventario,
            'motivo_cambio' => 'Modificación masiva de propiedad: Propio → Rentado',
        ]);
    }

    public function test_slide_over_cambiar_area_tiene_diseno_sigem(): void
    {
        $schema = \App\Filament\Concerns\ConModalesBajaCambio::getModalCambioAreaFormSchema(true);

        $nombresTopLevel = collect($schema)
            ->map(fn ($c) => method_exists($c, 'getName') ? $c->getName() : null)
            ->filter()
            ->values()
            ->all();

        $this->assertContains('equipo_resumen', $nombresTopLevel);
        $this->assertContains('tip', $nombresTopLevel);

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

        $checklistSchema = \App\Filament\Concerns\ConModalesBajaCambio::getChecklistCambioAreaSchema();
        $nombresChecklist = collect($checklistSchema)
            ->map(fn ($c) => method_exists($c, 'getName') ? $c->getName() : null)
            ->filter()
            ->values()
            ->all();

        $this->assertContains('exito', $nombresChecklist);
        $this->assertContains('campos_pdf', $nombresChecklist);
    }

    public function test_cambio_masivo_estado_cambio_de_area_valida_salon_y_guarda_metadatos(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $inv1 = $this->crearEquipo();
        $inv2 = $this->crearEquipo();

        // Creamos un salón que pertenece a area1, pero pasaremos nueva_area_id = area2
        // Debe limpiar salon_id porque salon1 no pertenece a area2 (FIX 3585873)
        Livewire::test(AdminDashboard::class)
            ->set('selectedRows', [(string) $inv1->id_inventario, (string) $inv2->id_inventario])
            ->callAction('cambioMasivo', [
                'nuevo_estado' => EstadoInventario::CambioDeArea->value,
                'nuevo_departamento_id' => $this->depto2->id_departamento,
                'nueva_area_id' => $this->area2->id_area,
                'nueva_aula' => 'Aula 202',
                'nuevo_salon_id' => $this->salon1->id, // Salón inválido para area2
                'motivo' => 'Reubicación al Lab B',
            ])
            ->assertHasNoActionErrors();

        $fresh1 = $inv1->fresh();
        $fresh2 = $inv2->fresh();

        $this->assertEquals($this->area2->id_area, $fresh1->area_id);
        $this->assertEquals($this->depto2->id_departamento, $fresh1->departamento_id);
        $this->assertNull($fresh1->salon_id, 'El salon_id inválido debió ser limpiado a null');
        $this->assertEquals('Aula 202', $fresh1->aula);
        $this->assertEquals('Reubicación al Lab B', $fresh1->especificaciones['cambio_motivo'] ?? null);
        $this->assertEquals($this->area2->nombre, $fresh1->especificaciones['cambio_destino_area'] ?? null);

        $this->assertEquals($this->area2->id_area, $fresh2->area_id);
        $this->assertNull($fresh2->salon_id);
    }

    public function test_cambio_masivo_asignado_con_receptor_guarda_asignacion(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $receptor = Receptor::create([
            'nombre' => 'Roberto',
            'apellido_paterno' => 'Hernández',
            'apellido_materno' => 'Gómez',
            'id_area' => $this->area1->id_area,
            'cargo' => 'Docente',
        ]);

        $inv1 = $this->crearEquipo();
        $inv2 = $this->crearEquipo();

        Livewire::test(AdminDashboard::class)
            ->set('selectedRows', [(string) $inv1->id_inventario, (string) $inv2->id_inventario])
            ->callAction('cambioMasivo', [
                'nuevo_estado' => EstadoInventario::Asignado->value,
                'nuevo_receptor_id' => $receptor->id_receptor,
                'motivo' => 'Asignación para semestre 2026-B',
            ])
            ->assertHasNoActionErrors();

        $fresh1 = $inv1->fresh();
        $this->assertEquals(EstadoInventario::Asignado->value, $fresh1->estado);
        $this->assertNotNull($fresh1->fecha_asignacion);
        $this->assertEquals($receptor->id_receptor, $fresh1->especificaciones['id_receptor'] ?? null);
        $this->assertStringContainsString('Roberto Hernández', $fresh1->especificaciones['receptor_nombre'] ?? '');

        $this->assertDatabaseHas('historial_estados', [
            'id_inventario' => $inv1->id_inventario,
            'estado_nuevo' => EstadoInventario::Asignado->value,
        ]);
    }

    public function test_cambio_masivo_en_mantenimiento_crea_registro_mantenimiento(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $inv1 = $this->crearEquipo();
        $inv2 = $this->crearEquipo();

        Livewire::test(AdminDashboard::class)
            ->set('selectedRows', [(string) $inv1->id_inventario, (string) $inv2->id_inventario])
            ->callAction('cambioMasivo', [
                'nuevo_estado' => EstadoInventario::EnMantenimiento->value,
                'tipo_servicio' => 'Preventivo',
                'descripcion_falla' => 'Limpieza de ventiladores y cambio de pasta térmica',
                'motivo' => 'Mantenimiento preventivo anual',
            ])
            ->assertHasNoActionErrors();

        $fresh1 = $inv1->fresh();
        $fresh2 = $inv2->fresh();

        $this->assertEquals(EstadoInventario::EnMantenimiento->value, $fresh1->estado);
        $this->assertEquals(EstadoInventario::EnMantenimiento->value, $fresh2->estado);

        $this->assertDatabaseHas('mantenimiento', [
            'id_inventario' => $inv1->id_inventario,
            'tipo_mantenimiento' => 'Preventivo',
            'descripcion_falla' => 'Limpieza de ventiladores y cambio de pasta térmica',
        ]);

        $this->assertDatabaseHas('mantenimiento', [
            'id_inventario' => $inv2->id_inventario,
            'tipo_mantenimiento' => 'Preventivo',
        ]);
    }

    public function test_cambio_masivo_dado_de_baja_aplica_baja_directa_con_motivo(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $inv1 = $this->crearEquipo();
        $inv2 = $this->crearEquipo();

        Livewire::test(AdminDashboard::class)
            ->set('selectedRows', [(string) $inv1->id_inventario, (string) $inv2->id_inventario])
            ->callAction('cambioMasivo', [
                'nuevo_estado' => EstadoInventario::DadoDeBaja->value,
                'motivo_baja' => 'Obsolescencia',
                'descripcion_baja' => 'Equipos con tecnología descontinuada',
            ])
            ->assertHasNoActionErrors();

        $fresh1 = $inv1->fresh();
        $this->assertEquals(EstadoInventario::DadoDeBaja->value, $fresh1->estado);
        $this->assertEquals('Obsolescencia', $fresh1->motivo_baja);
        $this->assertNotNull($fresh1->fecha_baja);
    }

    public function test_cambio_masivo_danado_guarda_descripcion_y_nivel_en_especificaciones(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $inv1 = $this->crearEquipo();
        $inv2 = $this->crearEquipo();

        Livewire::test(AdminDashboard::class)
            ->set('selectedRows', [(string) $inv1->id_inventario, (string) $inv2->id_inventario])
            ->callAction('cambioMasivo', [
                'nuevo_estado' => EstadoInventario::Danado->value,
                'descripcion_dano' => 'Gabinete golpeado y puerto USB roto',
                'nivel_dano' => 'Mayor',
                'motivo' => 'Caída accidental durante traslado',
            ])
            ->assertHasNoActionErrors();

        $fresh1 = $inv1->fresh();
        $this->assertEquals(EstadoInventario::Danado->value, $fresh1->estado);
        $this->assertEquals('Gabinete golpeado y puerto USB roto', $fresh1->especificaciones['descripcion_dano'] ?? null);
        $this->assertEquals('Mayor', $fresh1->especificaciones['nivel_dano'] ?? null);

        $this->assertDatabaseHas('historial_estados', [
            'id_inventario' => $inv1->id_inventario,
            'estado_nuevo' => EstadoInventario::Danado->value,
        ]);
    }

    public function test_cambio_masivo_devuelto_a_proveedor_asocia_proveedor_y_motivo(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $prov = Proveedor::create([
            'nombre_empresa' => 'Tecnología y Arrendamiento SA de CV',
            'rfc' => 'TAR990101XYZ',
            'activo' => true,
        ]);

        $inv1 = $this->crearEquipo();
        $inv2 = $this->crearEquipo();

        Livewire::test(AdminDashboard::class)
            ->set('selectedRows', [(string) $inv1->id_inventario, (string) $inv2->id_inventario])
            ->callAction('cambioMasivo', [
                'nuevo_estado' => EstadoInventario::DevueltoAProveedor->value,
                'proveedor_id' => $prov->id_proveedor,
                'motivo_devolucion' => 'Fin de contrato de arrendamiento',
                'motivo' => 'Devolución oficial',
            ])
            ->assertHasNoActionErrors();

        $fresh1 = $inv1->fresh();
        $this->assertEquals(EstadoInventario::DevueltoAProveedor->value, $fresh1->estado);
        $this->assertEquals($prov->id_proveedor, $fresh1->id_proveedor);
        $this->assertEquals('Fin de contrato de arrendamiento', $fresh1->especificaciones['motivo_devolucion'] ?? null);
    }

    public function test_cambio_masivo_bloquea_tipo_propiedad_en_estados_restringidos(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $inv1 = $this->crearEquipo(['tipo_propiedad' => 'Propio']);

        // Al cambiar a En Mantenimiento, el intento de pasar 'Rentado' debe ser ignorado
        Livewire::test(AdminDashboard::class)
            ->set('selectedRows', [(string) $inv1->id_inventario])
            ->callAction('cambioMasivo', [
                'nuevo_estado' => EstadoInventario::EnMantenimiento->value,
                'nuevo_tipo_propiedad' => 'Rentado',
                'tipo_servicio' => 'Preventivo',
                'descripcion_falla' => 'Revisión periódica',
            ])
            ->assertHasNoActionErrors();

        $this->assertEquals('Propio', $inv1->fresh()->tipo_propiedad, 'El tipo_propiedad no debió cambiar al pasar a En Mantenimiento');
    }

    public function test_slide_over_livewire_interaccion_completa(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $inv1 = $this->crearEquipo();
        $inv2 = $this->crearEquipo();

        $component = Livewire::test(AdminDashboard::class)
            ->set('selectedRows', [(string) $inv1->id_inventario, (string) $inv2->id_inventario])
            ->call('setSlideOverStep', 'cambio')
            ->assertSet('slideOverStep', 'cambio')
            ->set('cambioNuevoEstado', EstadoInventario::Danado->value)
            ->set('cambioDescripcionDano', 'Teclado mojado')
            ->set('cambioNivelDano', 'Menor')
            ->set('cambioMotivo', 'Reportado por profesor')
            ->call('ejecutarCambioSlideOver');

        $this->assertEquals(EstadoInventario::Danado->value, $inv1->fresh()->estado);
        $this->assertEquals('Teclado mojado', $inv1->fresh()->especificaciones['descripcion_dano'] ?? null);
        $this->assertEquals('Menor', $inv1->fresh()->especificaciones['nivel_dano'] ?? null);

        // Verifica que se hayan reseteado las propiedades
        $this->assertEmpty($component->get('selectedRows'));
        $this->assertEquals('principal', $component->get('slideOverStep'));
    }
}

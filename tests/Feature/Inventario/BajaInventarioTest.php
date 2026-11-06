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

class BajaInventarioTest extends TestCase
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

        $this->depto = Departamento::firstOrCreate(['nombre' => 'Depto Cómputo']);
        $this->area = Area::firstOrCreate(['nombre' => 'Soporte', 'id_departamento' => $this->depto->id_departamento]);
        $this->salon = Salon::firstOrCreate(['nombre' => 'A1', 'area_id' => $this->area->id_area]);

        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'HP']);
        $tipo = TipoMaterial::firstOrCreate(['nombre' => 'Impresora']);
        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pieza']);

        $this->material = Material::firstOrCreate(
            ['nombre' => 'HP LaserJet 4103'],
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
            'num_serie' => 'SN-BAJA-' . uniqid(),
            'estado' => $estado,
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'salon_id' => $this->salon->id,
            'fecha_registro' => now(),
        ]);
    }

    public function test_admin_dar_baja_directo(): void
    {
        $this->actingAs($this->adminUser);
        $equipo = $this->crearEquipo();

        $equipo->darBajaDirecta('Daño irreparable', 'Tarjeta madre quemada por sobrevoltaje');

        $equipo->refresh();
        $this->assertEquals('Dado de baja', $equipo->estado);
    }

    public function test_admin_dar_baja_registra_historial(): void
    {
        $this->actingAs($this->adminUser);
        $equipo = $this->crearEquipo();

        $equipo->darBajaDirecta('Daño irreparable', 'Sin reparación posible');

        $ultimo = HistorialEstado::where('id_inventario', $equipo->id_inventario)->latest('id_historial')->first();
        $this->assertNotNull($ultimo);
        $this->assertEquals('Disponible', $ultimo->estado_anterior);
        $this->assertEquals('Dado de baja', $ultimo->estado_nuevo);
        $this->assertEquals('Baja directa: Daño irreparable', $ultimo->motivo_cambio);
        $this->assertEquals($this->adminUser->id, $ultimo->id_usuario);
    }

    public function test_admin_dar_baja_guarda_motivo_y_fecha(): void
    {
        $this->actingAs($this->adminUser);
        $equipo = $this->crearEquipo();

        $equipo->darBajaDirecta('Obsolescencia', 'Equipo con más de 10 años de antigüedad');

        $equipo->refresh();
        $this->assertEquals('Obsolescencia', $equipo->motivo_baja);
        $this->assertEquals('Equipo con más de 10 años de antigüedad', $equipo->observaciones_baja);
        $this->assertNotNull($equipo->fecha_baja);
        $this->assertEquals($this->adminUser->id, $equipo->id_usuario_baja);
    }

    public function test_ss_solicitar_baja(): void
    {
        $this->actingAs($this->ssUser);
        $equipo = $this->crearEquipo();

        $equipo->solicitarBaja('Robo', 'Sustraído de laboratorio');

        $equipo->refresh();
        $this->assertEquals('Solicitud de baja', $equipo->estado);
        $this->assertEquals('Disponible', $equipo->estado_previo_baja);
        $this->assertEquals('Robo', $equipo->motivo_baja);
        $this->assertEquals('Sustraído de laboratorio', $equipo->observaciones_baja);
    }

    public function test_ss_solicitar_baja_registra_historial(): void
    {
        $this->actingAs($this->ssUser);
        $equipo = $this->crearEquipo();

        $equipo->solicitarBaja('Pérdida', 'Extraviado en traslado');

        $ultimo = HistorialEstado::where('id_inventario', $equipo->id_inventario)->latest('id_historial')->first();
        $this->assertNotNull($ultimo);
        $this->assertEquals('Disponible', $ultimo->estado_anterior);
        $this->assertEquals('Solicitud de baja', $ultimo->estado_nuevo);
        $this->assertEquals('Solicitud de baja: Pérdida', $ultimo->motivo_cambio);
        $this->assertEquals($this->ssUser->id, $ultimo->id_usuario);
    }

    public function test_admin_aprueba_baja(): void
    {
        $equipo = $this->crearEquipo();
        $equipo->solicitarBaja('Daño irreparable', 'Falla eléctrica total', $this->ssUser->id);

        $this->actingAs($this->adminUser);
        $equipo->aprobarBaja($this->adminUser->id);

        $equipo->refresh();
        $this->assertEquals('Dado de baja', $equipo->estado);
        $this->assertNotNull($equipo->fecha_baja);
        $this->assertEquals($this->adminUser->id, $equipo->id_usuario_baja);
    }

    public function test_admin_rechaza_baja(): void
    {
        $equipo = $this->crearEquipo('Asignado');
        $equipo->solicitarBaja('Otro', 'Se ve desgastado', $this->ssUser->id);

        $this->actingAs($this->adminUser);
        $equipo->rechazarBaja('El equipo aún es plenamente operativo y tiene piezas de repuesto');

        $equipo->refresh();
        $this->assertEquals('Asignado', $equipo->estado);
        $this->assertNull($equipo->motivo_baja);
        $this->assertNull($equipo->observaciones_baja);
        $this->assertNull($equipo->estado_previo_baja);
    }

    public function test_aprobar_baja_registra_historial(): void
    {
        $equipo = $this->crearEquipo();
        $equipo->solicitarBaja('Obsolescencia', 'Sin soporte de drivers', $this->ssUser->id);

        $this->actingAs($this->adminUser);
        $equipo->aprobarBaja($this->adminUser->id);

        $ultimo = HistorialEstado::where('id_inventario', $equipo->id_inventario)->latest('id_historial')->first();
        $this->assertNotNull($ultimo);
        $this->assertEquals('Solicitud de baja', $ultimo->estado_anterior);
        $this->assertEquals('Dado de baja', $ultimo->estado_nuevo);
        $this->assertEquals('Baja aprobada: Obsolescencia', $ultimo->motivo_cambio);
        $this->assertEquals($this->adminUser->id, $ultimo->id_usuario);
    }

    public function test_rechazar_baja_registra_historial(): void
    {
        $equipo = $this->crearEquipo('Disponible');
        $equipo->solicitarBaja('Otro', 'No enciende', $this->ssUser->id);

        $this->actingAs($this->adminUser);
        $equipo->rechazarBaja('Se enviará a garantía técnica en lugar de baja', $this->adminUser->id);

        $ultimo = HistorialEstado::where('id_inventario', $equipo->id_inventario)->latest('id_historial')->first();
        $this->assertNotNull($ultimo);
        $this->assertEquals('Solicitud de baja', $ultimo->estado_anterior);
        $this->assertEquals('Disponible', $ultimo->estado_nuevo);
        $this->assertEquals('Baja rechazada: Se enviará a garantía técnica en lugar de baja', $ultimo->motivo_cambio);
    }

    public function test_reactivar_equipo(): void
    {
        $this->actingAs($this->adminUser);
        $equipo = $this->crearEquipo();
        $equipo->darBajaDirecta('Daño irreparable', 'Sin piezas');

        $this->assertEquals('Dado de baja', $equipo->fresh()->estado);

        $equipo->reactivar($this->adminUser->id);

        $equipo->refresh();
        $this->assertEquals('Disponible', $equipo->estado);
        $this->assertNull($equipo->fecha_baja);
        $this->assertNull($equipo->motivo_baja);
        $this->assertNull($equipo->observaciones_baja);
    }

    public function test_reactivar_registra_historial(): void
    {
        $this->actingAs($this->adminUser);
        $equipo = $this->crearEquipo();
        $equipo->darBajaDirecta('Daño irreparable', 'Sin piezas');

        $equipo->reactivar($this->adminUser->id);

        $ultimo = HistorialEstado::where('id_inventario', $equipo->id_inventario)->latest('id_historial')->first();
        $this->assertNotNull($ultimo);
        $this->assertEquals('Dado de baja', $ultimo->estado_anterior);
        $this->assertEquals('Disponible', $ultimo->estado_nuevo);
        $this->assertEquals('Reactivación del equipo', $ultimo->motivo_cambio);
    }

    public function test_baja_ss_no_puede_aprobar(): void
    {
        $equipo = $this->crearEquipo();
        $equipo->darBajaDirecta('Obsolescencia', 'Antiguo');

        // SS no puede editar un equipo en estado "Dado de baja"
        $this->assertFalse(SSInventarioResource::canEdit($equipo));
    }

    public function test_equipo_baja_muestra_en_tabla(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->adminUser);

        $equipoBaja = $this->crearEquipo();
        $equipoBaja->darBajaDirecta('Robo', 'Confirmado robo');

        $equipoSolicitud = $this->crearEquipo();
        $equipoSolicitud->solicitarBaja('Daño irreparable', 'Roto');

        Livewire::test(ListInventarios::class)
            ->assertSeeHtml('Dado de baja')
            ->assertSeeHtml('Solicitud de baja');
    }

    public function test_equipo_baja_no_aparece_en_filtros_normales(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->adminUser);

        $equipoDisp = $this->crearEquipo('Disponible');
        $equipoBaja = $this->crearEquipo();
        $equipoBaja->darBajaDirecta('Obsolescencia', 'Fin de vida útil');

        // Filtrando por 'Disponible'
        Livewire::test(ListInventarios::class)
            ->set('tableColumnSearches.estado', 'Disponible')
            ->assertSee($equipoDisp->num_serie)
            ->assertDontSee($equipoBaja->num_serie);

        // Filtrando por 'Dado de baja'
        Livewire::test(ListInventarios::class)
            ->set('tableColumnSearches.estado', 'Dado de baja')
            ->assertSee($equipoBaja->num_serie)
            ->assertDontSee($equipoDisp->num_serie);
    }
}

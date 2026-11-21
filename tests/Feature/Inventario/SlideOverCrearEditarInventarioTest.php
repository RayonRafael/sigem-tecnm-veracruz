<?php

namespace Tests\Feature\Inventario;

use App\Filament\Pages\Dashboard as AdminDashboard;
use App\Filament\Resources\InventarioResource;
use App\Filament\Resources\InventarioResource\Schemas\CreateFormSchema;
use App\Filament\Resources\InventarioResource\Schemas\EditFormTabs;
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
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SlideOverCrearEditarInventarioTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Departamento $depto;
    protected Area $area;
    protected Proveedor $proveedor;
    protected Receptor $receptor1;
    protected Receptor $receptor2;
    protected Material $material;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->adminUser = User::where('email', 'admin@tecnm.edu.mx')->first();

        $this->depto = Departamento::firstOrCreate(
            ['nombre' => 'Centro de Cómputo'],
            ['encargado' => 'Ing. Héctor Pérez']
        );

        $this->area = Area::firstOrCreate([
            'nombre' => 'Laboratorio L1',
            'id_departamento' => $this->depto->id_departamento,
        ]);

        $this->proveedor = Proveedor::firstOrCreate(
            ['nombre_empresa' => 'CompuVeracruz SA'],
            ['telefono' => '2291000001', 'correo' => 'ventas@compuver.mx']
        );

        $this->receptor1 = Receptor::create([
            'nombre' => 'Carlos',
            'apellido_paterno' => 'Ramírez',
            'apellido_materno' => 'Soto',
            'cargo' => 'Jefe de Laboratorio',
            'id_area' => $this->area->id_area,
        ]);

        $this->receptor2 = Receptor::create([
            'nombre' => 'Elena',
            'apellido_paterno' => 'Morales',
            'apellido_materno' => 'Cruz',
            'cargo' => 'Docente Titular',
            'id_area' => $this->area->id_area,
        ]);

        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'Dell']);
        $tipo = TipoMaterial::firstOrCreate(['nombre' => 'Laptop']);
        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pieza']);

        $this->material = Material::firstOrCreate(
            ['nombre' => 'Latitude 5420'],
            [
                'modelo' => '5420',
                'id_marca' => $marca->id_marca,
                'id_tipodematerial' => $tipo->id_tipodematerial,
                'id_unidad' => $unidad->id_unidad,
                'stock_actual' => 5,
                'stock_minimo' => 1,
                'requiere_control_individual' => 1,
            ]
        );
    }

    /**
     * Recorre recursivamente componentes de Filament recolectando secciones y nombres de campos.
     *
     * @param  array<int, Component>  $components
     * @param  array<int, Section>  $sections
     * @param  array<int, string>  $fieldNames
     */
    protected function recolectarComponentes(array $components, array &$sections, array &$fieldNames): void
    {
        foreach ($components as $comp) {
            if ($comp instanceof Section) {
                $sections[] = $comp;
            }
            if ($comp instanceof Field) {
                $fieldNames[] = $comp->getName();
            }
            if (method_exists($comp, 'getChildComponentContainer')) {
                $children = $comp->getChildComponentContainer()->getComponents(withHidden: true);
                if (! empty($children)) {
                    $this->recolectarComponentes($children, $sections, $fieldNames);
                }
            }
        }
    }

    public function test_esquema_crear_y_editar_sin_collapsibles_orden_correcto_y_sin_jefe_departamento(): void
    {
        $livewire = new AdminDashboard();

        // 1. Auditar formulario CREAR
        $createContainer = ComponentContainer::make($livewire)->components(CreateFormSchema::make());
        $createSections = [];
        $createFields = [];
        $this->recolectarComponentes($createContainer->getComponents(withHidden: true), $createSections, $createFields);

        foreach ($createSections as $section) {
            $heading = (string) $section->getHeading();
            $this->assertFalse($section->isCollapsible(), "La sección '{$heading}' en Crear NO debe ser collapsible.");
            $this->assertFalse($section->isCollapsed(), "La sección '{$heading}' en Crear NO debe iniciar colapsada.");
        }

        $createHeadings = array_map(fn (Section $s) => (string) $s->getHeading(), $createSections);
        $posDatos = array_search('📦 Datos del equipo', $createHeadings, true);
        $posUbic = array_search('📍 Ubicación', $createHeadings, true);
        $posSpecs = array_search('🔧 Especificaciones técnicas', $createHeadings, true);
        $posAdmin = array_search('💰 Datos administrativos', $createHeadings, true);

        $this->assertNotFalse($posDatos);
        $this->assertNotFalse($posUbic);
        $this->assertNotFalse($posSpecs);
        $this->assertNotFalse($posAdmin);
        $this->assertTrue($posDatos < $posUbic && $posUbic < $posSpecs && $posSpecs < $posAdmin, 'El orden de secciones en Crear debe ser: Datos equipo → Ubicación → Especificaciones → Administrativos');

        $this->assertNotContains('jefe_departamento', $createFields, 'jefe_departamento debe estar eliminado del form Crear.');
        $this->assertContains('id_receptor', $createFields, 'id_receptor debe existir en el form Crear.');

        // 2. Auditar formulario EDITAR
        $editContainer = ComponentContainer::make($livewire)->components(EditFormTabs::make());
        $editTopComponents = $editContainer->getComponents(withHidden: true);
        $this->assertCount(4, $editTopComponents, 'EditFormTabs debe retornar las 4 secciones principales siempre desplegadas.');

        $editSections = [];
        $editFields = [];
        $this->recolectarComponentes($editTopComponents, $editSections, $editFields);

        foreach ($editSections as $section) {
            $heading = (string) $section->getHeading();
            $this->assertFalse($section->isCollapsible(), "La sección '{$heading}' en Editar NO debe ser collapsible.");
            $this->assertFalse($section->isCollapsed(), "La sección '{$heading}' en Editar NO debe iniciar colapsada.");
        }

        $editHeadings = array_map(fn (Section $s) => (string) $s->getHeading(), $editSections);
        $this->assertSame([
            '📦 Datos del equipo',
            '📍 Ubicación',
            '🔧 Especificaciones técnicas',
            '💰 Datos administrativos',
        ], $editHeadings);

        $this->assertNotContains('jefe_departamento', $editFields, 'jefe_departamento debe estar eliminado del form Editar.');
        $this->assertContains('id_receptor', $editFields, 'id_receptor debe existir en el form Editar.');
        $this->assertContains('departamento_id', $editFields, 'departamento_id debe existir en el form Editar.');
    }

    public function test_crear_equipo_con_receptor_y_datos_administrativos_guarda_correctamente(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(AdminDashboard::class)
            ->callAction('registrarEquipo', [
                'tipo_equipo' => 'lap',
                'num_serie' => 'SN-LAP-REC-001',
                'modelo' => 'Latitude 5420',
                'especificacion_texto' => 'Funcional',
                'observaciones_generales' => 'Equipo asignado a laboratorio',
                'estado' => 'Asignado',
                'tipo_propiedad' => 'Propio',
                'departamento_id' => $this->depto->id_departamento,
                'area_id' => $this->area->id_area,
                'aula' => 'E204',
                'id_receptor' => $this->receptor1->id_receptor,
                'ram' => '16 GB DDR4',
                'procesador' => 'Intel Core i7-1165G7',
                'disco' => '512 GB NVMe',
                'sistema_operativo' => 'Windows 11 Pro',
                'ip_address' => '192.168.10.45',
                'mac_address' => 'AA:BB:CC:11:22:33',
                'num_factura' => 'FAC-2026-999',
                'id_proveedor' => $this->proveedor->id_proveedor,
                'fecha_factura' => '2026-02-15',
                'fecha_registro' => '2026-02-16',
                'garantia_estado' => 'vigente',
                'especificaciones' => [
                    'marca' => 'Dell',
                ],
            ])
            ->assertHasNoActionErrors();

        $inv = Inventario::where('num_serie', 'SN-LAP-REC-001')->first();
        $this->assertNotNull($inv);

        // Receptor guardado en especificaciones y accesible por relación belongsTo
        $this->assertSame($this->receptor1->id_receptor, $inv->id_receptor);
        $this->assertSame('Carlos Ramírez Soto', $inv->receptor_nombre);
        $this->assertSame($this->receptor1->id_receptor, $inv->especificaciones['id_receptor'] ?? null);
        $this->assertSame('Carlos Ramírez Soto', $inv->especificaciones['receptor_nombre'] ?? null);
        $this->assertNotNull($inv->receptor);
        $this->assertTrue($inv->receptor->is($this->receptor1));

        // Especificaciones técnicas y datos administrativos guardados
        $this->assertSame('16 GB DDR4', $inv->ram);
        $this->assertSame('Intel Core i7-1165G7', $inv->procesador);
        $this->assertSame('192.168.10.45', $inv->ip_address);
        $this->assertSame('FAC-2026-999', $inv->num_factura);
        $this->assertSame($this->proveedor->id_proveedor, $inv->id_proveedor);
        $this->assertSame('E204', $inv->aula);
    }

    public function test_crear_pc_completa_propaga_receptor_a_todos_los_componentes(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $cpu = InventarioResource::registrarMultiplesEquipos([
            'tipo_equipo' => 'pc',
            'estacion' => 'EST-REC-77',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'aula' => 'L102',
            'id_receptor' => $this->receptor1->id_receptor,
            'num_serie' => 'SN-CPU-REC-77',
            'modelo' => 'OptiPlex 7090',
            'especificaciones' => [
                'cpu_marca' => 'Dell',
                'monitor_sn' => 'SN-MON-REC-77',
                'monitor_marca' => 'Dell',
                'teclado_sn' => 'SN-TEC-REC-77',
                'teclado_marca' => 'Dell',
                'mouse_sn' => 'SN-MOU-REC-77',
                'mouse_marca' => 'Dell',
            ],
        ]);

        $componentes = Inventario::where('estacion', 'EST-REC-77')->get();
        $this->assertCount(4, $componentes);

        foreach ($componentes as $comp) {
            $this->assertSame($this->receptor1->id_receptor, $comp->id_receptor);
            $this->assertSame('Carlos Ramírez Soto', $comp->receptor_nombre);
        }
    }

    public function test_editar_equipo_desde_dashboard_hidrata_y_actualiza_receptor_y_secciones(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $inv = Inventario::create([
            'id_producto' => $this->material->id_producto,
            'id_usuario' => $this->adminUser->id,
            'codigo' => 'DELL-5420-001',
            'num_serie' => 'SN-EDIT-REC-01',
            'subtipo' => 'Laptop',
            'modelo' => 'Latitude 5420',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'aula' => 'E101',
            'estado' => 'Asignado',
            'tipo_propiedad' => 'Propio',
            'fecha_registro' => now(),
            'especificaciones' => [
                'tipo_equipo' => 'lap',
                'marca' => 'Dell',
                'id_receptor' => $this->receptor1->id_receptor,
                'receptor_nombre' => $this->receptor1->nombre_completo,
            ],
        ]);

        // Hidratación correcta (sin '—' en campos vacíos y con id_receptor hidratado)
        $hidratado = InventarioResource::hidratarDatosEdicion([], $inv);
        $this->assertSame($this->receptor1->id_receptor, $hidratado['id_receptor']);
        $this->assertSame($this->depto->id_departamento, $hidratado['departamento_id']);
        $this->assertSame('', $hidratado['ram']);

        Livewire::test(AdminDashboard::class)
            ->callAction('editarEquipo', [
                'nombre_equipo' => 'Latitude 5420 Pro',
                'marca' => 'Dell',
                'modelo' => '5420 Pro',
                'num_serie' => 'SN-EDIT-REC-01',
                'estado' => 'Asignado',
                'tipo_propiedad' => 'Propio',
                'departamento_id' => $this->depto->id_departamento,
                'area_id' => $this->area->id_area,
                'aula' => 'E305',
                'id_receptor' => $this->receptor2->id_receptor,
                'ram' => '32 GB DDR4',
                'num_factura' => 'FAC-EDIT-555',
                'id_proveedor' => $this->proveedor->id_proveedor,
            ], ['id' => $inv->id_inventario, 'record' => $inv->id_inventario])
            ->assertHasNoActionErrors();

        $fresh = $inv->fresh();
        $this->assertSame($this->receptor2->id_receptor, $fresh->id_receptor);
        $this->assertSame('Elena Morales Cruz', $fresh->receptor_nombre);
        $this->assertTrue($fresh->receptor->is($this->receptor2));
        $this->assertSame('E305', $fresh->aula);
        $this->assertSame('32 GB DDR4', $fresh->ram);
        $this->assertSame('FAC-EDIT-555', $fresh->num_factura);
    }

    public function test_editar_equipo_permite_quitar_receptor_opcional(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $inv = Inventario::create([
            'id_producto' => $this->material->id_producto,
            'id_usuario' => $this->adminUser->id,
            'codigo' => 'DELL-5420-002',
            'num_serie' => 'SN-CLEAR-REC-02',
            'subtipo' => 'Laptop',
            'modelo' => 'Latitude 5420',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'estado' => 'Disponible',
            'tipo_propiedad' => 'Propio',
            'fecha_registro' => now(),
            'id_receptor' => $this->receptor1->id_receptor,
        ]);

        $this->assertSame($this->receptor1->id_receptor, $inv->fresh()->id_receptor);

        InventarioResource::guardarCambiosEquipo($inv, [
            'id_receptor' => null,
        ]);

        $fresh = $inv->fresh();
        $this->assertNull($fresh->id_receptor);
        $this->assertNull($fresh->receptor);
        $this->assertArrayNotHasKey('id_receptor', $fresh->especificaciones ?? []);
        $this->assertArrayNotHasKey('receptor_nombre', $fresh->especificaciones ?? []);
    }
}

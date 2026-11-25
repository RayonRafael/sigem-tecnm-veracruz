<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\InventarioResource\Schemas\CreateFormSchema;
use App\Filament\Resources\InventarioResource\Schemas\EditFormTabs;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Components\Field;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Fase3UxFormulariosTest extends TestCase
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

    /**
     * @param  array<int, mixed>  $components
     * @param  array<int, string>  $fieldNames
     */
    private function recolectarOrdenCampos(array $components, array &$fieldNames): void
    {
        foreach ($components as $component) {
            if ($component instanceof Field) {
                $fieldNames[] = $component->getName();
            }
            if (method_exists($component, 'getChildComponents')) {
                $this->recolectarOrdenCampos($component->getChildComponents(), $fieldNames);
            }
        }
    }

    public function test_campo_estado_esta_antes_de_ubicacion_en_crear_y_editar(): void
    {
        $livewire = new Dashboard();

        // 1. Formulario Crear (CreateFormSchema)
        $createFields = [];
        $createContainer = ComponentContainer::make($livewire)->components(CreateFormSchema::make());
        $this->recolectarOrdenCampos($createContainer->getComponents(withHidden: true), $createFields);

        $posEstadoCreate = array_search('estado', $createFields, true);
        $posPropCreate = array_search('tipo_propiedad', $createFields, true);
        $posDeptoCreate = array_search('departamento_id', $createFields, true);
        $posAreaCreate = array_search('area_id', $createFields, true);

        $this->assertNotFalse($posEstadoCreate);
        $this->assertNotFalse($posPropCreate);
        $this->assertNotFalse($posDeptoCreate);
        $this->assertNotFalse($posAreaCreate);
        $this->assertLessThan($posDeptoCreate, $posEstadoCreate, 'En Crear, estado debe aparecer antes de Ubicación (departamento_id).');
        $this->assertLessThan($posAreaCreate, $posEstadoCreate, 'En Crear, estado debe aparecer antes de area_id.');
        $this->assertLessThan($posDeptoCreate, $posPropCreate, 'En Crear, tipo_propiedad debe aparecer antes de Ubicación.');

        // 2. Formulario Editar (EditFormTabs)
        $editFields = [];
        $editContainer = ComponentContainer::make($livewire)->components(EditFormTabs::make());
        $this->recolectarOrdenCampos($editContainer->getComponents(withHidden: true), $editFields);

        $posMarcaEdit = array_search('marca', $editFields, true);
        $posEstadoEdit = array_search('estado', $editFields, true);
        $posPropEdit = array_search('tipo_propiedad', $editFields, true);
        $posDeptoEdit = array_search('departamento_id', $editFields, true);
        $posAreaEdit = array_search('area_id', $editFields, true);

        $this->assertNotFalse($posMarcaEdit);
        $this->assertNotFalse($posEstadoEdit);
        $this->assertNotFalse($posPropEdit);
        $this->assertNotFalse($posDeptoEdit);
        $this->assertNotFalse($posAreaEdit);
        $this->assertGreaterThan($posMarcaEdit, $posEstadoEdit);
        $this->assertLessThan($posDeptoEdit, $posEstadoEdit, 'En Editar, estado debe aparecer antes de Ubicación (departamento_id).');
        $this->assertLessThan($posAreaEdit, $posEstadoEdit, 'En Editar, estado debe aparecer antes de area_id.');
        $this->assertLessThan($posDeptoEdit, $posPropEdit, 'En Editar, tipo_propiedad debe aparecer antes de Ubicación.');
    }

    public function test_css_choices_dropdown_muestra_texto_completo_sin_truncar(): void
    {
        $baseScss = file_get_contents(public_path('css/scss/_base.scss'));
        $modalsScss = file_get_contents(public_path('css/scss/_modals.scss'));
        $formsScss = file_get_contents(public_path('css/scss/_forms.scss'));
        $compiledCss = file_get_contents(public_path('css/sigem-theme.css'));

        foreach ([$baseScss, $modalsScss, $formsScss] as $scss) {
            $this->assertStringContainsString('white-space: normal !important;', $scss);
            $this->assertStringContainsString('overflow: visible !important;', $scss);
            $this->assertStringContainsString('text-overflow: clip !important;', $scss);
        }

        // CSS compilado contiene las reglas para desplegable Choices.js sin truncado
        $this->assertStringContainsString('.choices__list--dropdown .choices__item', $compiledCss);
        $this->assertStringContainsString('.choices__item--choice{white-space:normal !important;overflow:visible !important;text-overflow:clip !important', $compiledCss);
    }

    public function test_js_tooltips_selects_choices_presente_y_completo(): void
    {
        $js = file_get_contents(public_path('js/sigem-titles.js'));

        $this->assertStringContainsString('applySelectTooltips', $js);
        $this->assertStringContainsString('.choices__list--single .choices__item', $js);
        $this->assertStringContainsString('.choices__list--dropdown .choices__item--choice', $js);
        $this->assertStringContainsString("setAttribute('title'", $js);
        $this->assertStringContainsString('MutationObserver', $js);
    }

    public function test_selects_encadenados_con_nombres_largos_funcionan_en_crear_y_editar(): void
    {
        $deptoMetal = Departamento::create(['nombre' => 'Departamento de Ingeniería Metal-Mecánica']);
        $deptoInd = Departamento::create(['nombre' => 'Departamento de Ingeniería Industrial']);
        $areaMetal = Area::create([
            'nombre' => 'Área Docente y Talleres Pesados de Manufactura',
            'id_departamento' => $deptoMetal->id_departamento,
        ]);
        $areaInd = Area::create([
            'nombre' => 'Proyectos de Vinculación e Investigación Industrial',
            'id_departamento' => $deptoInd->id_departamento,
        ]);

        $unidad = UnidadMedida::create(['nombre' => 'Pieza']);
        $marca = MarcaMaterial::create(['nombre' => 'Dell']);
        $tipo = TipoMaterial::create(['nombre' => 'Cómputo']);
        Material::create([
            'nombre' => 'OptiPlex 7090',
            'id_unidad' => $unidad->id_unidad,
            'id_marca' => $marca->id_marca,
            'id_tipodematerial' => $tipo->id_tipodematerial,
            'requiere_control_individual' => true,
        ]);

        // Crear equipo usando departamento + área con nombres largos
        Livewire::actingAs($this->admin)
            ->test(Dashboard::class)
            ->callAction('registrarEquipo', [
                'tipo_equipo' => 'lap',
                'estado' => 'Asignado',
                'tipo_propiedad' => 'Propio',
                'num_serie' => 'UX-LONG-001',
                'especificaciones' => ['marca' => 'Dell'],
                'modelo' => 'Latitude 5420',
                'departamento_id' => $deptoMetal->id_departamento,
                'area_id' => $areaMetal->id_area,
                'fecha_registro' => now()->format('Y-m-d'),
            ])
            ->assertHasNoActionErrors();

        $inv = Inventario::where('num_serie', 'UX-LONG-001')->first();
        $this->assertNotNull($inv);
        $this->assertSame($deptoMetal->id_departamento, $inv->departamento_id);
        $this->assertSame($areaMetal->id_area, $inv->area_id);
        $this->assertSame('Asignado', $inv->estado);

        // Editar equipo cambiando al segundo departamento + área encadenados
        Livewire::actingAs($this->admin)
            ->test(Dashboard::class)
            ->mountAction('editarEquipo', ['id' => $inv->id_inventario, 'record' => $inv->id_inventario])
            ->setActionData([
                'estado' => 'Disponible',
                'departamento_id' => $deptoInd->id_departamento,
                'area_id' => $areaInd->id_area,
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $inv->refresh();
        $this->assertSame('Disponible', $inv->estado);
        $this->assertSame($deptoInd->id_departamento, $inv->departamento_id);
        $this->assertSame($areaInd->id_area, $inv->area_id);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Filament\Pages\ConfiguracionPage;
use App\Filament\Pages\Dashboard;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\Receptor;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use App\Support\SigemUniqueValidator;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Fase5ValidacionesRefuerzoTest extends TestCase
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

    private function crearAreaAutocad(): array
    {
        $depto = Departamento::create(['nombre' => 'Departamento']);
        $area = Area::create(['nombre' => 'AUTOCAD', 'id_departamento' => $depto->id_departamento]);

        return [$depto, $area];
    }

    private function crearReceptorRoberto(): array
    {
        $depto = Departamento::create(['nombre' => 'Depto Base']);
        $area = Area::create(['nombre' => 'Área Base', 'id_departamento' => $depto->id_departamento]);
        $rec = Receptor::create([
            'nombre' => 'Roberto',
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => 'García',
            'id_area' => $area->id_area,
        ]);

        return [$depto, $area, $rec];
    }

    private function crearInventarioBase(): array
    {
        $depto = Departamento::create(['nombre' => 'Sistemas']);
        $area = Area::create(['nombre' => 'Laboratorio', 'id_departamento' => $depto->id_departamento]);
        $unidad = UnidadMedida::create(['nombre' => 'Pieza']);
        $marca = MarcaMaterial::create(['nombre' => 'Dell']);
        $tipo = TipoMaterial::create(['nombre' => 'Cómputo']);
        $material = Material::create([
            'nombre' => 'Vostro 3470',
            'id_unidad' => $unidad->id_unidad,
            'id_marca' => $marca->id_marca,
            'id_tipodematerial' => $tipo->id_tipodematerial,
            'requiere_control_individual' => true,
        ]);

        $inv = Inventario::create([
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

        return [$depto, $area, $marca, $inv];
    }

    // =========================================================================
    // CATÁLOGOS: Casos #1 a #13 + "autoca"
    // =========================================================================

    public function test_01_catalogo_exacto_autocad_bloquea(): void
    {
        $this->crearAreaAutocad();
        $res = SigemUniqueValidator::analizarCatalogo(Area::class, 'nombre', 'AUTOCAD', 'un área');
        $this->assertSame(SigemUniqueValidator::ACCION_BLOQUEAR, $res['accion']);
        $this->assertSame(1, $res['nivel']);
    }

    public function test_02_catalogo_minusculas_autocad_bloquea(): void
    {
        $this->crearAreaAutocad();
        $res = SigemUniqueValidator::analizarCatalogo(Area::class, 'nombre', 'autocad', 'un área');
        $this->assertSame(SigemUniqueValidator::ACCION_BLOQUEAR, $res['accion']);
        $this->assertSame(1, $res['nivel']);
    }

    public function test_03_catalogo_con_acento_autocad_bloquea(): void
    {
        $this->crearAreaAutocad();
        $res = SigemUniqueValidator::analizarCatalogo(Area::class, 'nombre', 'ÁUTOCAD', 'un área');
        $this->assertSame(SigemUniqueValidator::ACCION_BLOQUEAR, $res['accion']);
        $this->assertSame(1, $res['nivel']);
    }

    public function test_04_catalogo_con_espacios_laterales_autocad_bloquea(): void
    {
        $this->crearAreaAutocad();
        $res = SigemUniqueValidator::analizarCatalogo(Area::class, 'nombre', '  AUTOCAD  ', 'un área');
        $this->assertSame(SigemUniqueValidator::ACCION_BLOQUEAR, $res['accion']);
        $this->assertSame(1, $res['nivel']);
    }

    public function test_05_catalogo_espacios_multiples_y_puntos_bloquea(): void
    {
        Departamento::create(['nombre' => 'AUT O CAD']);
        Departamento::create(['nombre' => 'Ing.']);

        $resEspacios = SigemUniqueValidator::analizarCatalogo(Departamento::class, 'nombre', 'AUT  O  CAD', 'un departamento');
        $this->assertSame(SigemUniqueValidator::ACCION_BLOQUEAR, $resEspacios['accion']);
        $this->assertSame(1, $resEspacios['nivel']);

        $resPuntos = SigemUniqueValidator::analizarCatalogo(Departamento::class, 'nombre', 'Ing', 'un departamento');
        $this->assertSame(SigemUniqueValidator::ACCION_BLOQUEAR, $resPuntos['accion']);
        $this->assertSame(1, $resPuntos['nivel']);
    }

    public function test_06_catalogo_typo_autccad_advierte(): void
    {
        $this->crearAreaAutocad();
        $res = SigemUniqueValidator::analizarCatalogo(Area::class, 'nombre', 'AUTCCAD', 'un área');
        $this->assertSame(SigemUniqueValidator::ACCION_ADVERTIR, $res['accion']);
        $this->assertSame(2, $res['nivel']);
        $this->assertStringContainsString('AUTOCAD', (string) $res['titulo']);
    }

    public function test_07_catalogo_typo_autocadd_y_autoca_advierten(): void
    {
        $this->crearAreaAutocad();

        $res1 = SigemUniqueValidator::analizarCatalogo(Area::class, 'nombre', 'AUTOCADD', 'un área');
        $this->assertSame(SigemUniqueValidator::ACCION_ADVERTIR, $res1['accion']);
        $this->assertSame(2, $res1['nivel']);

        $res2 = SigemUniqueValidator::analizarCatalogo(Area::class, 'nombre', 'autoca', 'un área');
        $this->assertSame(SigemUniqueValidator::ACCION_ADVERTIR, $res2['accion']);
        $this->assertSame(2, $res2['nivel']);
        $this->assertStringContainsString('AUTOCAD', (string) $res2['titulo']);
    }

    public function test_08_catalogo_prefijo_autoc_sugiere_autocad(): void
    {
        $this->crearAreaAutocad();
        $res = SigemUniqueValidator::analizarCatalogo(Area::class, 'nombre', 'AUTOC', 'un área');
        $this->assertSame(SigemUniqueValidator::ACCION_SUGERIR, $res['accion']);
        $this->assertSame(3, $res['nivel']);
        $this->assertSame('AUTOCAD', $res['coincidencias'][0]['nombre']);
    }

    public function test_09_catalogo_abreviacion_depto_sugiere_departamento(): void
    {
        Departamento::create(['nombre' => 'Departamento']);
        $res = SigemUniqueValidator::analizarCatalogo(Departamento::class, 'nombre', 'depto', 'un departamento');
        $this->assertSame(SigemUniqueValidator::ACCION_SUGERIR, $res['accion']);
        $this->assertSame(3, $res['nivel']);
        $this->assertSame('Departamento', $res['coincidencias'][0]['nombre']);
    }

    public function test_10_catalogo_iniciales_disc_sugiere_sistemas(): void
    {
        Departamento::create(['nombre' => 'Depto de Ing en Sistemas y Comp']);

        $res1 = SigemUniqueValidator::analizarCatalogo(Departamento::class, 'nombre', 'DISC', 'un departamento');
        $this->assertSame(SigemUniqueValidator::ACCION_SUGERIR, $res1['accion']);
        $this->assertSame(3, $res1['nivel']);

        $res2 = SigemUniqueValidator::analizarCatalogo(Departamento::class, 'nombre', 'D.I.S.C.', 'un departamento');
        $this->assertSame(SigemUniqueValidator::ACCION_SUGERIR, $res2['accion']);
        $this->assertSame(3, $res2['nivel']);
    }

    public function test_11_catalogo_mecatronica_vs_metal_mecanica_advierte(): void
    {
        Departamento::create(['nombre' => 'Metal-Mecánica']);
        $res = SigemUniqueValidator::analizarCatalogo(Departamento::class, 'nombre', 'Mecatronica', 'un departamento');
        $this->assertSame(SigemUniqueValidator::ACCION_ADVERTIR, $res['accion']);
        $this->assertSame('Metal-Mecánica', $res['coincidencias'][0]['nombre']);
    }

    public function test_12_catalogo_diferente_inventario_permite(): void
    {
        $this->crearAreaAutocad();
        $res = SigemUniqueValidator::analizarCatalogo(Area::class, 'nombre', 'inventario', 'un área');
        $this->assertSame(SigemUniqueValidator::ACCION_PERMITIR, $res['accion']);
        $this->assertSame(0, $res['nivel']);
    }

    public function test_13_catalogo_vacio_bloquea_por_requerido(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ConfiguracionPage::class)
            ->callAction('crearDepartamento', ['nombre' => ''])
            ->assertHasActionErrors(['nombre' => 'required']);
    }

    // =========================================================================
    // PERSONAS (Receptor): Casos #14 a #21
    // =========================================================================

    public function test_14_persona_mismo_nombre_completo_bloquea(): void
    {
        $this->crearReceptorRoberto();
        $res = SigemUniqueValidator::analizarReceptor('Roberto', 'Pérez', 'García');
        $this->assertSame(SigemUniqueValidator::ACCION_BLOQUEAR, $res['accion']);
        $this->assertSame(1, $res['nivel']);
    }

    public function test_15_persona_sin_acentos_bloquea(): void
    {
        $this->crearReceptorRoberto();
        $res = SigemUniqueValidator::analizarReceptor('Roberto', 'Perez', 'Garcia');
        $this->assertSame(SigemUniqueValidator::ACCION_BLOQUEAR, $res['accion']);
        $this->assertSame(1, $res['nivel']);
    }

    public function test_16_persona_typo_apellido_garza_advierte(): void
    {
        $this->crearReceptorRoberto();
        $res = SigemUniqueValidator::analizarReceptor('Roberto', 'Pérez', 'Garza');
        $this->assertSame(SigemUniqueValidator::ACCION_ADVERTIR, $res['accion']);
        $this->assertSame(2, $res['nivel']);
        $this->assertStringContainsString('Roberto Pérez García', (string) $res['titulo']);
    }

    public function test_17_persona_nombre_incompleto_sin_materno_advierte(): void
    {
        $this->crearReceptorRoberto();
        $res = SigemUniqueValidator::analizarReceptor('Roberto', 'Pérez', '');
        $this->assertSame(SigemUniqueValidator::ACCION_ADVERTIR, $res['accion']);
        $this->assertSame('4a', $res['nivel']);
    }

    public function test_18_persona_abreviacion_inicial_r_advierte(): void
    {
        $this->crearReceptorRoberto();
        $res = SigemUniqueValidator::analizarReceptor('R.', 'Pérez', 'García');
        $this->assertSame(SigemUniqueValidator::ACCION_ADVERTIR, $res['accion']);
        $this->assertSame('4b', $res['nivel']);
        $this->assertStringContainsString('Roberto Pérez García', (string) $res['titulo']);
    }

    public function test_19_persona_solo_apellidos_sin_nombre_advierte(): void
    {
        $this->crearReceptorRoberto();
        $res = SigemUniqueValidator::analizarReceptor('', 'Pérez', 'García');
        $this->assertSame(SigemUniqueValidator::ACCION_ADVERTIR, $res['accion']);
        $this->assertSame('4a', $res['nivel']);
    }

    public function test_20_persona_diferente_juan_lopez_permite(): void
    {
        $this->crearReceptorRoberto();
        $res = SigemUniqueValidator::analizarReceptor('Juan', 'López', 'Martínez');
        $this->assertSame(SigemUniqueValidator::ACCION_PERMITIR, $res['accion']);
        $this->assertSame(0, $res['nivel']);
    }

    public function test_21_persona_completa_vs_existente_sin_materno_advierte(): void
    {
        $depto = Departamento::create(['nombre' => 'Depto 21']);
        $area = Area::create(['nombre' => 'Área 21', 'id_departamento' => $depto->id_departamento]);
        Receptor::create([
            'nombre' => 'Roberto',
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => '',
            'id_area' => $area->id_area,
        ]);

        $res = SigemUniqueValidator::analizarReceptor('Roberto', 'Perez', 'Garcia');
        $this->assertSame(SigemUniqueValidator::ACCION_ADVERTIR, $res['accion']);
        $this->assertSame('4a', $res['nivel']);
    }

    // =========================================================================
    // S/N DE INVENTARIO: Casos #22 a #25
    // =========================================================================

    public function test_22_sn_exacto_duplicado_bloquea(): void
    {
        $this->crearInventarioBase();
        $res = SigemUniqueValidator::analizarSerieInventario('24J29Z2');
        $this->assertSame(SigemUniqueValidator::ACCION_BLOQUEAR, $res['accion']);
        $this->assertSame(1, $res['nivel']);
        $this->assertStringContainsString('DELL-VOSTRO3470-014', (string) $res['mensaje']);
    }

    public function test_23_sn_minusculas_duplicado_bloquea(): void
    {
        $this->crearInventarioBase();
        $res = SigemUniqueValidator::analizarSerieInventario('24j29z2');
        $this->assertSame(SigemUniqueValidator::ACCION_BLOQUEAR, $res['accion']);
        $this->assertSame(1, $res['nivel']);
    }

    public function test_24_sn_similar_levenshtein_1_advierte(): void
    {
        $this->crearInventarioBase();
        $res = SigemUniqueValidator::analizarSerieInventario('24J29Z3');
        $this->assertSame(SigemUniqueValidator::ACCION_ADVERTIR, $res['accion']);
        $this->assertSame(2, $res['nivel']);
        $this->assertStringContainsString('24J29Z2', (string) $res['titulo']);
    }

    public function test_25_sn_vacio_se_permite(): void
    {
        $this->crearInventarioBase();
        $res = SigemUniqueValidator::analizarSerieInventario('');
        $this->assertSame(SigemUniqueValidator::ACCION_PERMITIR, $res['accion']);
        $this->assertSame(0, $res['nivel']);
    }

    // =========================================================================
    // INTEGRACIÓN LIVEWIRE + UX NO-BLOQUEANTE + CSS
    // =========================================================================

    public function test_26_livewire_catalogos_bloquea_nivel1_y_advierte_sin_bloquear_nivel2_y_3(): void
    {
        [$depto] = $this->crearAreaAutocad();

        // Nivel 1 (con acento): BLOQUEADO
        Livewire::actingAs($this->admin)
            ->test(ConfiguracionPage::class)
            ->callAction('crearArea', [
                'nombre' => 'ÁUTOCAD',
                'id_departamento' => $depto->id_departamento,
            ])
            ->assertHasActionErrors(['nombre']);

        // Nivel 2 (typo "AUTCCAD"): muestra banner .sigem-sim-alert.is-warn y PERMITE guardar de todas formas
        $cfgWarn = Livewire::actingAs($this->admin)
            ->test(ConfiguracionPage::class)
            ->mountAction('crearArea')
            ->setActionData([
                'nombre' => 'AUTCCAD',
                'id_departamento' => $depto->id_departamento,
            ])
            ->assertSee('sigem-sim-alert is-warn', false)
            ->assertSee('AUTOCAD')
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('area', ['nombre' => 'AUTCCAD']);
        $this->assertNotNull($cfgWarn);

        // Nivel 3 (abreviación "depto"): muestra sugerencia .sigem-sim-alert.is-suggest y PERMITE guardar si confirma
        Livewire::actingAs($this->admin)
            ->test(ConfiguracionPage::class)
            ->mountAction('crearDepartamento')
            ->setActionData(['nombre' => 'depto'])
            ->assertSee('sigem-sim-alert is-suggest', false)
            ->assertSee('Departamento')
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('departamento', ['nombre' => 'depto']);
    }

    public function test_27_livewire_receptor_bloquea_sin_acentos_y_advierte_abreviacion_sin_bloquear(): void
    {
        [, $area] = $this->crearReceptorRoberto();

        // Nivel 1: sin acentos -> BLOQUEADO
        Livewire::actingAs($this->admin)
            ->test(ConfiguracionPage::class)
            ->callAction('crearReceptor', [
                'nombre' => 'Roberto',
                'apellido_paterno' => 'Perez',
                'apellido_materno' => 'Garcia',
                'id_area' => $area->id_area,
            ])
            ->assertHasActionErrors(['nombre']);

        // Nivel 4b: "R." -> ADVERTENCIA visible, pero NO bloquea si el usuario guarda
        Livewire::actingAs($this->admin)
            ->test(ConfiguracionPage::class)
            ->mountAction('crearReceptor')
            ->setActionData([
                'nombre' => 'R.',
                'apellido_paterno' => 'Pérez',
                'apellido_materno' => 'García',
                'id_area' => $area->id_area,
            ])
            ->assertSee('sigem-sim-alert is-warn', false)
            ->assertSee('Roberto Pérez García')
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertSame(2, Receptor::count());
    }

    public function test_28_livewire_inventario_sn_similar_advierte_sin_bloquear_y_css_compilado(): void
    {
        [$depto, $area, $marca] = $this->crearInventarioBase();

        // Nivel 2 en Dashboard registrarEquipo: 24J29Z3 advierte sobre 24J29Z2 pero permite guardar
        Livewire::actingAs($this->admin)
            ->test(Dashboard::class)
            ->mountAction('registrarEquipo')
            ->setActionData([
                'tipo_equipo' => 'lap',
                'estado' => 'Disponible',
                'tipo_propiedad' => 'Propio',
                'num_serie' => '24J29Z3',
                'especificaciones' => ['marca' => $marca->nombre],
                'modelo' => 'Vostro 3470',
                'departamento_id' => $depto->id_departamento,
                'area_id' => $area->id_area,
                'fecha_registro' => now()->format('Y-m-d'),
            ])
            ->assertSee('sigem-sim-alert is-warn', false)
            ->assertSee('24J29Z2')
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('inventario', ['num_serie' => '24J29Z3']);

        $css = file_get_contents(public_path('css/sigem-theme.css'));
        $this->assertStringContainsString('.sigem-sim-alert', $css);
        $this->assertStringContainsString('.sigem-sim-alert.is-warn', $css);
        $this->assertStringContainsString('.sigem-sim-alert.is-suggest', $css);
    }
}

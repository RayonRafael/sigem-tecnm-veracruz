<?php

namespace Tests\Feature\Inventario;

use App\Filament\Pages\Dashboard as AdminDashboard;
use App\Filament\Pages\ReportesPage;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use App\Support\SigemReportExporter;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FiltrosEncadenadosYSeleccionPaginaTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Departamento $deptoSistemas;
    protected Departamento $deptoIndustrial;
    protected Area $areaDesarrollo;
    protected Area $areaRedes;
    protected Area $areaTaller;
    protected Material $material;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->adminUser = User::where('email', 'admin@tecnm.edu.mx')->first();

        $this->deptoSistemas = Departamento::firstOrCreate(['nombre' => 'Depto Sistemas']);
        $this->deptoIndustrial = Departamento::firstOrCreate(['nombre' => 'Depto Industrial']);

        $this->areaDesarrollo = Area::firstOrCreate([
            'nombre' => 'Desarrollo',
            'id_departamento' => $this->deptoSistemas->id_departamento,
        ]);
        $this->areaRedes = Area::firstOrCreate([
            'nombre' => 'Redes',
            'id_departamento' => $this->deptoSistemas->id_departamento,
        ]);
        $this->areaTaller = Area::firstOrCreate([
            'nombre' => 'Taller',
            'id_departamento' => $this->deptoIndustrial->id_departamento,
        ]);

        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'Dell']);
        $tipo = TipoMaterial::firstOrCreate(['nombre' => 'Computadora']);
        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pieza']);

        $this->material = Material::firstOrCreate(
            ['nombre' => 'OptiPlex 7090'],
            [
                'modelo' => '7090',
                'id_marca' => $marca->id_marca,
                'id_tipodematerial' => $tipo->id_tipodematerial,
                'id_unidad' => $unidad->id_unidad,
                'stock_actual' => 20,
                'stock_minimo' => 1,
                'requiere_control_individual' => 1,
            ]
        );
    }

    protected function crearEquipo(array $overrides = []): Inventario
    {
        return Inventario::create(array_merge([
            'id_producto' => $this->material->id_producto,
            'id_usuario' => $this->adminUser->id,
            'codigo' => 'FILT-' . strtoupper(substr(uniqid(), -6)),
            'num_serie' => 'SN-' . strtoupper(substr(uniqid(), -6)),
            'subtipo' => 'CPU',
            'modelo' => '7090',
            'departamento_id' => $this->deptoSistemas->id_departamento,
            'area_id' => $this->areaDesarrollo->id_area,
            'aula' => 'D201',
            'estado' => 'Disponible',
            'tipo_propiedad' => 'Propio',
            'fecha_registro' => now(),
        ], $overrides));
    }

    public function test_filtros_encadenados_dashboard_depto_area_aula_y_limpieza_en_cascada(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $eq1 = $this->crearEquipo(['departamento_id' => $this->deptoSistemas->id_departamento, 'area_id' => $this->areaDesarrollo->id_area, 'aula' => 'D201']);
        $this->crearEquipo(['departamento_id' => $this->deptoSistemas->id_departamento, 'area_id' => $this->areaDesarrollo->id_area, 'aula' => 'D202']);
        $this->crearEquipo(['departamento_id' => $this->deptoSistemas->id_departamento, 'area_id' => $this->areaRedes->id_area, 'aula' => 'R301']);
        $this->crearEquipo(['departamento_id' => $this->deptoIndustrial->id_departamento, 'area_id' => $this->areaTaller->id_area, 'aula' => 'T201']);

        $component = Livewire::test(AdminDashboard::class);

        // 1. Seleccionar Departamento = Depto Sistemas
        $component->set('tableColumnSearches.departamento', 'Depto Sistemas');
        $areas = $component->instance()->areasFiltradas;
        $this->assertArrayHasKey('Desarrollo', $areas);
        $this->assertArrayHasKey('Redes', $areas);
        $this->assertArrayNotHasKey('Taller', $areas);

        $aulasDepto = $component->instance()->aulasFiltradas;
        $this->assertArrayHasKey('D201', $aulasDepto);
        $this->assertArrayHasKey('D202', $aulasDepto);
        $this->assertArrayHasKey('R301', $aulasDepto);
        $this->assertArrayNotHasKey('T201', $aulasDepto);

        // 2. Seleccionar Área = Desarrollo -> Aula solo muestra D201 y D202
        $component->set('tableColumnSearches.area', 'Desarrollo');
        $aulasArea = $component->instance()->aulasFiltradas;
        $this->assertArrayHasKey('D201', $aulasArea);
        $this->assertArrayHasKey('D202', $aulasArea);
        $this->assertArrayNotHasKey('R301', $aulasArea);
        $this->assertArrayNotHasKey('T201', $aulasArea);

        // 3. Seleccionar aula y una fila, luego cambiar Departamento -> limpia Área, Aula y selección
        $component->set('tableColumnSearches.aula', 'D201')
            ->set('selectedRows', [(string) $eq1->id_inventario])
            ->set('tableColumnSearches.departamento', 'Depto Industrial');

        $this->assertSame('', $component->get('tableColumnSearches.area'));
        $this->assertSame('', $component->get('tableColumnSearches.aula'));
        $this->assertEmpty($component->get('selectedRows'));

        $areasInd = $component->instance()->areasFiltradas;
        $this->assertArrayHasKey('Taller', $areasInd);
        $this->assertArrayNotHasKey('Desarrollo', $areasInd);

        // 4. Departamento = '' (TODOS) -> muestra todas las áreas y todas las aulas
        $component->set('tableColumnSearches.departamento', '');
        $todasAreas = $component->instance()->areasFiltradas;
        $this->assertArrayHasKey('Desarrollo', $todasAreas);
        $this->assertArrayHasKey('Redes', $todasAreas);
        $this->assertArrayHasKey('Taller', $todasAreas);
    }

    public function test_filtros_encadenados_reportes_depto_area_aula_y_limpieza(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->crearEquipo(['codigo' => 'REP-D201', 'departamento_id' => $this->deptoSistemas->id_departamento, 'area_id' => $this->areaDesarrollo->id_area, 'aula' => 'D201']);
        $this->crearEquipo(['codigo' => 'REP-R301', 'departamento_id' => $this->deptoSistemas->id_departamento, 'area_id' => $this->areaRedes->id_area, 'aula' => 'R301']);
        $this->crearEquipo(['codigo' => 'REP-T201', 'departamento_id' => $this->deptoIndustrial->id_departamento, 'area_id' => $this->areaTaller->id_area, 'aula' => 'T201']);

        $component = Livewire::test(ReportesPage::class)
            ->call('cambiarPestanaPrincipal', 'nuevos')
            ->set('filtroDepartamentoId', (string) $this->deptoSistemas->id_departamento);

        $areasRep = $component->instance()->areasParaFiltros;
        $this->assertContains('Desarrollo', $areasRep);
        $this->assertContains('Redes', $areasRep);
        $this->assertNotContains('Taller', $areasRep);

        // Seleccionar área Desarrollo -> aulas solo D201
        $component->set('filtroAreaId', (string) $this->areaDesarrollo->id_area);
        $aulasRep = $component->instance()->aulasParaFiltros;
        $this->assertContains('D201', $aulasRep);
        $this->assertNotContains('R301', $aulasRep);
        $this->assertNotContains('T201', $aulasRep);

        // Validar que el reporte filtra correctamente por área y aula
        $datosFiltrados = SigemReportExporter::getReportData('inventario_general', [
            'departamento_id' => (string) $this->deptoSistemas->id_departamento,
            'area_id' => (string) $this->areaDesarrollo->id_area,
            'aula' => 'D201',
        ]);
        $this->assertCount(1, $datosFiltrados['rows']);
        $this->assertSame('REP-D201', $datosFiltrados['rows'][0][0]);

        // Cambiar departamento limpia área y aula
        $component->set('filtroAula', 'D201')
            ->set('filtroDepartamentoId', (string) $this->deptoIndustrial->id_departamento);
        $this->assertSame('', $component->get('filtroAreaId'));
        $this->assertSame('', $component->get('filtroAula'));
    }

    public function test_filtros_encadenados_slide_over_cambio_masivo_limpia_area_y_aula(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $component = Livewire::test(AdminDashboard::class)
            ->set('cambioDepartamentoId', (string) $this->deptoSistemas->id_departamento)
            ->set('cambioNuevaAreaId', (string) $this->areaDesarrollo->id_area)
            ->set('cambioAula', 'D201');

        // Cambiar área limpia aula
        $component->set('cambioNuevaAreaId', (string) $this->areaRedes->id_area);
        $this->assertSame('', $component->get('cambioAula'));

        // Cambiar departamento limpia área y aula
        $component->set('cambioAula', 'R301')
            ->set('cambioDepartamentoId', (string) $this->deptoIndustrial->id_departamento);
        $this->assertSame('', $component->get('cambioNuevaAreaId'));
        $this->assertSame('', $component->get('cambioAula'));
    }

    public function test_seleccionar_todos_marca_solo_pagina_actual_no_todos_los_registros(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        // Crear 15 equipos (con perPage = 10 -> Página 1 tiene 10, Página 2 tiene 5)
        for ($i = 1; $i <= 15; $i++) {
            $this->crearEquipo(['codigo' => sprintf('PAG-%03d', $i)]);
        }

        $component = Livewire::test(AdminDashboard::class)
            ->set('perPage', 10);

        $idsPagina1 = $component->get('paginaActualIds');
        $this->assertCount(10, $idsPagina1);

        // Toggle seleccionar página marca SOLO los 10 de la página 1 (NO los 15 totales)
        $component->call('toggleSeleccionarPagina');
        $seleccionados = $component->get('selectedRows');
        $this->assertCount(10, $seleccionados);
        $this->assertEqualsCanonicalizing($idsPagina1, $seleccionados);
        $component->assertSee('10 equipos seleccionados')
            ->assertSee('(solo esta página)');

        // Segundo toggle deselecciona los 10 de la página 1
        $component->call('toggleSeleccionarPagina');
        $this->assertEmpty($component->get('selectedRows'));

        // Compatibilidad con selectAll = true: también selecciona solo los 10 de la página actual
        $component->set('selectAll', true);
        $this->assertCount(10, $component->get('selectedRows'));
    }

    public function test_cambiar_de_pagina_limpia_seleccion_activa(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        for ($i = 1; $i <= 15; $i++) {
            $this->crearEquipo(['codigo' => sprintf('PGC-%03d', $i)]);
        }

        $component = Livewire::test(AdminDashboard::class)
            ->set('perPage', 10)
            ->call('toggleSeleccionarPagina');

        $this->assertCount(10, $component->get('selectedRows'));

        // Cambiar a página 2 -> limpia la selección
        $component->call('setPage', 2);
        $this->assertEmpty($component->get('selectedRows'));
        $this->assertFalse($component->get('selectAll'));
        $this->assertFalse($component->get('showSidePanel'));

        // En página 2, seleccionar página marca SOLO los 5 registros de página 2
        $component->call('toggleSeleccionarPagina');
        $this->assertCount(5, $component->get('selectedRows'));
    }

    public function test_accion_masiva_aplica_solo_a_equipos_seleccionados_de_la_pagina_actual(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        for ($i = 1; $i <= 15; $i++) {
            $this->crearEquipo([
                'codigo' => sprintf('MAS-%03d', $i),
                'tipo_propiedad' => 'Propio',
            ]);
        }

        Livewire::test(AdminDashboard::class)
            ->set('perPage', 10)
            ->call('toggleSeleccionarPagina')
            ->callAction('cambioMasivo', [
                'nuevo_estado' => 'Cambio de propiedad',
                'nuevo_tipo_propiedad' => 'Rentado',
                'motivo' => 'Cambio masivo solo página 1',
            ])
            ->assertHasNoActionErrors();

        // Exactamente 10 registros cambiaron a Rentado; los 5 de página 2 quedaron Propio
        $this->assertSame(10, Inventario::where('tipo_propiedad', 'Rentado')->count());
        $this->assertSame(5, Inventario::where('tipo_propiedad', 'Propio')->count());
    }
}

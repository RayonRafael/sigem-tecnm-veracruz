<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use App\Services\ConfigCatalogosService;
use App\Services\InventarioDashboardQuery;
use App\Support\SigemLegacyReportBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Fase1SinDuplicadosDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function crearMaterialBase(): array
    {
        $user = User::factory()->create();
        $unidad = UnidadMedida::create(['nombre' => 'Pieza']);
        $marca = MarcaMaterial::create(['nombre' => 'Dell']);
        $tipo = TipoMaterial::create(['nombre' => 'Cómputo']);
        $material = Material::create([
            'nombre' => 'OptiPlex 7090',
            'id_unidad' => $unidad->id_unidad,
            'id_marca' => $marca->id_marca,
            'id_tipodematerial' => $tipo->id_tipodematerial,
            'stock_actual' => 10,
            'requiere_control_individual' => true,
        ]);

        return [$user, $material];
    }

    public function test_equipos_area_no_triplica_cuando_hay_areas_con_mismo_nombre_ni_por_ubicacion_fisica(): void
    {
        [$user, $material] = $this->crearMaterialBase();

        $depto = Departamento::create(['nombre' => 'Sistemas']);

        // Simular IDs de 6 dígitos tipo TiDB Cloud (AUTO_INCREMENT en bloques de 30,000)
        DB::table('area')->insert([
            ['id_area' => 30001, 'nombre' => 'AUTOCAD', 'id_departamento' => $depto->id_departamento, 'created_at' => now(), 'updated_at' => now()],
            ['id_area' => 120001, 'nombre' => 'AUTOCAD', 'id_departamento' => $depto->id_departamento, 'created_at' => now(), 'updated_at' => now()],
            ['id_area' => 120002, 'nombre' => 'AUTOCAD', 'id_departamento' => $depto->id_departamento, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 5 equipos asignados al área 30001 con ubicacion_fisica que contiene "AUTOCAD"
        for ($i = 1; $i <= 5; $i++) {
            Inventario::create([
                'num_serie' => "SN-AUTO-00{$i}",
                'codigo' => "DELL-OPT-00{$i}",
                'id_producto' => $material->id_producto,
                'id_usuario' => $user->id,
                'departamento_id' => $depto->id_departamento,
                'area_id' => 30001,
                'ubicacion_fisica' => 'Sistemas > AUTOCAD > Aula 1',
                'estado' => 'Disponible',
                'estado_registro' => 'Aprobado',
                'tipo_propiedad' => 'Propio',
                'fecha_registro' => now(),
            ]);
        }

        $reporte = SigemLegacyReportBuilder::generar('equipos_area', 'xlsx');
        $this->assertNotNull($reporte);

        $rowsAutocad = array_values(array_filter(
            $reporte['rows'],
            fn ($row) => ($row['area'] ?? '') === 'AUTOCAD'
        ));

        // Antes del fix: salían 3 filas de AUTOCAD con 5 + 5 + 5 = 15 (triplicado).
        // Con el fix: 1 sola fila con 5 equipos; total global nunca supera Inventario::count().
        $this->assertCount(1, $rowsAutocad);
        $this->assertSame(5, $rowsAutocad[0]['total']);

        $sumaTotalReporte = array_sum(array_column($reporte['rows'], 'total'));
        $this->assertSame(5, $sumaTotalReporte);
        $this->assertLessThanOrEqual(Inventario::count(), $sumaTotalReporte);
    }

    public function test_equipos_area_no_duplica_por_subcadena_y_mantiene_fallback_sin_area_id(): void
    {
        [$user, $material] = $this->crearMaterialBase();

        $depto = Departamento::create(['nombre' => 'Ciencias Básicas']);
        $areaCorta = Area::create(['nombre' => 'CAD', 'id_departamento' => $depto->id_departamento]);
        $areaLarga = Area::create(['nombre' => 'Aula CAD', 'id_departamento' => $depto->id_departamento]);

        // Equipo 1: asignado explícitamente a "Aula CAD"
        Inventario::create([
            'num_serie' => 'SN-CAD-001',
            'codigo' => 'DELL-CAD-001',
            'id_producto' => $material->id_producto,
            'id_usuario' => $user->id,
            'area_id' => $areaLarga->id_area,
            'ubicacion_fisica' => 'Ciencias Básicas > Aula CAD',
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'fecha_registro' => now(),
        ]);

        // Equipo 2: legacy sin area_id, solo ubicacion_fisica -> debe asignarse SOLO a la coincidencia más específica ("Aula CAD")
        Inventario::create([
            'num_serie' => 'SN-CAD-002',
            'codigo' => 'DELL-CAD-002',
            'id_producto' => $material->id_producto,
            'id_usuario' => $user->id,
            'area_id' => null,
            'ubicacion_fisica' => 'Ciencias Básicas > Aula CAD > Estación 2',
            'estado' => 'Asignado',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'fecha_registro' => now(),
        ]);

        $reporte = SigemLegacyReportBuilder::generar('equipos_area', 'xlsx');
        $sumaTotal = array_sum(array_column($reporte['rows'], 'total'));

        $this->assertSame(2, $sumaTotal);
        $this->assertCount(1, $reporte['rows']);
        $this->assertSame('Aula CAD', $reporte['rows'][0]['area']);
        $this->assertSame(2, $reporte['rows'][0]['total']);
    }

    public function test_catalogos_y_dashboard_no_duplican_registros_y_muestran_ids_tidb_completos(): void
    {
        [$user, $material] = $this->crearMaterialBase();

        DB::table('departamento')->insert([
            ['id_departamento' => 12, 'nombre' => 'Dirección General', 'created_at' => now(), 'updated_at' => now()],
            ['id_departamento' => 120001, 'nombre' => 'Subdirección Académica', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('area')->insert([
            ['id_area' => 30001, 'nombre' => 'Laboratorio 1', 'id_departamento' => 12, 'created_at' => now(), 'updated_at' => now()],
            ['id_area' => 120002, 'nombre' => 'Laboratorio 2', 'id_departamento' => 120001, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $catDeptos = ConfigCatalogosService::getDatosCatalogoActivo('departamentos');
        $this->assertCount(2, $catDeptos['rows']);
        $htmlDeptos = implode(' ', array_column($catDeptos['rows'], 0));
        $this->assertStringContainsString('#12', $htmlDeptos);
        $this->assertStringContainsString('#120001', $htmlDeptos);

        $catAreas = ConfigCatalogosService::getDatosCatalogoActivo('areas');
        $this->assertCount(2, $catAreas['rows']);
        $htmlAreas = implode(' ', array_column($catAreas['rows'], 0));
        $this->assertStringContainsString('#30001', $htmlAreas);
        $this->assertStringContainsString('#120002', $htmlAreas);

        Inventario::create([
            'num_serie' => '24J29Z2',
            'codigo' => 'DELL-OPT-013',
            'id_producto' => $material->id_producto,
            'id_usuario' => $user->id,
            'departamento_id' => 12,
            'area_id' => 30001,
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'tipo_propiedad' => 'Propio',
            'fecha_registro' => now(),
        ]);

        foreach (['created_at', 'departamento', 'equipo', 'categoria', 'estado', 'area', 'aula', 'num_serie'] as $sortCol) {
            $resultados = InventarioDashboardQuery::obtenerQuery([], $sortCol, 'asc')->get();
            $this->assertCount(1, $resultados, "El ordenamiento por '{$sortCol}' no debe duplicar filas.");
        }
    }

    public function test_css_columna_id_catalogos_soporta_ids_6_digitos_y_vistas_tienen_wire_key(): void
    {
        $scss = file_get_contents(public_path('css/scss/_tables-overrides.scss'));
        $css = file_get_contents(public_path('css/sigem-theme.css'));

        $this->assertStringContainsString('width: 96px !important;', $scss);
        $this->assertStringContainsString('.sigem-cat-table .sigem-col-id', $css);
        $this->assertStringContainsString('width:96px !important', $css);
        $this->assertStringNotContainsString('width:55px !important', $css);

        $bladeConfig = file_get_contents(resource_path('views/components/configuracion/config-catalogos.blade.php'));
        $bladeSs = file_get_contents(resource_path('views/filament/servicio-social/pages/catalogos.blade.php'));
        $bladeDash = file_get_contents(resource_path('views/components/dashboard/table-filas.blade.php'));

        $this->assertStringContainsString('wire:key="cat-', $bladeConfig);
        $this->assertStringContainsString('wire:key="ss-cat-', $bladeSs);
        $this->assertStringContainsString('wire:key="inv-row-', $bladeDash);
    }
}

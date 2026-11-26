<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Filament\Pages\ReportesPage;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use App\Support\SigemReportCatalog;
use App\Support\SigemReportExporter;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FixFinalGraficasAcordeonTest extends TestCase
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
            'name' => 'Admin FixFinal',
            'tipo_usuario' => RoleEnum::ADMIN->value,
            'activo' => true,
        ]);
        $this->admin->assignRole($role);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        SigemReportExporter::clearCache();
    }

    public function test_catalogo_29_reportes_define_titulo_ui_y_descripcion_corta_y_exporter_genera_resumen_inline(): void
    {
        $catalogo = SigemReportCatalog::getCatalogo();
        $todos = [];

        foreach ($catalogo as $sub => $grupo) {
            foreach ($grupo as $id => $meta) {
                $todos[$id] = $meta;
            }
        }

        $this->assertCount(29, $todos, 'El catálogo debe contener exactamente 29 reportes (12 gráficas + 17 tablas).');

        $conGrafica = 0;
        $soloTabla = 0;

        foreach ($todos as $id => $meta) {
            $this->assertNotEmpty($meta['titulo_ui'] ?? '', "El reporte {$id} debe definir titulo_ui.");
            $this->assertNotEmpty($meta['descripcion_corta'] ?? '', "El reporte {$id} debe definir descripcion_corta.");

            if (! empty($meta['has_chart'])) {
                $conGrafica++;
            } else {
                $soloTabla++;
            }

            $data = SigemReportExporter::getReportData($id);
            $this->assertSame($meta['titulo_ui'], $data['titulo_ui'], "Exporter debe exponer titulo_ui para {$id}.");
            $this->assertSame($meta['descripcion_corta'], $data['descripcion_corta'], "Exporter debe exponer descripcion_corta para {$id}.");
            $this->assertNotEmpty($data['resumen_inline'] ?? '', "Exporter debe calcular resumen_inline para {$id}.");
            $this->assertStringStartsWith('· ', $data['resumen_inline'], "resumen_inline de {$id} debe iniciar con '· '.");
        }

        $this->assertSame(12, $conGrafica);
        $this->assertSame(17, $soloTabla);

        $this->assertNotEmpty($todos['inventario_por_departamento']['nota_grafica'] ?? '');
        $this->assertNotEmpty($todos['estadisticas_depto_mas_equipos']['nota_grafica'] ?? '');
    }

    public function test_grafica_por_departamento_crece_dinamicamente_sin_scroll_y_muestra_nota_de_ceros(): void
    {
        $this->actingAs($this->admin);

        $unidad = UnidadMedida::create(['nombre' => 'Pieza']);
        $marca = MarcaMaterial::create(['nombre' => 'HP']);
        $tipo = TipoMaterial::create(['nombre' => 'Laptop']);
        $material = Material::create([
            'nombre' => 'ProBook 450',
            'id_unidad' => $unidad->id_unidad,
            'id_marca' => $marca->id_marca,
            'id_tipodematerial' => $tipo->id_tipodematerial,
            'requiere_control_individual' => true,
        ]);

        // Crear 14 departamentos: solo 3 tienen equipo, 11 tienen 0 equipos
        for ($i = 1; $i <= 14; $i++) {
            $depto = Departamento::create(['nombre' => sprintf('Depto Dinámico %02d', $i)]);
            $area = Area::create(['nombre' => sprintf('Área %02d', $i), 'id_departamento' => $depto->id_departamento]);

            if ($i <= 3) {
                Inventario::create([
                    'num_serie' => sprintf('SN-DYN-%03d', $i),
                    'codigo' => sprintf('HP-DYN-%03d', $i),
                    'id_producto' => $material->id_producto,
                    'id_usuario' => $this->admin->id,
                    'departamento_id' => $depto->id_departamento,
                    'area_id' => $area->id_area,
                    'estado' => 'Disponible',
                    'estado_registro' => 'Aprobado',
                    'tipo_propiedad' => 'Propio',
                    'fecha_registro' => now(),
                ]);
            }
        }

        SigemReportExporter::clearCache();

        $component = Livewire::test(ReportesPage::class)
            ->set('pestanaPrincipal', 'nuevos')
            ->set('subpestanaReportes', 'graficas');

        $html = $component->html();

        // Fórmula dinámica para 14 items: max(350, 14 * 40 + 120) = 680px
        $this->assertStringContainsString('height: 680px', $html);
        $this->assertStringContainsString('overflow: visible;', $html);
        $this->assertStringNotContainsString('sigem-chart-scroll-box', $html);

        // Nota explicativa de departamentos con 0 equipos
        $this->assertStringContainsString('sigem-chart-note', $html);
        $this->assertStringContainsString('Los departamentos sin equipos se muestran con 0.', $html);

        // Encabezado descriptivo en 2 líneas + resumen inline
        $this->assertStringContainsString('Equipos por departamento', $html);
        $this->assertStringContainsString('· 14 deptos · 3 equipos', $html);
        $this->assertStringContainsString('Distribución de equipos agrupados por departamento del TecNM Veracruz', $html);
    }

    public function test_acordeon_muestra_titulos_descriptivos_resumen_y_descripcion_en_graficas_y_tablas(): void
    {
        $this->actingAs($this->admin);

        // Sub-pestaña Gráficas (12)
        $htmlGraficas = Livewire::test(ReportesPage::class)
            ->set('pestanaPrincipal', 'nuevos')
            ->set('subpestanaReportes', 'graficas')
            ->html();

        $this->assertStringContainsString('Equipos por tipo de material', $htmlGraficas);
        $this->assertStringContainsString('Concentrado por categoría: CPU, Monitores, RAM, Teclados, etc.', $htmlGraficas);
        $this->assertStringContainsString('Equipos por estado operativo', $htmlGraficas);
        $this->assertStringContainsString('Distribución Propio vs Rentado', $htmlGraficas);
        $this->assertStringContainsString('Gasto mensual en rentas', $htmlGraficas);

        // Sub-pestaña Inventario (3 tablas)
        $htmlInv = Livewire::test(ReportesPage::class)
            ->set('pestanaPrincipal', 'nuevos')
            ->set('subpestanaReportes', 'inventario')
            ->html();

        $this->assertStringContainsString('Inventario general', $htmlInv);
        $this->assertStringContainsString('Listado completo de equipos registrados con ubicación y estado', $htmlInv);
        $this->assertStringContainsString('Equipos por área', $htmlInv);
        $this->assertStringContainsString('Equipos por aula', $htmlInv);
        $this->assertStringContainsString('sigem-rep-acc-subdesc', $htmlInv);
        $this->assertStringContainsString('sigem-rep-acc-summary', $htmlInv);

        // Sub-pestaña Estadísticas (3 tablas)
        $htmlEst = Livewire::test(ReportesPage::class)
            ->set('pestanaPrincipal', 'nuevos')
            ->set('subpestanaReportes', 'estadisticas')
            ->html();

        $this->assertStringContainsString('Equipos más antiguos', $htmlEst);
        $this->assertStringContainsString('· Top 20', $htmlEst);
        $this->assertStringContainsString('Equipos sin asignar', $htmlEst);
        $this->assertStringContainsString('Resumen general', $htmlEst);

        // Sub-pestaña Mantenimiento (4 tablas)
        $htmlMant = Livewire::test(ReportesPage::class)
            ->set('pestanaPrincipal', 'nuevos')
            ->set('subpestanaReportes', 'mantenimiento')
            ->html();

        $this->assertStringContainsString('Equipos en mantenimiento', $htmlMant);
        $this->assertStringContainsString('Tiempo promedio de mantenimiento', $htmlMant);
        $this->assertStringContainsString('días prom', $htmlMant);

        // Sub-pestaña Rentas (4 tablas)
        $htmlRent = Livewire::test(ReportesPage::class)
            ->set('pestanaPrincipal', 'nuevos')
            ->set('subpestanaReportes', 'rentas')
            ->html();

        $this->assertStringContainsString('Equipos en renta', $htmlRent);
        $this->assertStringContainsString('Rentas vencidas', $htmlRent);
        $this->assertStringContainsString('Rentas por proveedor', $htmlRent);
        $this->assertStringContainsString('Seguimiento de rentas', $htmlRent);

        // Sub-pestaña Histórico (3 tablas)
        $htmlHist = Livewire::test(ReportesPage::class)
            ->set('pestanaPrincipal', 'nuevos')
            ->set('subpestanaReportes', 'historico')
            ->html();

        $this->assertStringContainsString('Bajas de equipos', $htmlHist);
        $this->assertStringContainsString('Entregas realizadas', $htmlHist);
        $this->assertStringContainsString('Historial general', $htmlHist);
    }

    public function test_css_y_js_mantienen_overflow_visible_y_altura_dinamica_sin_scroll(): void
    {
        $css = file_get_contents(public_path('css/sigem-reportes.css'));
        $js = file_get_contents(public_path('js/sigem-reportes-charts.js'));

        $this->assertIsString($css);
        $this->assertIsString($js);

        $this->assertStringContainsString('overflow: visible;', $css);
        $this->assertStringContainsString('.sigem-chart-note', $css);
        $this->assertStringContainsString('.sigem-rep-acc-trigger-rich', $css);
        $this->assertStringContainsString('.sigem-rep-acc-summary', $css);
        $this->assertStringContainsString('.sigem-rep-acc-subdesc', $css);
        $this->assertStringNotContainsString('.sigem-chart-scroll-box', $css);

        $this->assertStringContainsString('Math.max(350, labelsCount * 40 + 120)', $js);
        $this->assertStringContainsString("wrap.style.overflow = 'visible'", $js);
    }
}

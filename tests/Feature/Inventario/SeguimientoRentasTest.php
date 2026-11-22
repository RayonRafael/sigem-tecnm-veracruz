<?php

namespace Tests\Feature\Inventario;

use App\Filament\Pages\Dashboard as AdminDashboard;
use App\Filament\Pages\ReportesPage;
use App\Filament\Resources\InventarioResource;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\Proveedor;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use App\Support\SigemReportExporter;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class SeguimientoRentasTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Departamento $depto;
    protected Area $area;
    protected Proveedor $proveedor;
    protected Material $material;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->adminUser = User::where('email', 'admin@tecnm.edu.mx')->first();

        $this->depto = Departamento::firstOrCreate(['nombre' => 'Centro de Cómputo']);
        $this->area = Area::firstOrCreate([
            'nombre' => 'Laboratorio Redes',
            'id_departamento' => $this->depto->id_departamento,
        ]);

        $this->proveedor = Proveedor::firstOrCreate(
            ['nombre_empresa' => 'Arrendadora TecNM SA'],
            ['telefono' => '2291009988', 'correo' => 'rentas@arrendadora.mx']
        );

        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'HP']);
        $tipo = TipoMaterial::firstOrCreate(['nombre' => 'Laptop']);
        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pieza']);

        $this->material = Material::firstOrCreate(
            ['nombre' => 'ProBook 450 G9'],
            [
                'modelo' => '450 G9',
                'id_marca' => $marca->id_marca,
                'id_tipodematerial' => $tipo->id_tipodematerial,
                'id_unidad' => $unidad->id_unidad,
                'stock_actual' => 10,
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
            'codigo' => 'RENT-' . strtoupper(substr(uniqid(), -6)),
            'num_serie' => 'SN-' . strtoupper(substr(uniqid(), -6)),
            'subtipo' => 'Laptop',
            'modelo' => '450 G9',
            'departamento_id' => $this->depto->id_departamento,
            'area_id' => $this->area->id_area,
            'id_proveedor' => $this->proveedor->id_proveedor,
            'estado' => 'Disponible',
            'tipo_propiedad' => 'Rentado',
            'fecha_registro' => Carbon::now()->startOfDay(),
        ], $overrides));
    }

    public function test_accesores_renta_calculo_automatico_3_anos_y_estados_semaforicos(): void
    {
        $hoy = Carbon::now()->startOfDay();

        // 1. Equipo Propio -> todos los accesores de renta son null
        $propio = $this->crearEquipo(['tipo_propiedad' => 'Propio']);
        $this->assertNull($propio->renta_fecha_fin);
        $this->assertNull($propio->renta_estado);
        $this->assertNull($propio->renta_dias_restantes);

        // 2. Rentado sin fecha manual -> calcula fecha_registro + 3 años -> Vigente
        $vigente = $this->crearEquipo([
            'fecha_registro' => $hoy->copy()->subDays(10),
            'especificaciones' => [],
        ]);
        $esperadoFin = $hoy->copy()->subDays(10)->addYears(3);
        $this->assertSame($esperadoFin->format('Y-m-d'), $vigente->renta_fecha_fin?->format('Y-m-d'));
        $this->assertSame('Vigente', $vigente->renta_estado);
        $this->assertGreaterThan(180, $vigente->renta_dias_restantes);

        // 3. Rentado con fecha manual dentro de 3 meses -> Por vencer
        $porVencer = $this->crearEquipo([
            'fecha_registro' => $hoy->copy()->subYears(2),
            'especificaciones' => [
                'renta_fecha_fin' => $hoy->copy()->addMonths(3)->format('Y-m-d'),
            ],
        ]);
        $this->assertSame('Por vencer', $porVencer->renta_estado);
        $this->assertGreaterThanOrEqual(80, $porVencer->renta_dias_restantes);

        // 4. Rentado con fecha pasada (-10 días) -> Vencida
        $vencida = $this->crearEquipo([
            'fecha_registro' => $hoy->copy()->subYears(3)->subDays(10),
            'especificaciones' => [
                'renta_fecha_fin' => $hoy->copy()->subDays(10)->format('Y-m-d'),
            ],
        ]);
        $this->assertSame('Vencida', $vencida->renta_estado);
        $this->assertSame(-10, $vencida->renta_dias_restantes);
    }

    public function test_validacion_slide_over_rechaza_fecha_fin_renta_mayor_a_3_anos_y_menor_a_registro(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $fechaReg = Carbon::now()->startOfDay();

        // Excede 3 años (+1 día)
        Livewire::test(AdminDashboard::class)
            ->callAction('registrarEquipo', [
                'tipo_equipo' => 'lap',
                'num_serie' => 'SN-VAL-MAX-01',
                'modelo' => '450 G9',
                'estado' => 'Disponible',
                'tipo_propiedad' => 'Rentado',
                'departamento_id' => $this->depto->id_departamento,
                'area_id' => $this->area->id_area,
                'fecha_registro' => $fechaReg->format('Y-m-d'),
                'renta_fecha_fin' => $fechaReg->copy()->addYears(3)->addDay()->format('Y-m-d'),
                'especificaciones' => ['marca' => 'HP'],
            ])
            ->assertHasActionErrors(['renta_fecha_fin']);

        // Anterior a fecha_registro (-1 día)
        Livewire::test(AdminDashboard::class)
            ->callAction('registrarEquipo', [
                'tipo_equipo' => 'lap',
                'num_serie' => 'SN-VAL-MIN-01',
                'modelo' => '450 G9',
                'estado' => 'Disponible',
                'tipo_propiedad' => 'Rentado',
                'departamento_id' => $this->depto->id_departamento,
                'area_id' => $this->area->id_area,
                'fecha_registro' => $fechaReg->format('Y-m-d'),
                'renta_fecha_fin' => $fechaReg->copy()->subDay()->format('Y-m-d'),
                'especificaciones' => ['marca' => 'HP'],
            ])
            ->assertHasActionErrors(['renta_fecha_fin']);
    }

    public function test_registrar_equipo_rentado_auto_calcula_fecha_fin_y_muestra_notificacion(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $fechaReg = Carbon::parse('2026-08-15');
        $fechaFinEsperada = $fechaReg->copy()->addYears(3);

        Livewire::test(AdminDashboard::class)
            ->callAction('registrarEquipo', [
                'tipo_equipo' => 'lap',
                'num_serie' => 'SN-RENT-AUTO-01',
                'modelo' => '450 G9',
                'estado' => 'Disponible',
                'tipo_propiedad' => 'Rentado',
                'departamento_id' => $this->depto->id_departamento,
                'area_id' => $this->area->id_area,
                'id_proveedor' => $this->proveedor->id_proveedor,
                'fecha_registro' => $fechaReg->format('Y-m-d'),
                'renta_fecha_fin' => null,
                'especificaciones' => ['marca' => 'HP'],
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('Equipo registrado como rentado. Vence el ' . $fechaFinEsperada->format('d/m/Y'));

        $inv = Inventario::where('num_serie', 'SN-RENT-AUTO-01')->first();
        $this->assertNotNull($inv);
        $this->assertSame($fechaFinEsperada->format('Y-m-d'), $inv->especificaciones['renta_fecha_fin'] ?? null);
        $this->assertSame($fechaFinEsperada->format('Y-m-d'), $inv->renta_fecha_fin?->format('Y-m-d'));
    }

    public function test_editar_equipo_de_rentado_a_propio_limpia_renta_fecha_fin(): void
    {
        $inv = $this->crearEquipo([
            'tipo_propiedad' => 'Rentado',
            'fecha_registro' => Carbon::now()->subMonths(6),
            'especificaciones' => [
                'renta_fecha_fin' => Carbon::now()->addYears(2)->format('Y-m-d'),
            ],
        ]);

        $this->assertNotNull($inv->renta_fecha_fin);

        InventarioResource::guardarCambiosEquipo($inv, [
            'tipo_propiedad' => 'Propio',
        ]);

        $fresh = $inv->fresh();
        $this->assertSame('Propio', $fresh->tipo_propiedad);
        $this->assertArrayNotHasKey('renta_fecha_fin', $fresh->especificaciones ?? []);
        $this->assertNull($fresh->fecha_fin_renta);
        $this->assertNull($fresh->renta_fecha_fin);
    }

    public function test_dashboard_muestra_card_equipos_en_renta_y_badge_en_tabla_inventario(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $hoy = Carbon::now()->startOfDay();

        // 1 vigente, 1 por vencer, 1 vencida
        $this->crearEquipo([
            'codigo' => 'RENT-VIG-01',
            'fecha_registro' => $hoy->copy()->subDays(5),
            'especificaciones' => ['renta_fecha_fin' => $hoy->copy()->addYears(2)->format('Y-m-d')],
        ]);
        $this->crearEquipo([
            'codigo' => 'RENT-PRV-01',
            'fecha_registro' => $hoy->copy()->subYears(2),
            'especificaciones' => ['renta_fecha_fin' => $hoy->copy()->addMonths(2)->format('Y-m-d')],
        ]);
        $this->crearEquipo([
            'codigo' => 'RENT-VEN-01',
            'fecha_registro' => $hoy->copy()->subYears(3)->subDays(5),
            'especificaciones' => ['renta_fecha_fin' => $hoy->copy()->subDays(5)->format('Y-m-d')],
        ]);

        Livewire::test(AdminDashboard::class)
            ->assertSee('Equipos en renta')
            ->assertSee('1 vigente')
            ->assertSee('1 por vencer')
            ->assertSee('1 vencida')
            ->assertSee('Ver detalle')
            ->assertSee('reporte=rent_seguimiento')
            ->assertDontSee('Registrar activo')
            ->assertSee('ESTADO RENTA')
            ->assertSee('🟢 Vigente')
            ->assertSee('🟡 Por vencer')
            ->assertSee('🔴 Vencida');
    }

    public function test_reporte_rent_seguimiento_ordena_vencidas_por_vencer_vigentes_y_deep_link(): void
    {
        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $hoy = Carbon::now()->startOfDay();

        // Crear en orden inverso para verificar ordenamiento estricto
        $this->crearEquipo([
            'codigo' => 'ORD-VIG-01',
            'fecha_registro' => $hoy->copy(),
            'especificaciones' => ['renta_fecha_fin' => $hoy->copy()->addYears(2)->format('Y-m-d')],
        ]);
        $this->crearEquipo([
            'codigo' => 'ORD-PRV-01',
            'fecha_registro' => $hoy->copy()->subYears(2),
            'especificaciones' => ['renta_fecha_fin' => $hoy->copy()->addMonths(2)->format('Y-m-d')],
        ]);
        $this->crearEquipo([
            'codigo' => 'ORD-VEN-01',
            'fecha_registro' => $hoy->copy()->subYears(3)->subDays(15),
            'especificaciones' => ['renta_fecha_fin' => $hoy->copy()->subDays(15)->format('Y-m-d')],
        ]);

        $reporte = SigemReportExporter::getReportData('rent_seguimiento');

        $this->assertSame('Seguimiento de Rentas', $reporte['titulo']);
        $this->assertFalse($reporte['has_chart']);
        $this->assertSame(
            ['Código', 'Equipo', 'Proveedor', 'Inicio renta', 'Fin renta', 'Días restantes', 'Estado', 'Área', 'Depto', 'Receptor', 'Hora', 'Tipo Propiedad'],
            $reporte['headers']
        );
        $this->assertCount(3, $reporte['rows']);

        // Orden estricto: 1. 🔴 Vencida -> 2. 🟡 Por vencer -> 3. 🟢 Vigente
        $this->assertSame('ORD-VEN-01', $reporte['rows'][0][0]);
        $this->assertSame('🔴 Vencida', $reporte['rows'][0][6]);

        $this->assertSame('ORD-PRV-01', $reporte['rows'][1][0]);
        $this->assertSame('🟡 Por vencer', $reporte['rows'][1][6]);

        $this->assertSame('ORD-VIG-01', $reporte['rows'][2][0]);
        $this->assertSame('🟢 Vigente', $reporte['rows'][2][6]);

        // Verificar deep-link en ReportesPage
        Livewire::withQueryParams([
            'pestana' => 'nuevos',
            'subpestana' => 'rentas',
            'reporte' => 'rent_seguimiento',
        ])->test(ReportesPage::class)
            ->assertSet('pestanaPrincipal', 'nuevos')
            ->assertSet('subpestanaReportes', 'rentas')
            ->assertSet('nuevoReporteActivo', 'rent_seguimiento')
            ->assertSee('Seguimiento de Rentas');
    }
}

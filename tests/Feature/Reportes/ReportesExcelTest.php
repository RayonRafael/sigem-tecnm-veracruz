<?php

namespace Tests\Feature\Reportes;

use App\Filament\Pages\ReportesPage as AdminReportesPage;
use App\Filament\ServicioSocial\Pages\ReportesPage as SSReportesPage;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class ReportesExcelTest extends TestCase
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

    public function test_exportar_genera_archivo_xlsx_con_mime_type_correcto(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = $this->getAdminUser();
        $this->actingAs($admin);

        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pieza']);
        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'Dell']);
        $tipo = TipoMaterial::firstOrCreate(['nombre' => 'Laptop XL']);
        $material = Material::create([
            'nombre' => 'Dell Latitude XL',
            'id_unidad' => $unidad->id_unidad,
            'id_marca' => $marca->id_marca,
            'id_tipodematerial' => $tipo->id_tipodematerial,
            'stock_actual' => 1,
            'requiere_control_individual' => 1,
        ]);

        Inventario::create([
            'id_producto' => $material->id_producto,
            'num_serie' => 'SN-XL-001',
            'tipo_propiedad' => 'Propio',
            'estado' => 'Disponible',
            'fecha_registro' => now(),
            'id_usuario' => $admin->id,
        ]);

        $page = new AdminReportesPage();
        $response = $page->exportarExcel('inventario_categoria');

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
    }

    public function test_los_6_reportes_tienen_columnas_correctas_del_mockup(): void
    {
        $page = new AdminReportesPage();

        // 1. Inventario por categoría
        $rep1 = $page->getDatosReporte('inventario_categoria', 'xlsx');
        $this->assertEquals(
            ['Categoría', 'Total', 'Disponible', 'En mantto', 'Asignado', 'Cambio área', 'Solicitud baja'],
            $rep1['headers']
        );

        // 2. Equipos por área
        $rep2 = $page->getDatosReporte('equipos_area', 'xlsx');
        $this->assertEquals(
            ['Área', 'Total', 'Disponible', 'En mantto', 'Asignado', 'Cambio área', 'Solicitud baja'],
            $rep2['headers']
        );

        // 3. Equipos en mantenimiento
        $rep3 = $page->getDatosReporte('equipos_mantenimiento', 'xlsx');
        $this->assertEquals(
            ['Código', 'Equipo', 'Categoría', 'Área', 'Aula'],
            $rep3['headers']
        );

        // 4. Equipos con solicitud de baja
        $rep4 = $page->getDatosReporte('equipos_baja_danados', 'xlsx');
        $this->assertEquals(
            ['Código', 'Equipo', 'Categoría', 'Área', 'Último movimiento'],
            $rep4['headers']
        );

        // 5. Solicitudes de material
        $rep5 = $page->getDatosReporte('solicitudes_tipo_estado', 'xlsx');
        $this->assertEquals(
            ['Tipo', 'Pendiente', 'Autorizado', 'Entregado', 'Rechazado', 'Total'],
            $rep5['headers']
        );

        // 6. Movimientos SS
        $rep6 = $page->getDatosReporte('movimientos_recientes', 'xlsx');
        $this->assertEquals(
            ['Fecha', 'Usuario', 'Acción', 'Detalle'],
            $rep6['headers']
        );
    }

    public function test_toggle_visibilidad_controla_visibilidad_para_servicio_social(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser());

        $page = new AdminReportesPage();
        $page->mount();

        $estadoInicial = $page->visibilidadServicioSocial['inventario_categoria'] ?? true;
        $page->toggleVisibilidad('inventario_categoria');

        $this->assertEquals(! $estadoInicial, $page->visibilidadServicioSocial['inventario_categoria']);

        $cached = Cache::get('sigem_reportes_visibilidad_ss');
        $this->assertIsArray($cached);
        $this->assertEquals(! $estadoInicial, $cached['inventario_categoria']);
    }

    public function test_servicio_social_solo_ve_reportes_autorizados(): void
    {
        // Ocultar inventario_categoria para SS
        Cache::forever('sigem_reportes_visibilidad_ss', [
            'inventario_categoria' => false,
            'equipos_area' => true,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('servicio-social'));
        $this->actingAs($this->getServicioSocialUser());

        $ssPage = new SSReportesPage();
        $ssPage->mount();

        $this->assertFalse($ssPage->visibilidadServicioSocial['inventario_categoria']);
        $this->assertTrue($ssPage->visibilidadServicioSocial['equipos_area']);
    }
}

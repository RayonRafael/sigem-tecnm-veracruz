<?php

namespace Tests\Feature\Inventario;

use App\Models\Area;
use App\Models\Departamento;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventarioCamposNuevosTest extends TestCase
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

    public function test_campos_fillable_existen_en_modelo_inventario(): void
    {
        $fillable = (new Inventario())->getFillable();

        $camposRequeridos = [
            'codigo',
            'estacion',
            'tipo_impresora',
            'sistema_operativo',
            'modelo',
            'especificacion_texto',
            'departamento_id',
            'area_id',
            'salon_id',
        ];

        foreach ($camposRequeridos as $campo) {
            $this->assertContains($campo, $fillable, "El campo '{$campo}' debe estar en \$fillable de Inventario.");
        }
    }

    public function test_relaciones_departamento_area_y_salon(): void
    {
        $depto = Departamento::firstOrCreate(['nombre' => 'Depto Industrial']);
        $area = Area::firstOrCreate(['nombre' => 'Área Automatización', 'id_departamento' => $depto->id_departamento]);
        $salon = Salon::firstOrCreate(['nombre' => 'Aula I-1', 'area_id' => $area->id_area]);

        $user = $this->getAdminUser();

        $inv = Inventario::create([
            'num_serie' => 'SN-REL-001',
            'modelo' => 'RelTest',
            'departamento_id' => $depto->id_departamento,
            'area_id' => $area->id_area,
            'salon_id' => $salon->id,
            'id_usuario' => $user->id,
            'tipo_propiedad' => 'Propio',
            'estado' => 'Disponible',
            'fecha_registro' => now(),
        ]);

        $this->assertInstanceOf(Departamento::class, $inv->departamento);
        $this->assertEquals($depto->id_departamento, $inv->departamento->id_departamento);

        $this->assertInstanceOf(Area::class, $inv->area);
        $this->assertEquals($area->id_area, $inv->area->id_area);

        $this->assertInstanceOf(Salon::class, $inv->salon);
        $this->assertEquals($salon->id, $inv->salon->id);
    }

    public function test_generar_codigo_formato_correcto_marca_modelo_consecutivo(): void
    {
        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pieza']);
        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'HP']);
        $tipo = TipoMaterial::firstOrCreate(['nombre' => 'Impresora']);

        $material = Material::create([
            'nombre' => 'LaserJet Pro',
            'modelo' => 'LJ4103',
            'id_unidad' => $unidad->id_unidad,
            'id_marca' => $marca->id_marca,
            'id_tipodematerial' => $tipo->id_tipodematerial,
            'stock_actual' => 1,
            'requiere_control_individual' => 1,
        ]);

        $inv = new Inventario([
            'id_producto' => $material->id_producto,
            'modelo' => 'LJ4103',
        ]);

        $codigo = $inv->generarCodigo();

        $this->assertNotEmpty($codigo);
        $this->assertEquals('HP-LJ4103-001', $codigo);
    }

    public function test_familia_en_tipo_material_y_scope_por_familia(): void
    {
        $computo1 = TipoMaterial::firstOrCreate(['nombre' => 'Laptop Gamer'], ['familia' => 'Equipo de Cómputo']);
        $computo1->update(['familia' => 'Equipo de Cómputo']);

        $computo2 = TipoMaterial::firstOrCreate(['nombre' => 'Servidor Rack'], ['familia' => 'Equipo de Cómputo']);
        $computo2->update(['familia' => 'Equipo de Cómputo']);

        $mobiliario = TipoMaterial::firstOrCreate(['nombre' => 'Escritorio Modular'], ['familia' => 'Mobiliario']);
        $mobiliario->update(['familia' => 'Mobiliario']);

        $resultadosComputo = TipoMaterial::porFamilia('Equipo de Cómputo')->pluck('nombre')->all();
        $this->assertContains('Laptop Gamer', $resultadosComputo);
        $this->assertContains('Servidor Rack', $resultadosComputo);
        $this->assertNotContains('Escritorio Modular', $resultadosComputo);

        $resultadosMobiliario = TipoMaterial::porFamilia('Mobiliario')->pluck('nombre')->all();
        $this->assertContains('Escritorio Modular', $resultadosMobiliario);
        $this->assertNotContains('Laptop Gamer', $resultadosMobiliario);
    }
}

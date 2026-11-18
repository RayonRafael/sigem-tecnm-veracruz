<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\Salon;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventario>
 */
class InventarioFactory extends Factory
{
    protected $model = Inventario::class;

    public function definition(): array
    {
        $depto = Departamento::firstOrCreate(
            ['nombre' => 'Ingeniería Industrial'],
            ['encargado' => 'Israel de Jesús Cruz Mendoza']
        );

        $area = Area::firstOrCreate([
            'nombre' => 'Jefatura de Industrial',
            'id_departamento' => $depto->id_departamento,
        ]);

        $salon = Salon::firstOrCreate([
            'nombre' => 'Cubículo 1',
            'area_id' => $area->id_area,
        ]);

        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'Dell']);
        $tipo = TipoMaterial::firstOrCreate(['nombre' => 'Computadora']);
        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pieza']);

        $material = Material::firstOrCreate(
            ['nombre' => 'OptiPlex 7040'],
            [
                'descripcion' => 'CPU OptiPlex 7040 Core i7 16GB RAM',
                'modelo' => '7040',
                'id_marca' => $marca->id_marca,
                'id_tipodematerial' => $tipo->id_tipodematerial,
                'id_unidad' => $unidad->id_unidad,
                'stock_actual' => 5,
                'stock_minimo' => 1,
                'requiere_control_individual' => 1,
            ]
        );

        $user = User::first() ?? User::factory()->create();

        return [
            'id_producto' => $material->id_producto,
            'id_usuario' => $user->id,
            'codigo' => 'INV-' . strtoupper($this->faker->unique()->bothify('??-####')),
            'num_serie' => 'SN-' . strtoupper($this->faker->unique()->bothify('????####')),
            'modelo' => 'OptiPlex 7040',
            'especificacion_texto' => 'Windows 10, 1Tb Disco Duro, Core i7, 16Gb RAM DDR4',
            'tipo_propiedad' => 'Propio',
            'estado' => 'Disponible',
            'estado_registro' => 'Aprobado',
            'aprobado' => true,
            'departamento_id' => $depto->id_departamento,
            'area_id' => $area->id_area,
            'salon_id' => $salon->id,
            'fecha_registro' => now(),
        ];
    }
}


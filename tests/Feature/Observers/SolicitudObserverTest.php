<?php

namespace Tests\Feature\Observers;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\DetalleSolicitud;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\Receptor;
use App\Models\Solicitud;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SolicitudObserverTest extends TestCase
{
    use RefreshDatabase;

    private function getUser()
    {
        return User::firstOrCreate([
            'email' => 'test@test.com',
        ], [
            'name' => 'Test',
            'password' => bcrypt('password'),
            'tipo_usuario' => 'Administrador',
        ]);
    }

    private function createArea(): Area
    {
        $depto = Departamento::firstOrCreate(['nombre' => 'Sistemas']);

        return Area::firstOrCreate([
            'nombre' => 'Laboratorio',
            'id_departamento' => $depto->id_departamento,
        ]);
    }

    private function createMaterial(): Material
    {
        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pza']);
        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'Genérico']);
        $tipo = TipoMaterial::firstOrCreate(['nombre' => 'Consumible']);

        return Material::create([
            'nombre' => 'Laptop '.uniqid(),
            'id_unidad' => $unidad->id_unidad,
            'id_marca' => $marca->id_marca,
            'id_tipodematerial' => $tipo->id_tipodematerial,
            'stock_actual' => 10,
            'requiere_control_individual' => 0,
        ]);
    }

    private function createReceptor(): Receptor
    {
        $area = $this->createArea();

        return Receptor::create([
            'nombre' => 'Juan',
            'apellido_paterno' => 'Perez',
            'apellido_materno' => 'Gomez',
            'tipo_receptor' => 'Docente',
            'id_area' => $area->id_area,
        ]);
    }

    private function piezas(Material $material, int $n): array
    {
        $area = $this->createArea();
        $user = $this->getUser();
        $out = [];
        for ($k = 0; $k < $n; $k++) {
            $out[] = Inventario::create([
                'numero_serie' => 'SN-'.uniqid(),
                'id_producto' => $material->id_producto,
                'id_area' => $area->id_area,
                'estado' => 'Disponible',
                'estado_registro' => 'Aprobado',
                'tipo_propiedad' => 'Propio',
                'id_usuario' => $user->id,
                'fecha_registro' => now(),
            ]);
        }

        return $out;
    }

    private function solicitud(Material $material, int $cantidad, string $estado = 'Pendiente'): Solicitud
    {
        $solicitud = Solicitud::create([
            'id_receptor' => $this->createReceptor()->id_receptor,
            'tipo_movimiento' => 'Asignacion Permanente',
            'estado' => $estado,
            'id_usuario' => $this->getUser()->id,
            'fecha_solicitud' => now(),
        ]);
        DetalleSolicitud::create([
            'id_solicitud' => $solicitud->id_solicitud,
            'id_producto' => $material->id_producto,
            'cantidad' => $cantidad,
        ]);

        return $solicitud;
    }

    public function test_stock_is_derived_and_follows_solicitud_state(): void
    {
        $material = $this->createMaterial();
        $this->piezas($material, 10);

        $solicitud = $this->solicitud($material, 2);
        $this->assertEquals(10, $material->fresh()->stock_actual);

        $solicitud->update(['estado' => 'Autorizado']);
        $this->assertEquals(8, $material->fresh()->stock_actual);

        // Cancelar (caso que antes desincronizaba) restituye solo.
        $solicitud->update(['estado' => 'Cancelado']);
        $this->assertEquals(10, $material->fresh()->stock_actual);
    }

    public function test_stock_ignores_unapproved_pieces_and_never_goes_negative(): void
    {
        $material = $this->createMaterial();
        $piezas = $this->piezas($material, 3);
        $piezas[0]->update(['estado_registro' => 'Pendiente']);
        $this->assertEquals(2, $material->fresh()->stock_actual);

        $this->solicitud($material, 50, 'Autorizado');
        $this->assertEquals(0, $material->fresh()->stock_actual);
    }

    public function test_editing_quantity_and_deleting_solicitud_stay_consistent(): void
    {
        $material = $this->createMaterial();
        $this->piezas($material, 10);
        $solicitud = $this->solicitud($material, 2, 'Autorizado');
        $this->assertEquals(8, $material->fresh()->stock_actual);

        DetalleSolicitud::where('id_solicitud', $solicitud->id_solicitud)->update(['cantidad' => 5]);
        $this->assertEquals(5, $material->fresh()->stock_actual);

        DetalleSolicitud::where('id_solicitud', $solicitud->id_solicitud)->delete();
        $solicitud->delete();
        $this->assertEquals(10, $material->fresh()->stock_actual);
    }

    public function test_stock_bajo_scope_uses_derived_stock(): void
    {
        $material = $this->createMaterial();
        $material->update(['stock_minimo' => 3]);
        $this->piezas($material, 2);

        $this->assertTrue(Material::stockBajo()->whereKey($material->id_producto)->exists());

        $this->piezas($material, 5);
        $this->assertFalse(Material::stockBajo()->whereKey($material->id_producto)->exists());
    }
}
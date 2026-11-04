<?php

namespace App\Observers;

use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;

class InventarioObserver
{
    // NOTA: el stock de Material ya NO se incrementa/decrementa aquí.
    // Es un valor derivado (ver Material::stockActual()).

    public function creating(Inventario $inventario): void
    {
        // 1. Auto-match Material: Si no se selecciona material explícitamente
        if (empty($inventario->id_producto)) {
            $this->autoMatchMaterial($inventario);
        }

        // 2. Auto-generar código: MARCA-MODELO-### (ej. HP-LJ4103-001)
        if (empty($inventario->codigo)) {
            $inventario->codigo = $inventario->generarCodigo();
        }
    }

    public function updating(Inventario $inventario): void
    {
        if ($inventario->isDirty('estado') && $inventario->estado === 'Baja' && empty($inventario->fecha_baja)) {
            $inventario->fecha_baja = now();
        }
    }

    protected function autoMatchMaterial(Inventario $inventario): void
    {
        // Resolver TipoMaterial
        $tipoId = $inventario->id_tipodematerial
            ?? $inventario->tipo_material_id
            ?? null;
        $tipoNombre = $inventario->tipo_equipo
            ?? ($inventario->especificaciones['tipo_equipo'] ?? null)
            ?? $inventario->subtipo
            ?? null;

        $tipoMaterial = null;
        if ($tipoId) {
            $tipoMaterial = TipoMaterial::find($tipoId);
        } elseif ($tipoNombre) {
            $tipoMaterial = TipoMaterial::where('nombre', $tipoNombre)->first()
                ?? TipoMaterial::where('nombre', 'LIKE', "%{$tipoNombre}%")->first()
                ?? TipoMaterial::firstOrCreate(['nombre' => $tipoNombre]);
        }

        if (! $tipoMaterial) {
            $tipoMaterial = TipoMaterial::firstOrCreate(['nombre' => 'Equipo de Cómputo']);
        }

        // Resolver Marca
        $marcaId = $inventario->id_marca
            ?? $inventario->marca_id
            ?? null;
        $marcaNombre = $inventario->marca
            ?? ($inventario->especificaciones['marca'] ?? null)
            ?? ($inventario->especificaciones['cpu_marca'] ?? null)
            ?? null;

        $marca = null;
        if ($marcaId) {
            $marca = MarcaMaterial::find($marcaId);
        } elseif ($marcaNombre) {
            $marca = MarcaMaterial::where('nombre', $marcaNombre)->first()
                ?? MarcaMaterial::where('nombre', 'LIKE', "%{$marcaNombre}%")->first()
                ?? MarcaMaterial::firstOrCreate(['nombre' => $marcaNombre]);
        }

        if (! $marca) {
            $marca = MarcaMaterial::firstOrCreate(['nombre' => 'Genérico']);
        }

        // Resolver Modelo
        $modelo = $inventario->modelo
            ?? ($inventario->especificaciones['modelo'] ?? null)
            ?? null;

        // Buscar material existente por tipo_material + marca + modelo
        $materialQuery = Material::where('id_tipodematerial', $tipoMaterial->id_tipodematerial)
            ->where('id_marca', $marca->id_marca);

        if (! empty($modelo)) {
            $materialQuery->where('modelo', $modelo);
        }

        $material = $materialQuery->first();

        // Si no existe → crear automáticamente
        if (! $material) {
            $unidad = UnidadMedida::where('nombre', 'like', 'Pieza%')
                ->orWhere('nombre', 'like', 'Pza%')
                ->first()
                ?? UnidadMedida::first()
                ?? UnidadMedida::firstOrCreate(['nombre' => 'Pieza']);

            $nombreCompuesto = trim($marca->nombre . ' ' . $tipoMaterial->nombre . ($modelo ? ' ' . $modelo : ''));

            $material = Material::create([
                'nombre' => substr($nombreCompuesto, 0, 150),
                'modelo' => $modelo ? substr($modelo, 0, 100) : null,
                'id_marca' => $marca->id_marca,
                'id_tipodematerial' => $tipoMaterial->id_tipodematerial,
                'id_unidad' => $unidad->id_unidad,
                'requiere_control_individual' => 1,
                'stock_actual' => 0,
                'stock_minimo' => 0,
            ]);
        }

        $inventario->id_producto = $material->id_producto;
        $inventario->setRelation('material', $material);

        if (empty($inventario->modelo) && ! empty($material->modelo)) {
            $inventario->modelo = $material->modelo;
        }
    }
}

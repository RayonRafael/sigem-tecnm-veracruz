<?php

namespace App\Observers;

use App\Enums\EstadoInventario;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\HistorialEstado;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\Salon;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;

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

        // 3. Sincronizar ubicación física inicial si no viene asignada
        if (empty($inventario->ubicacion_fisica)) {
            $ubicacion = $this->construirUbicacion(
                $inventario->departamento_id,
                $inventario->area_id,
                $inventario->salon_id,
                $inventario->estacion
            );
            if (! empty($ubicacion)) {
                $inventario->ubicacion_fisica = substr($ubicacion, 0, 150);
            }
        }
    }

    public function created(Inventario $inventario): void
    {
        $ubicacion = $this->construirUbicacion(
            $inventario->departamento_id,
            $inventario->area_id,
            $inventario->salon_id,
            $inventario->estacion
        );

        $usuarioId = auth()->id() ?? $inventario->id_usuario ?? User::where('email', 'admin@tecnm.edu.mx')->value('id') ?? User::first()?->id ?? 1;

        HistorialEstado::create([
            'id_inventario' => $inventario->id_inventario,
            'id_usuario' => $usuarioId,
            'estado_anterior' => null,
            'estado_nuevo' => $inventario->estado ?? EstadoInventario::Disponible->value,
            'motivo_cambio' => 'Alta inicial en el inventario',
            'ubicacion_fisica' => $ubicacion ?: ($inventario->ubicacion_fisica ?: 'Sin ubicación asignada'),
            'fecha_cambio' => now(),
        ]);
    }

    public function updating(Inventario $inventario): void
    {
        if ($inventario->isDirty('estado') && in_array($inventario->estado, ['Baja', EstadoInventario::DadoDeBaja->value], true) && empty($inventario->fecha_baja)) {
            $inventario->fecha_baja = now();
        }

        if ($inventario->isDirty(['departamento_id', 'area_id', 'salon_id', 'estacion'])) {
            $ubicacion = $this->construirUbicacion(
                $inventario->departamento_id,
                $inventario->area_id,
                $inventario->salon_id,
                $inventario->estacion
            );
            if (! empty($ubicacion)) {
                $inventario->ubicacion_fisica = substr($ubicacion, 0, 150);
            }
        }
    }

    public function updated(Inventario $inventario): void
    {
        if ($inventario->skipHistorialObserver) {
            return;
        }

        $cambioUbicacion = $inventario->wasChanged(['departamento_id', 'area_id', 'salon_id', 'estacion']);

        if ($cambioUbicacion) {
            $ubicacionAnterior = $this->construirUbicacion(
                $inventario->getOriginal('departamento_id'),
                $inventario->getOriginal('area_id'),
                $inventario->getOriginal('salon_id'),
                $inventario->getOriginal('estacion')
            );

            $ubicacionNueva = $this->construirUbicacion(
                $inventario->departamento_id,
                $inventario->area_id,
                $inventario->salon_id,
                $inventario->estacion
            );

            if ($ubicacionAnterior !== $ubicacionNueva) {
                $textoAnterior = $ubicacionAnterior ?: 'Sin ubicación previa';
                $textoNuevo = $ubicacionNueva ?: 'Sin ubicación asignada';
                $usuarioId = auth()->id() ?? $inventario->id_usuario ?? User::where('email', 'admin@tecnm.edu.mx')->value('id') ?? User::first()?->id ?? 1;

                HistorialEstado::create([
                    'id_inventario' => $inventario->id_inventario,
                    'id_usuario' => $usuarioId,
                    'estado_anterior' => $inventario->getOriginal('estado') ?? $inventario->estado ?? EstadoInventario::Disponible->value,
                    'estado_nuevo' => $inventario->estado ?? EstadoInventario::Disponible->value,
                    'motivo_cambio' => "Cambio de ubicación: {$textoAnterior} → {$textoNuevo}",
                    'ubicacion_fisica' => $ubicacionNueva ?: null,
                    'fecha_cambio' => now(),
                ]);
            }
        }
    }

    public function construirUbicacion(?int $departamentoId, ?int $areaId, ?int $salonId, ?string $estacion): string
    {
        return \App\Support\UbicacionHelper::construir($departamentoId, $areaId, $salonId, $estacion);
    }

    protected function autoMatchMaterial(Inventario $inventario): void
    {
        // Resolver TipoMaterial
        $tipoId = $inventario->id_tipodematerial
            ?? $inventario->tipo_material_id
            ?? null;
        $tipoNombre = $inventario->subtipo
            ?? ($inventario->especificaciones['componente'] ?? null)
            ?? $inventario->tipo_equipo
            ?? ($inventario->especificaciones['tipo_equipo'] ?? null)
            ?? null;

        $tipoMaterial = null;
        if ($tipoId) {
            $tipoMaterial = TipoMaterial::find($tipoId);
        } elseif ($tipoNombre) {
            $tipoBusqueda = match (strtolower(trim($tipoNombre))) {
                'mouse', 'raton', 'ratón' => 'Muse',
                'pc' => 'CPU',
                default => $tipoNombre,
            };

            $tipoMaterial = TipoMaterial::where('nombre', $tipoBusqueda)->first()
                ?? TipoMaterial::where('nombre', 'LIKE', "%{$tipoBusqueda}%")->first()
                ?? TipoMaterial::where('nombre', $tipoNombre)->first()
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

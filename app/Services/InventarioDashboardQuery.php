<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\TipoMaterial;
use App\Support\SigemReportExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class InventarioDashboardQuery
{
    public static function obtenerOpcionesFiltros(): array
    {
        $ver = SigemReportExporter::cacheVersion();

        return Cache::remember("sigem_dash_filtros_v{$ver}", SigemReportExporter::CACHE_TTL_SECONDS, function () {
            $categoriasFiltro = ['' => 'Todas'];
            try {
                $categoriasFiltro += TipoMaterial::orderBy('nombre')->pluck('nombre', 'nombre')->toArray();
            } catch (\Throwable $e) {
            }

            $areasFiltro = ['' => 'Todas'];
            try {
                $areasFiltro += Area::orderBy('nombre')->pluck('nombre', 'nombre')->toArray();
            } catch (\Throwable $e) {
            }

            $departamentosFiltro = ['' => 'Todos'];
            try {
                $departamentosFiltro += Departamento::orderBy('nombre')->pluck('nombre', 'nombre')->toArray();
            } catch (\Throwable $e) {
            }

            return [$categoriasFiltro, $areasFiltro, $departamentosFiltro];
        });
    }

    public static function obtenerQuery(
        array $searches = [],
        string $sortColumn = 'created_at',
        string $sortDirection = 'desc'
    ): Builder {
        $invQuery = Inventario::query()
            ->with(['material.tipo', 'area.departamento', 'departamento', 'salon'])
            ->select('inventario.*');

        $deptoSearch = $searches['departamento'] ?? $searches['departamento_nombre'] ?? null;
        if (filled($deptoSearch) && strtoupper(trim((string) $deptoSearch)) !== 'TODOS') {
            $termDepto = '%' . trim((string) $deptoSearch) . '%';
            $invQuery->where(function (Builder $q) use ($termDepto) {
                $q->whereHas('departamento', fn ($sub) => $sub->where('nombre', 'like', $termDepto))
                    ->orWhereHas('area.departamento', fn ($sub) => $sub->where('nombre', 'like', $termDepto))
                    ->orWhere('ubicacion_fisica', 'like', $termDepto);
            });
        }

        $equipoSearch = $searches['equipo'] ?? $searches['material_nombre'] ?? null;
        if (filled($equipoSearch)) {
            $termEquipo = '%' . trim((string) $equipoSearch) . '%';
            $invQuery->where(function (Builder $q) use ($termEquipo) {
                $q->whereHas('material', fn ($sub) => $sub->where('nombre', 'like', $termEquipo))
                    ->orWhere('subtipo', 'like', $termEquipo)
                    ->orWhere('codigo', 'like', $termEquipo)
                    ->orWhere('num_serie', 'like', $termEquipo)
                    ->orWhere('modelo', 'like', $termEquipo);
            });
        }

        $catSearch = $searches['categoria'] ?? $searches['material_tipo_nombre'] ?? null;
        if (filled($catSearch)) {
            $catTrim = trim((string) $catSearch);
            $invQuery->where(function (Builder $q) use ($catTrim) {
                $q->whereHas('material.tipo', fn ($sub) => $sub->where('nombre', 'like', $catTrim . '%'))
                    ->orWhere('subtipo', 'like', "%{$catTrim}%");
            });
        }

        if (filled($searches['estado'] ?? null)) {
            $invQuery->where('estado', trim((string) $searches['estado']));
        }

        $areaSearch = $searches['area'] ?? $searches['ubicacion_fisica'] ?? null;
        if (filled($areaSearch)) {
            $termArea = '%' . trim((string) $areaSearch) . '%';
            $invQuery->where(function (Builder $q) use ($termArea) {
                $q->whereHas('area', fn ($sub) => $sub->where('nombre', 'like', $termArea))
                    ->orWhere('ubicacion_fisica', 'like', $termArea);
            });
        }

        if (filled($searches['aula'] ?? null)) {
            $termAula = '%' . trim((string) $searches['aula']) . '%';
            $invQuery->where(function (Builder $q) use ($termAula) {
                $q->where('inventario.aula', 'like', $termAula)
                    ->orWhereHas('salon', fn ($sub) => $sub->where('nombre', 'like', $termAula))
                    ->orWhere('especificaciones->aula', 'like', $termAula)
                    ->orWhere('ubicacion_fisica', 'like', $termAula);
            });
        }

        if (filled($searches['num_serie'] ?? null)) {
            $termSerie = '%' . trim((string) $searches['num_serie']) . '%';
            $invQuery->where(function (Builder $q) use ($termSerie) {
                $q->where('num_serie', 'like', $termSerie)
                    ->orWhere('codigo', 'like', $termSerie);
            });
        }

        if (filled($searches['ip_address'] ?? null)) {
            $termIp = '%' . trim((string) $searches['ip_address']) . '%';
            $invQuery->where(function (Builder $q) use ($termIp) {
                $q->where('ip_address', 'like', $termIp)
                    ->orWhere('especificaciones->ip_address', 'like', $termIp);
            });
        }

        if (filled($searches['mac_address'] ?? null)) {
            $termMac = '%' . trim((string) $searches['mac_address']) . '%';
            $invQuery->where(function (Builder $q) use ($termMac) {
                $q->where('mac_address', 'like', $termMac)
                    ->orWhere('especificaciones->mac_address', 'like', $termMac);
            });
        }

        if (filled($searches['busqueda'] ?? null)) {
            $term = '%' . trim((string) $searches['busqueda']) . '%';
            $invQuery->where(function (Builder $q) use ($term) {
                $q->where('num_serie', 'like', $term)
                    ->orWhere('codigo', 'like', $term)
                    ->orWhere('subtipo', 'like', $term)
                    ->orWhere('modelo', 'like', $term)
                    ->orWhere('ubicacion_fisica', 'like', $term)
                    ->orWhere('mac_address', 'like', $term)
                    ->orWhere('ip_address', 'like', $term)
                    ->orWhereHas('area', fn ($a) => $a->where('nombre', 'like', $term))
                    ->orWhereHas('material', fn ($m) => $m->where('nombre', 'like', $term)
                        ->orWhereHas('marca', fn ($b) => $b->where('nombre', 'like', $term)));
            });
        }

        if (filled($searches['fecha_desde'] ?? null)) {
            $invQuery->whereDate('fecha_registro', '>=', $searches['fecha_desde']);
        }
        if (filled($searches['fecha_hasta'] ?? null)) {
            $invQuery->whereDate('fecha_registro', '<=', $searches['fecha_hasta']);
        }

        $direction = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        return match ($sortColumn) {
            'departamento' => $invQuery->leftJoin('departamento', 'inventario.departamento_id', '=', 'departamento.id_departamento')
                ->orderBy('departamento.nombre', $direction),
            'equipo' => $invQuery->leftJoin('material', 'inventario.id_producto', '=', 'material.id_producto')
                ->orderBy('material.nombre', $direction),
            'categoria' => $invQuery->leftJoin('material', 'inventario.id_producto', '=', 'material.id_producto')
                ->leftJoin('tipo_material', 'material.id_tipodematerial', '=', 'tipo_material.id_tipodematerial')
                ->orderBy('tipo_material.nombre', $direction),
            'estado' => $invQuery->orderBy('estado', $direction),
            'area' => $invQuery->leftJoin('area', 'inventario.area_id', '=', 'area.id_area')
                ->orderBy('area.nombre', $direction),
            'aula' => $invQuery->leftJoin('salones', 'inventario.salon_id', '=', 'salones.id')
                ->orderByRaw("COALESCE(NULLIF(inventario.aula, ''), salones.nombre) " . ($direction === 'asc' ? 'ASC' : 'DESC')),
            'num_serie' => $invQuery->orderBy('num_serie', $direction),
            'ip_address' => $invQuery->orderBy('ip_address', $direction),
            'mac_address' => $invQuery->orderBy('mac_address', $direction),
            default => $invQuery->orderBy('inventario.created_at', $direction),
        };
    }

    public static function obtenerInventarios(array $searches): Collection
    {
        return static::obtenerQuery($searches)->take(50)->get();
    }

    public static function obtenerCategoriasTop(): array
    {
        $ver = SigemReportExporter::cacheVersion();

        return Cache::remember("sigem_dash_cat_top_v{$ver}", SigemReportExporter::CACHE_TTL_SECONDS, function () {
            $todasCategorias = Inventario::query()
                ->join('material', 'inventario.id_producto', '=', 'material.id_producto')
                ->leftJoin('tipo_material', 'material.id_tipodematerial', '=', 'tipo_material.id_tipodematerial')
                ->selectRaw("COALESCE(tipo_material.nombre, 'General') as cat, count(*) as total")
                ->groupBy('cat')
                ->orderByDesc('total')
                ->pluck('total', 'cat')
                ->toArray();

            $top = [];
            $otras = 0;
            $idx = 0;
            foreach ($todasCategorias as $catNom => $catCant) {
                if ($idx < 8) {
                    $top[$catNom] = $catCant;
                } else {
                    $otras += $catCant;
                }
                $idx++;
            }
            if ($otras > 0) {
                $top['Otras categorías'] = $otras;
            }

            return $top;
        });
    }
}

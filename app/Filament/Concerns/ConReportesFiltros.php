<?php

namespace App\Filament\Concerns;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\Salon;
use App\Support\SigemReportExporter;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;

trait ConReportesFiltros
{
    public ?string $filtroFechaDesde = null;

    public ?string $filtroFechaHasta = null;

    public ?string $filtroDepartamentoId = '';

    public ?string $filtroAreaId = '';

    public ?string $filtroAula = '';

    public function updatedFiltroDepartamentoId(): void
    {
        $this->filtroAreaId = '';
        $this->filtroAula = '';
        $this->cerrarNuevoReporte();
    }

    public function updatedFiltroAreaId(): void
    {
        $this->filtroAula = '';
        $this->cerrarNuevoReporte();
    }

    public function limpiarFiltrosNuevosReportes(): void
    {
        $this->filtroFechaDesde = null;
        $this->filtroFechaHasta = null;
        $this->filtroDepartamentoId = '';
        $this->filtroAreaId = '';
        $this->filtroAula = '';
        $this->cerrarNuevoReporte();
    }

    #[Computed]
    public function departamentosParaFiltros(): array
    {
        return $this->getDepartamentosParaFiltros();
    }

    public function getDepartamentosParaFiltros(): array
    {
        $ver = SigemReportExporter::cacheVersion();

        return Cache::remember("sigem_rep_deptos_v{$ver}", SigemReportExporter::CACHE_TTL_SECONDS, function () {
            return Departamento::orderBy('nombre')->pluck('nombre', 'id_departamento')->toArray();
        });
    }

    #[Computed]
    public function areasParaFiltros(): array
    {
        if (blank($this->filtroDepartamentoId) || strtoupper((string) $this->filtroDepartamentoId) === 'TODOS') {
            return Area::orderBy('nombre')->pluck('nombre', 'id_area')->toArray();
        }

        return Area::where('id_departamento', (int) $this->filtroDepartamentoId)
            ->orderBy('nombre')
            ->pluck('nombre', 'id_area')
            ->toArray();
    }

    #[Computed]
    public function aulasParaFiltros(): array
    {
        $areaId = filled($this->filtroAreaId) ? (int) $this->filtroAreaId : null;
        $deptoId = (! $areaId && filled($this->filtroDepartamentoId) && strtoupper((string) $this->filtroDepartamentoId) !== 'TODOS')
            ? (int) $this->filtroDepartamentoId
            : null;

        $aulasInv = Inventario::query()
            ->when($areaId, fn ($q) => $q->where('area_id', $areaId))
            ->when($deptoId, function ($q) use ($deptoId) {
                $q->where(function ($sub) use ($deptoId) {
                    $sub->where('departamento_id', $deptoId)
                        ->orWhereHas('area', fn ($aq) => $aq->where('id_departamento', $deptoId));
                });
            })
            ->whereNotNull('aula')
            ->where('aula', '!=', '')
            ->distinct()
            ->pluck('aula');

        $aulasSalon = Salon::query()
            ->when($areaId, fn ($q) => $q->where('area_id', $areaId))
            ->when($deptoId, fn ($q) => $q->whereHas('area', fn ($aq) => $aq->where('id_departamento', $deptoId)))
            ->whereNotNull('nombre')
            ->where('nombre', '!=', '')
            ->distinct()
            ->pluck('nombre');

        return $aulasInv->merge($aulasSalon)
            ->map(fn ($a) => trim((string) $a))
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    protected function obtenerFiltrosNuevosReportes(): array
    {
        return [
            'fecha_desde' => $this->filtroFechaDesde,
            'fecha_hasta' => $this->filtroFechaHasta,
            'departamento_id' => $this->filtroDepartamentoId,
            'area_id' => $this->filtroAreaId,
            'aula' => $this->filtroAula,
        ];
    }
}

<?php

namespace App\Filament\Concerns;

use App\Models\Area;
use App\Models\Departamento;
use App\Support\SigemReportExporter;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;

trait ConReportesFiltros
{
    public ?string $filtroFechaDesde = null;

    public ?string $filtroFechaHasta = null;

    public ?string $filtroDepartamentoId = '';

    public ?string $filtroAreaId = '';

    public function updatedFiltroFechaDesde(): void
    {
        $this->reiniciarPaginacionNuevosReportes();
    }

    public function updatedFiltroFechaHasta(): void
    {
        $this->reiniciarPaginacionNuevosReportes();
    }

    public function updatedFiltroDepartamentoId(): void
    {
        $this->filtroAreaId = '';
        $this->reiniciarPaginacionNuevosReportes();
    }

    public function updatedFiltroAreaId(): void
    {
        $this->reiniciarPaginacionNuevosReportes();
    }

    public function limpiarFiltrosNuevosReportes(): void
    {
        $this->filtroFechaDesde = null;
        $this->filtroFechaHasta = null;
        $this->filtroDepartamentoId = '';
        $this->filtroAreaId = '';
        $this->reiniciarPaginacionNuevosReportes();
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

    protected function obtenerFiltrosNuevosReportes(): array
    {
        return [
            'fecha_desde' => $this->filtroFechaDesde,
            'fecha_hasta' => $this->filtroFechaHasta,
            'departamento_id' => $this->filtroDepartamentoId,
            'area_id' => $this->filtroAreaId,
        ];
    }
}

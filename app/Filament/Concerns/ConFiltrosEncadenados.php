<?php

namespace App\Filament\Concerns;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\Salon;

trait ConFiltrosEncadenados
{
    /**
     * Computed property: Áreas disponibles según el departamento seleccionado en los filtros de tabla.
     *
     * @return array<string, string>
     */
    public function getAreasFiltradasProperty(): array
    {
        $deptoRaw = trim((string) ($this->tableColumnSearches['departamento'] ?? ''));

        if ($deptoRaw === '' || strtoupper($deptoRaw) === 'TODOS') {
            return ['' => 'Todas'] + Area::orderBy('nombre')->pluck('nombre', 'nombre')->toArray();
        }

        $depto = is_numeric($deptoRaw)
            ? Departamento::find((int) $deptoRaw)
            : Departamento::where('nombre', $deptoRaw)->first();

        if (! $depto) {
            return ['' => 'Todas'];
        }

        return ['' => 'Todas'] + Area::where('id_departamento', $depto->id_departamento)
            ->orderBy('nombre')
            ->pluck('nombre', 'nombre')
            ->toArray();
    }

    /**
     * Computed property: Aulas disponibles según el área (o departamento) seleccionado en los filtros de tabla.
     *
     * @return array<string, string>
     */
    public function getAulasFiltradasProperty(): array
    {
        $deptoRaw = trim((string) ($this->tableColumnSearches['departamento'] ?? ''));
        $areaRaw = trim((string) ($this->tableColumnSearches['area'] ?? ''));

        $areaId = null;
        if ($areaRaw !== '' && strtoupper($areaRaw) !== 'TODAS') {
            $area = is_numeric($areaRaw)
                ? Area::find((int) $areaRaw)
                : Area::where('nombre', $areaRaw)
                    ->when($deptoRaw !== '' && strtoupper($deptoRaw) !== 'TODOS', function ($q) use ($deptoRaw) {
                        $depto = is_numeric($deptoRaw)
                            ? Departamento::find((int) $deptoRaw)
                            : Departamento::where('nombre', $deptoRaw)->first();
                        if ($depto) {
                            $q->where('id_departamento', $depto->id_departamento);
                        }
                    })
                    ->first();
            $areaId = $area?->id_area;
        }

        $deptoId = null;
        if (! $areaId && $deptoRaw !== '' && strtoupper($deptoRaw) !== 'TODOS') {
            $depto = is_numeric($deptoRaw)
                ? Departamento::find((int) $deptoRaw)
                : Departamento::where('nombre', $deptoRaw)->first();
            $deptoId = $depto?->id_departamento;
        }

        $aulasInv = Inventario::query()
            ->when($areaId, fn ($q) => $q->where('area_id', $areaId))
            ->when(! $areaId && $deptoId, function ($q) use ($deptoId) {
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
            ->when(! $areaId && $deptoId, fn ($q) => $q->whereHas('area', fn ($aq) => $aq->where('id_departamento', $deptoId)))
            ->whereNotNull('nombre')
            ->where('nombre', '!=', '')
            ->distinct()
            ->pluck('nombre');

        $aulas = $aulasInv->merge($aulasSalon)
            ->map(fn ($a) => trim((string) $a))
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $opciones = ['' => 'Todas'];
        foreach ($aulas as $aula) {
            $opciones[$aula] = $aula;
        }

        return $opciones;
    }
}

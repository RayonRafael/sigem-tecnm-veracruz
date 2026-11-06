<?php

namespace App\Support;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\Salon;
use Filament\Forms;

class UbicacionHelper
{
    /**
     * Construye una cadena descriptiva de ubicación jerárquica:
     * Departamento > Área > Aula X > Estación Y
     */
    public static function construir($departamentoId, $areaId, $salonId = null, $estacion = null): string
    {
        $partes = [];

        $departamento = $departamentoId ? Departamento::find($departamentoId) : null;
        $area = $areaId ? Area::with('departamento')->find($areaId) : null;
        $salon = $salonId ? Salon::with('area.departamento')->find($salonId) : null;

        if (! $area && $salon?->area) {
            $area = $salon->area;
        }

        if (! $departamento && $area?->departamento) {
            $departamento = $area->departamento;
        }

        if ($departamento?->nombre) {
            $partes[] = trim($departamento->nombre);
        }

        if ($area?->nombre) {
            $partes[] = trim($area->nombre);
        }

        if ($salon?->nombre) {
            $nombreSalon = trim($salon->nombre);
            $partes[] = str_starts_with(strtolower($nombreSalon), 'aula')
                ? $nombreSalon
                : 'Aula ' . $nombreSalon;
        }

        if (! empty($estacion)) {
            $estacionStr = trim((string) $estacion);
            $partes[] = str_starts_with(strtolower($estacionStr), 'estación') || str_starts_with(strtolower($estacionStr), 'estacion')
                ? $estacionStr
                : 'Estación ' . $estacionStr;
        }

        return implode(' > ', $partes);
    }

    /**
     * Actualiza el campo 'ubicacion_fisica' en el formulario reactivo de Filament.
     */
    public static function actualizarDescriptiva(Forms\Set $set, Forms\Get $get): void
    {
        $partes = [];
        if ($deptoId = $get('departamento_id')) {
            $depto = Departamento::find($deptoId);
            if ($depto) {
                $partes[] = $depto->nombre;
            }
        }
        if ($areaId = $get('area_id')) {
            $area = Area::find($areaId);
            if ($area) {
                $partes[] = $area->nombre;
            }
        }
        if ($aula = $get('especificaciones.aula')) {
            $partes[] = "Aula {$aula}";
        }
        if (! empty($partes)) {
            $set('ubicacion_fisica', implode(' > ', $partes));
        }
    }
}

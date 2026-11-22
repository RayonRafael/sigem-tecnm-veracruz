<?php

namespace App\Filament\Concerns;

use App\Support\SigemBajaFormatHelper;
use App\Support\SigemExcelExporter;
use App\Support\SigemPdfGenerator;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

trait ConExportacionMasiva
{
    /**
     * Exporta a Excel (.xlsx) únicamente los registros seleccionados.
     */
    public function exportarSeleccion()
    {
        if (empty($this->selectedRows)) {
            Notification::make()->title('No hay equipos seleccionados para exportar')->warning()->send();

            return null;
        }

        return SigemExcelExporter::exportarInventario($this, $this->selectedRows);
    }

    /**
     * Devuelve los registros seleccionados elegibles para Formato 4 (Dado de baja / Solicitud de baja).
     */
    public function obtenerRegistrosBajaSeleccionados(): Collection
    {
        return SigemBajaFormatHelper::filtrarInventariosEnBaja($this->obtenerRegistrosSeleccionados());
    }

    /**
     * Descarga en un PDF multipágina los Formatos de Baja (Formato 4) de los bienes en baja seleccionados.
     */
    public function descargarFormatosBajaLote()
    {
        $bajas = $this->obtenerRegistrosBajaSeleccionados();

        if ($error = SigemBajaFormatHelper::mensajeErrorLote($bajas)) {
            Notification::make()
                ->title($error)
                ->warning()
                ->send();

            return null;
        }

        if ($bajas->count() === 1) {
            return SigemPdfGenerator::descargarFormatoBaja($bajas->first());
        }

        return SigemPdfGenerator::descargarFormatosBajaLote($bajas);
    }
}

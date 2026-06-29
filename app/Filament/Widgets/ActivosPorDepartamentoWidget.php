<?php

namespace App\Filament\Widgets;

use App\Models\Departamento;
use App\Models\Inventario;
use Filament\Widgets\ChartWidget;

class ActivosPorDepartamentoWidget extends ChartWidget
{
    protected static ?string $heading = 'Activos por Departamento';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 8;

    protected function getData(): array
    {
        $departamentos = Departamento::all();
        $labels = [];
        $datos = [];

        foreach ($departamentos as $depto) {
            $labels[] = $depto->nombre;
            $datos[] = Inventario::whereHas('detallesSolicitud.solicitud.receptor.area', function ($query) use ($depto) {
                $query->where('id_departamento', $depto->id_departamento);
            })->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Activos asignados',
                    'data' => $datos,
                    'backgroundColor' => '#1b65d4',
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}

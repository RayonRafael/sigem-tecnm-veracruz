<?php

namespace App\Filament\Widgets;

use App\Models\Inventario;
use Filament\Widgets\ChartWidget;

class ActivosPorEstadoWidget extends ChartWidget
{
    protected static ?string $heading = 'Activos por Estado';
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $estados = ['Disponible', 'Asignado', 'En Mantenimiento', 'Dañado', 'Baja', 'Devuelto a Proveedor'];
        $conteos = [];

        foreach ($estados as $estado) {
            $conteos[] = Inventario::where('estado', $estado)->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Cantidad de Activos',
                    'data' => $conteos,
                    'backgroundColor' => [
                        '#10B981',
                        '#3B82F6',
                        '#F59E0B',
                        '#EF4444',
                        '#6B7280',
                        '#8B5CF6',
                    ],
                ],
            ],
            'labels' => $estados,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
<?php

namespace App\Filament\Widgets;

use App\Models\Inventario;
use Filament\Widgets\ChartWidget;

class ActivosPorEstadoWidget extends ChartWidget
{
    protected static ?string $heading = 'Activos por Estado';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 8;

    protected function getData(): array
    {
        $estados = [
            'Disponible', 
            'Asignado', 
            'En Mantenimiento', 
            'Dañado', 
            'Baja', 
            'Devuelto a Proveedor'
        ];
        
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
                        '#0f9d58', // Verde (Disponible)
                        '#1b65d4', // Azul TecNM (Asignado)
                        '#f4a623', // Naranja (En Mantenimiento)
                        '#d93025', // Rojo (Dañado)
                        '#6b7280', // Gris (Baja)
                        '#0b1d3a', // Azul Marino Oscuro (Devuelto a Proveedor)
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $estados,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right',
                ],
            ],
        ];
    }
}
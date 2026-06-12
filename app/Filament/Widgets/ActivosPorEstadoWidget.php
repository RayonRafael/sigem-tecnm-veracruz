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
                        '#235B4E', // Verde (Disponible)
                        '#1B396A', // Azul (Asignado)
                        '#B38E5D', // Dorado (En Mantenimiento)
                        '#9D2449', // Guinda (Dañado)
                        '#807E82', // Gris (Baja)
                        '#4B5563', // Gris Oscuro (Devuelto a Proveedor)
                    ],
                    'borderColor' => [
                        '#194339',
                        '#13284B',
                        '#8F714A',
                        '#7A1B39',
                        '#5F5E60',
                        '#374151',
                    ],
                    'borderWidth' => 2,
                    'borderRadius' => 8,
                    'borderSkipped' => false,
                ],
            ],
            'labels' => $estados,
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
                        'precision' => 0,
                    ],
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.05)',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'padding' => 12,
                    'cornerRadius' => 8,
                    'titleFont' => [
                        'size' => 14,
                        'weight' => 'bold',
                    ],
                    'bodyFont' => [
                        'size' => 13,
                    ],
                ],
            ],
            'animation' => [
                'duration' => 1000,
                'easing' => 'easeOutQuart',
            ],
        ];
    }
}
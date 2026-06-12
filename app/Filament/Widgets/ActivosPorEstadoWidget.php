<?php

namespace App\Filament\Widgets;

use App\Models\Inventario;
use Filament\Widgets\ChartWidget;

class ActivosPorEstadoWidget extends ChartWidget
{
    protected static ?string $heading = 'Activos por Estado';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

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
                        '#10B981',
                        '#3B82F6',
                        '#F59E0B',
                        '#EF4444',
                        '#6B7280',
                        '#8B5CF6',
                    ],
                    'borderColor' => [
                        '#059669',
                        '#2563EB',
                        '#D97706',
                        '#DC2626',
                        '#4B5563',
                        '#7C3AED',
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
<?php

namespace App\Filament\Widgets;

use App\Models\Inventario;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ActivosPorEstadoWidget extends ChartWidget
{
    protected static ?string $heading = 'Activos por Estado';
    
    protected static ?int $sort = 5; // Para que aparezca debajo de las tarjetas

    protected function getData(): array
    {
        // Consulta para contar inventarios agrupados por su estado
        $data = Inventario::query()
            ->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return [
            'datasets' => [
                [
                    'label' => 'Cantidad de Equipos',
                    'data' => $data->values()->toArray(),
                    'backgroundColor' => [
                        '#10B981', // Verde (Disponible)
                        '#3B82F6', // Azul (Asignado)
                        '#F59E0B', // Naranja (En Mantenimiento)
                        '#EF4444', // Rojo (Dañado)
                        '#6B7280', // Gris (Baja)
                        '#8B5CF6', // Morado (Devuelto)
                    ],
                ],
            ],
            'labels' => $data->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
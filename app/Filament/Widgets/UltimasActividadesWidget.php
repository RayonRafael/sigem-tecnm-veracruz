<?php

namespace App\Filament\Widgets;

use App\Models\BitacoraSistema;
use Filament\Widgets\Widget;

class UltimasActividadesWidget extends Widget
{
    protected static ?string $heading = 'Últimas Actividades';
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 8;
    protected static string $view = 'filament.widgets.ultimas-actividades-widget';

    protected function getViewData(): array
    {
        $actividades = BitacoraSistema::with('usuario')
            ->orderBy('fecha_hora', 'desc')
            ->limit(6)
            ->get();

        return [
            'actividades' => $actividades,
        ];
    }
}

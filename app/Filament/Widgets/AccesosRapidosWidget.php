<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class AccesosRapidosWidget extends Widget
{
    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = 4;
    protected static string $view = 'filament.widgets.accesos-rapidos-widget';
}
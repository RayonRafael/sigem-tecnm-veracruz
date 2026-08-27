<?php

namespace App\Filament\ServicioSocial\Pages;

use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Solicitud;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Panel';
    protected static ?string $navigationGroup = 'Panel';
    protected static ?string $title = 'SIGEM - Servicio Social';
    protected static string $view = 'filament.servicio-social.pages.dashboard';

    public function getColumns(): int | string | array
    {
        return [
            'default' => 12,
            'sm'      => 12,
            'md'      => 12,
            'lg'      => 12,
            'xl'      => 12,
            '2xl'     => 12,
        ];
    }

    public function getViewData(): array
    {
        $userId = auth()->id();

        // 1. Welcome Card Stats (Specific for SS as requested)
        $misSolicitudes = Solicitud::where('id_usuario', $userId)->count();
        $misSolicitudesPendientes = Solicitud::where('id_usuario', $userId)->where('estado', 'Pendiente')->count();
        $misMantenimientos = Mantenimiento::where('id_usuario_solicita', $userId)->count();
        $totalActivos = Inventario::count();
        
        // Bitacora activity just for this user
        $actividadReciente = \App\Models\BitacoraSistema::with('usuario')->where('id_usuario', $userId)->latest('fecha_hora')->limit(5)->get();

        return [
            'misSolicitudes' => $misSolicitudes,
            'misSolicitudesPendientes' => $misSolicitudesPendientes,
            'misMantenimientos' => $misMantenimientos,
            'totalActivos' => $totalActivos,
            'actividadReciente' => $actividadReciente,
        ];
    }
}

<?php

namespace App\Filament\ServicioSocial\Pages;

use App\Models\BitacoraSistema;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Solicitud;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Carbon;

class Dashboard extends BaseDashboard
{
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

        // 1. Welcome Card Stats
        $misRegistrosHoy = Inventario::where('id_usuario', $userId)
            ->whereDate('created_at', Carbon::today())
            ->count();

        $pendientesAprobacion = Inventario::where('id_usuario', $userId)
            ->where('aprobado', false)
            ->count();

        $reportesActivos = Mantenimiento::where('id_usuario_solicita', $userId)
            ->whereIn('estado', ['Pendiente', 'Solicitado', 'En Proceso', 'En mantenimiento'])
            ->count();

        // 2. Solicitudes (mini tabla 3 rows)
        $solicitudesRecientes = Solicitud::with('usuario')
            ->where('id_usuario', $userId)
            ->latest('created_at')
            ->limit(3)
            ->get();

        // 3. Mantenimiento (mini tabla 3 rows)
        $mantenimientosRecientes = Mantenimiento::with(['inventario', 'inventario.material', 'usuarioSolicita'])
            ->where('id_usuario_solicita', $userId)
            ->latest('created_at')
            ->limit(3)
            ->get();

        return [
            'misRegistrosHoy' => $misRegistrosHoy,
            'pendientesAprobacion' => $pendientesAprobacion,
            'reportesActivos' => $reportesActivos,
            'solicitudesRecientes' => $solicitudesRecientes,
            'mantenimientosRecientes' => $mantenimientosRecientes,
        ];
    }
}

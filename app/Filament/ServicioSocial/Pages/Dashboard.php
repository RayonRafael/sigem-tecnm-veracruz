<?php

namespace App\Filament\ServicioSocial\Pages;

use App\Models\BitacoraSistema;
use App\Models\Inventario;
use App\Models\Mantenimiento;
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

        // 1. Mis registros hoy
        $misRegistrosHoy = Inventario::where('id_usuario', $userId)
            ->whereDate('created_at', Carbon::today())
            ->count();

        // 2. Pendientes de aprobación
        $pendientesAprobacion = Inventario::where('id_usuario', $userId)
            ->where('aprobado', false)
            ->count();

        // 3. Reportes activos
        $reportesActivos = Mantenimiento::where('id_usuario_solicita', $userId)
            ->whereIn('estado', ['Pendiente', 'Solicitado', 'En Proceso', 'En mantenimiento'])
            ->count();

        // Panel de Mis tareas pendientes
        $tareasPendientes = Inventario::with('material')
            ->where('id_usuario', $userId)
            ->where('aprobado', false)
            ->latest()
            ->limit(5)
            ->get();

        // Panel de Actividad reciente
        $actividadReciente = BitacoraSistema::with('usuario')
            ->where('id_usuario', $userId)
            ->latest('fecha_hora')
            ->limit(5)
            ->get();

        return [
            'misRegistrosHoy' => $misRegistrosHoy,
            'pendientesAprobacion' => $pendientesAprobacion,
            'reportesActivos' => $reportesActivos,
            'tareasPendientes' => $tareasPendientes,
            'actividadReciente' => $actividadReciente,
        ];
    }
}

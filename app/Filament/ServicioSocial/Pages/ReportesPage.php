<?php

namespace App\Filament\ServicioSocial\Pages;

use App\Enums\RoleEnum;
use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Pages\ReportesPage as BaseReportesPage;

class ReportesPage extends BaseReportesPage
{
    use ConDashboardBreadcrumb;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Gestión de inventario';

    protected static ?string $navigationLabel = 'Reportes';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'reportes';

    protected static ?string $title = '📊 Reportes';

    protected static string $view = 'filament.servicio-social.pages.reportes';

    public function getTitle(): string
    {
        return '📊 Reportes';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public static function canAccess(): bool
    {
        return auth()->user() && (
            auth()->user()->hasRole(RoleEnum::SERVICIO_SOCIAL->value) ||
            auth()->user()->hasPermissionTo(RoleEnum::PERM_ACCESS_SERVICIO_SOCIAL) ||
            auth()->user()->hasRole(RoleEnum::ADMIN->value)
        );
    }
}

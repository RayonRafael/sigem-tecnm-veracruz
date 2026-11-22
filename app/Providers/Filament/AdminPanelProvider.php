<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Providers\Filament\Concerns\ConPanelConfig;
use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    use ConPanelConfig;

    public function panel(Panel $panel): Panel
    {
        return $this->configureBasePanel($panel, [
            'brandName' => 'SIGEM - TecNM Veracruz',
            'loginBadgeClasses' => 'bg-blue-50 text-blue-700 border border-blue-200',
            'loginBadgeLabel' => '🔒 Panel de Administración',
            'switchPanelUrl' => '/servicio-social/login',
            'switchPanelLabel' => '¿Eres de Servicio Social? Inicia sesión aquí',
            'logoutRoute' => 'filament.admin.auth.logout',
            'mostrarRolePill' => false,
            'roleBannerClass' => 'sigem-role-banner sigem-role-admin',
            'roleBannerTitle' => 'Acceso completo de administración',
            'roleBannerLabel' => '👑 Administrador',
            'sectionRoutes' => [
                'inventarios' => 'Inventario general',
                'solicituds' => 'Lista de solicitudes de materiales',
                'mantenimientos' => 'Mantenimiento',
                'configuracion' => 'Configuración y Catálogos',
                'reportes' => 'Reportes',
                'users' => 'Usuarios',
                'profile' => 'Mi Perfil',
            ],
        ])
            ->default()
            ->id('admin')
            ->path('admin')
            ->navigationGroups($this->makeNavigationGroups([
                'Panel principal',
                'Gestión de inventario',
                'Catálogos',
                'Administración',
            ]))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ]);
    }
}

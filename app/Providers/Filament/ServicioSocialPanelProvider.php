<?php

namespace App\Providers\Filament;

use App\Filament\ServicioSocial\Pages\Dashboard;
use App\Providers\Filament\Concerns\ConPanelConfig;
use Filament\Panel;
use Filament\PanelProvider;

class ServicioSocialPanelProvider extends PanelProvider
{
    use ConPanelConfig;

    public function panel(Panel $panel): Panel
    {
        return $this->configureBasePanel($panel, [
            'brandName' => 'SIGEM - Servicio Social',
            'loginBadgeClasses' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            'loginBadgeLabel' => '👥 Panel de Servicio Social',
            'switchPanelUrl' => '/admin/login',
            'switchPanelLabel' => '¿Eres Administrador? Inicia sesión aquí',
            'logoutRoute' => 'filament.servicio-social.auth.logout',
            'mostrarRolePill' => true,
            'roleBannerClass' => 'sigem-role-banner',
            'roleBannerTitle' => null,
            'roleBannerLabel' => '🎓 Servicio Social',
            'sectionRoutes' => [
                'inventarios' => 'Inventario general',
                'solicituds' => 'Lista de solicitudes de materiales',
                'mantenimientos' => 'Mantenimiento',
                'catalogos' => 'Catálogos',
                'reportes' => 'Reportes',
                'profile' => 'Mi Perfil',
            ],
        ])
            ->id('servicio-social')
            ->path('servicio-social')
            ->navigationGroups($this->makeNavigationGroups([
                'Panel principal',
                'Gestión de inventario',
            ]))
            ->discoverResources(in: app_path('Filament/ServicioSocial/Resources'), for: 'App\\Filament\\ServicioSocial\\Resources')
            ->discoverPages(in: app_path('Filament/ServicioSocial/Pages'), for: 'App\\Filament\\ServicioSocial\\Pages')
            ->pages([
                Dashboard::class,
            ]);
    }
}

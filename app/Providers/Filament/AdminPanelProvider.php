<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('SIGEM - TecNM Veracruz')
            ->colors([
                'primary' => Color::hex('#1B396A'),      // Azul Institucional (Pantone 294 C)
                'success' => Color::hex('#235B4E'),      // Verde (Pantone 626 C)
                'warning' => Color::hex('#B38E5D'),      // Dorado (Pantone 465 C)
                'danger'  => Color::hex('#9D2449'),      // Guinda (Pantone 7420 C)
                'info'    => Color::hex('#1B396A'),      // Azul
                'gray'    => Color::hex('#807E82'),      // Gris (Cool Gray 10 C)
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->widgets([
                \App\Filament\Widgets\StatsOverview::class,
                \App\Filament\Widgets\ActivosPorEstadoWidget::class,
                \App\Filament\Widgets\AlertasWidget::class,
                \App\Filament\Widgets\UltimasActividadesWidget::class,
                \App\Filament\Widgets\RegistroRapidoWidget::class,
                \App\Filament\Widgets\AccesosRapidosWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
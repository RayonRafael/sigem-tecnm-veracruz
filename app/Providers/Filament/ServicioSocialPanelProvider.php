<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ServicioSocialPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('servicio-social')
            ->path('servicio-social')
            ->login()
            ->brandName('SIGEM - Servicio Social')
            ->brandLogo(asset('images/sigem-logo.svg'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/sigem-favicon.svg'))
            ->font('DM Sans', 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap')
            ->colors([
                'primary' => Color::hex('#1b65d4'),
                'success' => Color::hex('#0f9d58'),
                'warning' => Color::hex('#f4a623'),
                'danger' => Color::hex('#d93025'),
                'info' => Color::hex('#0b1d3a'),
                'gray' => Color::hex('#6b7280'),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('<style>
                    /* Fondo del sidebar: azul marino oscuro (#0b1d3a) y texto claro */
                    aside.fi-sidebar {
                        background-color: #0b1d3a !important;
                        color: #ffffff !important;
                    }
                    aside.fi-sidebar a, aside.fi-sidebar .fi-nav-link {
                        color: rgba(255, 255, 255, 0.75) !important;
                    }
                    aside.fi-sidebar .fi-nav-link.active {
                        background-color: rgba(27, 101, 212, 0.3) !important;
                        color: #ffffff !important;
                    }
                    aside.fi-sidebar .fi-nav-link:hover {
                        background-color: rgba(255, 255, 255, 0.1) !important;
                        color: #ffffff !important;
                    }
                    aside.fi-sidebar .fi-nav-group-label {
                        color: rgba(255, 255, 255, 0.6) !important;
                    }
                    /* Fondo de la aplicación: gris claro (#f0f4f8) */
                    main.fi-main, body {
                        background-color: #f0f4f8 !important;
                    }
                    /* JetBrains Mono para datos numéricos/códigos */
                    @import url("https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&display=swap");
                    .font-mono, .fi-ta-text-item-label code, .fi-ta-text-item-label[style*="monospace"] {
                        font-family: "JetBrains Mono", monospace !important;
                    }
                    /* ===== LOGIN CUSTOMIZATION ===== */
                    .fi-simple-layout {
                        background-image: url("{{ asset("images/fondo.jpg") }}") !important;
                        background-size: cover !important;
                        background-position: center !important;
                        background-attachment: fixed !important;
                        position: relative;
                        z-index: 1;
                    }
                    .fi-simple-layout::before {
                        content: "";
                        position: absolute;
                        top: 0; left: 0; right: 0; bottom: 0;
                        background: rgba(11, 29, 58, 0.75) !important;
                        z-index: -1;
                    }
                    .fi-simple-main-ctn > div {
                        background: rgba(255, 255, 255, 0.95) !important;
                        backdrop-filter: blur(8px) !important;
                        border-radius: 12px !important;
                        box-shadow: 0 10px 30px rgba(0,0,0,0.3) !important;
                        border: 1px solid rgba(255,255,255,0.2) !important;
                    }
                </style>')
            )
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Panel')
                    ->collapsible(true),
                NavigationGroup::make()
                    ->label('Inventario')
                    ->collapsible(true),
                NavigationGroup::make()
                    ->label('Catálogos')
                    ->collapsible(true),
            ])
            ->spa()
            ->sidebarWidth('20rem')
            ->discoverResources(in: app_path('Filament/ServicioSocial/Resources'), for: 'App\\Filament\\ServicioSocial\\Resources')
            ->discoverPages(in: app_path('Filament/ServicioSocial/Pages'), for: 'App\\Filament\\ServicioSocial\\Pages')
            ->pages([
                \App\Filament\ServicioSocial\Pages\Dashboard::class,
            ])
            ->widgets([])
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

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
            ->brandLogo(asset('images/sigem-logo.svg'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/sigem-logo.svg'))
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
                    /* ===== FONDO GENERAL Y APP ===== */
                    main.fi-main, body {
                        background: radial-gradient(circle at top right, rgba(27,101,212,0.1), transparent 40%),
                                    radial-gradient(circle at bottom left, rgba(11,29,58,0.05), transparent 40%),
                                    #f0f4f8 !important;
                    }
                    .dark main.fi-main, .dark body {
                        background: radial-gradient(circle at top right, rgba(27,101,212,0.1), transparent 40%),
                                    radial-gradient(circle at bottom left, rgba(11,29,58,0.05), transparent 40%),
                                    #0d1117 !important;
                    }

                    /* ===== TOPBAR ====== */
                    .fi-topbar {
                        background-color: #0b1d3a !important;
                        border-bottom: 1px solid rgba(255,255,255,0.1) !important;
                        left: 0 !important;
                        width: 100% !important;
                    }
                    .dark .fi-topbar {
                        background-color: #050e1d !important;
                        border-bottom: 1px solid rgba(255,255,255,0.05) !important;
                    }
                    .fi-topbar, .fi-topbar * {
                        color: #ffffff !important;
                    }

                    /* ===== DROPDOWN / MENU DE USUARIO ===== */
                    /* Forzar visibilidad en modo claro */
                    .fi-dropdown-panel, .fi-dropdown-list, .fi-dropdown-list-item, .fi-user-menu {
                        background-color: #ffffff !important;
                    }
                    .fi-dropdown-panel *, .fi-dropdown-list-item * {
                        color: #1a1a2e !important;
                    }
                    .fi-dropdown-list-item:hover {
                        background-color: #f1f5f9 !important;
                    }

                    /* Dropdown modo oscuro */
                    .dark .fi-dropdown-panel, .dark .fi-dropdown-list, .dark .fi-dropdown-list-item, .dark .fi-user-menu {
                        background-color: #161b22 !important;
                        border-color: #30363d !important;
                    }
                    .dark .fi-dropdown-panel *, .dark .fi-dropdown-list-item * {
                        color: #e2e8f0 !important;
                    }
                    .dark .fi-dropdown-list-item:hover {
                        background-color: #1f2937 !important;
                    }


                    /* ===== BOTONES CORRECTIVOS (TEXTO VISIBLE) ===== */
                    /* Primario */
                    .fi-btn-color-primary, button[style*="--c-400:var(--primary-400)"], a[style*="--c-400:var(--primary-400)"], .btn-primary {
                        background-color: #1b65d4 !important;
                        color: #ffffff !important;
                    }
                    .fi-btn-color-primary *, button[style*="--c-400:var(--primary-400)"] *, a[style*="--c-400:var(--primary-400)"] *, .btn-primary * {
                        color: #ffffff !important;
                    }
                    .fi-btn-color-primary:hover, button[style*="--c-400:var(--primary-400)"]:hover, a[style*="--c-400:var(--primary-400)"]:hover, .btn-primary:hover {
                        background-color: #0d47a1 !important;
                    }

                    /* Éxito */
                    .fi-btn-color-success, button[style*="--c-400:var(--success-400)"], a[style*="--c-400:var(--success-400)"], .btn-success {
                        background-color: #0f9d58 !important;
                        color: #ffffff !important;
                    }
                    .fi-btn-color-success *, button[style*="--c-400:var(--success-400)"] *, a[style*="--c-400:var(--success-400)"] *, .btn-success * {
                        color: #ffffff !important;
                    }

                    /* Peligro */
                    .fi-btn-color-danger, button[style*="--c-400:var(--danger-400)"], a[style*="--c-400:var(--danger-400)"], .btn-danger {
                        background-color: #d93025 !important;
                        color: #ffffff !important;
                    }
                    .fi-btn-color-danger *, button[style*="--c-400:var(--danger-400)"] *, a[style*="--c-400:var(--danger-400)"] *, .btn-danger * {
                        color: #ffffff !important;
                    }

                    /* Advertencia */
                    .fi-btn-color-warning, button[style*="--c-400:var(--warning-400)"], a[style*="--c-400:var(--warning-400)"], .btn-warning {
                        background-color: #f4a623 !important;
                        color: #ffffff !important;
                    }
                    .fi-btn-color-warning *, button[style*="--c-400:var(--warning-400)"] *, a[style*="--c-400:var(--warning-400)"] *, .btn-warning * {
                        color: #ffffff !important;
                    }

                    /* Secundario / Gris */
                    .fi-btn-color-gray, button[style*="--c-400:var(--gray-400)"], a[style*="--c-400:var(--gray-400)"], .btn-secondary {
                        background-color: #e5e7eb !important;
                        color: #111827 !important;
                    }
                    .fi-btn-color-gray *, button[style*="--c-400:var(--gray-400)"] *, a[style*="--c-400:var(--gray-400)"] *, .btn-secondary * {
                        color: #111827 !important;
                    }
                    .fi-btn-color-gray:hover, button[style*="--c-400:var(--gray-400)"]:hover, a[style*="--c-400:var(--gray-400)"]:hover, .btn-secondary:hover {
                        background-color: #d1d5db !important;
                    }

                    /* Secundario / Gris en Modo Oscuro */
                    .dark .fi-btn-color-gray, .dark button[style*="--c-400:var(--gray-400)"], .dark a[style*="--c-400:var(--gray-400)"], .dark .btn-secondary {
                        background-color: #374151 !important;
                        color: #e2e8f0 !important;
                    }
                    .dark .fi-btn-color-gray *, .dark button[style*="--c-400:var(--gray-400)"] *, .dark a[style*="--c-400:var(--gray-400)"] *, .dark .btn-secondary * {
                        color: #e2e8f0 !important;
                    }
                    .dark .fi-btn-color-gray:hover, .dark button[style*="--c-400:var(--gray-400)"]:hover, .dark a[style*="--c-400:var(--gray-400)"]:hover, .dark .btn-secondary:hover {
                        background-color: #4b5563 !important;
                    }

                    /* Botones Outline / Ghost */
                    .fi-btn-outline {
                        background-color: transparent !important;
                    }
                    .fi-btn-outline.fi-btn-color-primary, .fi-btn-outline[style*="--c-400:var(--primary-400)"] {
                        border-color: #1b65d4 !important;
                        color: #1b65d4 !important;
                    }
                    .fi-btn-outline.fi-btn-color-primary *, .fi-btn-outline[style*="--c-400:var(--primary-400)"] * {
                        color: #1b65d4 !important;
                    }
                    .dark .fi-btn-outline.fi-btn-color-primary, .dark .fi-btn-outline[style*="--c-400:var(--primary-400)"] {
                        border-color: #3b82f6 !important;
                        color: #3b82f6 !important;
                    }
                    .dark .fi-btn-outline.fi-btn-color-primary *, .dark .fi-btn-outline[style*="--c-400:var(--primary-400)"] * {
                        color: #3b82f6 !important;
                    }

                    /* ===== CARDS Y SUPERFICIES ===== */
                    .fi-ta-ctn, .fi-fo-component-ctn, .fi-modal-window, .fi-section {
                        border: 1px solid rgba(27,101,212,0.15) !important;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.03) !important;
                        background-color: #ffffff !important;
                    }
                    .fi-ta-header, .fi-section-header {
                        background-color: rgba(27,101,212,0.02) !important;
                        border-bottom: 1px solid rgba(27,101,212,0.1) !important;
                    }

                    /* Cards y Superficies Modo Oscuro */
                    .dark .fi-ta-ctn, .dark .fi-fo-component-ctn, .dark .fi-modal-window, .dark .fi-section {
                        border: 1px solid #30363d !important;
                        background-color: #161b22 !important;
                    }
                    .dark .fi-ta-header, .dark .fi-section-header {
                        background-color: #1f2937 !important;
                        border-bottom: 1px solid #30363d !important;
                    }
                    .dark .fi-ta-record, .dark .fi-ta-row, .dark .fi-ta-cell {
                        border-color: #30363d !important;
                    }
                    .dark .fi-ta-row:nth-child(even) {
                        background-color: #1f2937 !important;
                    }

                    /* ===== TABLAS Y FORMULARIOS (MODO OSCURO) ===== */
                    .dark input, .dark select, .dark textarea {
                        background-color: #0d1117 !important;
                        border-color: #30363d !important;
                        color: #e2e8f0 !important;
                    }
                    .dark .fi-ta-text-item-label, .dark th, .dark td, .dark label, .dark .fi-fo-field-wrp-label, .dark .fi-ta-empty-state-heading {
                        color: #e2e8f0 !important;
                    }
                    .dark .fi-fo-field-wrp-helper-text {
                        color: #8b95a5 !important;
                    }

                    /* ===== LINKS INTERACTIVOS ===== */
                    .fi-main a:not([class*="btn"]), .fi-ta-text-item-label a {
                        color: #1b65d4 !important;
                        transition: color 0.2s;
                    }
                    .fi-main a:not([class*="btn"]):hover, .fi-ta-text-item-label a:hover {
                        color: #0d47a1 !important;
                    }

                    .dark .fi-main a:not([class*="btn"]), .dark .fi-ta-text-item-label a {
                        color: #3b82f6 !important;
                    }
                    .dark .fi-main a:not([class*="btn"]):hover, .dark .fi-ta-text-item-label a:hover {
                        color: #60a5fa !important;
                    }

                    /* ===== BADGES INSTITUCIONALES ===== */
                    .fi-badge-color-success, .fi-badge[style*="--c-400:var(--success-400)"] {
                        background-color: rgba(15, 157, 88, 0.15) !important;
                        color: #0f9d58 !important;
                        border: 1px solid rgba(15, 157, 88, 0.3) !important;
                    }
                    .fi-badge-color-warning, .fi-badge[style*="--c-400:var(--warning-400)"] {
                        background-color: rgba(244, 166, 35, 0.15) !important;
                        color: #d97706 !important;
                        border: 1px solid rgba(244, 166, 35, 0.3) !important;
                    }
                    .fi-badge-color-danger, .fi-badge[style*="--c-400:var(--danger-400)"] {
                        background-color: rgba(217, 48, 37, 0.15) !important;
                        color: #d93025 !important;
                        border: 1px solid rgba(217, 48, 37, 0.3) !important;
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

                    .dark .fi-simple-main-ctn > div {
                        background: rgba(13, 17, 23, 0.95) !important;
                        border: 1px solid rgba(48, 54, 61, 0.8) !important;
                    }
                    .dark .fi-simple-main-ctn h2 {
                        color: #e2e8f0 !important;
                    }

                    /* ===== ELIMINAR ESPACIO DEL SIDEBAR ===== */
                    .fi-sidebar-layout { padding-left: 0 !important; }
                    .fi-sidebar { display: none !important; width: 0 !important; min-width: 0 !important; }
                    .fi-main { width: 100% !important; max-width: 100% !important; margin-left: 0 !important; padding-left: 24px !important; padding-right: 24px !important; }
                </style>')
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render('
                    @if(!request()->routeIs(\'filament.admin.pages.dashboard\'))
                        <a href="{{ url(\'/admin\') }}" style="position: fixed; bottom: 30px; left: 30px; z-index: 9999; background-color: #0b1d3a; color: white; padding: 12px 24px; border-radius: 999px; font-weight: 600; font-family: sans-serif; text-decoration: none; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -4px rgba(0, 0, 0, 0.2); display: flex; align-items: center; gap: 10px; transition: all 0.2s ease-in-out;" onmouseover="this.style.backgroundColor=\'#16325c\'; this.style.transform=\'translateY(-2px)\'" onmouseout="this.style.backgroundColor=\'#0b1d3a\'; this.style.transform=\'translateY(0)\'">
                            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                            Volver al Dashboard
                        </a>
                    @endif
                ')
            )
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Panel principal')
                    ->collapsible(true),
                NavigationGroup::make()
                    ->label('Gestión de inventario')
                    ->collapsible(true),
                NavigationGroup::make()
                    ->label('Catálogos')
                    ->collapsible(true),
                NavigationGroup::make()
                    ->label('Administración')
                    ->collapsible(true),
            ])
            ->spa()
            ->sidebarWidth('20rem')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
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
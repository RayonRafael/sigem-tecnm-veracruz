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
/* =========================================
   SIGEM - FILAMENT GLOBAL THEME OVERRIDES
   ========================================= */

/* ===== 1. FONDO GENERAL Y APP ===== */
main.fi-main, body {
    background: radial-gradient(circle at top left, rgba(11, 29, 58, 0.03) 0%, transparent 40%),
                radial-gradient(circle at bottom right, rgba(27, 101, 212, 0.03) 0%, transparent 40%),
                #f0f4f8 !important;
    background-attachment: fixed !important;
}
.dark main.fi-main, .dark body {
    background: radial-gradient(circle at top left, rgba(11, 29, 58, 0.2) 0%, transparent 40%),
                radial-gradient(circle at bottom right, rgba(27, 101, 212, 0.15) 0%, transparent 40%),
                #0d1117 !important;
    background-attachment: fixed !important;
}
body::before {
    content: ""; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
    pointer-events: none; z-index: 9999; opacity: 0.03;
    background-image: url(\'data:image/svg+xml;utf8,<svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><filter id="noiseFilter"><feTurbulence type="fractalNoise" baseFrequency="0.65" numOctaves="3" stitchTiles="stitch"/></filter><rect width="100%" height="100%" filter="url(%23noiseFilter)"/></svg>\');
}
.dark body::before { opacity: 0.02; }

/* ===== 2. CARDS Y CONTENEDORES ===== */
.fi-ta-ctn, .fi-fo-component-ctn, .fi-section, .fi-card {
    background-color: #ffffff !important;
    border-radius: 16px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03) !important;
    position: relative;
}
.fi-ta-ctn::before, .fi-fo-component-ctn::before, .fi-section::before, .fi-card::before {
    content: ""; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, rgba(11,29,58,0.5) 0%, rgba(27,101,212,0.5) 100%) !important;
    border-radius: 16px 16px 0 0 !important; z-index: 10; pointer-events: none;
}
.dark .fi-ta-ctn, .dark .fi-fo-component-ctn, .dark .fi-section, .dark .fi-card {
    background-color: #161b22 !important;
    border-color: #30363d !important;
}
.dark .fi-ta-ctn::before, .dark .fi-fo-component-ctn::before, .dark .fi-section::before, .dark .fi-card::before {
    background: linear-gradient(90deg, rgba(11,29,58,0.8) 0%, rgba(27,101,212,0.8) 100%) !important;
}
/* Padding en secciones */
.fi-section-content {
    padding: 1.5rem !important;
}

/* ===== 3. TÍTULOS DE PÁGINA ===== */
h1, .fi-header-heading {
    color: #0b1d3a !important;
    font-weight: 700 !important;
}
.dark h1, .dark .fi-header-heading {
    color: #e2e8f0 !important;
}

/* ===== 4. TABLAS DE LISTADO ===== */
.fi-ta table {
    border-collapse: separate !important;
    border-spacing: 0 !important;
}
.fi-ta-header, .fi-ta-header-cell, th {
    background-color: #f8fafc !important;
    border-bottom: 1px solid #e2e8f0 !important;
}
.fi-ta-header-cell *, th * {
    color: #5f6b7a !important;
    font-weight: 700 !important;
}
.fi-ta-row, tr {
    background-color: #ffffff !important;
    border-bottom: 1px solid #f1f5f9 !important;
    transition: background-color 0.2s;
}
.fi-ta-row:hover, tr:hover {
    background-color: #f0f4f8 !important;
}
.fi-ta-row:nth-child(even), tr:nth-child(even) {
    background-color: #fafbfc !important;
}
/* Tablas Modo Oscuro */
.dark .fi-ta-header, .dark .fi-ta-header-cell, .dark th {
    background-color: #161b22 !important;
    border-color: #30363d !important;
}
.dark .fi-ta-header-cell *, .dark th * {
    color: #8b95a5 !important;
}
.dark .fi-ta-row, .dark tr {
    background-color: #0d1117 !important;
    border-color: #30363d !important;
}
.dark .fi-ta-row:hover, .dark tr:hover {
    background-color: #1c2333 !important;
}
.dark .fi-ta-row:nth-child(even), .dark tr:nth-child(even) {
    background-color: #121820 !important;
}

/* ===== 5. INPUTS, SELECTS, TEXTAREAS ===== */
.fi-input, .fi-select-input, textarea, select {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 10px !important;
    color: #1a1a2e !important;
    transition: all 0.2s;
}
.fi-input:focus, .fi-select-input:focus, textarea:focus, select:focus {
    border-color: #1b65d4 !important;
    box-shadow: 0 0 0 3px rgba(27,101,212,0.1) !important;
}
.dark .fi-input, .dark .fi-select-input, .dark textarea, .dark select {
    background-color: #161b22 !important;
    border-color: #30363d !important;
    color: #e2e8f0 !important;
}
.dark .fi-input:focus, .dark .fi-select-input:focus, .dark textarea:focus, .dark select:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1) !important;
}

/* ===== 6. LABELS DE CAMPOS ===== */
.fi-fo-field-wrp-label, .fi-fo-field-wrp-label * {
    color: #1a1a2e !important;
    font-weight: 600 !important;
}
.dark .fi-fo-field-wrp-label, .dark .fi-fo-field-wrp-label * {
    color: #e2e8f0 !important;
}

/* ===== 7. BOTONES ===== */
.fi-btn {
    border-radius: 10px !important;
    transition: all 0.2s ease-in-out !important;
}

/* Primario */
.fi-btn-color-primary, .btn-primary {
    background-color: #1b65d4 !important;
    color: #ffffff !important;
    border: none !important;
}
.fi-btn-color-primary *, .btn-primary * { color: #ffffff !important; }
.fi-btn-color-primary:hover, .btn-primary:hover { background-color: #0e4bad !important; }

/* Éxito */
.fi-btn-color-success, .btn-success {
    background-color: #0f9d58 !important;
    color: #ffffff !important;
    border: none !important;
}
.fi-btn-color-success *, .btn-success * { color: #ffffff !important; }
.fi-btn-color-success:hover, .btn-success:hover { background-color: #0b8a4b !important; }

/* Peligro */
.fi-btn-color-danger, .btn-danger {
    background-color: #d93025 !important;
    color: #ffffff !important;
    border: none !important;
}
.fi-btn-color-danger *, .btn-danger * { color: #ffffff !important; }
.fi-btn-color-danger:hover, .btn-danger:hover { background-color: #b8251c !important; }

/* Advertencia */
.fi-btn-color-warning, .btn-warning {
    background-color: #f4a623 !important;
    color: #1a1a2e !important;
    border: none !important;
}
.fi-btn-color-warning *, .btn-warning * { color: #1a1a2e !important; }
.fi-btn-color-warning:hover, .btn-warning:hover { background-color: #e09515 !important; }

/* Info */
.fi-btn-color-info, .btn-info {
    background-color: #0b1d3a !important;
    color: #ffffff !important;
    border: none !important;
}
.fi-btn-color-info *, .btn-info * { color: #ffffff !important; }
.fi-btn-color-info:hover, .btn-info:hover { background-color: #050e1d !important; }

/* Secundario / Gris */
.fi-btn-color-gray, .btn-secondary {
    background-color: #f0f4f8 !important;
    color: #1a1a2e !important;
    border: 1px solid #e2e8f0 !important;
}
.fi-btn-color-gray *, .btn-secondary * { color: #1a1a2e !important; }
.fi-btn-color-gray:hover, .btn-secondary:hover { 
    border-color: #1b65d4 !important; 
    color: #1b65d4 !important;
}

/* Modo oscuro botones secundarios */
.dark .fi-btn-color-gray, .dark .btn-secondary {
    background-color: #161b22 !important;
    color: #e2e8f0 !important;
    border-color: #30363d !important;
}
.dark .fi-btn-color-gray *, .dark .btn-secondary * { color: #e2e8f0 !important; }
.dark .fi-btn-color-gray:hover, .dark .btn-secondary:hover {
    border-color: #3b82f6 !important;
    color: #3b82f6 !important;
}

/* ===== 8. BADGES Y ETIQUETAS ===== */
.fi-badge { border-radius: 999px !important; font-weight: 600 !important; }
.fi-badge-color-success { background-color: #e6f7ed !important; color: #0f9d58 !important; }
.fi-badge-color-warning { background-color: #fef7e6 !important; color: #f4a623 !important; }
.fi-badge-color-danger { background-color: #fce8e6 !important; color: #d93025 !important; }
.fi-badge-color-info { background-color: #e8f0fe !important; color: #1b65d4 !important; }
.fi-badge-color-gray { background-color: #f1f5f9 !important; color: #5f6b7a !important; }

.dark .fi-badge-color-success { background-color: rgba(15,157,88,0.2) !important; color: #4ade80 !important; }
.dark .fi-badge-color-warning { background-color: rgba(244,166,35,0.2) !important; color: #fbbf24 !important; }
.dark .fi-badge-color-danger { background-color: rgba(217,48,37,0.2) !important; color: #f87171 !important; }
.dark .fi-badge-color-info { background-color: rgba(27,101,212,0.2) !important; color: #60a5fa !important; }
.dark .fi-badge-color-gray { background-color: rgba(139,149,165,0.2) !important; color: #e2e8f0 !important; }

/* ===== 9. BREADCRUMBS ===== */
.fi-breadcrumbs { align-items: center; }
.fi-breadcrumbs-item-label { color: #8b95a5 !important; }
.fi-breadcrumbs-item:last-child .fi-breadcrumbs-item-label { color: #1a1a2e !important; font-weight: 600 !important; }
.fi-breadcrumbs-separator { color: #cbd5e1 !important; }
.dark .fi-breadcrumbs-item-label { color: #8b95a5 !important; }
.dark .fi-breadcrumbs-item:last-child .fi-breadcrumbs-item-label { color: #e2e8f0 !important; }
.dark .fi-breadcrumbs-separator { color: #30363d !important; }

/* ===== 10. PAGINACIÓN ===== */
.fi-pagination-item { border-radius: 8px !important; margin: 0 2px !important; }
.fi-pagination-item-active, .fi-pagination-item-active button { background-color: #1b65d4 !important; color: #ffffff !important; }
.fi-pagination-item-active * { color: #ffffff !important; }
.fi-pagination-item:not(.fi-pagination-item-active), .fi-pagination-item:not(.fi-pagination-item-active) button { background-color: #ffffff !important; color: #5f6b7a !important; }
.fi-pagination-item:not(.fi-pagination-item-active):hover { background-color: #f0f4f8 !important; }
.dark .fi-pagination-item-active, .dark .fi-pagination-item-active button { background-color: #1b65d4 !important; }
.dark .fi-pagination-item:not(.fi-pagination-item-active), .dark .fi-pagination-item:not(.fi-pagination-item-active) button { background-color: #161b22 !important; color: #8b95a5 !important; }
.dark .fi-pagination-item:not(.fi-pagination-item-active):hover { background-color: #1c2333 !important; }

/* ===== 11. FILTROS Y BÚSQUEDA ===== */
.fi-ta-filters, .fi-ta-search, .fi-global-search {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 12px !important;
}
.dark .fi-ta-filters, .dark .fi-ta-search, .dark .fi-global-search {
    background-color: #161b22 !important;
    border-color: #30363d !important;
}

/* ===== 12. NOTIFICACIONES Y TOASTS ===== */
.fi-no-notification {
    border-radius: 12px !important;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
    background-color: #ffffff !important;
}
.fi-no-notification-status-success { border-left: 4px solid #0f9d58 !important; }
.fi-no-notification-status-danger { border-left: 4px solid #d93025 !important; }
.fi-no-notification-status-info { border-left: 4px solid #1b65d4 !important; }
.dark .fi-no-notification {
    background-color: #161b22 !important;
    box-shadow: 0 10px 25px rgba(0,0,0,0.3) !important;
}

/* ===== 13. MODALES NATIVOS DE FILAMENT ===== */
.fi-modal-window {
    border-radius: 16px !important;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15) !important;
}
.fi-modal-header {
    background-color: #fafbfc !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-radius: 16px 16px 0 0 !important;
}
.dark .fi-modal-window {
    background-color: #161b22 !important;
    border: 1px solid #30363d !important;
}
.dark .fi-modal-header {
    background-color: #0d1117 !important;
    border-bottom-color: #30363d !important;
}

/* ===== 14. BOTONES DE ACCIÓN EN TABLAS ===== */
.fi-ta-actions .fi-icon-btn {
    border-radius: 8px !important;
    transition: background-color 0.2s !important;
}
.fi-ta-actions .fi-icon-btn-color-primary:hover { background-color: rgba(27,101,212,0.1) !important; }
.fi-ta-actions .fi-icon-btn-color-danger:hover { background-color: rgba(217,48,37,0.1) !important; }

/* ===== 15. WIZARD ===== */
.fi-wizard {
    background-color: #ffffff !important;
    border-radius: 16px !important;
    border: 1px solid #e2e8f0 !important;
}
.fi-wizard-step-active .fi-wizard-step-icon { background-color: #1b65d4 !important; color: #fff !important; }
.fi-wizard-step-completed .fi-wizard-step-icon { background-color: #0f9d58 !important; color: #fff !important; }
.fi-wizard-step-pending .fi-wizard-step-icon { background-color: #f1f5f9 !important; color: #8b95a5 !important; }
.dark .fi-wizard {
    background-color: #161b22 !important;
    border-color: #30363d !important;
}
.dark .fi-wizard-step-pending .fi-wizard-step-icon { background-color: #30363d !important; color: #8b95a5 !important; }

/* ===== 16. SELECTS CON BÚSQUEDA ===== */
.fi-select-input-dropdown, .choices__list--dropdown {
    background-color: #ffffff !important;
    border-radius: 10px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
}
.fi-select-input-option:hover, .choices__item--choice:hover { background-color: #f0f4f8 !important; }
.fi-select-input-option-selected, .choices__item--selectable.is-highlighted { background-color: #e8f0fe !important; color: #1b65d4 !important; }
.dark .fi-select-input-dropdown, .dark .choices__list--dropdown {
    background-color: #161b22 !important;
    border-color: #30363d !important;
}
.dark .fi-select-input-option:hover, .dark .choices__item--choice:hover { background-color: #1c2333 !important; }
.dark .fi-select-input-option-selected, .dark .choices__item--selectable.is-highlighted { background-color: rgba(27,101,212,0.2) !important; color: #60a5fa !important; }

/* ===== 17. CHECKBOX Y RADIO ===== */
.fi-checkbox, .fi-radio {
    color: #1b65d4 !important;
    border-radius: 4px !important;
    transition: all 0.2s !important;
}
.dark .fi-checkbox, .dark .fi-radio {
    background-color: #0d1117 !important;
    border-color: #30363d !important;
}

/* ===== 18. TABLAS VACÍAS ===== */
.fi-ta-empty-state-heading { color: #8b95a5 !important; font-weight: 600 !important; }
.fi-ta-empty-state-icon { color: #cbd5e1 !important; }
.dark .fi-ta-empty-state-heading { color: #e2e8f0 !important; }
.dark .fi-ta-empty-state-icon { color: #30363d !important; }

/* ===== TOPBAR ====== */
.fi-topbar {
    background-color: #0b1d3a !important;
    border-bottom: none !important;
    box-shadow: none !important;
    left: 0 !important;
    width: 100% !important;
    height: 52px !important;
    min-height: 52px !important;
    position: relative !important;
    z-index: 20 !important;
}
.fi-topbar nav {
    height: 52px !important;
    min-height: 52px !important;
    padding-top: 0 !important;
    padding-bottom: 0 !important;
}
.dark .fi-topbar {
    background-color: #050e1d !important;
}
.fi-topbar, .fi-topbar * { color: #ffffff !important; }
.fi-topbar .fi-user-menu button { background: transparent !important; border: none !important; }
.fi-topbar .fi-user-menu button:hover { background-color: rgba(255,255,255,0.1) !important; }

/* ===== DROPDOWN / MENU DE USUARIO ===== */
.fi-dropdown-panel, .fi-dropdown-list, .fi-dropdown-list-item, .fi-user-menu { background-color: #ffffff !important; }
.fi-dropdown-panel *, .fi-dropdown-list-item * { color: #1a1a2e !important; }
.fi-dropdown-list-item:hover { background-color: #f1f5f9 !important; }
.dark .fi-dropdown-panel, .dark .fi-dropdown-list, .dark .fi-dropdown-list-item, .dark .fi-user-menu { background-color: #161b22 !important; border-color: #30363d !important; }
.dark .fi-dropdown-panel *, .dark .fi-dropdown-list-item * { color: #e2e8f0 !important; }
.dark .fi-dropdown-list-item:hover { background-color: #1f2937 !important; }

/* ===== LINKS INTERACTIVOS (TEXTO) ===== */
.fi-main a:not([class*="btn"]), .fi-ta-text-item-label a { color: #1b65d4 !important; transition: color 0.2s; }
.fi-main a:not([class*="btn"]):hover, .fi-ta-text-item-label a:hover { color: #0d47a1 !important; }
.dark .fi-main a:not([class*="btn"]), .dark .fi-ta-text-item-label a { color: #3b82f6 !important; }
.dark .fi-main a:not([class*="btn"]):hover, .dark .fi-ta-text-item-label a:hover { color: #60a5fa !important; }

/* JetBrains Mono */
@import url("https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&display=swap");
.font-mono, .fi-ta-text-item-label code, .fi-ta-text-item-label[style*="monospace"] { font-family: "JetBrains Mono", monospace !important; }

/* ===== LOGIN CUSTOMIZATION ===== */
.fi-simple-layout {
    background-image: url("{{ asset(\'images/fondo.jpg\') }}") !important;
    background-size: cover !important;
    background-position: center !important;
    background-attachment: fixed !important;
    position: relative;
    z-index: 1;
}
.fi-simple-layout::before {
    content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(11, 29, 58, 0.75) !important; z-index: -1;
}
.fi-simple-main-ctn > div {
    background: rgba(255, 255, 255, 0.95) !important;
    backdrop-filter: blur(8px) !important; border-radius: 12px !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3) !important; border: 1px solid rgba(255,255,255,0.2) !important;
}
.dark .fi-simple-main-ctn > div {
    background: rgba(13, 17, 23, 0.95) !important; border: 1px solid rgba(48, 54, 61, 0.8) !important;
}
.dark .fi-simple-main-ctn h2 { color: #e2e8f0 !important; }

/* ===== ELIMINAR ESPACIO DEL SIDEBAR ===== */
.fi-sidebar-layout { padding-left: 0 !important; }
.fi-sidebar { display: none !important; width: 0 !important; min-width: 0 !important; }
.fi-main { width: 100% !important; max-width: 100% !important; margin-left: 0 !important; padding-left: 24px !important; padding-right: 24px !important; }
</style>')
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render('
                    @if(!request()->routeIs(\'filament.servicio-social.pages.dashboard\'))
                        <a href="{{ url(\'/servicio-social\') }}" style="position: fixed; bottom: 30px; left: 30px; z-index: 9999; background-color: #0b1d3a; color: white; padding: 12px 24px; border-radius: 999px; font-weight: 600; font-family: sans-serif; text-decoration: none; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -4px rgba(0, 0, 0, 0.2); display: flex; align-items: center; gap: 10px; transition: all 0.2s ease-in-out;" onmouseover="this.style.backgroundColor=\'#16325c\'; this.style.transform=\'translateY(-2px)\'" onmouseout="this.style.backgroundColor=\'#0b1d3a\'; this.style.transform=\'translateY(0)\'">
                            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                            Volver al Dashboard
                        </a>
                    @endif
                ')
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

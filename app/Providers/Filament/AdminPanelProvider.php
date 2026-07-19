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
/* =========================================
   SIGEM - FILAMENT GLOBAL THEME OVERRIDES
   ========================================= */

/* ===== 0. OCULTAR TOPBAR COMPLETAMENTE ===== */
.fi-topbar { display: none !important; height: 0 !important; overflow: hidden !important; position: absolute !important; }
body { padding-top: 0 !important; }
.fi-main { padding-top: 0 !important; margin-top: 0 !important; height: 100% !important; min-height: 100vh !important; }
.fi-main-ctn { max-width: 100% !important; padding-left: 2rem !important; padding-right: 2rem !important; }
.fi-header { margin-bottom: 2rem !important; padding-top: 1.5rem !important; }

/* ===== 1. FONDO GENERAL Y APP ===== */
main.fi-main, body {
    background-color: #f8fafc !important;
    background-image: none !important;
}
.dark main.fi-main, .dark body {
    background-color: #0f172a !important;
}

/* ===== 2. CARDS Y CONTENEDORES ===== */
.fi-ta-ctn, .fi-fo-component-ctn, .fi-section, .fi-card {
    background-color: #ffffff !important;
    border-radius: 12px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03) !important;
}
.fi-ta-ctn::before, .fi-fo-component-ctn::before, .fi-section::before, .fi-card::before {
    display: none !important; /* Quitar la barra de gradiente superior anterior */
}
.dark .fi-ta-ctn, .dark .fi-fo-component-ctn, .dark .fi-section, .dark .fi-card {
    background-color: #1e293b !important;
    border-color: #334155 !important;
}
/* Padding en secciones */
.fi-section-content {
    padding: 1.5rem !important;
}

/* ===== 3. TÍTULOS DE PÁGINA ===== */
h1, .fi-header-heading {
    color: #0f172a !important;
    font-weight: 700 !important;
    font-size: 1.875rem !important;
    letter-spacing: -0.025em !important;
}
.dark h1, .dark .fi-header-heading {
    color: #f1f5f9 !important;
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
    color: #475569 !important;
    font-weight: 600 !important;
}
.fi-ta-row, tr {
    background-color: #ffffff !important;
    border-bottom: 1px solid #f1f5f9 !important;
    transition: background-color 0.2s;
}
.fi-ta-row:hover, tr:hover {
    background-color: #f8fafc !important;
}
.fi-ta-row:nth-child(even), tr:nth-child(even) {
    background-color: #ffffff !important;
}
/* Tablas Modo Oscuro */
.dark .fi-ta-header, .dark .fi-ta-header-cell, .dark th {
    background-color: #1e293b !important;
    border-color: #334155 !important;
}
.dark .fi-ta-header-cell *, .dark th * {
    color: #94a3b8 !important;
}
.dark .fi-ta-row, .dark tr {
    background-color: #0f172a !important;
    border-color: #334155 !important;
}
.dark .fi-ta-row:hover, .dark tr:hover {
    background-color: #1e293b !important;
}

/* ===== 5. INPUTS, SELECTS, TEXTAREAS ===== */
.fi-input, .fi-select-input, textarea, select {
    background-color: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important;
    color: #0f172a !important;
    transition: all 0.2s;
}
.fi-input:focus, .fi-select-input:focus, textarea:focus, select:focus {
    border-color: #2563eb !important;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.2) !important;
}
.dark .fi-input, .dark .fi-select-input, .dark textarea, .dark select {
    background-color: #1e293b !important;
    border-color: #475569 !important;
    color: #f1f5f9 !important;
}
.dark .fi-input:focus, .dark .fi-select-input:focus, .dark textarea:focus, .dark select:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.2) !important;
}

/* ===== 6. LABELS DE CAMPOS ===== */
.fi-fo-field-wrp-label, .fi-fo-field-wrp-label * {
    color: #334155 !important;
    font-weight: 600 !important;
}
.dark .fi-fo-field-wrp-label, .dark .fi-fo-field-wrp-label * {
    color: #e2e8f0 !important;
}

/* ===== 7. BOTONES ===== */
.fi-btn {
    border-radius: 8px !important;
    transition: all 0.2s ease-in-out !important;
    font-weight: 500 !important;
}

/* Primario */
.fi-btn-color-primary, .btn-primary {
    background-color: #2563eb !important;
    color: #ffffff !important;
    border: none !important;
}
.fi-btn-color-primary *, .btn-primary * { color: #ffffff !important; }
.fi-btn-color-primary:hover, .btn-primary:hover { background-color: #1d4ed8 !important; }

/* Éxito */
.fi-btn-color-success, .btn-success {
    background-color: #10b981 !important;
    color: #ffffff !important;
    border: none !important;
}
.fi-btn-color-success *, .btn-success * { color: #ffffff !important; }
.fi-btn-color-success:hover, .btn-success:hover { background-color: #059669 !important; }

/* Peligro */
.fi-btn-color-danger, .btn-danger {
    background-color: #ef4444 !important;
    color: #ffffff !important;
    border: none !important;
}
.fi-btn-color-danger *, .btn-danger * { color: #ffffff !important; }
.fi-btn-color-danger:hover, .btn-danger:hover { background-color: #dc2626 !important; }

/* Advertencia */
.fi-btn-color-warning, .btn-warning {
    background-color: #f59e0b !important;
    color: #ffffff !important;
    border: none !important;
}
.fi-btn-color-warning *, .btn-warning * { color: #ffffff !important; }
.fi-btn-color-warning:hover, .btn-warning:hover { background-color: #d97706 !important; }

/* Info */
.fi-btn-color-info, .btn-info {
    background-color: #3b82f6 !important;
    color: #ffffff !important;
    border: none !important;
}
.fi-btn-color-info *, .btn-info * { color: #ffffff !important; }
.fi-btn-color-info:hover, .btn-info:hover { background-color: #2563eb !important; }

/* Secundario / Gris */
.fi-btn-color-gray, .btn-secondary {
    background-color: #f8fafc !important;
    color: #334155 !important;
    border: 1px solid #e2e8f0 !important;
}
.fi-btn-color-gray *, .btn-secondary * { color: #334155 !important; }
.fi-btn-color-gray:hover, .btn-secondary:hover { 
    background-color: #f1f5f9 !important;
}

/* Modo oscuro botones secundarios */
.dark .fi-btn-color-gray, .dark .btn-secondary {
    background-color: #1e293b !important;
    color: #e2e8f0 !important;
    border-color: #334155 !important;
}
.dark .fi-btn-color-gray *, .dark .btn-secondary * { color: #e2e8f0 !important; }
.dark .fi-btn-color-gray:hover, .dark .btn-secondary:hover {
    background-color: #334155 !important;
}

/* ===== 8. BADGES Y ETIQUETAS ===== */
.fi-badge { border-radius: 999px !important; font-weight: 500 !important; }
.fi-badge-color-success { background-color: #d1fae5 !important; color: #059669 !important; }
.fi-badge-color-warning { background-color: #fef3c7 !important; color: #d97706 !important; }
.fi-badge-color-danger { background-color: #fee2e2 !important; color: #dc2626 !important; }
.fi-badge-color-info { background-color: #dbeafe !important; color: #2563eb !important; }
.fi-badge-color-gray { background-color: #f1f5f9 !important; color: #475569 !important; }

.dark .fi-badge-color-success { background-color: rgba(16,185,129,0.2) !important; color: #34d399 !important; }
.dark .fi-badge-color-warning { background-color: rgba(245,158,11,0.2) !important; color: #fbbf24 !important; }
.dark .fi-badge-color-danger { background-color: rgba(239,68,68,0.2) !important; color: #f87171 !important; }
.dark .fi-badge-color-info { background-color: rgba(59,130,246,0.2) !important; color: #60a5fa !important; }
.dark .fi-badge-color-gray { background-color: rgba(148,163,184,0.2) !important; color: #94a3b8 !important; }

/* ===== 9. BREADCRUMBS ===== */
.fi-breadcrumbs { align-items: center; }
.fi-breadcrumbs-item-label { color: #64748b !important; }
.fi-breadcrumbs-item:last-child .fi-breadcrumbs-item-label { color: #334155 !important; font-weight: 600 !important; }
.fi-breadcrumbs-separator { color: #cbd5e1 !important; }
.dark .fi-breadcrumbs-item-label { color: #94a3b8 !important; }
.dark .fi-breadcrumbs-item:last-child .fi-breadcrumbs-item-label { color: #e2e8f0 !important; }
.dark .fi-breadcrumbs-separator { color: #334155 !important; }

/* ===== 10. PAGINACIÓN ===== */
.fi-pagination-item { border-radius: 8px !important; margin: 0 2px !important; }
.fi-pagination-item-active, .fi-pagination-item-active button { background-color: #2563eb !important; color: #ffffff !important; }
.fi-pagination-item-active * { color: #ffffff !important; }
.fi-pagination-item:not(.fi-pagination-item-active), .fi-pagination-item:not(.fi-pagination-item-active) button { background-color: #ffffff !important; color: #475569 !important; }
.fi-pagination-item:not(.fi-pagination-item-active):hover { background-color: #f8fafc !important; }
.dark .fi-pagination-item-active, .dark .fi-pagination-item-active button { background-color: #3b82f6 !important; }
.dark .fi-pagination-item:not(.fi-pagination-item-active), .dark .fi-pagination-item:not(.fi-pagination-item-active) button { background-color: #1e293b !important; color: #94a3b8 !important; }
.dark .fi-pagination-item:not(.fi-pagination-item-active):hover { background-color: #334155 !important; }

/* ===== 11. FILTROS Y BÚSQUEDA ===== */
.fi-ta-filters, .fi-ta-search, .fi-global-search {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 8px !important;
}
.dark .fi-ta-filters, .dark .fi-ta-search, .dark .fi-global-search {
    background-color: #1e293b !important;
    border-color: #334155 !important;
}

/* ===== 12. NOTIFICACIONES Y TOASTS ===== */
.fi-no-notification {
    border-radius: 12px !important;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
    background-color: #ffffff !important;
}
.fi-no-notification-status-success { border-left: 4px solid #10b981 !important; }
.fi-no-notification-status-danger { border-left: 4px solid #ef4444 !important; }
.fi-no-notification-status-info { border-left: 4px solid #3b82f6 !important; }
.dark .fi-no-notification {
    background-color: #1e293b !important;
    box-shadow: 0 10px 25px rgba(0,0,0,0.3) !important;
}

/* ===== 13. MODALES NATIVOS DE FILAMENT ===== */
.fi-modal-window {
    border-radius: 12px !important;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1) !important;
}
.fi-modal-header {
    background-color: #f8fafc !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-radius: 12px 12px 0 0 !important;
}
.dark .fi-modal-window {
    background-color: #1e293b !important;
    border: 1px solid #334155 !important;
}
.dark .fi-modal-header {
    background-color: #0f172a !important;
    border-bottom-color: #334155 !important;
}

/* ===== 14. BOTONES DE ACCIÓN EN TABLAS ===== */
.fi-ta-actions .fi-icon-btn {
    border-radius: 8px !important;
    transition: background-color 0.2s !important;
}
.fi-ta-actions .fi-icon-btn-color-primary:hover { background-color: rgba(37,99,235,0.1) !important; }
.fi-ta-actions .fi-icon-btn-color-danger:hover { background-color: rgba(239,68,68,0.1) !important; }

/* ===== 15. WIZARD ===== */
.fi-wizard {
    background-color: #ffffff !important;
    border-radius: 12px !important;
    border: 1px solid #e2e8f0 !important;
}
.fi-wizard-step-active .fi-wizard-step-icon { background-color: #2563eb !important; color: #fff !important; }
.fi-wizard-step-completed .fi-wizard-step-icon { background-color: #10b981 !important; color: #fff !important; }
.fi-wizard-step-pending .fi-wizard-step-icon { background-color: #f8fafc !important; color: #94a3b8 !important; }
.dark .fi-wizard {
    background-color: #1e293b !important;
    border-color: #334155 !important;
}
.dark .fi-wizard-step-pending .fi-wizard-step-icon { background-color: #334155 !important; color: #94a3b8 !important; }

/* ===== 16. SELECTS CON BÚSQUEDA ===== */
.fi-select-input-dropdown, .choices__list--dropdown {
    background-color: #ffffff !important;
    border-radius: 8px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05) !important;
}
.fi-select-input-option:hover, .choices__item--choice:hover { background-color: #f8fafc !important; }
.fi-select-input-option-selected, .choices__item--selectable.is-highlighted { background-color: #dbeafe !important; color: #2563eb !important; }
.dark .fi-select-input-dropdown, .dark .choices__list--dropdown {
    background-color: #1e293b !important;
    border-color: #334155 !important;
}
.dark .fi-select-input-option:hover, .dark .choices__item--choice:hover { background-color: #334155 !important; }
.dark .fi-select-input-option-selected, .dark .choices__item--selectable.is-highlighted { background-color: rgba(37,99,235,0.2) !important; color: #60a5fa !important; }

/* ===== 17. CHECKBOX Y RADIO ===== */
.fi-checkbox, .fi-radio {
    color: #2563eb !important;
    border-radius: 4px !important;
    transition: all 0.2s !important;
}
.fi-radio:checked {
    background-color: #2563eb !important;
    border-color: #2563eb !important;
    background-image: url("data:image/svg+xml,%3csvg viewBox=\'0 0 16 16\' fill=\'white\' xmlns=\'http://www.w3.org/2000/svg\'%3e%3ccircle cx=\'8\' cy=\'8\' r=\'3\'/%3e%3c/svg%3e") !important;
    background-size: 100% 100% !important;
    background-position: center !important;
    background-repeat: no-repeat !important;
}
input[type="radio"]:checked + span, input[type="radio"]:checked ~ label, label:has(input[type="radio"]:checked) span {
    font-weight: 700 !important;
    color: #1d4ed8 !important;
}
.fi-fo-radio-option-label:has(input[type="radio"]:checked) {
    background-color: #eff6ff !important;
    border-radius: 8px !important;
    padding-left: 0.5rem !important;
    padding-right: 0.5rem !important;
}

.dark .fi-checkbox, .dark .fi-radio {
    background-color: #0f172a !important;
    border-color: #334155 !important;
}
.dark input[type="radio"]:checked + span, .dark input[type="radio"]:checked ~ label, .dark label:has(input[type="radio"]:checked) span {
    color: #60a5fa !important;
}
.dark .fi-fo-radio-option-label:has(input[type="radio"]:checked) {
    background-color: rgba(37,99,235,0.15) !important;
}

/* ===== 18. TABLAS VACÍAS ===== */
.fi-ta-empty-state-heading { color: #475569 !important; font-weight: 600 !important; }
.fi-ta-empty-state-icon { color: #cbd5e1 !important; }
.dark .fi-ta-empty-state-heading { color: #f1f5f9 !important; }
.dark .fi-ta-empty-state-icon { color: #475569 !important; }

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
                    @if(!request()->routeIs(\'filament.admin.pages.dashboard\'))
                        <a href="{{ url(\'/admin\') }}" style="position: fixed; bottom: 20px; left: 20px; z-index: 9999; background-color: #ffffff; color: #475569; padding: 8px 16px; border-radius: 8px; font-weight: 500; font-size: 14px; font-family: sans-serif; text-decoration: none; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 6px; transition: all 0.2s;" onmouseover="this.style.backgroundColor=\'#f8fafc\'; this.style.color=\'#0f172a\'" onmouseout="this.style.backgroundColor=\'#ffffff\'; this.style.color=\'#475569\'">
                            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
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
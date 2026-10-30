<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
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
use Illuminate\Support\HtmlString;
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
            ->brandLogo(new HtmlString('<div class="sigem-brand"><img src="' . asset('images/sigem-logo.svg') . '" alt="SIGEM" class="sigem-brand-logo"></div>'))
            ->brandLogoHeight('auto')
            ->favicon(asset('images/sigem-favicon.svg?v=2'))
            ->font('DM Sans')
            ->globalSearch(false)
            ->darkMode(false)
            ->colors([
                'primary' => Color::hex('#1b65d4'),
                'success' => Color::hex('#059669'),
                'warning' => Color::hex('#d97706'),
                'danger'  => Color::hex('#dc2626'),
                'info'    => Color::hex('#0891b2'),
                'reassign'=> Color::hex('#8b5cf6'),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
<link rel="stylesheet" href="{{ asset(\'css/sigem-theme.css\') }}">
')
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn (): string => Blade::render('
<div class="sigem-topbar-brand flex items-center gap-x-3">
    <img src="{{ asset(\'images/sigem-logo.svg\') }}" alt="SIGEM" class="h-8">
    <span class="font-bold text-lg hidden sm:block">SIGEM - TecNM Veracruz</span>
</div>
')
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn (): string => Blade::render('
<div class="flex justify-center items-center gap-4 mb-6">
    <img src="{{ asset(\'images/sigem-logo.svg\') }}" alt="SIGEM" style="height: 40px; object-fit: contain;">
    <img src="{{ asset(\'images/tecnm-logo.png\') }}" alt="TecNM" style="height: 40px; object-fit: contain;">
    <img src="{{ asset(\'images/itv-logo.png\') }}" alt="ITV" style="height: 40px; object-fit: contain;">
</div>
')
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): string => Blade::render('
<div class="text-center mt-6 flex flex-col gap-3">
    <a href="{{ url(\'/servicio-social/login\') }}" class="text-sm font-medium" style="color: #1b65d4; text-decoration: underline;">
        ¿Eres de Servicio Social? Inicia sesión aquí
    </a>
    <a href="{{ url(\'/\') }}" class="text-sm text-gray-500 hover:text-gray-700 font-medium">
        &larr; Ir al inicio
    </a>
</div>
')
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn (): string => Blade::render('
<div class="sigem-user-card">
    <div class="sigem-avatar" title="{{ auth()->user()?->name ?? \'Usuario\' }}">
        {{ mb_strtoupper(mb_substr(auth()->user()?->name ?? \'U\', 0, 1)) }}
    </div>
    <form method="POST" action="{{ route(\'filament.admin.auth.logout\') }}" class="m-0 p-0 flex items-center justify-center">
        @csrf
        <button type="submit" class="sigem-logout" title="Cerrar sesión" aria-label="Cerrar sesión">
            &#x23FB;
        </button>
    </form>
</div>
')
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => Blade::render('
@if (auth()->check())
@php
    $path = request()->path();
    $section = match(true) {
        str_contains($path, "inventarios") => "Inventario general",
        str_contains($path, "solicituds") => "Solicitud de Material/Equipo",
        str_contains($path, "mantenimientos") => "Mantenimiento",
        str_contains($path, "configuracion") => "Configuración y Catálogos",
        str_contains($path, "reportes") => "Reportes",
        str_contains($path, "users") => "Usuarios",
        default => "Panel principal",
    };
@endphp
<span class="sigem-crumb">SIGEM › <b>{{ $section }}</b></span>
{{-- Campo de búsqueda aparente en el topbar (Fase 9: Buscador global) --}}
<div class="sigem-topbar-search"
     role="button"
     tabindex="0"
     aria-label="Buscar en SIGEM"
     onclick="window.dispatchEvent(new CustomEvent(\'abrir-command-palette\'))"
     onkeydown="if(event.key===\'Enter\'||event.key===\' \'){event.preventDefault();window.dispatchEvent(new CustomEvent(\'abrir-command-palette\'));}">
    <span>🔍</span>
    <span>Buscar en SIGEM…</span>
</div>
<span class="sigem-role-banner sigem-role-admin"
      title="Acceso completo de administración">👑 Administrador</span>
@endif
')
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render('
@if (auth()->check())
    @livewire(\App\Livewire\CommandPalette::class)
@endif
<script>
    (function () {
        var isTooltipsUpdating = false;
        function initSidebarTooltips() {
            if (isTooltipsUpdating) return;
            isTooltipsUpdating = true;
            try {
                document.querySelectorAll(\'.fi-sidebar-item\').forEach(function (item) {
                    var label = item.querySelector(\'.fi-sidebar-item-label, .fi-sidebar-nav-item-label\');
                    var text = label ? label.textContent.trim() : \'\';
                    if (!text) {
                        var link = item.querySelector(\'a\');
                        if (link && link.title) {
                            text = link.title.trim();
                        }
                    }
                    if (text && item.getAttribute(\'data-tooltip\') !== text) {
                        item.setAttribute(\'data-tooltip\', text);
                    }
                });
            } finally {
                isTooltipsUpdating = false;
            }
        }
        if (document.readyState === \'loading\') {
            document.addEventListener(\'DOMContentLoaded\', initSidebarTooltips);
        } else {
            initSidebarTooltips();
        }
        document.addEventListener(\'livewire:navigated\', initSidebarTooltips);
        var sidebarObserver = new MutationObserver(function () {
            initSidebarTooltips();
        });
        var sidebar = document.querySelector(\'.fi-sidebar\');
        if (sidebar) {
            sidebarObserver.observe(sidebar, { childList: true, subtree: true });
        }

        var isSlideUpdating = false;
        function updateSlideOverState() {
            if (isSlideUpdating) return;
            isSlideUpdating = true;
            try {
                var openSlideOver = document.querySelector(\'.fi-modal-open .fi-modal-slide-over-window, .fi-modal.fi-modal-open .fi-modal-slide-over-window\');
                if (openSlideOver) {
                    if (!document.body.classList.contains(\'slide-over-open\')) {
                        document.body.classList.add(\'slide-over-open\');
                    }
                } else {
                    if (document.body.classList.contains(\'slide-over-open\')) {
                        document.body.classList.remove(\'slide-over-open\');
                    }
                }
            } finally {
                Promise.resolve().then(function () {
                    isSlideUpdating = false;
                });
            }
        }
        window.addEventListener(\'modal-opened\', function () { setTimeout(updateSlideOverState, 30); });
        window.addEventListener(\'modal-closed\', function () { setTimeout(updateSlideOverState, 30); });
        window.addEventListener(\'close-modal\', function () { setTimeout(updateSlideOverState, 30); });
        window.addEventListener(\'open-modal\', function () { setTimeout(updateSlideOverState, 30); });
        document.addEventListener(\'livewire:navigated\', function () { setTimeout(updateSlideOverState, 30); });

        var slideObserver = new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                var target = mutations[i].target;
                if (target === document.body) continue;
                if (target && target.classList && (target.classList.contains(\'fi-modal\') || target.classList.contains(\'fi-modal-window\') || (target.closest && target.closest(\'.fi-modal\')))) {
                    updateSlideOverState();
                    break;
                }
            }
        });
        slideObserver.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: [\'class\'] });
    })();
</script>
')
            )

            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Panel principal')
                    ->collapsible(false),
                NavigationGroup::make()
                    ->label('Gestión de inventario')
                    ->collapsible(false),
                NavigationGroup::make()
                    ->label('Catálogos')
                    ->collapsible(false),
                NavigationGroup::make()
                    ->label('Administración')
                    ->collapsible(false),
            ])
            ->sidebarWidth('64px')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
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

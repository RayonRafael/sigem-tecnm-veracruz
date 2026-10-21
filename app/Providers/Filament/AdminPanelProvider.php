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
            ->brandLogo(new HtmlString('<div class="sigem-brand"><div class="sigem-brand-logo">S</div></div>'))
            ->brandLogoHeight('auto')
            ->favicon(asset('images/sigem-favicon.svg?v=2'))
            ->font('DM Sans')
            ->globalSearch(false)
            ->colors([
                'primary' => Color::hex('#1b65d4'),
                'success' => Color::hex('#059669'),
                'warning' => Color::hex('#d97706'),
                'danger'  => Color::hex('#dc2626'),
                'info'    => Color::hex('#0891b2'),
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
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): string => Blade::render('
@if (auth()->check())
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
        function initSidebarTooltips() {
            document.querySelectorAll(\'.fi-sidebar-item\').forEach(function (item) {
                var label = item.querySelector(\'.fi-sidebar-item-label, .fi-sidebar-nav-item-label\');
                var text = label ? label.textContent.trim() : \'\';
                if (!text) {
                    var link = item.querySelector(\'a\');
                    if (link && link.title) {
                        text = link.title.trim();
                    }
                }
                if (text) {
                    item.setAttribute(\'data-tooltip\', text);
                }
            });
        }
        if (document.readyState === \'loading\') {
            document.addEventListener(\'DOMContentLoaded\', initSidebarTooltips);
        } else {
            initSidebarTooltips();
        }
        document.addEventListener(\'livewire:navigated\', initSidebarTooltips);
        var observer = new MutationObserver(function () {
            initSidebarTooltips();
        });
        var sidebar = document.querySelector(\'.fi-sidebar\');
        if (sidebar) {
            observer.observe(sidebar, { childList: true, subtree: true });
        }
    })();
</script>
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

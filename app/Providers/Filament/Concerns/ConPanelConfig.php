<?php

namespace App\Providers\Filament\Concerns;

use App\Filament\Pages\EditProfile;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
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

trait ConPanelConfig
{
    /** @param array<string, mixed> $config */
    protected function configureBasePanel(Panel $panel, array $config): Panel
    {
        return $panel
            ->login()
            ->profile(EditProfile::class, isSimple: false)
            ->passwordReset()
            ->brandName($config['brandName'])
            ->brandLogo(new HtmlString('<div class="sigem-brand"><img src="' . asset('images/sigem-icon.svg') . '" alt="SIGEM" class="sigem-brand-logo"></div>'))
            ->brandLogoHeight('auto')
            ->favicon(asset('images/sigem-icon.svg'))
            ->font('DM Sans')
            ->globalSearch(false)
            ->darkMode(false)
            ->colors([
                'primary' => Color::hex('#1b65d4'),
                'success' => Color::hex('#059669'),
                'warning' => Color::hex('#d97706'),
                'danger' => Color::hex('#dc2626'),
                'info' => Color::hex('#0891b2'),
                'reassign' => Color::hex('#8b5cf6'),
            ])
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => $this->renderHeadEnd())
            ->renderHook(PanelsRenderHook::TOPBAR_START, fn (): string => $this->renderTopbarStart($config['brandName']))
            ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, fn (): string => $this->renderLoginBefore($config['loginBadgeClasses'], $config['loginBadgeLabel']))
            ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, fn (): string => $this->renderLoginAfter($config['switchPanelUrl'], $config['switchPanelLabel']))
            ->renderHook(PanelsRenderHook::SIDEBAR_FOOTER, fn (): string => $this->renderSidebarFooter($config['logoutRoute'], $config['mostrarRolePill'] ?? false))
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn (): string => $this->renderUserMenuBefore($config))
            ->renderHook(PanelsRenderHook::BODY_END, fn (): string => $this->renderBodyEnd())
            ->sidebarWidth('64px')
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
            ->authMiddleware([Authenticate::class]);
    }

    /** @param array<int, string> $labels @return array<int, NavigationGroup> */
    protected function makeNavigationGroups(array $labels): array
    {
        return array_map(fn (string $label): NavigationGroup => NavigationGroup::make()->label($label)->collapsible(false), $labels);
    }

    protected function renderHeadEnd(): string
    {
        return Blade::render(<<<'BLADE'
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
<link rel="stylesheet" href="{{ asset('css/sigem-theme.css') }}?v={{ file_exists(public_path('css/sigem-theme.css')) ? filemtime(public_path('css/sigem-theme.css')) : 1 }}">
<script src="{{ asset('js/sigem-network.js') }}" defer></script>
<script src="{{ asset('js/sigem-titles.js') }}" defer></script>
BLADE);
    }

    protected function renderTopbarStart(string $brandName): string
    {
        return Blade::render(<<<'BLADE'
<button type="button" class="sigem-hamburger" onclick="toggleSidebar()" aria-label="Abrir menú lateral">☰</button>
<div class="sigem-topbar-brand flex items-center gap-x-3">
    <img src="{{ asset('images/sigem-logo.svg') }}" alt="SIGEM" class="h-8">
    <span class="font-bold text-lg hidden sm:block">{{ $brandName }}</span>
</div>
BLADE, ['brandName' => $brandName]);
    }

    protected function renderLoginBefore(string $badgeClasses, string $badgeLabel): string
    {
        return Blade::render(<<<'BLADE'
<div class="flex flex-col items-center gap-3 mb-6">
    <div class="flex justify-center items-center gap-4">
        <img src="{{ asset('images/sigem-logo.svg') }}" alt="SIGEM" style="height: 38px; object-fit: contain;">
        <img src="{{ asset('images/tecnm-logo.webp') }}" alt="TecNM" loading="lazy" style="height: 38px; object-fit: contain;">
        <img src="{{ asset('images/itv-logo.webp') }}" alt="ITV" loading="lazy" style="height: 38px; object-fit: contain;">
    </div>
    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $badgeClasses }} shadow-sm">{{ $badgeLabel }}</span>
</div>
<script>
(function() {
    function applyPlaceholders() {
        var email = document.querySelector('.fi-simple-main input[type="email"], input[type="email"]');
        if (email && !email.getAttribute('placeholder')) email.setAttribute('placeholder', 'usuario@veracruz.tecnm.mx');
        var pass = document.querySelector('.fi-simple-main input[type="password"], input[type="password"]');
        if (pass && !pass.getAttribute('placeholder')) pass.setAttribute('placeholder', '••••••••');
    }
    applyPlaceholders(); setTimeout(applyPlaceholders, 50); setTimeout(applyPlaceholders, 200); setTimeout(applyPlaceholders, 600);
})();
</script>
BLADE, ['badgeClasses' => $badgeClasses, 'badgeLabel' => $badgeLabel]);
    }

    protected function renderLoginAfter(string $switchPanelUrl, string $switchPanelLabel): string
    {
        return Blade::render(<<<'BLADE'
<div class="text-center mt-6 flex flex-col gap-3">
    <a href="{{ url($switchPanelUrl) }}" class="text-sm font-medium" style="color: #1b65d4; text-decoration: underline;">{{ $switchPanelLabel }}</a>
    <a href="{{ url('/') }}" class="text-sm text-gray-500 hover:text-gray-700 font-medium">&larr; Ir al inicio</a>
</div>
BLADE, ['switchPanelUrl' => $switchPanelUrl, 'switchPanelLabel' => $switchPanelLabel]);
    }

    protected function renderSidebarFooter(string $logoutRoute, bool $mostrarRolePill): string
    {
        return Blade::render(<<<'BLADE'
<div class="sigem-user-card">
    <div class="sigem-avatar" title="{{ auth()->user()?->name ?? 'Usuario' }}">{{ mb_strtoupper(mb_substr(auth()->user()?->name ?? 'U', 0, 1)) }}</div>
    @if ($mostrarRolePill)<span class="sigem-role-pill">SS</span>@endif
    <form method="POST" action="{{ route($logoutRoute) }}" class="m-0 p-0 flex items-center justify-center">
        @csrf
        <button type="submit" class="sigem-logout" title="Cerrar sesión" aria-label="Cerrar sesión">&#x23FB;</button>
    </form>
</div>
BLADE, ['logoutRoute' => $logoutRoute, 'mostrarRolePill' => $mostrarRolePill]);
    }

    /** @param array<string, mixed> $config */
    protected function renderUserMenuBefore(array $config): string
    {
        return Blade::render(<<<'BLADE'
@if (auth()->check())
@php
    $path = request()->path();
    $section = "Panel principal";
    foreach ($sectionRoutes as $needle => $label) {
        if (str_contains($path, $needle)) { $section = $label; break; }
    }
@endphp
<span class="sigem-crumb">SIGEM › <b>{{ $section }}</b></span>
<div class="sigem-topbar-search" role="button" tabindex="0" aria-label="Buscar en SIGEM"
     onclick="window.dispatchEvent(new CustomEvent('abrir-command-palette'))"
     onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.dispatchEvent(new CustomEvent('abrir-command-palette'));}">
    <span>🔍</span><span>Buscar en SIGEM…</span>
</div>
<span class="{{ $roleBannerClass }}"@if ($roleBannerTitle) title="{{ $roleBannerTitle }}"@endif>{{ $roleBannerLabel }}</span>
@endif
BLADE, [
            'sectionRoutes' => $config['sectionRoutes'],
            'roleBannerClass' => $config['roleBannerClass'],
            'roleBannerTitle' => $config['roleBannerTitle'] ?? null,
            'roleBannerLabel' => $config['roleBannerLabel'],
        ]);
    }

    protected function renderBodyEnd(): string
    {
        return Blade::render(<<<'BLADE'
@if (auth()->check()) @livewire(\App\Livewire\CommandPalette::class) @endif
<div class="sigem-sidebar-overlay" onclick="toggleSidebar()"></div>
<script>
window.toggleSidebar = function () {
    var s = document.querySelector('.fi-sidebar'), o = document.querySelector('.sigem-sidebar-overlay');
    if (s) s.classList.toggle('is-open'); if (o) o.classList.toggle('is-active');
};
function closeSigemSidebar() {
    var s = document.querySelector('.fi-sidebar'), o = document.querySelector('.sigem-sidebar-overlay');
    if (s) s.classList.remove('is-open'); if (o) o.classList.remove('is-active');
}
document.addEventListener('livewire:navigated', closeSigemSidebar);
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeSigemSidebar(); });
(function () {
    var isTooltipsUpdating = false;
    function initSidebarTooltips() {
        if (isTooltipsUpdating) return; isTooltipsUpdating = true;
        try {
            document.querySelectorAll('.fi-sidebar-item').forEach(function (item) {
                var label = item.querySelector('.fi-sidebar-item-label, .fi-sidebar-nav-item-label');
                var text = label ? label.textContent.trim() : '';
                if (!text) { var link = item.querySelector('a'); if (link && link.title) text = link.title.trim(); }
                if (text && item.getAttribute('data-tooltip') !== text) item.setAttribute('data-tooltip', text);
            });
        } finally { isTooltipsUpdating = false; }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initSidebarTooltips); else initSidebarTooltips();
    document.addEventListener('livewire:navigated', initSidebarTooltips);
    var sidebar = document.querySelector('.fi-sidebar');
    if (sidebar) new MutationObserver(initSidebarTooltips).observe(sidebar, { childList: true, subtree: true });

    var isSlideUpdating = false;
    function updateSlideOverState() {
        if (isSlideUpdating) return; isSlideUpdating = true;
        try {
            var open = document.querySelector('.fi-modal-open .fi-modal-slide-over-window, .fi-modal.fi-modal-open .fi-modal-slide-over-window');
            document.body.classList.toggle('slide-over-open', !!open);
        } finally { Promise.resolve().then(function () { isSlideUpdating = false; }); }
    }
    ['modal-opened', 'modal-closed', 'close-modal', 'open-modal'].forEach(function (ev) { window.addEventListener(ev, function () { setTimeout(updateSlideOverState, 30); }); });
    document.addEventListener('livewire:navigated', function () { setTimeout(updateSlideOverState, 30); });
    new MutationObserver(function (mutations) {
        for (var i = 0; i < mutations.length; i++) {
            var t = mutations[i].target;
            if (t !== document.body && t && t.classList && (t.classList.contains('fi-modal') || t.classList.contains('fi-modal-window') || (t.closest && t.closest('.fi-modal')))) {
                updateSlideOverState(); break;
            }
        }
    }).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
})();
</script>
BLADE);
    }
}

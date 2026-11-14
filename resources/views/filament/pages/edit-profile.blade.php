<x-dynamic-component
    :component="static::isSimple() ? 'filament-panels::page.simple' : 'filament-panels::page'"
>
    @php
        $user = auth()->user();
        $initial = mb_strtoupper(mb_substr($user?->name ?? 'U', 0, 1));
        $panelId = filament()->getCurrentPanel()?->getId() ?? 'admin';
        $isAdmin = $panelId === 'admin';
    @endphp

    {{-- Marcador para ocultar el header nativo duplicado de Filament --}}
    <div class="sigem-rep-header" style="display: none;" aria-hidden="true"></div>

    <div class="sigem-profile-wrapper">
        <div class="sigem-profile-card">
            {{-- Franja superior institucional --}}
            <div class="sigem-profile-accent"></div>

            {{-- Barra de logos institucionales y badge del panel --}}
            <div class="sigem-profile-logos-bar">
                <div class="sigem-profile-logos">
                    <img src="{{ asset('images/sigem-logo.svg') }}" alt="SIGEM" class="sigem-profile-logo">
                    <img src="{{ asset('images/tecnm-logo.webp') }}" alt="TecNM" loading="lazy" class="sigem-profile-logo">
                    <img src="{{ asset('images/itv-logo.webp') }}" alt="ITV" loading="lazy" class="sigem-profile-logo">
                </div>

                @if ($isAdmin)
                    <span class="sigem-profile-panel-badge sigem-profile-panel-admin">
                        🔒 Panel de Administración
                    </span>
                @else
                    <span class="sigem-profile-panel-badge sigem-profile-panel-ss">
                        👥 Panel de Servicio Social
                    </span>
                @endif
            </div>

            {{-- Encabezado principal "Mi Perfil" con avatar e información actual --}}
            <div class="sigem-profile-header">
                <div class="sigem-profile-avatar-wrap">
                    <div class="sigem-profile-avatar" title="{{ $user?->name ?? 'Usuario' }}">
                        {{ $initial }}
                    </div>
                    <span class="sigem-profile-avatar-icon" aria-hidden="true">👤</span>
                </div>

                <div class="sigem-profile-title-group">
                    <h1 class="sigem-profile-title">Mi Perfil</h1>
                    <p class="sigem-profile-subtitle">
                        Actualiza tu información personal y tus credenciales de acceso al sistema.
                    </p>
                    <div class="sigem-profile-meta">
                        <span class="sigem-profile-meta-item">
                            <strong>Usuario:</strong> {{ $user?->name ?? 'Usuario' }}
                        </span>
                        <span class="sigem-profile-meta-sep">•</span>
                        <span class="sigem-profile-meta-item">
                            <strong>Correo:</strong> {{ $user?->email ?? '' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Cuerpo del formulario --}}
            <div class="sigem-profile-body">
                <x-filament-panels::form id="form" wire:submit="save">
                    {{ $this->form }}

                    <div class="sigem-profile-actions">
                        <x-filament-panels::form.actions
                            :actions="$this->getCachedFormActions()"
                            :full-width="$this->hasFullWidthFormActions()"
                        />
                    </div>
                </x-filament-panels::form>
            </div>
        </div>
    </div>
</x-dynamic-component>


@props([
    'panel' => 'admin',
    'isSkeletonPreview' => false,
    'mantenimientosPendientes' => 0,
    'solicitudesPendientes' => 0,
    'misSolicitudesPendientes' => 0,
    'resumenRentas' => ['total' => 0, 'vigentes' => 0, 'por_vencer' => 0, 'vencidas' => 0],
])

@if($isSkeletonPreview)
    {{-- SKELETON PREVIEW: ACCIONES RÁPIDAS (4 CARDS) --}}
    <div class="sigem-quick-grid sigem-skeleton-grid">
        @for($i = 0; $i < 4; $i++)
            <div class="sigem-skeleton-card sigem-skeleton-quick">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                    <span class="sigem-skeleton" style="width: 36px; height: 36px; border-radius: 10px;"></span>
                    <span class="sigem-skeleton" style="width: 14px; height: 14px; border-radius: 4px;"></span>
                </div>
                <span class="sigem-skeleton sigem-skeleton-cell" style="width: 65%; height: 16px; margin-bottom: 6px;"></span>
                <span class="sigem-skeleton sigem-skeleton-cell" style="width: 85%; height: 12px;"></span>
            </div>
        @endfor
    </div>
@else
    <div class="sigem-quick-grid">
        @if($panel === 'admin')
            <!-- 1. Autorizar mantenimiento -->
            <a class="sigem-quick" href="{{ url('/admin/mantenimientos') }}">
                <div class="arrow">→</div>
                <div class="sigem-quick-ico sigem-i-warning">🔧</div>
                <div class="sigem-quick-title">
                    Autorizar mantenimiento
                    @if(($mantenimientosPendientes ?? 0) > 0)
                        <span class="sigem-qbadge">{{ $mantenimientosPendientes }}</span>
                    @endif
                </div>
                <div class="sigem-quick-desc">Revisar reportes pendientes</div>
            </a>

            <!-- 2. Revisar solicitudes -->
            <a class="sigem-quick" href="{{ url('/admin/solicituds') }}">
                <div class="arrow">→</div>
                <div class="sigem-quick-ico sigem-i-primary">📋</div>
                <div class="sigem-quick-title">
                    Revisar solicitudes
                    @if(($solicitudesPendientes ?? 0) > 0)
                        <span class="sigem-qbadge">{{ $solicitudesPendientes }}</span>
                    @endif
                </div>
                <div class="sigem-quick-desc">Aprobar o rechazar</div>
            </a>

            {{-- 3. Equipos en renta --}}
            @php
                $rentVigentes = (int) ($resumenRentas['vigentes'] ?? 0);
                $rentPorVencer = (int) ($resumenRentas['por_vencer'] ?? 0);
                $rentVencidas = (int) ($resumenRentas['vencidas'] ?? 0);
            @endphp
            <a class="sigem-quick" href="{{ url('/admin/reportes?pestana=nuevos&subpestana=rentas&reporte=rent_seguimiento') }}" title="Ver seguimiento de equipos en renta">
                <div class="arrow">→</div>
                <div class="sigem-quick-ico sigem-i-info">🏢</div>
                <div class="sigem-quick-title">
                    Equipos en renta
                    @if($rentVencidas > 0)
                        <span class="sigem-qbadge" style="background: #fee2e2; color: #b91c1c;">{{ $rentVencidas }}</span>
                    @elseif($rentPorVencer > 0)
                        <span class="sigem-qbadge" style="background: #fef3c7; color: #b45309;">{{ $rentPorVencer }}</span>
                    @endif
                </div>
                <div class="sigem-quick-desc" style="display: flex; flex-wrap: wrap; gap: 4px; align-items: center; font-size: 12px;">
                    <span title="Vigentes (> 6 meses)">🟢 {{ $rentVigentes }} vigente{{ $rentVigentes === 1 ? '' : 's' }}</span>
                    <span>|</span>
                    <span title="Por vencer (0-6 meses)">🟡 {{ $rentPorVencer }} por vencer</span>
                    <span>|</span>
                    <span title="Vencidas">🔴 {{ $rentVencidas }} vencida{{ $rentVencidas === 1 ? '' : 's' }}</span>
                </div>
                <div style="font-size: 11.5px; font-weight: 600; color: #1b65d4; margin-top: 4px;">→ Ver detalle</div>
            </a>

            <!-- 4. Ver bitácora -->
            <a class="sigem-quick" href="{{ url('/admin/configuracion?tab=bitacora') }}">
                <div class="arrow">→</div>
                <div class="sigem-quick-ico sigem-i-info">🕘</div>
                <div class="sigem-quick-title">Ver bitácora</div>
                <div class="sigem-quick-desc">Historial de acciones</div>
            </a>
        @else
            {{-- 1. Crear solicitud --}}
            <a class="sigem-quick" href="{{ url('/servicio-social/solicituds?abrir=create') }}">
                <div class="arrow">→</div>
                <div class="sigem-quick-ico sigem-i-primary">📋</div>
                <div class="sigem-quick-title">Crear solicitud</div>
                <div class="sigem-quick-desc">Pide salida, entrada o traslado</div>
            </a>

            {{-- 2. Reportar mantenimiento --}}
            <a class="sigem-quick" href="{{ url('/servicio-social/mantenimientos?abrir=create') }}">
                <div class="arrow">→</div>
                <div class="sigem-quick-ico sigem-i-warning">🔧</div>
                <div class="sigem-quick-title">Reportar mantenimiento</div>
                <div class="sigem-quick-desc">Avisa que un equipo necesita servicio</div>
            </a>

            {{-- 3. Registrar equipo --}}
            <a class="sigem-quick" href="javascript:void(0)" wire:click="mountAction('registrarEquipo')">
                <div class="arrow">→</div>
                <div class="sigem-quick-ico sigem-i-success">＋</div>
                <div class="sigem-quick-title">Registrar equipo</div>
                <div class="sigem-quick-desc">Da de alta un equipo o estación</div>
            </a>

            {{-- 4. Seguir mis trámites --}}
            <a class="sigem-quick" href="{{ url('/servicio-social/solicituds') }}">
                <div class="arrow">→</div>
                <div class="sigem-quick-ico sigem-i-info">🕘</div>
                <div class="sigem-quick-title">
                    Seguir mis trámites
                    @if(($misSolicitudesPendientes ?? 0) > 0)
                        <span class="sigem-qbadge">{{ $misSolicitudesPendientes }}</span>
                    @endif
                </div>
                <div class="sigem-quick-desc">Estado de lo enviado</div>
            </a>
        @endif
    </div>
@endif

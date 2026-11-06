@props([
    'panel' => 'admin',
    'isSkeletonPreview' => false,
    'totalActivos' => 0,
    'creadosEsteMes' => 0,
    'inventarioDisponibles' => 0,
    'porcentajeDisponibles' => 0,
    'inventarioEnMantenimiento' => 0,
    'mantenimientosPendientes' => 0,
    'requierenAtencion' => 0,
    'misSolicitudes' => 0,
    'solicitudesAutorizadas' => 0,
    'misMantenimientos' => 0,
    'mantenimientoEnRevision' => 0,
])

@if($isSkeletonPreview)
    {{-- SKELETON PREVIEW: KPIS (4 MÉTRICAS) --}}
    <div class="sigem-stats-grid sigem-skeleton-grid">
        @for($i = 0; $i < 4; $i++)
            <div class="sigem-skeleton-card sigem-skeleton-stat">
                <span class="sigem-skeleton" style="width: 36px; height: 36px; border-radius: 10px; margin-bottom: 10px;"></span>
                <span class="sigem-skeleton sigem-skeleton-cell" style="width: 45%; height: 26px; margin-bottom: 6px;"></span>
                <span class="sigem-skeleton sigem-skeleton-cell" style="width: 65%; height: 14px; margin-bottom: 6px;"></span>
                <span class="sigem-skeleton sigem-skeleton-cell" style="width: 50%; height: 12px;"></span>
            </div>
        @endfor
    </div>
@else
    <div class="sigem-stats-grid">
        @if($panel === 'admin')
            <!-- 1. Equipos registrados -->
            <div class="sigem-stat">
                <div class="sigem-stat-ico sigem-i-primary">📦</div>
                <div class="sigem-stat-val">{{ $totalActivos ?? 0 }}</div>
                <div class="sigem-stat-label">Equipos registrados</div>
                <div class="sigem-stat-hint sigem-h-ok">↗ {{ $creadosEsteMes ?? 0 }} nuevos este mes</div>
            </div>

            <!-- 2. Disponibles -->
            <div class="sigem-stat">
                <div class="sigem-stat-ico sigem-i-success">✓</div>
                <div class="sigem-stat-val">{{ $inventarioDisponibles ?? 0 }}</div>
                <div class="sigem-stat-label">Disponibles</div>
                <div class="sigem-stat-hint sigem-h-info">{{ $porcentajeDisponibles }}% del parque</div>
            </div>

            <!-- 3. En mantenimiento -->
            <div class="sigem-stat">
                <div class="sigem-stat-ico sigem-i-warning">🔧</div>
                <div class="sigem-stat-val">{{ $inventarioEnMantenimiento ?? 0 }}</div>
                <div class="sigem-stat-label">En mantenimiento</div>
                <div class="sigem-stat-hint sigem-h-warn">{{ $mantenimientosPendientes ?? 0 }} pendientes</div>
            </div>

            <!-- 4. Requieren atención -->
            <div class="sigem-stat">
                <div class="sigem-stat-ico sigem-i-danger">⚠️</div>
                <div class="sigem-stat-val">{{ $requierenAtencion }}</div>
                <div class="sigem-stat-label">Requieren atención</div>
                <div class="sigem-stat-hint sigem-h-warn">cambios de área y solicitudes de baja</div>
            </div>
        @else
            {{-- 1. Equipos registrados --}}
            <div class="sigem-stat">
                <div class="sigem-stat-ico sigem-i-primary">📦</div>
                <div class="sigem-stat-val">{{ $totalActivos ?? 0 }}</div>
                <div class="sigem-stat-label">Equipos registrados</div>
                <div class="sigem-stat-hint sigem-h-info">✏️ Alta, cambios y bajas</div>
            </div>

            {{-- 2. Mis solicitudes --}}
            <div class="sigem-stat">
                <div class="sigem-stat-ico sigem-i-primary">📋</div>
                <div class="sigem-stat-val">{{ $misSolicitudes ?? 0 }}</div>
                <div class="sigem-stat-label">Mis solicitudes</div>
                <div class="sigem-stat-hint sigem-h-ok">✓ {{ $solicitudesAutorizadas ?? 0 }} autorizadas</div>
            </div>

            {{-- 3. Mis mantenimientos --}}
            <div class="sigem-stat">
                <div class="sigem-stat-ico sigem-i-warning">🔧</div>
                <div class="sigem-stat-val">{{ $misMantenimientos ?? 0 }}</div>
                <div class="sigem-stat-label">Mis mantenimientos</div>
                <div class="sigem-stat-hint sigem-h-warn">{{ $mantenimientoEnRevision ?? 0 }} en revisión</div>
            </div>

            {{-- 4. Equipos disponibles --}}
            <div class="sigem-stat">
                <div class="sigem-stat-ico sigem-i-success">✓</div>
                <div class="sigem-stat-val">{{ $inventarioDisponibles ?? 0 }}</div>
                <div class="sigem-stat-label">Equipos disponibles</div>
                <div class="sigem-stat-hint sigem-h-info">Número total</div>
            </div>
        @endif
    </div>
@endif

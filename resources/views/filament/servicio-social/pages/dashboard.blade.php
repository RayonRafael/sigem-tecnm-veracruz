<x-filament-panels::page>
    <div x-data="{ activeModal: null }" class="sigem-dashboard">
        <style>
            .sigem-dashboard {
                --bg: #f0f4f8;
                --card-bg: #ffffff;
                --accent: #1b65d4;
                --accent-light: #e8f0fe;
                --accent-dark: #0e4bad;
                --success: #0f9d58;
                --success-light: #e6f7ed;
                --warning: #f4a623;
                --warning-light: #fef7e6;
                --danger: #d93025;
                --danger-light: #fce8e6;
                --info: #1a73e8;
                --info-light: #e8f0fe;
                --text-primary: #1a1a2e;
                --text-secondary: #5f6b7a;
                --text-muted: #8b95a5;
                --border: #e2e8f0;
                --border-light: #f1f5f9;
                --radius: 12px;
                --radius-sm: 8px;
                --shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
                --shadow-md: 0 4px 12px rgba(0,0,0,0.08);

                font-family: 'DM Sans', sans-serif;
                color: var(--text-primary);
            }

            .sigem-dashboard * {
                box-sizing: border-box;
            }

            /* ===== BENTO GRID ===== */
            .bento-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }

            .bento-item {
                background: var(--card-bg);
                border: 1px solid var(--border);
                border-radius: var(--radius);
                padding: 24px;
                box-shadow: var(--shadow);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
                display: flex;
                flex-direction: column;
            }

            .bento-item:hover {
                transform: scale(1.01);
                box-shadow: var(--shadow-md);
            }

            .span-2 {
                grid-column: span 2;
            }

            /* ===== HEADERS ===== */
            .bento-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 16px;
            }

            .bento-title {
                font-size: 16px;
                font-weight: 700;
                color: var(--text-primary);
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .bento-title svg {
                width: 20px;
                height: 20px;
                stroke: var(--accent);
                fill: none;
                stroke-width: 2;
            }

            /* ===== BUTTONS ===== */
            .btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 8px 14px;
                border-radius: var(--radius-sm);
                font-family: inherit;
                font-size: 13px;
                font-weight: 600;
                border: none;
                cursor: pointer;
                transition: all 0.15s ease;
                text-decoration: none;
            }

            .btn-primary {
                background: var(--accent);
                color: #fff;
            }

            .btn-primary:hover {
                background: var(--accent-dark);
            }

            .btn-secondary {
                background: var(--bg);
                color: var(--text-primary);
                border: 1px solid var(--border);
            }

            .btn-secondary:hover {
                border-color: var(--accent);
                color: var(--accent);
            }

            /* ===== WELCOME CARD STATS ===== */
            .welcome-stats {
                display: flex;
                gap: 20px;
                margin: 20px 0;
            }
            .welcome-stat-item {
                flex: 1;
                background: var(--bg);
                padding: 16px;
                border-radius: var(--radius-sm);
                border: 1px solid var(--border-light);
            }
            .welcome-stat-value {
                font-family: 'JetBrains Mono', monospace;
                font-size: 28px;
                font-weight: 700;
                color: var(--text-primary);
                line-height: 1.2;
            }
            .welcome-stat-label {
                font-size: 13px;
                color: var(--text-secondary);
                font-weight: 500;
            }

            /* ===== MINI TABLES ===== */
            .mini-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }
            .mini-table th {
                text-align: left;
                padding: 8px;
                font-size: 11px;
                font-weight: 700;
                color: var(--text-muted);
                text-transform: uppercase;
                border-bottom: 1px solid var(--border);
            }
            .mini-table td {
                padding: 10px 8px;
                font-size: 13px;
                border-bottom: 1px solid var(--border-light);
                color: var(--text-primary);
            }
            .mini-table tr:last-child td {
                border-bottom: none;
            }
            .mono-text {
                font-family: 'JetBrains Mono', monospace;
                font-size: 12px;
                font-weight: 600;
            }

            /* ===== BADGES ===== */
            .badge-status {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 3px 8px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
            }
            .badge-status.active { background: var(--success-light); color: var(--success); }
            .badge-status.warning { background: var(--warning-light); color: #b45309; }
            .badge-status.danger { background: var(--danger-light); color: var(--danger); }
            .badge-status.info { background: var(--info-light); color: var(--info); }
            .badge-status.muted { background: #f1f5f9; color: var(--text-muted); }
            .badge-status::before {
                content: '';
                width: 5px;
                height: 5px;
                border-radius: 50%;
                background: currentColor;
            }

            /* ===== CATALOG ICONS GRID ===== */
            .catalog-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 12px;
                margin-top: 10px;
            }
            .catalog-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 16px 8px;
                border-radius: var(--radius-sm);
                background: var(--bg);
                border: 1px solid var(--border);
                cursor: pointer;
                transition: all 0.2s;
                text-align: center;
            }
            .catalog-item:hover {
                background: var(--accent-light);
                border-color: var(--accent);
                transform: translateY(-2px);
            }
            .catalog-icon {
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
                background: var(--card-bg);
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                color: var(--accent);
            }
            .catalog-icon svg {
                width: 18px;
                height: 18px;
                stroke: currentColor;
                fill: none;
                stroke-width: 1.8;
            }
            .catalog-label {
                font-size: 11px;
                font-weight: 600;
                color: var(--text-primary);
                line-height: 1.2;
            }

            /* ===== MODALS (ALPINE) ===== */
            .modal-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(11, 29, 58, 0.4);
                backdrop-filter: blur(4px);
                z-index: 1000;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .modal-window {
                background: var(--card-bg);
                border-radius: var(--radius);
                width: 100%;
                max-width: 500px;
                box-shadow: var(--shadow-lg);
                overflow: hidden;
            }
            .modal-header {
                padding: 20px 24px;
                border-bottom: 1px solid var(--border);
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .modal-title {
                font-size: 18px;
                font-weight: 700;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .modal-title svg {
                width: 22px;
                height: 22px;
                stroke: var(--accent);
                fill: none;
            }
            .modal-close {
                background: none;
                border: none;
                cursor: pointer;
                color: var(--text-muted);
                padding: 4px;
            }
            .modal-close:hover { color: var(--danger); }
            .modal-close svg { width: 20px; height: 20px; stroke: currentColor; fill: none; stroke-width: 2; }
            .modal-body {
                padding: 24px;
            }
            .modal-footer {
                padding: 16px 24px;
                background: var(--bg);
                border-top: 1px solid var(--border);
                display: flex;
                justify-content: flex-end;
            }

            @media (max-width: 1024px) {
                .bento-grid { grid-template-columns: 1fr; }
                .span-2 { grid-column: span 1; }
                .catalog-grid { grid-template-columns: repeat(2, 1fr); }
                .welcome-stats { flex-wrap: wrap; }
            }
        </style>

        <div class="bento-grid">
            
            <!-- 1. WELCOME CARD (Span 2) -->
            <div class="bento-item span-2">
                <div class="bento-header">
                    <div class="bento-title">
                        <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        ¡Hola de nuevo, {{ auth()->user()->name ?? 'Usuario' }}!
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <a href="{{ url('/servicio-social/inventarios/create') }}" class="btn btn-primary">Nuevo Registro</a>
                    </div>
                </div>

                <div class="welcome-stats">
                    <div class="welcome-stat-item" style="border-top: 3px solid var(--accent);">
                        <div class="welcome-stat-value">{{ $misRegistrosHoy ?? 0 }}</div>
                        <div class="welcome-stat-label">Mis Registros de Hoy</div>
                    </div>
                    <div class="welcome-stat-item" style="border-top: 3px solid var(--warning);">
                        <div class="welcome-stat-value">{{ $pendientesAprobacion ?? 0 }}</div>
                        <div class="welcome-stat-label">Pendientes de Aprobación</div>
                    </div>
                    <div class="welcome-stat-item" style="border-top: 3px solid var(--success);">
                        <div class="welcome-stat-value">{{ $reportesActivos ?? 0 }}</div>
                        <div class="welcome-stat-label">Mis Reportes Activos</div>
                    </div>
                </div>
            </div>

            <!-- 2. SOLICITUDES (Span 1) -->
            <div class="bento-item">
                <div class="bento-header">
                    <div class="bento-title">
                        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        Mis Solicitudes
                    </div>
                    <a href="{{ url('/servicio-social/solicituds') }}" class="btn btn-secondary" style="padding: 4px 10px; font-size: 11px;">Ver todas</a>
                </div>
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($solicitudesRecientes ?? [] as $sol)
                        <tr>
                            <td class="mono-text">SOL-{{ str_pad($sol->id_solicitud, 4, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $sol->tipo_movimiento ?? 'N/A' }}</td>
                            <td><span class="badge-status warning">{{ $sol->estado }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" style="text-align: center; color: var(--text-muted);">No has realizado solicitudes recientes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 3. MANTENIMIENTO (Span 1) -->
            <div class="bento-item">
                <div class="bento-header">
                    <div class="bento-title">
                        <svg viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        Mis Reportes
                    </div>
                    <a href="{{ url('/servicio-social/mantenimientos/create') }}" class="btn btn-primary" style="padding: 4px 10px; font-size: 11px;">Nuevo</a>
                </div>
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>Activo</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mantenimientosRecientes ?? [] as $mant)
                        <tr>
                            <td class="mono-text">{{ $mant->inventario?->num_serie ?? 'N/A' }}</td>
                            <td>{{ Str::limit($mant->tipo_servicio ?? 'N/A', 15) }}</td>
                            <td><span class="badge-status warning">{{ $mant->estado }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" style="text-align: center; color: var(--text-muted);">No tienes reportes pendientes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 4. CATÁLOGOS REDUCIDOS (Span 2) -->
            <div class="bento-item span-2">
                <div class="bento-header">
                    <div class="bento-title">
                        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        Accesos Rápidos
                    </div>
                </div>
                
                @php
                    $catalogosSS = [
                        ['nombre' => 'Inventario', 'icon' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>', 'url' => url('/servicio-social/inventarios')],
                        ['nombre' => 'Solicitudes', 'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>', 'url' => url('/servicio-social/solicituds')],
                        ['nombre' => 'Mantenimiento', 'icon' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>', 'url' => url('/servicio-social/mantenimientos')],
                    ];
                @endphp

                <div class="catalog-grid">
                    @foreach($catalogosSS as $cat)
                    <div class="catalog-item" @click="activeModal = '{{ Str::slug($cat['nombre']) }}'">
                        <div class="catalog-icon">
                            <svg viewBox="0 0 24 24">{!! $cat['icon'] !!}</svg>
                        </div>
                        <div class="catalog-label">{{ $cat['nombre'] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- MODALS (ALPINE.JS) -->
        @foreach($catalogosSS as $cat)
        <div class="modal-backdrop" 
             x-show="activeModal === '{{ Str::slug($cat['nombre']) }}'" 
             x-transition.opacity
             @click.self="activeModal = null"
             @keydown.escape.window="activeModal = null"
             style="display: none;">
            
            <div class="modal-window"
                 x-show="activeModal === '{{ Str::slug($cat['nombre']) }}'"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95">
                
                <div class="modal-header">
                    <div class="modal-title">
                        <svg viewBox="0 0 24 24">{!! $cat['icon'] !!}</svg>
                        {{ $cat['nombre'] }}
                    </div>
                    <button class="modal-close" @click="activeModal = null">
                        <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                
                <div class="modal-body">
                    <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 20px;">
                        Estás a punto de acceder al módulo de <strong>{{ $cat['nombre'] }}</strong>.
                    </p>
                    <div style="background: var(--bg); padding: 16px; border-radius: var(--radius-sm); border: 1px dashed var(--border);">
                        <div style="font-size: 13px; font-weight: 600; margin-bottom: 4px;">Atajo rápido</div>
                        <div style="font-size: 12px; color: var(--text-muted);">Redirigiendo al panel de servicio social...</div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" style="margin-right: 12px;" @click="activeModal = null">Cancelar</button>
                    <a href="{{ $cat['url'] }}" class="btn btn-primary">
                        Ir al módulo
                        <svg viewBox="0 0 24 24" style="width: 14px; height: 14px; margin-left: 4px;"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
            </div>
        </div>
        @endforeach

    </div>
</x-filament-panels::page>
